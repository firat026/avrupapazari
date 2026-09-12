<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

$user = currentUser();
$lang = currentLang();
$stmt = getDB()->prepare("SELECT c.id, c.module, c.icon, COALESCE(ct.name, en.name, c.slug) AS name, COALESCE(ct.description, en.description, '') AS description FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = 'en' WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order ASC");
$stmt->execute([$lang]);
$categories = $stmt->fetchAll();
$targets = ['arac' => url('pages/post-vehicle.php')];

$pageTitle = t('post.title') . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['post.css'];
include __DIR__ . '/../includes/header.php';
?>
<main class="post-page" data-testid="post-page">
  <header class="post-head">
    <span class="eyebrow"><?= e(t('post.free_note')) ?></span>
    <h1><?= e(t('post.title')) ?></h1>
    <p><?= e(t('post.choose_category')) ?></p>
    <?php if (!$user): ?><p class="post-login"><?= e(t('post.login_required')) ?> <a href="#" data-open-auth="login"><?= e(t('auth.login')) ?></a></p><?php endif; ?>
  </header>
  <div class="post-grid">
    <?php foreach ($categories as $cat): $target = $targets[$cat['module']] ?? null; ?>
    <?php if ($target && $user): ?><a class="post-card" href="<?= e($target) ?>" data-testid="post-<?= e($cat['module']) ?>"><?php elseif ($target): ?><a class="post-card" href="#" data-open-auth="login" data-testid="post-<?= e($cat['module']) ?>"><?php else: ?><div class="post-card disabled" data-testid="post-<?= e($cat['module']) ?>"><?php endif; ?>
      <span class="post-icon"><i data-lucide="<?= e($cat['icon'] ?: 'grid-2x2') ?>"></i></span>
      <span class="post-name"><?= e($cat['name']) ?></span>
      <span class="post-desc"><?= e($target ? $cat['description'] : t('post.coming_soon')) ?></span>
    <?= $target ? '</a>' : '</div>' ?>
    <?php endforeach; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
