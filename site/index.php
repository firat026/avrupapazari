<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$siteMode = is_file(__DIR__ . '/admin/.site_mode') ? trim((string)file_get_contents(__DIR__ . '/admin/.site_mode')) : 'online';
if ($siteMode === 'offline' && empty($_SESSION['admin_id'])) {
    header('Location: ' . url('maintenance.php'), true, 302);
    exit;
}

$pdo = getDB();
$lang = currentLang();
$pageTitle = setting('site_name', 'AvrupaPazari') . ' - ' . t('footer.slogan');
$pageStyles = ['home.css'];

$sections = ['bento', 'popular', 'featured', 'howto', 'cta'];
try {
    $rows = $pdo->query('SELECT section_key FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll(PDO::FETCH_COLUMN);
    if ($rows) $sections = $rows;
} catch (Throwable $e) {}

$categories = [];
try {
    $stmt = $pdo->prepare("SELECT c.id, c.module, c.slug, c.icon, c.color_1, c.color_2, c.image, COALESCE(ct.name, en.name, c.slug) AS name, COALESCE(ct.description, en.description) AS description,
        (SELECT COUNT(*) FROM listings l WHERE l.status = 'active' AND (l.category_id = c.id OR l.category_id IN (SELECT id FROM categories s WHERE s.parent_id = c.id))) AS listing_count
        FROM categories c
        LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ?
        LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = 'en'
        WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order ASC, c.id ASC");
    $stmt->execute([$lang]);
    $categories = $stmt->fetchAll();
    $order = ['arac' => 0, 'emlak' => 1, 'ikinci_el' => 2, 'esnaf' => 3, 'jobs' => 4];
    usort($categories, static fn(array $a, array $b): int => ($order[$a['module']] ?? 99) <=> ($order[$b['module']] ?? 99));
} catch (Throwable $e) {}

$popular = [];
if (in_array('popular', $sections, true)) {
    $limit = max(4, (int)setting('popular_count', '8'));
    $stmt = $pdo->prepare("SELECT l.id, l.slug, l.title, l.image, l.price, l.price_type, l.is_premium, l.created_at, l.module, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE l.status = 'active' ORDER BY l.is_premium DESC, l.view_count DESC, l.created_at DESC LIMIT " . $limit);
    $stmt->execute();
    $popular = $stmt->fetchAll();
}

$businesses = [];
if (in_array('featured', $sections, true)) {
    $limit = max(4, (int)setting('featured_count', '4'));
    $businesses = $pdo->query("SELECT id, name, slug, city, country, image, rating, review_count FROM esnaf WHERE status = 'active' ORDER BY is_featured DESC, rating DESC, id DESC LIMIT " . $limit)->fetchAll();
}

$moduleLabel = [];
foreach ($categories as $c) $moduleLabel[$c['module']] = $c['name'];

include __DIR__ . '/includes/header.php';
?>
<main class="home" data-testid="home-page">
<?php foreach ($sections as $section): ?>
<?php if ($section === 'bento'): ?>
  <section class="home-section bento-section">
    <div class="container">
      <div class="hero-copy">
        <h1 data-testid="home-title"><?= e(t('home.hero_title')) ?></h1>
        <p><?= e(t('home.hero_subtitle')) ?></p>
      </div>
      <div class="home-bento" data-testid="bento-grid">
        <?php foreach ($categories as $i => $cat): ?>
        <a href="<?= e(categoryUrl($cat)) ?>" class="home-card<?= $i < 2 ? ' large' : '' ?> module-<?= e($cat['module']) ?>" data-testid="bento-<?= e($cat['module']) ?>">
          <?php if (!empty($cat['image'])): ?>
          <img src="<?= e(imageUrl($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" loading="<?= $i < 2 ? 'eager' : 'lazy' ?>">
          <?php else: ?>
          <div class="home-card-fallback" style="background:linear-gradient(135deg,<?= e($cat['color_1'] ?: '#1d7a4e') ?>,<?= e($cat['color_2'] ?: '#0d3d25') ?>)"></div>
          <?php endif; ?>
          <div class="home-card-shade"></div>
          <div class="home-card-body">
            <?php if ($i === 0): ?><span class="chip chip-accent"><?= e(t('common.popular')) ?></span><?php endif; ?>
            <?php if ($cat['module'] === 'jobs'): ?><span class="chip chip-new"><?= e(t('common.new')) ?></span><?php endif; ?>
            <h2><?= e($cat['name']) ?></h2>
            <p><?= e($cat['description'] ?: '') ?><?php if ((int)$cat['listing_count'] > 0): ?> <span class="count"><?= (int)$cat['listing_count'] ?>+ <?= e(t('home.listings')) ?></span><?php endif; ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

<?php elseif ($section === 'popular'): ?>
  <section class="home-section">
    <div class="container">
      <div class="section-head">
        <div><span class="eyebrow"><?= e(t('common.popular')) ?></span><h2><?= e(setting('popular_title', t('home.popular_listings'))) ?></h2></div>
        <a class="text-link" href="<?= url('pages/search-vehicles.php') ?>"><?= e(t('common.show_all')) ?> <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
      </div>
      <div class="listing-grid" data-testid="popular-listings">
        <?php foreach ($popular as $item): ?>
        <a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$item['slug'])) ?>" class="listing-card">
          <div class="listing-media">
            <?php if (!empty($item['image'])): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="no-image"><?= e(t('listing.no_image')) ?></span><?php endif; ?>
            <?php if ((int)$item['is_premium']): ?><span class="chip chip-gold"><?= e(t('common.premium')) ?></span><?php endif; ?>
          <?= favoriteButton((int)$item['id']) ?></div>
          <div class="listing-body">
            <div class="listing-price"><?= e(formatPrice($item['price'])) ?></div>
            <h3><?= e($item['title']) ?></h3>
            <div class="listing-meta">
              <span><?= e($moduleLabel[$item['module']] ?? '') ?></span>
              <span><?= e($item['city_name'] ?? '') ?><?= $item['city_name'] ? ' · ' : '' ?><?= e(timeAgo($item['created_at'])) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
        <?php if (!$popular): ?><p class="empty"><?= e(t('home.no_listings')) ?></p><?php endif; ?>
      </div>
    </div>
  </section>

