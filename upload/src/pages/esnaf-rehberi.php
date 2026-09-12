<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$currentPage = 'business_dir';
$pageTitle = 'Esnaf Rehberi - AvrupaPazari';
$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");
$lang = currentLang();

$businesses = [];
try {
    $stmt = $pdo->query("SELECT * FROM esnaf ORDER BY is_featured DESC, rating DESC, name ASC");
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$q = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? '');
$category = trim($_GET['category'] ?? '');

$pick = static function(array $row, array $keys, string $fallback = ''): string {
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $fallback;
};

$businesses = array_values(array_filter($businesses, function(array $b) use ($q, $city, $category, $pick): bool {
    $name = $pick($b, ['name', 'business_name', 'title']);
    $cat = $pick($b, ['category', 'category_name', 'type', 'sector']);
    $place = $pick($b, ['city', 'city_name', 'location']);
    $haystack = mb_strtolower($name . ' ' . $cat . ' ' . $place . ' ' . $pick($b, ['description', 'bio', 'about']));
    if ($q !== '' && mb_strpos($haystack, mb_strtolower($q)) === false) return false;
    if ($city !== '' && mb_strpos(mb_strtolower($place), mb_strtolower($city)) === false) return false;
    if ($category !== '' && mb_strtolower($cat) !== mb_strtolower($category)) return false;
    return true;
}));

$categories = [];
$cities = [];
foreach ($businesses as $b) {
    $cat = $pick($b, ['category', 'category_name', 'type', 'sector']);
    $place = $pick($b, ['city', 'city_name', 'location']);
    if ($cat !== '') $categories[$cat] = true;
    if ($place !== '') $cities[$place] = true;
}
ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);
ksort($cities, SORT_NATURAL | SORT_FLAG_CASE);

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/esnaf-rehberi.css?v=1">

