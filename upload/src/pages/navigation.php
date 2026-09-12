<?php
$navLang = function_exists('currentLang') ? currentLang() : 'en';
$navCategories = [];
try {
    $q = getDB()->prepare("SELECT c.module, COALESCE(ct.name, c.slug) AS name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order, c.id");
    $q->execute([$navLang]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) $navCategories[(string)$row['module']] = (string)$row['name'];
} catch (Throwable $e) {}
$navText = static function (string $key, string $fallback): string {
    $value = function_exists('t') ? t($key) : $fallback;
    return $value === $key ? $fallback : $value;
};
$navUrl = static fn(string $path): string => BASE_URL . '/' . ltrim($path, '/');
?>
<link rel="stylesheet" href="<?= e($navUrl('assets/css/navi.css')) ?>?v=20260907-3">
<header class="navi" data-navi>
  <div class="navi__top">
    <div class="navi__left">
      <a class="navi__logo-mark" href="<?= e($navUrl('/')) ?>" aria-label="Avrupapazari home">
        <span>AP</span>
      </a>
    </div>
    <a class="navi__brand" href="<?= e($navUrl('/')) ?>">Avrupa<strong>pazari</strong></a>
    <div class="navi__right">
      <button class="navi__theme" type="button" data-navi-theme aria-label="Toggle theme"><svg class="navi__moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg><svg class="navi__sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2"/></svg></button>
      <div class="navi__languages"><?php foreach (['en','tr','nl','de'] as $locale): ?><a class="<?= $navLang === $locale ? 'is-active' : '' ?>" href="<?= e(function_exists('langUrl') ? langUrl($_SERVER['REQUEST_URI'] ?? '/', $locale) : ('?lang=' . $locale)) ?>"><?= strtoupper($locale) ?></a><?php endforeach; ?></div>
      <a class="navi__register" href="<?= e($navUrl(empty($_SESSION['user_id']) ? 'kayit.php' : 'profil.php')) ?>"><?= e(empty($_SESSION['user_id']) ? $navText('nav.register', 'Register') : $navText('nav.my_account', 'My account')) ?></a>
    </div>
  </div>
  <div class="navi__bottom">
    <nav class="navi__links" aria-label="Primary navigation">
      <a class="is-current" href="<?= e($navUrl('/')) ?>"><?= e($navText('nav.all', 'All')) ?></a>
      <a href="<?= e($navUrl('pages/businesses.php')) ?>"><?= e($navCategories['esnaf'] ?? $navText('nav.business_dir', 'Professionals')) ?></a>
      <a href="<?= e($navUrl('pages/second-hand.php')) ?>"><?= e($navCategories['ikinci_el'] ?? $navText('nav.second_hand', 'Second hand')) ?></a>
      <a href="<?= e($navUrl('pages/property.php')) ?>"><?= e($navCategories['emlak'] ?? $navText('nav.real_estate', 'Real estate')) ?></a>
      <a href="<?= e($navUrl('pages/search-vehicles.php')) ?>"><?= e($navCategories['arac'] ?? $navText('nav.vehicles', 'Vehicles')) ?></a>
      <a class="navi__map-link" href="<?= e($navUrl('pages/map.php')) ?>"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><?= e($navText('nav.map_search', 'Search on map')) ?></a>
    </nav>
    <a class="navi__post" href="<?= e($navUrl('ilan-ver.php')) ?>"><span>+</span><?= e($navText('nav.post_ad', 'Post an ad')) ?></a>
  </div>
</header>
<script>(function(){var r=document.documentElement,b=document.querySelector('[data-navi-theme]'),s=localStorage.getItem('site-theme');if(s==='light'||s==='dark')r.dataset.theme=s;if(b)b.addEventListener('click',function(){var n=r.dataset.theme==='dark'?'light':'dark';r.dataset.theme=n;localStorage.setItem('site-theme',n);});})();</script>
