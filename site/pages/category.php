<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

$pdo = getDB();
$lang = currentLang();
$id = (int)($_GET['id'] ?? 0);
$module = preg_replace('/[^a-z_]/', '', (string)($_GET['module'] ?? ''));

$where = $id > 0 ? 'c.id = ?' : 'c.module = ? AND c.parent_id IS NULL';
$stmt = $pdo->prepare("SELECT c.*, COALESCE(ct.name, en.name, c.slug) AS name, COALESCE(ct.description, en.description, '') AS description FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = 'en' WHERE {$where} AND c.is_active = 1 LIMIT 1");
$stmt->execute([$lang, $id > 0 ? $id : $module]);
$category = $stmt->fetch();
if (!$category) {
    include __DIR__ . '/404.php';
    exit;
}
if ($id > 0 && $category['parent_id'] === null && in_array($category['module'], ['emlak', 'arac', 'ikinci_el', 'esnaf'], true)) {
    header('Location: ' . categoryUrl($category));
    exit;
}
$isJobs = $category['module'] === 'jobs';
$selfUrl = url($isJobs ? 'pages/jobs.php' : 'pages/category.php?id=' . (int)$category['id']);
$qs = static fn(array $extra): string => $selfUrl . (str_contains($selfUrl, '?') ? '&' : '?') . http_build_query(array_filter($extra, static fn($v) => $v !== '' && $v !== 0 && $v !== null));

