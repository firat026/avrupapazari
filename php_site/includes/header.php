<?php
// Header - Premium tasarım: sol arama + logo, sağ araçlar
if (!isset($L)) { require_once __DIR__ . '/../config.php'; }
$base = defined('BASE_URL') ? BASE_URL : '/';
?>
<header class="site-header">
  <div class="container header-inner">

    <!-- SOL: Marka -->
    <a href="<?= $base ?>index.php" class="brand-block" aria-label="<?= t('home') ?>">
      <span class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1V9.5Z"/>
        </svg>
      </span>
      <span class="brand">
        <span class="brand-primary">Avrupa</span><span class="brand-secondary">pazarı</span>
      </span>
    </a>

    <!-- ORTA: Arama -->
    <form class="search-bar" role="search" onsubmit="return false;">
      <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" placeholder="<?= t('search_placeholder') ?>" aria-label="Search">
      <span class="search-divider"></span>
      <button type="submit" class="search-submit">
        <?= t('search') ?>
      </button>
    </form>

    <!-- SAĞ: Araçlar -->
    <div class="header-tools">
      <button id="themeToggle" class="icon-btn" aria-label="Theme">
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
      </button>

      <div class="lang-switch" role="group" aria-label="Language">
        <button type="button" class="lang-current" id="langBtn">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span><?= strtoupper($current_lang) ?></span>
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="lang-menu" id="langMenu">
          <?php $names = ['tr'=>'Türkçe','en'=>'English','nl'=>'Nederlands','de'=>'Deutsch']; ?>
          <?php foreach ($names as $lg => $ln): ?>
            <a href="?lang=<?= $lg ?>" class="lang-item <?= $current_lang === $lg ? 'active' : '' ?>">
              <span class="lang-code"><?= strtoupper($lg) ?></span>
              <span class="lang-name"><?= $ln ?></span>
              <?php if ($current_lang === $lg): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="#" class="btn btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        <span><?= t('login') ?></span>
      </a>
      <a href="#" class="btn btn-primary">
        <?= t('register') ?>
      </a>
    </div>
  </div>
</header>
