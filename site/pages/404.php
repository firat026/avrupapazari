<?php
declare(strict_types=1);
if (!function_exists('e')) { require_once __DIR__ . '/../functions.php'; }
http_response_code(404);
$pageTitle = t('page.not_found') . ' - ' . setting('site_name', 'AvrupaPazari');
$pageStyles = ['page.css'];
include __DIR__ . '/../includes/header.php';
?>
<main class="error-page" data-testid="not-found-page">
  <div class="error-code">404</div>
  <h1><?= e(t('page.not_found')) ?></h1>
  <p><?= e(t('page.not_found_desc')) ?></p>
  <a class="btn-primary" href="<?= url('/') ?>"><?= e(t('page.back_home')) ?></a>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