<?php elseif ($section === 'featured'): ?>
  <section class="home-section">
    <div class="container">
      <div class="section-head">
        <div><span class="eyebrow"><?= e(t('nav.business_dir')) ?></span><h2><?= e(setting('featured_title', t('home.featured_businesses'))) ?></h2></div>
        <a class="text-link" href="<?= url('pages/businesses.php') ?>"><?= e(t('common.show_all')) ?> <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
      </div>
      <div class="business-grid" data-testid="featured-businesses">
        <?php foreach ($businesses as $b): $initials = mb_strtoupper(mb_substr((string)$b['name'], 0, 1)); ?>
        <a href="<?= url('pages/businesses.php?slug=' . rawurlencode((string)$b['slug'])) ?>" class="business-card">
          <div class="business-avatar"><?php if (!empty($b['image'])): ?><img src="<?= e(imageUrl($b['image'])) ?>" alt="" loading="lazy"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
          <h3><?= e($b['name']) ?></h3>
          <p><?= e($b['city'] ?? '') ?><?= !empty($b['country']) ? ', ' . e($b['country']) : '' ?></p>
          <div class="rating"><svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21l1.5-7L2 9.3l7-.8z"/></svg><?= number_format((float)($b['rating'] ?? 0), 1) ?><small>(<?= (int)($b['review_count'] ?? 0) ?>)</small></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

<?php elseif ($section === 'howto'): ?>
  <section class="home-section">
    <div class="container">
      <div class="section-head"><div><span class="eyebrow">01 · 02 · 03</span><h2><?= e(t('home.how_it_works')) ?></h2></div></div>
      <div class="steps">
        <?php for ($n = 1; $n <= 3; $n++): ?>
        <div class="step"><span class="step-num"><?= $n ?></span><h3><?= e(setting("step{$n}_title", t("home.step{$n}_title"))) ?></h3><p><?= e(setting("step{$n}_desc", t("home.step{$n}_desc"))) ?></p></div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

<?php elseif ($section === 'cta'): ?>
  <section class="home-section">
    <div class="container">
      <div class="cta" data-testid="home-cta">
        <div><h2><?= e(setting('cta_title', t('home.cta_title'))) ?></h2><p><?= e(setting('cta_desc', t('home.cta_desc'))) ?></p></div>
        <a class="btn-primary cta-btn" href="<?= url('pages/post.php') ?>" data-open-post><?= e(setting('cta_btn', t('home.cta_btn'))) ?></a>
      </div>
    </div>
  </section>
<?php endif; ?>
<?php endforeach; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