<main class="business-directory">
  <section class="directory-hero">
    <div class="directory-hero-glow" aria-hidden="true"></div>
    <div class="directory-hero-inner">
      <div class="directory-kicker"><span></span><?= e(__('nav.business_dir')) ?></div>
      <h1><?= e($lang === 'nl' ? 'Vind ondernemers bij jou in de buurt' : ($lang === 'en' ? 'Find local businesses near you' : 'Yakınındaki esnafı bul')) ?></h1>
      <p><?= e($lang === 'nl' ? 'Ontdek lokale ondernemers en neem direct contact op.' : ($lang === 'en' ? 'Discover local businesses and contact them directly.' : 'Yerel işletmeleri keşfet, doğrudan iletişime geç.')) ?></p>
      <form class="directory-search" method="get" action="<?= BASE_URL ?>/esnaf-rehberi.php">
        <div class="search-field"><i data-lucide="search"></i><input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e($lang === 'nl' ? 'Zoek bedrijf, dienst of vakman' : ($lang === 'en' ? 'Search business, service or professional' : 'İşletme, hizmet veya esnaf ara')) ?>"></div>
        <div class="search-field search-location"><i data-lucide="map-pin"></i><select name="city"><option value=""><?= e($lang === 'nl' ? 'Alle steden' : ($lang === 'en' ? 'All cities' : 'Tüm şehirler')) ?></option><?php foreach ($cities as $name => $_): ?><option value="<?= e($name) ?>" <?= mb_strtolower($city) === mb_strtolower($name) ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="directory-search-btn"><?= e($lang === 'nl' ? 'Zoeken' : ($lang === 'en' ? 'Search' : 'Ara')) ?></button>
      </form>
    </div>
  </section>

  <section class="directory-content">
    <div class="directory-heading"><div><span class="directory-overline"><?= count($businesses) ?> <?= e($lang === 'nl' ? 'resultaten' : ($lang === 'en' ? 'results' : 'sonuç')) ?></span><h2><?= e($lang === 'nl' ? 'Lokale ondernemers' : ($lang === 'en' ? 'Local businesses' : 'Yerel işletmeler')) ?></h2></div><a class="directory-add" href="<?= BASE_URL ?>/index.php#post"><?= e($lang === 'nl' ? 'Bedrijf toevoegen' : ($lang === 'en' ? 'Add business' : 'İşletme ekle')) ?> <span>+</span></a></div>
    <div class="directory-filters"><a class="filter-chip <?= $category === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/esnaf-rehberi.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"><?= e($lang === 'nl' ? 'Alle' : ($lang === 'en' ? 'All' : 'Tümü')) ?></a><?php foreach ($categories as $name => $_): ?><a class="filter-chip <?= mb_strtolower($category) === mb_strtolower($name) ? 'active' : '' ?>" href="<?= BASE_URL ?>/esnaf-rehberi.php?category=<?= urlencode($name) ?>"><?= e($name) ?></a><?php endforeach; ?></div>

    <?php if (!$businesses): ?>
      <div class="directory-empty"><i data-lucide="search-x"></i><h3><?= e($lang === 'nl' ? 'Geen bedrijven gevonden' : ($lang === 'en' ? 'No businesses found' : 'İşletme bulunamadı')) ?></h3><p><?= e($lang === 'nl' ? 'Probeer een andere zoekopdracht.' : ($lang === 'en' ? 'Try another search.' : 'Başka bir arama deneyin.')) ?></p><a href="<?= BASE_URL ?>/esnaf-rehberi.php"><?= e($lang === 'nl' ? 'Toon alles' : ($lang === 'en' ? 'Show all' : 'Tümünü göster')) ?></a></div>
    <?php else: ?>
      <div class="business-grid">
      <?php foreach ($businesses as $index => $b):
          $name = $pick($b, ['name', 'business_name', 'title'], $lang === 'nl' ? 'Ondernemer' : 'Business');
          $cat = $pick($b, ['category', 'category_name', 'type', 'sector'], $lang === 'nl' ? 'Lokale onderneming' : 'Local business');
          $place = $pick($b, ['city', 'city_name', 'location']);
          $desc = $pick($b, ['description', 'bio', 'about']);
          $slug = $pick($b, ['slug']);
          $img = $pick($b, ['image', 'logo', 'photo']);
          $rating = (float)$pick($b, ['rating'], '0');
          $featured = !empty($b['is_featured']);
          $initials = mb_strtoupper(mb_substr($name, 0, 2));
      ?>
        <article class="business-card <?= $featured ? 'is-featured' : '' ?>">
          <div class="business-cover" <?= $img ? 'style="background-image:url(\'' . e(imageUrl($img)) . '\')"' : '' ?>><div class="business-cover-shade"></div><?php if ($featured): ?><span class="business-badge"><?= e($lang === 'nl' ? 'Uitgelicht' : ($lang === 'en' ? 'Featured' : 'Öne çıkan')) ?></span><?php endif; ?><div class="business-mark"><?= e($initials) ?></div></div>
          <div class="business-body"><div class="business-category"><?= e($cat) ?></div><h3><?= e($name) ?></h3><?php if ($desc !== ''): ?><p><?= e(mb_strimwidth($desc, 0, 118, '...')) ?></p><?php endif; ?><div class="business-meta"><?php if ($place !== ''): ?><span><i data-lucide="map-pin"></i><?= e($place) ?></span><?php endif; ?><?php if ($rating > 0): ?><span class="business-rating"><b>★</b> <?= number_format($rating, 1, ',', '.') ?></span><?php endif; ?></div><a class="business-link" href="<?= BASE_URL ?>/esnaf.php?slug=<?= e($slug !== '' ? $slug : slugify($name)) ?>"><?= e($lang === 'nl' ? 'Profiel bekijken' : ($lang === 'en' ? 'View profile' : 'Profili gör')) ?> <span>↗</span></a></div>
        </article>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<script>document.addEventListener('DOMContentLoaded',function(){if(window.lucide)lucide.createIcons()});</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
