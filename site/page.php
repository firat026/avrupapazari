<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$slug = preg_replace('/[^a-z-]/', '', (string)($_GET['slug'] ?? ''));
$page = null;
if ($slug !== '') {
    $stmt = getDB()->prepare("SELECT title, content, updated_at FROM static_pages WHERE slug = ? AND is_active = 1 AND lang IN (?, 'en') ORDER BY lang = ? DESC LIMIT 1");
    $stmt->execute([$slug, currentLang(), currentLang()]);
    $page = $stmt->fetch() ?: null;
}
if (!$page) {
    http_response_code(404);
    include __DIR__ . '/pages/404.php';
    exit;
}

$pageTitle = $page['title'] . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['page.css'];
include __DIR__ . '/includes/header.php';
?>
<main class="static-page" data-testid="static-page">
  <article class="static-card">
    <span class="eyebrow"><?= e(t('footer.info')) ?></span>
    <h1><?= e($page['title']) ?></h1>
    <div class="static-content"><?= $page['content'] ?></div>
    <p class="static-updated"><?= e(t('page.updated')) ?>: <?= e(date('d.m.Y', strtotime((string)$page['updated_at']))) ?></p>
  </article>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
