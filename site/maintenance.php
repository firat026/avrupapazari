<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
http_response_code(503);
$pageTitle = setting('site_name', 'AvrupaPazari');
?>
<!DOCTYPE html>
<html lang="<?= e(currentLang()) ?>" data-theme="light">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>"><link rel="stylesheet" href="<?= asset('css/page.css') ?>">
</head>
<body>
<main class="error-page" data-testid="maintenance-page">
  <div class="error-code">503</div>
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e(t('common.loading')) ?></p>
</main>
</body>
</html>
