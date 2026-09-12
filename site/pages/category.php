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

$stmt = $pdo->prepare("SELECT l.id, l.slug, l.title, l.image, l.price, l.is_premium, l.created_at, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE l.status = 'active' AND (l.category_id = ? OR l.category_id IN (SELECT id FROM categories WHERE parent_id = ?)) ORDER BY l.is_premium DESC, l.created_at DESC");
$stmt->execute([(int)$category['id'], (int)$category['id']]);
$items = $stmt->fetchAll();

$isJobs = $category['module'] === 'jobs';
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
      <span class="chip chip-accent"><?= e(t('category.results', ['count' => count($items)])) ?></span>
      <h1 data-testid="category-title"><?= e($title) ?></h1>
      <?php if ($subtitle): ?><p><?= e($subtitle) ?></p><?php endif; ?>
    </div>
  </section>

  <section class="category-body">
    <?php if (!$items): ?>
      <div class="category-empty" data-testid="category-empty">
        <h3><?= e($isJobs ? t('jobs.empty') : t('category.empty_title')) ?></h3>
        <p><?= e(t('category.empty_desc')) ?></p>
        <a class="btn-primary" href="<?= url('pages/post.php') ?>"><?= e(t('nav.post_ad')) ?></a>
      </div>
    <?php else: ?>
    <div class="listing-grid">
      <?php foreach ($items as $item): ?>
      <a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$item['slug'])) ?>" class="listing-card">
        <div class="listing-media"><?php if ($item['image']): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="no-image"><?= e(t('listing.no_image')) ?></span><?php endif; ?><?php if ((int)$item['is_premium']): ?><span class="chip chip-gold"><?= e(t('common.premium')) ?></span><?php endif; ?></div>
        <div class="listing-body">
          <div class="listing-price"><?= e(formatPrice($item['price'])) ?></div>
          <h3><?= e($item['title']) ?></h3>
          <div class="listing-meta"><span><?= e($item['city_name'] ?? '') ?></span><span><?= e(timeAgo($item['created_at'])) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
