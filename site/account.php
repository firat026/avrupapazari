<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$user = requireLogin();

$stmt = getDB()->prepare("SELECT l.id, l.slug, l.title, l.image, l.price, l.status, l.view_count, l.created_at, l.module, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE l.user_id = ? ORDER BY l.created_at DESC");
$stmt->execute([(int)$user['id']]);
$listings = $stmt->fetchAll();

$favStmt = getDB()->prepare("SELECT l.id, l.slug, l.title, l.image, l.price, l.is_premium, l.created_at, ci.name AS city_name FROM favorites f JOIN listings l ON l.id = f.listing_id LEFT JOIN cities ci ON ci.id = l.city_id WHERE f.user_id = ? AND l.status = 'active' ORDER BY f.created_at DESC");
$favStmt->execute([(int)$user['id']]);
$favorites = $favStmt->fetchAll();

$pageTitle = t('account.title') . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['home.css', 'account.css'];
include __DIR__ . '/includes/header.php';
?>
<main class="account" data-testid="account-page">
  <section class="account-hero">
    <div class="account-avatar"><?php if (!empty($user['profile_image'])): ?><img src="<?= e(imageUrl($user['profile_image'])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr((string)$user['first_name'], 0, 1) . mb_substr((string)$user['last_name'], 0, 1))) ?><?php endif; ?></div>
    <div>
      <span class="eyebrow"><?= e(t('account.title')) ?></span>
      <h1 data-testid="account-name"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
      <p><?= e($user['email']) ?> · <?= e(t('account.member_since')) ?> <?= e(date('m.Y', strtotime((string)$user['created_at']))) ?></p>
    </div>
    <div class="account-actions">
      <a class="btn-primary" href="<?= url('pages/post.php') ?>"><?= e(t('nav.post_ad')) ?></a>
      <a class="pill-btn" href="<?= url('auth/logout.php') ?>" data-testid="account-logout"><?= e(t('nav.logout')) ?></a>
    </div>
  </section>

  <section class="account-section" id="listings">
    <div class="section-head"><div><span class="eyebrow"><?= count($listings) ?></span><h2><?= e(t('account.listings')) ?></h2></div></div>
    <?php if (!$listings): ?>
      <div class="account-empty"><p><?= e(t('account.no_listings')) ?></p><a class="btn-primary" href="<?= url('pages/post.php') ?>"><?= e(t('nav.post_ad')) ?></a></div>
    <?php else: ?>
    <div class="listing-grid">
      <?php foreach ($listings as $item): ?>
      <a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$item['slug'])) ?>" class="listing-card">
        <div class="listing-media"><?php if ($item['image']): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt=""><?php else: ?><span class="no-image"><?= e(t('listing.no_image')) ?></span><?php endif; ?><span class="chip status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span><?= favoriteButton((int)$item['id']) ?></div>
        <div class="listing-body">
          <div class="listing-price"><?= e(formatPrice($item['price'])) ?></div>
          <h3><?= e($item['title']) ?></h3>
          <div class="listing-meta"><span><?= e($item['city_name'] ?? '') ?></span><span><?= (int)$item['view_count'] ?> <?= e(t('account.views')) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <section class="account-section" id="favorites" data-testid="account-favorites">
    <div class="section-head"><div><span class="eyebrow"><?= count($favorites) ?></span><h2><?= e(t('account.favorites')) ?></h2></div></div>
    <?php if (!$favorites): ?>
      <div class="account-empty" data-testid="favorites-empty"><p><?= e(t('account.no_favorites')) ?></p></div>
    <?php else: ?>
    <div class="listing-grid">
      <?php foreach ($favorites as $item): ?>
      <a href="<?= url('pages/listing.php?slug=' . rawurlencode((string)$item['slug'])) ?>" class="listing-card" data-fav-card data-testid="favorite-card-<?= (int)$item['id'] ?>">
        <div class="listing-media"><?php if ($item['image']): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt=""><?php else: ?><span class="no-image"><?= e(t('listing.no_image')) ?></span><?php endif; ?><?= favoriteButton((int)$item['id']) ?></div>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
