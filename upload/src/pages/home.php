<?php
/** Homepage data provider. Visible copy is resolved through the active database language. */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDB();
$pdo->exec('SET NAMES utf8mb4');
$lang = currentLang();
$categories = $latestListings = $featuredBusinesses = $sectionTitles = [];

try {
    $query = $pdo->prepare("SELECT c.id, c.module, c.slug, c.icon, c.color_1, c.color_2, COALESCE(ct.name, tr.name, c.slug) AS name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? LEFT JOIN category_translations tr ON tr.category_id = c.id AND tr.lang = 'tr' WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order ASC, c.id ASC");
    $query->execute([$lang]);
    $categories = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) { $categories = []; }

try {
    $query = $pdo->query("SELECT l.id, l.title, l.image, l.price, l.is_premium, l.is_featured, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE l.status IN ('active', 'reserved') ORDER BY l.is_featured DESC, l.is_premium DESC, l.created_at DESC, l.id DESC LIMIT 8");
    $latestListings = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) { $latestListings = []; }

try {
    $query = $pdo->query("SELECT id, name, slug, city, image, rating FROM esnaf WHERE status = 'active' ORDER BY is_featured DESC, rating DESC, id DESC LIMIT 4");
    $featuredBusinesses = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) { $featuredBusinesses = []; }

try {
    $query = $pdo->query("SELECT section_key, title FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $section) $sectionTitles[$section['section_key']] = (string)$section['title'];
} catch (Throwable $exception) { $sectionTitles = []; }

$sectionTitle = static function (string $key, string $translationKey) use ($sectionTitles): string { return e($sectionTitles[$key] ?? t($translationKey)); };
$categoryUrl = static function (array $category): string { return url('kategori.php?slug=' . rawurlencode((string)$category['slug'])); };
$imageUrlFor = static function (array $item): string { return imageUrl($item['image'] ?? ''); };
$priceLabel = static function (array $item): string { $price = (float)($item['price'] ?? 0); return $price > 0 ? '€ ' . number_format($price, 0, ',', '.') : e(t('common.free')); };
$pageTitle = t('nav.home');
include __DIR__ . '/../includes/header.php';
?>
<main class="homepage">
    <section class="home-hero" aria-labelledby="home-title"><div class="home-hero-inner"><div class="home-hero-copy">
        <span class="home-kicker"><?= e(t('header.all_europe')) ?></span><h1 id="home-title"><?= e(t('home.hero_title')) ?></h1><p><?= e(t('home.hero_subtitle')) ?></p>
        <a class="home-hero-action" href="<?= e(url('pages/search-vehicles.php')) ?>"><?= e(t('common.search')) ?><span aria-hidden="true">↗</span></a>
    </div><div class="home-hero-mark" aria-hidden="true"><span>EU</span><i></i><i></i><i></i><i></i><i></i></div></div></section>

    <section class="home-section" aria-labelledby="category-title"><div class="home-section-heading"><div><span class="home-eyebrow"><?= e(t('header.category')) ?></span><h2 id="category-title"><?= $sectionTitle('categories', 'home.categories') ?></h2></div><a class="home-text-link" href="<?= e(url('pages/kategori.php')) ?>"><?= e(t('common.show_all')) ?> ↗</a></div>
        <div class="home-category-grid"><?php foreach ($categories as $index => $category): ?><a class="home-category-card" href="<?= e($categoryUrl($category)) ?>"><span class="home-category-number"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span class="home-category-icon" style="--category-start:<?= e($category['color_1'] ?: '#1d7a4e') ?>;--category-end:<?= e($category['color_2'] ?: '#0d3d25') ?>"><i data-lucide="<?= e($category['icon'] ?: 'grid-2x2') ?>"></i></span><strong><?= e($category['name']) ?></strong><span class="home-category-arrow">↗</span></a><?php endforeach; ?></div>
    </section>

    <?php if ($latestListings): ?><section class="home-section" aria-labelledby="listing-title"><div class="home-section-heading"><div><span class="home-eyebrow"><?= e(t('common.new')) ?></span><h2 id="listing-title"><?= $sectionTitle('latest_listings', 'listing.added') ?></h2></div><a class="home-text-link" href="<?= e(url('pages/search-vehicles.php')) ?>"><?= e(t('common.show_all')) ?> ↗</a></div><div class="home-listing-grid"><?php foreach ($latestListings as $listing): ?><a class="home-listing-card" href="<?= e(url('listing.php?id=' . (int)$listing['id'])) ?>"><div class="home-listing-image"><?php if ($imageUrlFor($listing)): ?><img src="<?= e($imageUrlFor($listing)) ?>" alt="" loading="lazy"><?php else: ?><span><?= e(t('listing.no_image')) ?></span><?php endif; ?><?php if ((int)$listing['is_premium'] === 1 || (int)$listing['is_featured'] === 1): ?><b><?= e(t('common.featured')) ?></b><?php endif; ?></div><div class="home-listing-body"><strong><?= $priceLabel($listing) ?></strong><h3><?= e($listing['title']) ?></h3><?php if (!empty($listing['city_name'])): ?><span><?= e($listing['city_name']) ?></span><?php endif; ?></div></a><?php endforeach; ?></div></section><?php endif; ?>

    <?php if ($featuredBusinesses): ?><section class="home-section" aria-labelledby="business-title"><div class="home-section-heading"><div><span class="home-eyebrow"><?= e(t('nav.business_dir')) ?></span><h2 id="business-title"><?= $sectionTitle('businesses', 'nav.business_dir') ?></h2></div><a class="home-text-link" href="<?= e(url('pages/businesses.php')) ?>"><?= e(t('common.show_all')) ?> ↗</a></div><div class="home-business-grid"><?php foreach ($featuredBusinesses as $business): ?><a class="home-business-card" href="<?= e(url('esnaf.php?slug=' . rawurlencode((string)$business['slug']))) ?>"><div class="home-business-avatar"><?php if (!empty($business['image'])): ?><img src="<?= e(imageUrl($business['image'])) ?>" alt="" loading="lazy"><?php else: ?><?= e(mb_strtoupper(mb_substr((string)$business['name'], 0, 1))) ?><?php endif; ?></div><div><h3><?= e($business['name']) ?></h3><span><?= e($business['city'] ?? '') ?></span></div><?php if ((float)($business['rating'] ?? 0) > 0): ?><b>★ <?= e(number_format((float)$business['rating'], 1, ',', '.')) ?></b><?php endif; ?></a><?php endforeach; ?></div></section><?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