// Filters
$countryId = (int)($_GET['country_id'] ?? 0);
$cityId = (int)($_GET['city_id'] ?? 0);
$subId = (int)($_GET['sub'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$sort = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc', 'popular'], true) ? $_GET['sort'] : 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$nameCol = ['tr' => 'name_tr', 'nl' => 'name_nl', 'en' => 'name_en', 'de' => 'name_de'][$lang] ?? 'name_en';
$countries = $pdo->query("SELECT id, {$nameCol} AS name FROM countries WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();
$cities = [];
if ($countryId) {
    $s = $pdo->prepare('SELECT id, name FROM cities WHERE country_id = ? AND is_active = 1 ORDER BY name');
    $s->execute([$countryId]);
    $cities = $s->fetchAll();
}
$s = $pdo->prepare("SELECT c.id, COALESCE(ct.name, en.name, c.slug) AS name, (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id AND l.status = 'active') AS cnt FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = 'en' WHERE c.parent_id = ? AND c.is_active = 1 ORDER BY c.sort_order, c.id");
$s->execute([$lang, (int)$category['id']]);
$subcategories = $s->fetchAll();

$conds = ["l.status = 'active'"];
$params = [];
if ($subId) { $conds[] = 'l.category_id = ?'; $params[] = $subId; }
else { $conds[] = '(l.category_id = ? OR l.category_id IN (SELECT id FROM categories WHERE parent_id = ?))'; $params[] = (int)$category['id']; $params[] = (int)$category['id']; }
if ($countryId) { $conds[] = 'l.country_id = ?'; $params[] = $countryId; }
if ($cityId) { $conds[] = 'l.city_id = ?'; $params[] = $cityId; }
if ($q !== '') { $conds[] = '(l.title LIKE ? OR l.description LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }
$whereSql = implode(' AND ', $conds);
$orderSql = ['newest' => 'l.is_premium DESC, l.created_at DESC', 'price_asc' => 'l.price ASC', 'price_desc' => 'l.price DESC', 'popular' => 'l.view_count DESC'][$sort];

$s = $pdo->prepare("SELECT COUNT(*) FROM listings l WHERE {$whereSql}");
$s->execute($params);
$total = (int)$s->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$s = $pdo->prepare("SELECT l.id, l.slug, l.title, l.image, l.price, l.is_premium, l.created_at, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE {$whereSql} ORDER BY {$orderSql} LIMIT " . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$s->execute($params);
$items = $s->fetchAll();

$filters = ['country_id' => $countryId, 'city_id' => $cityId, 'sub' => $subId, 'q' => $q, 'sort' => $sort];
$title = $isJobs ? t('jobs.title') : $category['name'];
$subtitle = $isJobs ? t('jobs.subtitle') : $category['description'];
$pageTitle = $title . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['home.css', 'category.css'];
include __DIR__ . '/../includes/header.php';
?>
<main class="category-page" data-testid="category-page">
  <section class="category-hero<?= $category['image'] ? ' has-image' : '' ?>">
    <?php if ($category['image']): ?><img src="<?= e(imageUrl($category['image'])) ?>" alt=""><?php endif; ?>
    <div class="category-hero-body">
      <span class="chip chip-accent"><?= e(t('category.results', ['count' => $total])) ?></span>
      <h1 data-testid="category-title"><?= e($title) ?></h1>
      <?php if ($subtitle): ?><p><?= e($subtitle) ?></p><?php endif; ?>
    </div>
    <a class="category-map-link" href="<?= url('pages/map.php?module=' . e($category['module'])) ?>"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><?= e(t('nav.map_search')) ?></a>
  </section>

  <div class="sh-layout category-layout">
    <aside class="sh-sidebar category-sidebar" data-testid="category-sidebar">
      <form class="sh-filter-form" method="get" action="<?= e($selfUrl) ?>">
        <?php if (!$isJobs): ?><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><?php endif; ?>
        <input type="hidden" name="sub" value="<?= $subId ?>">
        <div class="filter-group">
          <label class="filter-label"><?= e(t('common.country')) ?></label>
          <select name="country_id" data-testid="filter-country" data-city-target="filterCity">
            <option value=""><?= e(t('nav.all')) ?></option>
            <?php foreach ($countries as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $countryId === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
          <select name="city_id" id="filterCity" data-testid="filter-city"<?= $countryId ? '' : ' disabled' ?>>
            <option value=""><?= e(t('common.city')) ?></option>
            <?php foreach ($cities as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $cityId === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label"><?= e(t('common.search')) ?></label>
          <div class="filter-search"><input type="text" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search.placeholder')) ?>" data-testid="filter-q"><button type="submit" aria-label="<?= e(t('common.search')) ?>"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button></div>
        </div>
        <?php if ($subcategories): ?>
        <div class="filter-group">
          <label class="filter-label"><?= e(t('footer.categories')) ?></label>
          <ul class="filter-list">
            <li><a href="<?= e($qs(array_merge($filters, ['sub' => 0, 'page' => 0]))) ?>" class="<?= !$subId ? 'active' : '' ?>"><?= e(t('nav.all')) ?><span><?= $total ?></span></a></li>
            <?php foreach ($subcategories as $sc): ?>
            <li><a href="<?= e($qs(array_merge($filters, ['sub' => (int)$sc['id'], 'page' => 0]))) ?>" class="<?= $subId === (int)$sc['id'] ? 'active' : '' ?>"><?= e($sc['name']) ?><span><?= (int)$sc['cnt'] ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary filter-apply" data-testid="filter-apply"><?= e(t('common.filter')) ?></button>
        <a class="filter-reset" href="<?= e($selfUrl) ?>"><?= e(t('common.reset')) ?></a>
      </form>
    </aside>

    <section class="sh-main category-main" data-testid="category-results">
      <div class="results-bar">
        <span><?= e(t('category.results', ['count' => $total])) ?></span>
        <form method="get" action="<?= e($selfUrl) ?>" class="results-sort">
          <?php foreach ($filters as $k => $v): if ($k !== 'sort' && $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>"><?php endif; endforeach; ?>
          <?php if (!$isJobs): ?><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><?php endif; ?>
          <select name="sort" data-testid="results-sort">
            <?php foreach (['newest' => t('sort.newest'), 'price_asc' => t('sort.price_asc'), 'price_desc' => t('sort.price_desc'), 'popular' => t('sort.popular')] as $k => $label): ?>
            <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <?php if (!$items): ?>
        <div class="category-empty" data-testid="category-empty">
          <h3><?= e($isJobs ? t('jobs.empty') : t('category.empty_title')) ?></h3>
          <p><?= e(t('category.empty_desc')) ?></p>
          <a class="btn-primary" href="<?= url('pages/post.php') ?>" data-open-post><?= e(t('nav.post_ad')) ?></a>
        </div>
      <?php else: ?>
      <div class="listing-grid three">
        <?php foreach ($items as $item): ?>
        <a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$item['slug'])) ?>" class="listing-card">
          <div class="listing-media"><?php if ($item['image']): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="no-image"><?= e(t('listing.no_image')) ?></span><?php endif; ?><?php if ((int)$item['is_premium']): ?><span class="chip chip-gold"><?= e(t('common.premium')) ?></span><?php endif; ?><?= favoriteButton((int)$item['id']) ?></div>
          <div class="listing-body">
            <div class="listing-price"><?= e(formatPrice($item['price'])) ?></div>
            <h3><?= e($item['title']) ?></h3>
            <div class="listing-meta"><span><?= e($item['city_name'] ?? '') ?></span><span><?= e(timeAgo($item['created_at'])) ?></span></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
      <nav class="pagination" data-testid="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="<?= e($qs(array_merge($filters, ['page' => $p]))) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<script src="<?= asset('js/listing-ajax.js') ?>" defer></script>
<script src="<?= asset('js/category-filters.js') ?>" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
