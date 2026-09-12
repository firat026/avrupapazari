<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

$slug = (string)($_GET['slug'] ?? '');
$stmt = getDB()->prepare("SELECT e.*, COALESCE(ct.name, en.name) AS category_name FROM esnaf e LEFT JOIN category_translations ct ON ct.category_id = e.category_id AND ct.lang = ? LEFT JOIN category_translations en ON en.category_id = e.category_id AND en.lang = 'en' WHERE e.slug = ? AND e.status = 'active' LIMIT 1");
$stmt->execute([currentLang(), $slug]);
$business = $stmt->fetch();
if (!$business) {
    include __DIR__ . '/404.php';
    exit;
}
$pageTitle = $business['name'] . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['home.css', 'business.css'];
include __DIR__ . '/../includes/header.php';
?>
<main class="business-page" data-testid="business-page">
  <a class="text-link back-link" href="<?= url('pages/businesses.php') ?>"><svg viewBox="0 0 24 24"><path d="m15 6-6 6 6 6"/></svg> <?= e(t('nav.business_dir')) ?></a>
  <section class="business-hero">
    <div class="business-avatar large"><?php if ($business['image']): ?><img src="<?= e(imageUrl($business['image'])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr((string)$business['name'], 0, 1))) ?><?php endif; ?></div>
    <div class="business-info">
      <?php if ($business['category_name']): ?><span class="eyebrow"><?= e($business['category_name']) ?></span><?php endif; ?>
      <h1 data-testid="business-name"><?= e($business['name']) ?></h1>
      <p class="business-location"><?= e(trim(($business['address'] ? $business['address'] . ', ' : '') . ($business['city'] ?? '') . ($business['country'] ? ', ' . $business['country'] : ''))) ?></p>
      <div class="rating"><svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21l1.5-7L2 9.3l7-.8z"/></svg><?= number_format((float)$business['rating'], 1) ?> <small>(<?= (int)$business['review_count'] ?>)</small></div>
    </div>
    <div class="business-contact">
      <?php if ($business['phone']): ?><a class="btn-primary" href="tel:<?= e(preg_replace('/\s+/', '', (string)$business['phone'])) ?>"><?= e($business['phone']) ?></a><?php endif; ?>
      <?php if ($business['email']): ?><a class="pill-btn" href="mailto:<?= e($business['email']) ?>"><?= e($business['email']) ?></a><?php endif; ?>
      <?php if ($business['website']): ?><a class="pill-btn" href="<?= e($business['website']) ?>" target="_blank" rel="noopener"><?= e(preg_replace('~^https?://~', '', (string)$business['website'])) ?></a><?php endif; ?>
    </div>
  </section>
  <?php if ($business['description']): ?><section class="business-about"><h2><?= e(t('footer.about')) ?></h2><p><?= nl2br(e($business['description'])) ?></p></section><?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
