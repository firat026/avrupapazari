<?php
define('BASE_URL', '../');
require_once __DIR__ . '/../config.php';
$page_key = 'map';
$page_desc = null;
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('map') ?> — <?= t('site_name') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/../includes/header.php'; ?>
  <?php require __DIR__ . '/../includes/nav.php'; ?>
  <main class="main-content">
    <section class="container">
      <div class="section-head">
        <h2><?= t('map') ?></h2>
        <p class="muted"><?= t('latest_ads_sub') ?></p>
      </div>
      <div style="aspect-ratio: 16/8; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border);">
        <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=-10.5%2C35.5%2C25.0%2C55.0&layer=mapnik" style="width:100%;height:100%;border:0;" loading="lazy"></iframe>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
  <script src="../assets/js/script.js"></script>
</body>
</html>
