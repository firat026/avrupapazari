<?php
declare(strict_types=1);
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config.php'; }
if (!function_exists('e')) { require_once __DIR__ . '/../functions.php'; }

$lang = currentLang();
$user = currentUser();
$pageTitle = $pageTitle ?? setting('site_name', 'AvrupaPazari');
$pageStyles = $pageStyles ?? [];
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navCategories = [];
try {
    $stmt = getDB()->prepare('SELECT c.id, c.module, c.icon, COALESCE(ct.name, en.name, c.slug) AS name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = \'en\' WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order ASC, c.id ASC');
    $stmt->execute([$lang]);
    $navCategories = $stmt->fetchAll();
} catch (Throwable $e) {}

$navIcons = [
    'arac' => '<path d="M5 17h14M6 11l1.5-4h9L18 11M4 11h16v6H4z"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/>',
    'emlak' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h14v-9.5"/><path d="M9 20v-5h6v5"/>',
    'ikinci_el' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'esnaf' => '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v3a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0V9Z"/><path d="M5 14v6h14v-6"/>',
    'jobs' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>',
];
$pageByModule = ['arac' => 'search-vehicles.php', 'emlak' => 'property.php', 'ikinci_el' => 'second-hand.php', 'esnaf' => 'businesses.php', 'jobs' => 'jobs.php'];
$currentModule = array_search($currentScript, $pageByModule, true);
$mapHref = url('pages/map.php') . ($currentModule ? '?module=' . $currentModule : '');
$langNames = ['tr' => 'Türkçe', 'nl' => 'Nederlands', 'en' => 'English', 'de' => 'Deutsch'];
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<script>(function(){try{var t=localStorage.getItem('site_theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/header.css') ?>">
<link rel="stylesheet" href="<?= asset('css/footer.css') ?>">
<link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
<link rel="stylesheet" href="<?= asset('css/post-modal.css') ?>">
<script>window.SITE_USER = <?= $user ? 'true' : 'false' ?>;</script>
<?php foreach ($pageStyles as $style): ?>
<link rel="stylesheet" href="<?= asset('css/' . $style) ?>">
<?php endforeach; ?>
</head>
<body>
<header class="site-header" data-testid="site-header">
  <div class="header-top">
    <div class="header-inner">
      <a class="brand" href="<?= url('/') ?>" data-testid="brand-link">
        <span class="brand-mark"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h14v-9.5"/><path d="M9 20v-5h6v5"/></svg></span>
        <span class="brand-text">Avrupa<em>pazari</em></span>
      </a>

      <form class="header-search" action="<?= url('pages/search-vehicles.php') ?>" method="get" role="search" data-testid="header-search-form">
        <svg class="search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(t('search.placeholder')) ?>" aria-label="<?= e(t('common.search')) ?>" data-testid="header-search-input">
        <button type="submit" class="search-btn" data-testid="header-search-submit"><?= e(t('common.search')) ?></button>
      </form>

      <div class="header-tools">
        <button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="Theme" data-testid="theme-toggle">
          <svg class="ico-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
          <svg class="ico-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>

        <div class="dropdown lang-switch" data-dropdown data-testid="lang-switch">
          <button type="button" class="pill-btn dropdown-toggle" data-testid="lang-toggle">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
            <span><?= strtoupper($lang) ?></span>
            <svg class="chev" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="dropdown-menu">
            <?php foreach ($langNames as $code => $name): ?>
            <a href="<?= e(langUrl($code)) ?>" class="dropdown-item<?= $code === $lang ? ' active' : '' ?>" data-testid="lang-option-<?= $code ?>"><b><?= strtoupper($code) ?></b><span><?= $name ?></span></a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($user): ?>
        <div class="dropdown user-menu" data-dropdown data-testid="user-menu">
          <button type="button" class="pill-btn dropdown-toggle" data-testid="user-menu-toggle">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr((string)$user['first_name'], 0, 1))) ?></span>
            <span class="user-name"><?= e($user['first_name']) ?></span>
            <svg class="chev" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="dropdown-menu">
            <a href="<?= url('account.php') ?>" class="dropdown-item" data-testid="menu-account"><?= e(t('nav.my_account')) ?></a>
            <a href="<?= url('account.php#listings') ?>" class="dropdown-item"><?= e(t('nav.my_listings')) ?></a>
            <a href="<?= url('account.php#favorites') ?>" class="dropdown-item" data-testid="menu-favorites"><?= e(t('account.favorites')) ?></a>
            <a href="<?= url('auth/logout.php') ?>" class="dropdown-item danger" data-testid="menu-logout"><?= e(t('nav.logout')) ?></a>
          </div>
        </div>
        <?php else: ?>
        <button type="button" class="btn-primary join-btn" data-open-auth="register" data-testid="join-button">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
          <span><?= e(t('nav.join')) ?></span>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <nav class="header-nav" aria-label="Categories">
    <div class="header-inner">
      <a class="nav-map<?= $currentScript === 'map.php' ? ' active' : '' ?>" href="<?= e($mapHref) ?>" data-testid="nav-map">
        <svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <span><?= e(t('nav.map_search')) ?></span>
      </a>
      <div class="nav-links">
        <?php foreach ($navCategories as $cat): $module = (string)$cat['module']; ?>
        <a class="nav-link<?= ($pageByModule[$module] ?? '') === $currentScript ? ' active' : '' ?>" href="<?= e(categoryUrl($cat)) ?>" data-testid="nav-<?= e($module) ?>">
          <svg viewBox="0 0 24 24"><?= $navIcons[$module] ?? '<circle cx="12" cy="12" r="4"/>' ?></svg>
          <span><?= e($cat['name']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <a class="btn-primary nav-post" href="<?= url('pages/post.php') ?>" data-open-post data-testid="nav-post-ad">
        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        <span><?= e(t('nav.post_ad')) ?></span>
      </a>
    </div>
  </nav>
</header>

<?php if (!$user) { include __DIR__ . '/auth-modal.php'; } ?>
<?php include __DIR__ . '/post-modal.php'; ?>
