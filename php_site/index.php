<?php
define('BASE_URL', './');
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('site_name') ?> — <?= t('latest_ads') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/includes/header.php'; ?>
  <?php require __DIR__ . '/includes/nav.php'; ?>

  <main class="main-content">
    <!-- Kategori Kartları (Bento Grid) -->
    <section class="container categories">
      <div class="bento-grid">
        <!-- Vasıtalar -->
        <a href="pages/vasitalar.php" class="cat-card cat-large">
          <img src="<?= $CATEGORIES[0]['image'] ?>" alt="<?= t('vasitalar') ?>" loading="lazy">
          <div class="cat-overlay"></div>
          <div class="cat-content">
            <span class="chip chip-primary"><?= t('popular_category') ?></span>
            <h3 class="cat-title"><?= t('vasitalar') ?></h3>
            <p class="cat-desc"><?= t('vasitalar_desc') ?> (10<?= t('more_ads') ?>)</p>
          </div>
        </a>

        <!-- Emlak -->
        <a href="pages/emlak.php" class="cat-card cat-large">
          <img src="<?= $CATEGORIES[1]['image'] ?>" alt="<?= t('emlak') ?>" loading="lazy">
          <div class="cat-overlay"></div>
          <div class="cat-content">
            <h3 class="cat-title"><?= t('emlak') ?></h3>
            <p class="cat-desc"><?= t('emlak_desc') ?> (32<?= t('more_ads') ?>)</p>
          </div>
        </a>

        <!-- İkinci El -->
        <a href="pages/ikinci-el.php" class="cat-card cat-small">
          <img src="<?= $CATEGORIES[2]['image'] ?>" alt="<?= t('ikinci_el') ?>" loading="lazy">
          <div class="cat-overlay"></div>
          <div class="cat-content">
            <h3 class="cat-title"><?= t('ikinci_el') ?></h3>
            <p class="cat-desc"><?= t('ikinci_el_desc') ?> (18<?= t('more_ads') ?>)</p>
          </div>
        </a>

        <!-- Hizmetler -->
        <a href="pages/hizmetler.php" class="cat-card cat-small">
          <img src="<?= $CATEGORIES[3]['image'] ?>" alt="<?= t('hizmetler') ?>" loading="lazy">
          <div class="cat-overlay"></div>
          <div class="cat-content">
            <h3 class="cat-title"><?= t('hizmetler') ?></h3>
            <p class="cat-desc"><?= t('hizmetler_desc') ?> (9<?= t('more_ads') ?>)</p>
          </div>
        </a>

        <!-- İş İlanları -->
        <a href="pages/is-ilanlari.php" class="cat-card cat-small cat-tinted">
          <img src="<?= $CATEGORIES[4]['image'] ?>" alt="<?= t('is_ilanlari') ?>" loading="lazy">
          <div class="cat-overlay tinted"></div>
          <div class="cat-content">
            <span class="chip chip-new"><?= t('new') ?></span>
            <h3 class="cat-title"><?= t('is_ilanlari') ?></h3>
            <p class="cat-desc"><?= t('is_ilanlari_desc') ?></p>
          </div>
        </a>
      </div>
    </section>

    <!-- Son Eklenen İlanlar -->
    <section class="container latest-section">
      <div class="section-head">
        <h2><?= t('latest_ads') ?></h2>
        <p class="muted"><?= t('latest_ads_sub') ?></p>
      </div>

      <div class="listings-grid">
        <?php foreach ($LISTINGS as $item): ?>
          <a href="#" class="listing-card">
            <div class="listing-img">
              <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
            </div>
            <div class="listing-body">
              <span class="listing-cat"><?= t($item['category']) ?></span>
              <h4><?= htmlspecialchars($item['title']) ?></h4>
              <div class="listing-meta">
                <span class="price"><?= $item['price'] ?></span>
                <span class="city">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <?= $item['city'] ?>
                </span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- Sabit İlan Ver Butonu -->
  <a href="#" class="fab-post">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    <?= t('post_ad') ?>
  </a>

  <?php require __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/script.js"></script>
</body>
</html>
