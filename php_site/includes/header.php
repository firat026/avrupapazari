<?php
// Header - Logo, Dark Mode, Diller, Kayıt/Giriş
if (!isset($L)) { require_once __DIR__ . '/../config.php'; }
$base = defined('BASE_URL') ? BASE_URL : '/';
?>
<header class="site-header">
  <div class="container header-inner">
    <!-- Sol: Ana sayfa ikonu -->
    <a href="<?= $base ?>index.php" class="logo-icon" aria-label="<?= t('home') ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1V9.5Z"/>
      </svg>
    </a>

    <!-- Orta: Logo yazı -->
    <a href="<?= $base ?>index.php" class="brand">
      <span class="brand-primary">Avrupa</span><span class="brand-secondary">pazarı</span>
    </a>

    <!-- Sağ: Araçlar -->
    <div class="header-tools">
      <button id="themeToggle" class="icon-btn" aria-label="Theme">
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
      </button>

      <div class="lang-switch" role="group" aria-label="Language">
        <?php foreach (['en','tr','nl','de'] as $lg): ?>
          <a href="?lang=<?= $lg ?>" class="lang-btn <?= $current_lang === $lg ? 'active' : '' ?>"><?= strtoupper($lg) ?></a>
        <?php endforeach; ?>
      </div>

      <a href="#" class="btn btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?= t('register') ?>
      </a>
      <a href="#" class="btn btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        <?= t('login') ?>
      </a>
    </div>
  </div>
</header>
