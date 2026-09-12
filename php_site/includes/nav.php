<?php
// Ana Navigasyon - Kategori linkleri
if (!isset($L)) { require_once __DIR__ . '/../config.php'; }
$base = defined('BASE_URL') ? BASE_URL : '/';
?>
<nav class="site-nav">
  <div class="container nav-inner">
    <a href="<?= $base ?>pages/harita.php" class="nav-pill nav-map">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <span><?= t('map') ?></span>
    </a>

    <div class="nav-links">
      <a href="<?= $base ?>pages/vasitalar.php" class="nav-link"><?= t('vasitalar') ?></a>
      <a href="<?= $base ?>pages/emlak.php" class="nav-link"><?= t('emlak') ?></a>
      <a href="<?= $base ?>pages/ikinci-el.php" class="nav-link"><?= t('ikinci_el') ?></a>
      <a href="<?= $base ?>pages/hizmetler.php" class="nav-link"><?= t('hizmetler') ?></a>
      <a href="<?= $base ?>pages/is-ilanlari.php" class="nav-link"><?= t('is_ilanlari') ?></a>
    </div>

    <div class="nav-spacer"></div>
  </div>
</nav>
