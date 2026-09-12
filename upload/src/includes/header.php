<?php
/**
 * includes/header.php - İlan Ver Modal (Büyük, Şehirli, Zengin RDW)
 */
if (!defined('BASE_URL')) { require_once __DIR__ . '/../config.php'; }
if (!function_exists('getDB')) { require_once __DIR__ . '/../functions.php'; }
$lang = currentLang();
$theme = $_SESSION['theme'] ?? 'light';
$isVehicleSearchPage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'search-vehicles.php';
$isStableListingPage = in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['search-vehicles.php', 'property.php'], true);

$navCategories = [];
try {
    $pdo = getDB();
    $pdo->exec("SET NAMES utf8mb4");
    $stmt = $pdo->prepare("SELECT c.id, c.module, ct.name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? WHERE c.is_active = 1 AND c.parent_id IS NULL ORDER BY c.sort_order ASC");
    $stmt->execute([$lang]);
    $navCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Ülkeler DB'den (UTF-8 düzgün)
$navCountries = [];
try {
    $pdo->exec("SET NAMES utf8mb4");
    $nameCol = 'name_tr';
    if ($lang === 'nl') $nameCol = 'name_nl';
    elseif ($lang === 'en') $nameCol = 'name_en';
    $navCountries = $pdo->query("SELECT id, code, {$nameCol} as name FROM countries WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Şehirler JSON (ülkelere göre gruplu)
$citiesJson = '{}';
try {
    $stmt = $pdo->query("SELECT c.name, c.country_id FROM cities c WHERE c.is_active = 1 ORDER BY c.name ASC");
    $allCities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grouped = [];
    foreach ($allCities as $city) {
        $grouped[(int)$city['country_id']][] = $city['name'];
    }
    $citiesJson = json_encode($grouped, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="<?= $theme ?>"<?= $isVehicleSearchPage ? ' class="vehicle-search-page stable-listing-page"' : ($isStableListingPage ? ' class="stable-listing-page"' : '') ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'AvrupaPazari') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=2">
    <style>
        .search-segment{position:relative;user-select:none}
        .search-dropdown{display:none;position:absolute;top:calc(100% + 16px);left:0;min-width:240px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 12px 40px var(--card-shadow-hover);z-index:999;padding:8px;max-height:320px;overflow-y:auto}
        .search-dropdown.show{display:block}
        .sdd-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-size:.84rem;font-weight:500;color:var(--text);cursor:pointer;transition:background .15s}
        .sdd-item:hover{background:var(--surface-2)}
        .sdd-item.sel{background:var(--accent-light);color:var(--accent);font-weight:700}
        .sdd-item .icon{color:var(--text-3)}.sdd-item.sel .icon{color:var(--accent)}
        .search-input-wrap{flex:1;display:flex;align-items:center}
        .search-input-wrap input{width:100%;border:none;background:transparent;font-family:inherit;font-size:.88rem;color:var(--text);padding:8px 14px;outline:none;height:44px;border-radius:100px}
        .search-input-wrap input::placeholder{color:var(--text-3)}
        .s-overlay{display:none;position:fixed;inset:0;z-index:998}.s-overlay.show{display:block}
        .ilan-ver-btn{display:flex;align-items:center;gap:8px;padding:12px 28px;background:linear-gradient(135deg,#1d7a4e,#2da065);color:#fff;font-family:inherit;font-size:.88rem;font-weight:700;border:none;border-radius:100px;cursor:pointer;box-shadow:0 4px 16px rgba(29,122,78,.3);transition:all .2s cubic-bezier(.16,1,.3,1);white-space:nowrap;text-decoration:none}
        .ilan-ver-btn:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(29,122,78,.4);background:linear-gradient(135deg,#156b42,#1d7a4e)}
        .ilan-ver-btn:active{transform:translateY(0)}
        .ilan-ver-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
        .search-nav{position:relative;display:flex;align-items:center;gap:16px;padding:16px 24px 16px 300px}
        .search-capsule{flex:1;max-width:680px}
        .iv-overlay{display:none;position:fixed;inset:0;background:rgba(10,20,15,.65);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;padding:20px}
        .iv-overlay.show{display:flex}
        .iv-modal{background:var(--surface,#fff);border-radius:24px;width:100%;max-width:720px;max-height:90vh;overflow-y:auto;box-shadow:0 32px 100px rgba(0,0,0,.25);animation:ivUp .3s cubic-bezier(.16,1,.3,1)}
        @keyframes ivUp{from{opacity:0;transform:translateY(24px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
        .iv-header{padding:28px 32px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border,#e8f0ec)}
        .iv-title{font-size:1.3rem;font-weight:800;color:var(--text,#1a3a2a)}
        .iv-close{width:40px;height:40px;border-radius:50%;border:none;background:var(--surface-2,#f0f4f2);color:var(--text-2,#5a7a68);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}
        .iv-close:hover{background:var(--border,#e0e8e4)}
        .iv-close svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2}
        .iv-body{padding:28px 32px 32px}
        .iv-steps{display:flex;gap:8px;margin-bottom:28px}
        .iv-step-dot{flex:1;height:5px;border-radius:3px;background:var(--border,#e0e8e4);transition:background .3s}
        .iv-step-dot.active{background:#1d7a4e}
        .iv-back{display:none;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:none;background:var(--surface-2,#f0f4f2);color:var(--text-2,#4a6b58);font-size:.82rem;font-weight:600;cursor:pointer;margin-bottom:18px;transition:background .15s}
        .iv-back:hover{background:var(--border,#e0e8e4)}
        .iv-back.show{display:inline-flex}
        .iv-back svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2}
        .iv-cat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
        .iv-cat-item{display:flex;align-items:center;gap:14px;padding:20px;border-radius:14px;border:2px solid var(--border,#e8f0ec);background:var(--surface,#fff);cursor:pointer;transition:all .15s cubic-bezier(.16,1,.3,1)}
        .iv-cat-item:hover{border-color:#1d7a4e;background:#f0faf4;transform:translateY(-1px)}
        .iv-cat-item.selected{border-color:#1d7a4e;background:#e8f5ee}
        .iv-cat-icon{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#e8f5ee,#d4edde);display:flex;align-items:center;justify-content:center;color:#1d7a4e;flex-shrink:0}
        .iv-cat-item.selected .iv-cat-icon{background:linear-gradient(135deg,#1d7a4e,#2da065);color:#fff}
        .iv-cat-name{font-size:.92rem;font-weight:700;color:var(--text,#1a3a2a)}
        .iv-select{width:100%;height:52px;border:2px solid var(--border,#e0e8e4);border-radius:12px;padding:0 16px;font-size:.9rem;font-weight:600;color:var(--text,#1a3a2a);background:var(--surface,#fff);outline:none;cursor:pointer;margin-bottom:16px;transition:border-color .15s;appearance:auto}
        .iv-select:focus{border-color:#1d7a4e}
        .iv-label{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3,#7a9b88);margin-bottom:8px;display:block}
        .iv-plaka-section{display:none;margin-top:20px;padding:24px;border-radius:16px;background:var(--surface-2,#f6faf8);border:1px solid var(--border,#e8f0ec)}
        .iv-plaka-section.show{display:block;animation:ivUp .3s cubic-bezier(.16,1,.3,1)}
        .iv-plaka-title{font-size:.95rem;font-weight:700;color:var(--text,#1a3a2a);margin-bottom:6px}
        .iv-plaka-desc{font-size:.8rem;color:var(--text-3,#7a9b88);margin-bottom:16px}
        .iv-plaka-box{display:flex;align-items:stretch;border-radius:12px;overflow:hidden;border:3px solid #f5c518;background:#f5c518;box-shadow:0 4px 16px rgba(245,197,24,.2)}
        .iv-plaka-flag{width:48px;background:#003da5;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;gap:2px;padding:8px 0}
        .iv-plaka-flag-stars{font-size:.55rem;color:#f5c518}
        .iv-plaka-input{flex:1;border:none;outline:none;font-size:1.8rem;font-weight:900;font-family:'Space Grotesk',monospace;text-transform:uppercase;padding:14px 20px;letter-spacing:3px;color:#1a1a1a;background:#f5c518}
        .iv-plaka-input::placeholder{color:rgba(0,0,0,.25);font-weight:500;letter-spacing:0;font-size:.9rem}
        .iv-plaka-actions{display:flex;gap:10px;margin-top:14px}
        .iv-plaka-btn{flex:1;padding:14px 24px;background:#1d7a4e;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;transition:background .15s}
        .iv-plaka-btn:hover{background:#156b42}
        .iv-plaka-btn:disabled{background:#b8d4c4;cursor:not-allowed}
        .iv-plaka-skip{flex:0;padding:14px 20px;background:var(--surface,#fff);color:var(--text-2,#4a6b58);border:2px solid var(--border,#e0e8e4);border-radius:10px;font-weight:600;font-size:.84rem;cursor:pointer;transition:border-color .15s;white-space:nowrap}
        .iv-plaka-skip:hover{border-color:#1d7a4e}
        .iv-loading{display:none;text-align:center;padding:16px;color:var(--text-3);font-size:.84rem}
        .iv-loading.show{display:block}
        .iv-spinner{display:inline-block;width:18px;height:18px;border:2px solid #e0e8e4;border-top-color:#1d7a4e;border-radius:50%;animation:spin .6s linear infinite;margin-right:8px;vertical-align:middle}
        @keyframes spin{to{transform:rotate(360deg)}}
        .iv-plaka-error{margin-top:12px;padding:12px 16px;border-radius:10px;background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;font-size:.82rem;display:none}
        .iv-plaka-error.show{display:block}
        .iv-plaka-result{margin-top:20px;display:none}
        .iv-plaka-result.show{display:block;animation:ivUp .25s cubic-bezier(.16,1,.3,1)}
        .iv-car-card{padding:24px;border-radius:14px;background:var(--surface,#fff);border:2px solid #1d7a4e}
        .iv-car-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border,#e8f0ec)}
        .iv-car-title{font-size:1.15rem;font-weight:800;color:var(--text,#1a3a2a)}
        .iv-car-badge{padding:4px 10px;border-radius:6px;background:#e8f5ee;color:#1d7a4e;font-size:.7rem;font-weight:700;text-transform:uppercase}
        .iv-car-specs{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        .iv-car-spec{padding:12px;border-radius:10px;background:var(--surface-2,#f8fdfb);border:1px solid var(--border,#e8f0ec)}
        .iv-car-spec-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3,#7a9b88);margin-bottom:4px}
        .iv-car-spec-value{font-size:.9rem;font-weight:700;color:var(--text,#1a3a2a)}
        .iv-continue{width:100%;padding:16px;background:#1d7a4e;color:#fff;border:none;border-radius:12px;font-size:.92rem;font-weight:700;cursor:pointer;margin-top:24px;display:none;transition:all .15s}
        .iv-continue.show{display:block}
        .iv-continue:hover{background:#156b42;transform:translateY(-1px)}
        @media(max-width:768px){
            .search-nav{flex-direction:column;gap:12px}
            .ilan-ver-btn{width:100%;justify-content:center}
            .iv-cat-grid{grid-template-columns:1fr}
            .iv-car-specs{grid-template-columns:repeat(2,1fr)}
            .iv-modal{border-radius:16px}
            .iv-body{padding:20px}
        }
        @media(min-width:769px){
            .topbar-inner{display:grid;grid-template-columns:420px minmax(0,1fr);gap:16px;justify-content:initial}
            .topbar-left{width:420px;min-width:420px;flex:none}
            .topbar-right{width:100%;min-width:0}
            .search-nav{display:grid;grid-template-columns:minmax(0,680px) 180px;align-items:center;justify-content:center;gap:16px}
            .search-capsule{width:680px;max-width:calc(100vw - 420px);min-width:0;flex:none}
            .ilan-ver-btn{width:180px;min-width:180px;flex:none}
        }
        .topbar-lang,.topbar-btn,.topbar-cta,.theme-toggle{flex-shrink:0}
        .topbar-lang{width:34px;min-width:34px;overflow:hidden}
        .topbar-btn,.topbar-cta{overflow:hidden;text-overflow:ellipsis}
        .topbar-btn{width:96px;min-width:96px}
        .topbar-cta{width:120px;min-width:120px}
        /* Use one immediately available font in the header to prevent font-swap movement. */
        .topbar,.search-nav,.topbar *,.search-nav *{font-family:Arial,Helvetica,sans-serif!important}
        .map-search-link{height:44px;display:inline-flex;position:relative;z-index:20;align-items:center;justify-content:center;gap:7px;padding:0 18px;border:1px solid #dce8e2;border-radius:22px;background:#fff;color:#1d7a4e;font-size:.84rem;font-weight:700;text-decoration:none;white-space:nowrap;flex:0 0 auto;cursor:pointer;pointer-events:auto}
        .map-search-link:hover{background:#f0fdf4;border-color:#bfe5c8}
        .map-search-link svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
        @media(max-width:768px){.map-search-link{display:none}}
        /* Lock the header anchors so translated labels cannot move the layout. */
        @media(min-width:769px){
            .topbar-inner{display:block;position:relative;height:52px}
            .topbar-left{position:absolute;left:24px;top:0;width:420px;height:52px}
            .topbar-right{position:absolute;right:24px;top:9px;width:390px;min-width:390px;height:34px}
            .topbar-right .topbar-divider{flex:0 0 1px}
            .search-nav{display:flex;justify-content:center;align-items:center;gap:16px;height:98px;min-height:98px;padding:16px 24px}
            .search-capsule{flex:0 0 680px;width:680px;max-width:680px;height:58px;min-height:58px}
            .map-search-link{flex:0 0 210px;width:210px}
            .ilan-ver-btn{flex:0 0 180px;width:180px;min-width:180px;height:52px}
        }
    
        html { scrollbar-gutter: stable; }
        body { overflow-y: scroll; }
        .vehicle-search-page,
        .vehicle-search-page *,
        .vehicle-search-page *::before,
        .vehicle-search-page *::after {
            animation: none !important;
            transition: none !important;
        }
        .vehicle-search-page a:hover,
        .vehicle-search-page button:hover {
            transform: none !important;
        }
        html.vehicle-search-page {
            visibility: hidden;
        }
        .stable-listing-page,
        .stable-listing-page *,
        .stable-listing-page *::before,
        .stable-listing-page *::after {
            animation: none !important;
            transition: none !important;
        }
        .stable-listing-page a:hover,
        .stable-listing-page button:hover {
            transform: none !important;
        }

    </style>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navigation.css?v=5">
</head>
<body>

<header class="topbar redesigned-header">
    <div class="reference-header">
        <div class="reference-header__left">
            <a class="reference-home" href="<?= BASE_URL ?>/" aria-label="<?= e(t('nav.home')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h14v-9.5"/><path d="M9 20v-5h6v5"/></svg>
            </a>
            <a class="reference-map" href="<?= BASE_URL ?>/pages/map.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <?= e(t('nav.map_search')) ?>
            </a>
        </div>
        <a class="reference-logo" href="<?= BASE_URL ?>/">Avrupa<em>pazari</em></a>
        <div class="reference-header__right">
            <button class="reference-theme" id="themeBtn" type="button" aria-label="Tema değiştir">◐</button>
            <div class="reference-languages">
                <a class="<?= currentLang()==='tr'?'active':'' ?>" href="<?= htmlspecialchars(langUrl($_SERVER['REQUEST_URI'], 'tr')) ?>">TR</a>
                <a class="<?= currentLang()==='nl'?'active':'' ?>" href="<?= htmlspecialchars(langUrl($_SERVER['REQUEST_URI'], 'nl')) ?>">NL</a>
                <a class="<?= currentLang()==='en'?'active':'' ?>" href="<?= htmlspecialchars(langUrl($_SERVER['REQUEST_URI'], 'en')) ?>">EN</a>
                <a class="<?= currentLang()==='de'?'active':'' ?>" href="<?= htmlspecialchars(langUrl($_SERVER['REQUEST_URI'], 'de')) ?>">DE</a>
            </div>
            <a class="reference-register" href="<?= BASE_URL ?>/kayit.php"><?= t('nav.register') ?></a>
            <a class="reference-login" href="<?= BASE_URL ?>/giris.php"><?= t('nav.login') ?></a>
        </div>
    </div>
</header>

<nav class="category-nav redesigned-category-nav" aria-label="Kategori menüsü">
    <div class="reference-category-nav">
        <a class="reference-category reference-category--active" href="<?= BASE_URL ?>/pages/kategori.php"><?= e(t('common.all')) ?></a>
        <?php foreach($navCategories as $cat):
            $catUrl = match ($cat['module'] ?? '') {
                'emlak' => BASE_URL . '/pages/property.php',
                'arac' => BASE_URL . '/pages/search-vehicles.php',
                'ikinci_el' => BASE_URL . '/pages/second-hand.php',
                'esnaf' => BASE_URL . '/pages/businesses.php',
                default => BASE_URL . '/pages/kategori.php?id=' . (int)$cat['id'],
            };
        ?>
            <a class="reference-category" href="<?= e($catUrl) ?>"><?= e($cat['name'] ?? $cat['module']) ?></a>
        <?php endforeach; ?>
        <a class="reference-category" href="<?= BASE_URL ?>/pages/map.php"><?= e(t('nav.map_search')) ?></a>
        <button class="reference-post" id="ilanVerBtn2" type="button">+ <?= t('nav.post_ad') ?></button>
    </div>
</nav>

<div class="legacy-search-compat" aria-hidden="true"><!-- SEARCH + İLAN VER -->
<div class="search-nav">

    <div class="s-overlay" id="sOverlay"></div>
    <form class="search-capsule" action="<?= BASE_URL . (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'map.php' ? '/pages/map.php' : '/search-vehicles.php') ?>" method="GET" id="headerSearchForm">
        <input type="hidden" name="category" id="hCat" value="">
        <input type="hidden" name="country" id="hCountry" value="">
        <div class="search-segment" id="segCat">
            <span class="search-segment-label"><?= t('header.category') ?></span>
            <span class="search-segment-value" id="valCat"><?= t('common.all') ?></span>
            <div class="search-dropdown" id="ddCat">
                <div class="sdd-item sel" data-val="" data-label="<?= t('common.all') ?>"><span class="icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span><?= t('common.all') ?></div>
                <?php foreach($navCategories as $cat): ?>
                <div class="sdd-item" data-val="<?= htmlspecialchars($cat['module']) ?>" data-label="<?= htmlspecialchars($cat['name']??$cat['module']) ?>"><span class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg></span><?= htmlspecialchars($cat['name']??$cat['module']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="search-segment" id="segLoc">
            <span class="search-segment-label"><?= t('header.location') ?></span>
            <span class="search-segment-value" id="valLoc"><?= t('header.all_europe') ?></span>
            <div class="search-dropdown" id="ddLoc">
                <div class="sdd-item sel" data-val="" data-label="<?= t('header.all_europe') ?>"><span class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span><?= t('header.all_europe') ?></div>
                <?php foreach($navCountries as $c): ?>
                <div class="sdd-item" data-val="<?= $c['id'] ?>" data-label="<?= htmlspecialchars($c['name']) ?>"><span class="icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span><?= htmlspecialchars($c['name']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="search-input-wrap"><input type="text" name="q" placeholder="<?= t('search.placeholder') ?>"></div>
        <button type="submit" class="search-go"><span class="icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span></button>
    </form>

    <a class="map-search-link" href="<?= BASE_URL ?>/pages/map.php">
        <svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <?= t('nav.map_search') ?>
    </a>

    <button class="ilan-ver-btn" id="legacyIlanVerBtn2" type="button">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <?= t('nav.post_ad') ?>
    </button>
</div>

</div>
<!-- İLAN VER MODAL -->
<div class="iv-overlay" id="ivOverlay">
    <div class="iv-modal">
        <div class="iv-header">
            <span class="iv-title"><?= t('nav.post_ad') ?></span>
            <button class="iv-close" id="ivClose"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="iv-body">
            <div class="iv-steps">
                <div class="iv-step-dot active" id="ivDot1"></div>
                <div class="iv-step-dot" id="ivDot2"></div>
                <div class="iv-step-dot" id="ivDot3"></div>
            </div>
            <button class="iv-back" id="ivBack"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg> <?= t('common.back') ?></button>

            <!-- STEP 1: Kategori -->
            <div id="ivStep1">
                <p style="font-size:.88rem;color:var(--text-2);margin-bottom:20px;font-weight:500"><?= t('modal.which_category') ?></p>
                <div class="iv-cat-grid" id="ivCatGrid">
                    <?php foreach($navCategories as $cat): ?>
                    <div class="iv-cat-item" data-module="<?= htmlspecialchars($cat['module']) ?>" data-id="<?= $cat['id'] ?>">
                        <div class="iv-cat-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg></div>
                        <div class="iv-cat-name"><?= htmlspecialchars($cat['name']??$cat['module']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 2: Ülke + Şehir + Plaka -->
            <div id="ivStep2" style="display:none">
                <label class="iv-label"><?= t('listing.country') ?></label>
                <select class="iv-select" id="ivCountrySelect">
                    <option value=""><?= t('post.select_country') ?>...</option>
                    <?php foreach($navCountries as $c): ?>
                    <option value="<?= htmlspecialchars($c['code'] ?? '') ?>" data-id="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="ivCityWrap" style="display:none">
                    <label class="iv-label"><?= t('listing.city') ?></label>
                    <select class="iv-select" id="ivCitySelect">
                        <option value=""><?= t('post.select_city') ?>...</option>
                    </select>
                </div>

                <!-- Plaka (araç + NL) -->
                <div class="iv-plaka-section" id="ivPlakaSection">
                    <div class="iv-plaka-title"><?= t('modal.plate_autofill') ?></div>
                    <div class="iv-plaka-desc"><?= t('modal.plate_desc') ?></div>
                    <div class="iv-plaka-box">
                        <div class="iv-plaka-flag">
                            <span class="iv-plaka-flag-stars">★ ★ ★</span>
                            <span>NL</span>
                        </div>
                        <input type="text" class="iv-plaka-input" id="ivPlakaInput" placeholder="XX-999-X" maxlength="9">
                    </div>
                    <div class="iv-plaka-actions">
                        <button type="button" class="iv-plaka-btn" id="ivPlakaBtn"><?= t('modal.plate_lookup') ?></button>
                        <button type="button" class="iv-plaka-skip" id="ivPlakaSkip"><?= t('modal.skip') ?></button>
                    </div>
                    <div class="iv-loading" id="ivPlakaLoading"><span class="iv-spinner"></span> <?= t('vehicle.lookup_loading') ?></div>
                    <div class="iv-plaka-error" id="ivPlakaError"><?= t('vehicle.lookup_not_found') ?></div>

                    <div class="iv-plaka-result" id="ivPlakaResult">
                        <div class="iv-car-card">
                            <div class="iv-car-header">
                                <div class="iv-car-title" id="ivCarTitle"></div>
                                <span class="iv-car-badge" id="ivCarBadge"><?= t('modal.rdw_verified') ?></span>
                            </div>
                            <div class="iv-car-specs">
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.brand') ?></div><div class="iv-car-spec-value" id="ivCarMerk"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.model') ?></div><div class="iv-car-spec-value" id="ivCarModel"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.build_year') ?></div><div class="iv-car-spec-value" id="ivCarYear"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.color') ?></div><div class="iv-car-spec-value" id="ivCarColor"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.fuel') ?></div><div class="iv-car-spec-value" id="ivCarFuel"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.power') ?></div><div class="iv-car-spec-value" id="ivCarPower"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.engine_cc') ?></div><div class="iv-car-spec-value" id="ivCarEngine"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.body_type') ?></div><div class="iv-car-spec-value" id="ivCarBody"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.weight') ?></div><div class="iv-car-spec-value" id="ivCarWeight"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.doors') ?></div><div class="iv-car-spec-value" id="ivCarDoors"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.seats') ?></div><div class="iv-car-spec-value" id="ivCarSeats"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.apk') ?></div><div class="iv-car-spec-value" id="ivCarAPK"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.catalog_price') ?></div><div class="iv-car-spec-value" id="ivCarPrice"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.tow_weight') ?></div><div class="iv-car-spec-value" id="ivCarTow"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.first_reg') ?></div><div class="iv-car-spec-value" id="ivCarFirstReg"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="iv-continue" id="ivContinueBtn"><?= t('common.next') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var CITIES_DATA = <?= $citiesJson ?>;
var LANG_SELECT_CITY = '<?= t('post.select_city') ?>...';

(function(){
    var overlay=document.getElementById('sOverlay'),openDD=null;
    function closeAll(){document.querySelectorAll('.search-dropdown').forEach(function(d){d.classList.remove('show')});overlay.classList.remove('show');openDD=null}
    document.getElementById('segCat').addEventListener('click',function(e){e.stopPropagation();var dd=document.getElementById('ddCat');if(openDD===dd){closeAll();return}closeAll();dd.classList.add('show');overlay.classList.add('show');openDD=dd});
    document.getElementById('segLoc').addEventListener('click',function(e){e.stopPropagation();var dd=document.getElementById('ddLoc');if(openDD===dd){closeAll();return}closeAll();dd.classList.add('show');overlay.classList.add('show');openDD=dd});
    overlay.addEventListener('click',closeAll);
    document.getElementById('ddCat').addEventListener('click',function(e){var item=e.target.closest('.sdd-item');if(!item)return;e.stopPropagation();var category=item.dataset.val||'';document.getElementById('hCat').value=category;document.getElementById('valCat').textContent=item.dataset.label;this.querySelectorAll('.sdd-item').forEach(function(i){i.classList.remove('sel')});item.classList.add('sel');closeAll();if(window.refreshMapListings)window.refreshMapListings(category,document.getElementById('hCountry').value)});
    document.getElementById('ddLoc').addEventListener('click',function(e){var item=e.target.closest('.sdd-item');if(!item)return;e.stopPropagation();document.getElementById('hCountry').value=item.dataset.val;document.getElementById('valLoc').textContent=item.dataset.label;this.querySelectorAll('.sdd-item').forEach(function(i){i.classList.remove('sel')});item.classList.add('sel');closeAll();if(window.refreshMapListings)window.refreshMapListings(document.getElementById('hCat').value,item.dataset.val)});

    var ivOverlay=document.getElementById('ivOverlay'),selectedCat=null,selectedCountry=null;
    document.getElementById('ilanVerBtn2').addEventListener('click',openIV);
    function openIV(){ivOverlay.classList.add('show');document.body.style.overflow='hidden';resetModal()}
    document.getElementById('ivClose').addEventListener('click',closeIV);
    ivOverlay.addEventListener('click',function(e){if(e.target===ivOverlay)closeIV()});
    function closeIV(){ivOverlay.classList.remove('show');document.body.style.overflow=''}

    function resetModal(){
        document.getElementById('ivStep1').style.display='block';
        document.getElementById('ivStep2').style.display='none';
        document.getElementById('ivDot1').classList.add('active');
        document.getElementById('ivDot2').classList.remove('active');
        document.getElementById('ivPlakaSection').classList.remove('show');
        document.getElementById('ivPlakaResult').classList.remove('show');
        document.getElementById('ivPlakaError').classList.remove('show');
        document.getElementById('ivContinueBtn').classList.remove('show');
        document.getElementById('ivCityWrap').style.display='none';
        document.getElementById('ivBack').classList.remove('show');
        document.querySelectorAll('.iv-cat-item').forEach(function(el){el.classList.remove('selected')});
        selectedCat=null;selectedCountry=null;
    }

    document.getElementById('ivBack').addEventListener('click',function(){
        document.getElementById('ivStep2').style.display='none';
        document.getElementById('ivStep1').style.display='block';
        document.getElementById('ivDot2').classList.remove('active');
        this.classList.remove('show');
        document.getElementById('ivPlakaSection').classList.remove('show');
        document.getElementById('ivPlakaResult').classList.remove('show');
        document.getElementById('ivContinueBtn').classList.remove('show');
    });

    document.getElementById('ivCatGrid').addEventListener('click',function(e){
        var item=e.target.closest('.iv-cat-item');if(!item)return;
        document.querySelectorAll('.iv-cat-item').forEach(function(el){el.classList.remove('selected')});
        item.classList.add('selected');
        selectedCat=item.dataset.module;
        setTimeout(function(){
            document.getElementById('ivStep1').style.display='none';
            document.getElementById('ivStep2').style.display='block';
            document.getElementById('ivDot2').classList.add('active');
            document.getElementById('ivBack').classList.add('show');
        },200);
    });

    document.getElementById('ivCountrySelect').addEventListener('change',function(){
        selectedCountry=this.value;
        var countryId=this.selectedOptions[0]?this.selectedOptions[0].dataset.id:'';
        var isArac=(selectedCat==='arac'||selectedCat==='vehicles'||selectedCat==='cars');
        var cityWrap=document.getElementById('ivCityWrap');
        var citySelect=document.getElementById('ivCitySelect');
        if(countryId&&CITIES_DATA[countryId]){
            citySelect.innerHTML='<option value="">'+LANG_SELECT_CITY+'</option>';
            CITIES_DATA[countryId].forEach(function(c){
                citySelect.innerHTML+='<option value="'+c+'">'+c+'</option>';
            });
            cityWrap.style.display='block';
        } else { cityWrap.style.display='none'; }
        if(isArac&&selectedCountry==='NL'){
            document.getElementById('ivPlakaSection').classList.add('show');
        } else {
            document.getElementById('ivPlakaSection').classList.remove('show');
        }
        if(selectedCountry) document.getElementById('ivContinueBtn').classList.add('show');
    });

    document.getElementById('ivPlakaSkip').addEventListener('click',function(){
        var countryOpt = document.getElementById('ivCountrySelect').selectedOptions[0];
        var countryId = countryOpt ? countryOpt.dataset.id : '';
        var city = document.getElementById('ivCitySelect').value;
        var vehicleData = { rdw_verified: false, vehicle_type: 'auto', brand: '', model: '', build_year: '', color: '', fuel_type: '', power_hp: '', doors: '', apk_tot: '', country_id: countryId, city_id: city, fuel_display: '' };
        localStorage.setItem('vehicleData', JSON.stringify(vehicleData));
        window.location.href = '<?= BASE_URL ?>/post-vehicle.php';
    });

    document.getElementById('ivContinueBtn').addEventListener('click',function(){
        var fuelMap = {'benzine':'benzine','diesel':'diesel','elektriciteit':'elektrisch','lpg':'lpg'};
        var rdwRaw = document.getElementById('ivPlakaResult').dataset.json;
        var rdw = rdwRaw ? JSON.parse(rdwRaw) : null;
        var countryOpt = document.getElementById('ivCountrySelect').selectedOptions[0];
        var countryId = countryOpt ? countryOpt.dataset.id : '';
        var city = document.getElementById('ivCitySelect').value;
        var vehicleData = {
            rdw_verified: !!rdw, vehicle_type: 'auto',
            brand: rdw ? rdw.merk : '', model: rdw ? rdw.model : '',
            build_year: rdw ? rdw.year : '', color: rdw ? rdw.kleur : '',
            fuel_type: rdw && rdw.brandstof ? (rdw.brandstof.toLowerCase().includes('hybride') ? 'hybride' : (fuelMap[rdw.brandstof.toLowerCase()] || rdw.brandstof)) : '',
            power_hp: rdw && rdw.power ? Math.round(rdw.power * 1.36) : '',
            doors: rdw ? rdw.doors : '',
            apk_tot: document.getElementById('ivCarAPK').textContent !== '-' ? document.getElementById('ivCarAPK').textContent : '',
            country_id: countryId, city_id: city, fuel_display: rdw ? rdw.brandstof : ''
        };
        localStorage.setItem('vehicleData', JSON.stringify(vehicleData));
        window.location.href = '<?= BASE_URL ?>/post-vehicle.php';
    });

    document.getElementById('headerSearchForm').addEventListener('submit',function(e){
        var query=this.querySelector('input[name="q"]').value.trim();
        if(!query){e.preventDefault();return;}
        var chosenCategory=document.getElementById('hCat').value;
        if(chosenCategory === 'emlak'){
            e.preventDefault();
            this.action = '<?= BASE_URL ?>/pages/property.php';
            this.submit();
        }
    });

    document.getElementById('ivPlakaBtn').addEventListener('click',queryRDW);
    document.getElementById('ivPlakaInput').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();queryRDW()}});

    function queryRDW(){
        var plate=document.getElementById('ivPlakaInput').value.replace(/[\s\-]/g,'').toUpperCase();
        if(plate.length<5)return;
        var loading=document.getElementById('ivPlakaLoading'),result=document.getElementById('ivPlakaResult'),error=document.getElementById('ivPlakaError'),btn=document.getElementById('ivPlakaBtn');
        loading.classList.add('show');result.classList.remove('show');error.classList.remove('show');btn.disabled=true;
        Promise.all([
            fetch('https://opendata.rdw.nl/resource/m9d7-ebf2.json?kenteken='+plate).then(function(r){return r.json()}),
            fetch('https://opendata.rdw.nl/resource/8ys7-d773.json?kenteken='+plate).then(function(r){return r.json()})
        ]).then(function(results){
            loading.classList.remove('show');btn.disabled=false;
            var data=results[0],fuel=results[1];
            if(!data||data.length===0){error.classList.add('show');return}
            var car=data[0],f=fuel&&fuel.length>0?fuel[0]:{};
            var merk=car.merk||'',model=car.handelsbenaming||'',kleur=car.eerste_kleur||'';
            var datum=car.datum_eerste_toelating||'',year=datum?datum.substring(0,4):'',month=datum?datum.substring(4,6):'';
            var massa=car.massa_ledig_voertuig||'',body=car.inrichting||'',doors=car.aantal_deuren||'',seats=car.aantal_zitplaatsen||'';
            var apk=car.vervaldatum_apk||'',price=car.catalogusprijs||'',tow=car.maximum_massa_trekken_geremd||'';
            var brandstofList=fuel.map(function(x){return x.brandstof_omschrijving}).filter(Boolean);
            var brandstof=brandstofList.length>1?'Hybride ('+brandstofList.join(' / ')+')':brandstofList[0]||'-';
            var power=Math.max.apply(null,fuel.map(function(x){return parseFloat(x.nettomaximumvermogen||x.netto_max_vermogen_elektrisch||0)}))||'';
            var cc=car.cilinderinhoud||'';
            document.getElementById('ivCarTitle').textContent=merk+' '+model;
            document.getElementById('ivCarMerk').textContent=merk;
            document.getElementById('ivCarModel').textContent=model;
            document.getElementById('ivCarYear').textContent=year;
            document.getElementById('ivCarColor').textContent=kleur;
            document.getElementById('ivCarFuel').textContent=brandstof;
            document.getElementById('ivCarPower').textContent=power?(Math.round(power*1.36)+' HP / '+power+' kW'):'-';
            document.getElementById('ivCarEngine').textContent=cc?(cc+' cc'):'-';
            document.getElementById('ivCarBody').textContent=body||'-';
            document.getElementById('ivCarWeight').textContent=massa?(massa+' kg'):'-';
            document.getElementById('ivCarDoors').textContent=doors||'-';
            document.getElementById('ivCarSeats').textContent=seats||'-';
            var apkFormatted=apk?(apk.substring(6,8)+'.'+apk.substring(4,6)+'.'+apk.substring(0,4)):'-';
            document.getElementById('ivCarAPK').textContent=apkFormatted;
            document.getElementById('ivCarPrice').textContent=price?('\u20ac '+Number(price).toLocaleString('nl-NL')):'-';
            document.getElementById('ivCarTow').textContent=tow?(tow+' kg'):'-';
            var firstReg=datum?(datum.substring(6,8)+'.'+month+'.'+year):'-';
            document.getElementById('ivCarFirstReg').textContent=firstReg;
            result.dataset.json=JSON.stringify({merk:merk,model:model,year:year,kleur:kleur,brandstof:brandstof,power:power,doors:doors});
            result.classList.add('show');
        }).catch(function(){loading.classList.remove('show');btn.disabled=false;error.classList.add('show');});
    }
})();
</script>
