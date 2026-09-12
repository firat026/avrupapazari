<?php
// Ortak kategori sayfası şablonu
if (!isset($L)) { require_once __DIR__ . '/../config.php'; }
if (!isset($page_key)) $page_key = 'vasitalar';
$title = t($page_key);
// Bu kategoriye ait ilanları filtrele
$items = array_filter($LISTINGS, function($x) use ($page_key) { return $x['category'] === $page_key; });
if (empty($items)) $items = $LISTINGS; // gösteri için fallback
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> — <?= t('site_name') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/../includes/header.php'; ?>
  <?php require __DIR__ . '/../includes/nav.php'; ?>
  <main class="main-content">
    <section class="container">
      <div class="section-head" style="text-align:left;">
        <h2><?= $title ?></h2>
        <?php if (isset($page_desc) && $page_desc): ?>
          <p class="muted"><?= t($page_desc) ?></p>
        <?php endif; ?>
      </div>
      <div class="listings-grid">
        <?php foreach ($items as $item): ?>
          <a href="#" class="listing-card">
            <div class="listing-img">
              <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
            </div>
            <div class="listing-body">
              <span class="listing-cat"><?= t($item['category']) ?></span>
              <h4><?= htmlspecialchars($item['title']) ?></h4>
              <div class="listing-meta">
                <span class="price"><?= $item['price'] ?></span>
                <span class="city"><?= $item['city'] ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
  <script src="../assets/js/script.js"></script>
</body>
</html>
