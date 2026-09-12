<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDB();
$pdo->exec('SET NAMES utf8mb4');
$lang = currentLang();
$userId = $_SESSION['user_id'] ?? null;

// Translation dictionary loader
$ui = [];
try {
    $q = $pdo->prepare("SELECT key_group, key_name, value FROM translations WHERE lang = ? AND key_group IN ('property','emlak','second_hand','common','search','listing','map')");
    $q->execute([$lang]);
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $ui[$r['key_group'] . '.' . $r['key_name']] = (string)$r['value'];
    }
} catch (Throwable $e) {}

$label = static function (string $key, string $fallback = '') use ($ui): string {
    $aliases = [
        'filters' => 'search.filters',
        'location' => 'listing.location',
        'country' => 'listing.country',
        'city' => 'listing.city',
        'price' => 'listing.price',
        'min_price' => 'search.price_min',
        'max_price' => 'search.price_max',
        'sort' => 'search.sort',
        'common_sort_newest' => 'search.sort_newest',
        'common_sort_standard' => 'search.sort_standard',
        'common_sort_date_newest' => 'search.sort_date_newest',
        'common_sort_date_oldest' => 'search.sort_date_oldest',
        'common_sort_oldest' => 'search.sort_oldest',
        'common_sort_price_low' => 'search.sort_price_low',
        'common_sort_price_high' => 'search.sort_price_high',
        'common_search' => 'common.search',
        'clear_filters' => 'search.clear_filters',
        'condition' => 'second_hand.condition',
        'delivery' => 'second_hand.delivery',
        'all_categories' => 'common.all',
    ];
    $alias = $aliases[$key] ?? null;
    return (string)($ui['second_hand.' . $key] ?? $ui['common.' . $key] ?? ($alias && isset($ui[$alias]) ? $ui[$alias] : $fallback));
};

// Filter parameters
$categoryId = (int)($_GET['category_id'] ?? 0);
$countryId = (int)($_GET['country_id'] ?? 0);
$cityId = (int)($_GET['city_id'] ?? 0);
$minPrice = max(0, (int)($_GET['price_min'] ?? 0));
$maxPrice = max(0, (int)($_GET['price_max'] ?? 0));
$propertyType = (string)($_GET['property_type'] ?? '');
$transactionType = (string)($_GET['transaction_type'] ?? '');
$searchQuery = trim((string)($_GET['q'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'newest');

// Build SQL query
$where = ["l.module = 'emlak'", "l.status IN ('active','reserved')"];
$params = [];

if ($categoryId > 0 && $categoryId !== 3) {
    $where[] = "(l.category_id = :cat_id OR l.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id_sub OR parent_id IN (SELECT id FROM categories WHERE parent_id = :cat_id_sub2)))";
    $params['cat_id'] = $categoryId;
    $params['cat_id_sub'] = $categoryId;
    $params['cat_id_sub2'] = $categoryId;
}

if ($countryId > 0) {
    $where[] = "l.country_id = :country_id";
    $params['country_id'] = $countryId;
}

if ($cityId > 0) {
    $where[] = "l.city_id = :city_id";
    $params['city_id'] = $cityId;
}

if ($minPrice > 0) {
    $where[] = "l.price >= :min_price";
    $params['min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "l.price <= :max_price";
    $params['max_price'] = $maxPrice;
}

if ($searchQuery !== '') {
    $where[] = "(l.title LIKE :q OR l.description LIKE :q)";
    $params['q'] = '%' . $searchQuery . '%';
}

if (in_array($propertyType, ['appartement', 'huis', 'kamer', 'studio', 'bedrijf', 'grond'], true)) {
    $where[] = "EXISTS (SELECT 1 FROM listing_emlak pe WHERE pe.listing_id = l.id AND pe.property_type = :property_type)";
    $params['property_type'] = $propertyType;
}

if (in_array($transactionType, ['huur', 'koop'], true)) {
    $where[] = "EXISTS (SELECT 1 FROM listing_emlak pt WHERE pt.listing_id = l.id AND pt.transaction_type = :transaction_type)";
    $params['transaction_type'] = $transactionType;
}

$ws = implode(' AND ', $where);

// Sorting logic
$order = match ($sort) {
    'price_asc' => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'oldest' => 'l.created_at ASC',
    default => 'l.created_at DESC'
};

$countryCol = $lang === 'nl' ? 'name_nl' : ($lang === 'en' ? 'name_en' : 'name_tr');

// Fetch master data from DB
$countries = [];
$cities = [];
$categoriesTree = [];
$selectedCategory = null;
$items = [];
$total = 0;

try {
    $countries = $pdo->query("SELECT id, {$countryCol} AS name, code FROM countries WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
    $cities = $pdo->query("SELECT id, name, country_id FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch categories with translations
    $catStmt = $pdo->prepare("SELECT c.id, c.parent_id, c.slug, c.icon, c.sort_order,
        COALESCE(ct.name, tr.name, c.slug) AS name,
        COALESCE(ct.description, tr.description, '') AS description,
        (SELECT COUNT(*) FROM listings l WHERE l.module = 'emlak' AND l.status IN ('active','reserved') AND (l.category_id = c.id OR l.category_id IN (SELECT id FROM categories WHERE parent_id = c.id))) AS listing_count
        FROM categories c
        LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = :lang
        LEFT JOIN category_translations tr ON tr.category_id = c.id AND tr.lang = 'tr'
        WHERE c.module = 'emlak' AND c.is_active = 1
        ORDER BY c.parent_id IS NOT NULL ASC, c.sort_order ASC, c.id ASC");
    $catStmt->execute(['lang' => $lang]);
    $allCats = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $parents = [];
    $children = [];
    foreach ($allCats as $cat) {
        if (empty($cat['parent_id']) || (int)$cat['parent_id'] === 3) {
            // Main category under second hand
            if ((int)$cat['id'] !== 3) {
                $parents[(int)$cat['id']] = $cat;
            }
        } else {
            $children[(int)$cat['parent_id']][] = $cat;
        }
        if ($categoryId === (int)$cat['id']) {
            $selectedCategory = $cat;
        }
    }
    
    foreach ($parents as $pid => $pCat) {
        $pCat['children'] = $children[$pid] ?? [];
        $categoriesTree[] = $pCat;
    }
    
    // Total count query
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM listings l WHERE {$ws}");
    $cStmt->execute($params);
    $total = (int)$cStmt->fetchColumn();
    
    // Listings query
    $lStmt = $pdo->prepare("SELECT l.*, ci.name AS city_name, co.{$countryCol} AS country_name,
        COALESCE(ct.name, tr.name, '') AS category_name,
        (SELECT filename FROM listing_images WHERE listing_id = l.id ORDER BY is_cover DESC, sort_order ASC LIMIT 1) AS gallery_image
        FROM listings l
        LEFT JOIN cities ci ON ci.id = l.city_id
        LEFT JOIN countries co ON co.id = l.country_id
        LEFT JOIN categories c ON c.id = l.category_id
        LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = :lang
        LEFT JOIN category_translations tr ON tr.category_id = c.id AND tr.lang = 'tr'
        WHERE {$ws}
        ORDER BY l.is_featured DESC, l.is_premium DESC, {$order}
        LIMIT 60");
    
    // Keep all bindings named. Mixed positional/named placeholders made PDO fail and the catch hid the error.
    $lStmt->execute(array_merge(['lang' => $lang], $params));
    $items = $lStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Throwable $e) {}

function getPropertyImage(array $item): string {
    $imgCandidates = array_filter([
        $item['image'] ?? null,
        $item['gallery_image'] ?? null,
        'listing_' . (int)$item['id'] . '_1.jpg',
        'img_' . (int)$item['id'] . '_1.jpg', 'listings/daire-amsterdam.jpg'
    ]);
    
    $searchDirs = [
        __DIR__ . '/../uploads/listings/' . (int)$item['id'] . '/',
        __DIR__ . '/../uploads/listings/',
        __DIR__ . '/../uploads/'
    ];
    
    foreach ($imgCandidates as $cand) {
        $candClean = ltrim(str_replace('listings/', '', $cand), '/');
        foreach ($searchDirs as $dir) {
            if (is_file($dir . $candClean)) {
                $rel = str_replace(__DIR__ . '/../uploads/', '', $dir . $candClean);
                return BASE_URL . '/uploads/' . str_replace('%2F', '/', rawurlencode($rel));
            }
            if (is_file($dir . $cand)) {
                $rel = str_replace(__DIR__ . '/../uploads/', '', $dir . $cand);
                return BASE_URL . '/uploads/' . str_replace('%2F', '/', rawurlencode($rel));
            }
        }
    }
    return BASE_URL . '/uploads/listings/property-placeholder.png';
}

$pageTitle = ($selectedCategory ? $selectedCategory['name'] . ' - ' : '') . (t('property.title') ?: (t('nav.property') ?: 'Emlak İlanları'));
include __DIR__ . '/../includes/header.php';
?>
<script>
(function () {
    try {
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        var y = sessionStorage.getItem('property_scroll_y');
        sessionStorage.removeItem('property_scroll_y');
        window.__propertyScrollY = y === null ? null : (parseInt(y, 10) || 0);
    } catch (e) {}
})();
</script>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/property.css?v=5">

<main class="sh-page">
    <!-- Breadcrumb & Top Bar -->
    <div class="sh-container">
        <nav class="sh-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(BASE_URL) ?>/"><?= e(t('nav.home') ?: 'Ana Sayfa') ?></a>
            <span class="sep">/</span>
            <a href="<?= e(BASE_URL) ?>/pages/property.php" class="<?= !$categoryId ? 'current' : '' ?>"><?= e(t('nav.property') ?: (t('property.title') ?: 'Emlak')) ?></a>
            <?php if ($selectedCategory): ?>
                <span class="sep">/</span>
                <span class="current"><?= e($selectedCategory['name']) ?></span>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Hero Category Bar (Sahibinden / Marktplaats style) -->
    <section class="sh-hero-categories">
        <div class="sh-container">
            <div class="sh-hero-head">
                <div class="sh-hero-title">
                    <h1><?= e($selectedCategory ? $selectedCategory['name'] : (t('property.title') ?: (t('nav.property') ?: 'Emlak İlanları'))) ?></h1>
                    <p><?= e($selectedCategory && $selectedCategory['description'] ? $selectedCategory['description'] : (t('second_hand.subtitle') ?: 'Binlerce ikinci el ve sıfır ürünü keşfet, güvenle al ve sat.')) ?></p>
                </div>
                <div class="sh-hero-actions">
                    <a href="<?= e(BASE_URL) ?>/pages/map.php?module=emlak<?= $categoryId ? '&category_id=' . $categoryId : '' ?>" class="sh-map-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                        <span><?= e(t('nav.map_search') ?: 'Haritada Ara') ?></span>
                    </a>
                </div>
            </div>

            <!-- Category Carousel Cards -->
            <div class="sh-cats-slider">
                <a href="<?= e(BASE_URL) ?>/pages/property.php" class="sh-cat-card <?= !$categoryId ? 'active' : '' ?>">
                    <div class="sh-cat-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                    <span class="sh-cat-card-name"><?= e(t('common.all') ?: 'Tümü') ?></span>
                </a>
                <?php foreach ($categoriesTree as $pCat): ?>
                    <a href="<?= e(BASE_URL) ?>/pages/property.php?category_id=<?= (int)$pCat['id'] ?>" class="sh-cat-card <?= $categoryId === (int)$pCat['id'] ? 'active' : '' ?>">
                        <div class="sh-cat-card-icon"><i data-lucide="<?= e($pCat['icon'] ?: 'tag') ?>"></i></div>
                        <span class="sh-cat-card-name"><?= e($pCat['name']) ?></span>
                        <?php if (!empty($pCat['listing_count'])): ?>
                            <span class="sh-cat-card-count"><?= (int)$pCat['listing_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Main Content Layout -->
    <div class="sh-container">
        <div class="sh-layout">
            <!-- Sidebar Filters -->
            <aside class="sh-sidebar">
                <form class="sh-filter-form" method="get" action="<?= e(BASE_URL) ?>/pages/property.php" id="shFilterForm">
                    <?php if ($categoryId): ?>
                        <input type="hidden" name="category_id" value="<?= $categoryId ?>">
                    <?php endif; ?>

                    <div class="sh-filter-header">
                        <div class="sh-filter-header-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <strong><?= e($label('filters', 'Filtreler')) ?></strong>
                        </div>
                        <a href="<?= e(BASE_URL) ?>/pages/property.php" class="sh-clear-link"><?= e($label('clear_filters', 'Temizle')) ?></a>
                    </div>

                    <!-- Location: Country & City -->
                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e($label('location', 'Konum')) ?></label>
                        <div class="sh-select-wrapper">
                            <select name="country_id" id="shCountrySelect">
                                <option value=""><?= e($label('country', 'Tüm Ülkeler')) ?></option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sh-select-wrapper sh-city-select-wrapper">
                            <select name="city_id" id="shCitySelect" <?= !$countryId ? 'disabled' : '' ?>>
                                <option value=""><?= e($label('city', 'Tüm Şehirler')) ?></option>
                                <?php foreach ($cities as $ct): ?>
                                    <option value="<?= (int)$ct['id'] ?>" data-country-id="<?= (int)$ct['country_id'] ?>" <?= $cityId === (int)$ct['id'] ? 'selected' : '' ?>><?= e($ct['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="sh-filter-group">
                        <div class="sh-search-input-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e(t('search.placeholder') ?: 'Kelime veya ilan no...') ?>">
                        </div>
                    </div>

                    <!-- Categories Tree Accordion -->
                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e(t('map.categories') ?: 'Kategoriler') ?></label>
                        <div class="sh-cat-tree">
                            <?php foreach ($categoriesTree as $pCat): ?>
                                <div class="sh-cat-tree-item <?= ($categoryId === (int)$pCat['id'] || in_array($categoryId, array_column($pCat['children'], 'id'), true)) ? 'open active' : '' ?>">
                                    <div class="sh-cat-tree-header">
                                        <a href="<?= e(BASE_URL) ?>/pages/property.php?category_id=<?= (int)$pCat['id'] ?>" class="sh-cat-tree-link <?= $categoryId === (int)$pCat['id'] ? 'current' : '' ?>">
                                            <i data-lucide="<?= e($pCat['icon'] ?: 'tag') ?>"></i>
                                            <span><?= e($pCat['name']) ?></span>
                                        </a>
                                        <?php if (!empty($pCat['children'])): ?>
                                            <button type="button" class="sh-cat-tree-toggle" aria-label="Toggle subcategories">⌄</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($pCat['children'])): ?>
                                        <div class="sh-cat-sub-list">
                                            <?php foreach ($pCat['children'] as $sCat): ?>
                                                <a href="<?= e(BASE_URL) ?>/pages/property.php?category_id=<?= (int)$sCat['id'] ?>" class="sh-cat-sub-link <?= $categoryId === (int)$sCat['id'] ? 'current' : '' ?>">
                                                    <span><?= e($sCat['name']) ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <!-- Price Range -->
                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e($label('price', 'Fiyat')) ?> (€)</label>
                        <div class="sh-price-inputs">
                            <input type="number" name="price_min" min="0" value="<?= $minPrice ?: '' ?>" placeholder="<?= e($label('min_price', 'Min €')) ?>">
                            <span class="sep">-</span>
                            <input type="number" name="price_max" min="0" value="<?= $maxPrice ?: '' ?>" placeholder="<?= e($label('max_price', 'Max €')) ?>">
                        </div>
                    </div>

                    <!-- Condition (Durum) -->
                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e(t('listing.condition') ?: 'Ürün Durumu') ?></label>
                        <div class="sh-radio-options">
                            <label class="sh-radio-label">
                                <input type="radio" name="property_type" value="" <?= $propertyType === '' ? 'checked' : '' ?>>
                                <span><?= e(t('common.all') ?: 'Tümü') ?></span>
                            </label>
                            <label class="sh-radio-label">
                                <input type="radio" name="property_type" value="appartement" <?= $propertyType === 'appartement' ? 'checked' : '' ?>>
                                <span><?= e(t('second_hand.condition_new') ?: 'Sıfır / Yeni') ?></span>
                            </label>
                            <label class="sh-radio-label">
                                <input type="radio" name="property_type" value="huis" <?= $propertyType === 'huis' ? 'checked' : '' ?>>
                                <span><?= e(t('second_hand.condition_like_new') ?: 'Yeni Gibi') ?></span>
                            </label>
                            <label class="sh-radio-label">
                                <input type="radio" name="property_type" value="studio" <?= $propertyType === 'studio' ? 'checked' : '' ?>>
                                <span><?= e(t('second_hand.condition_used') ?: 'İkinci El / Kullanılmış') ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Delivery (Teslimat) -->
                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e(t('second_hand.delivery') ?: 'Teslimat') ?></label>
                        <div class="sh-radio-options">
                            <label class="sh-radio-label">
                                <input type="radio" name="transaction_type" value="" <?= $transactionType === '' ? 'checked' : '' ?>>
                                <span><?= e(t('common.all') ?: 'Tümü') ?></span>
                            </label>
                            <label class="sh-radio-label">
                                <input type="radio" name="transaction_type" value="huur" <?= $transactionType === 'huur' ? 'checked' : '' ?>>
                                <span><?= e(t('second_hand.delivery_pickup') ?: 'Elden Teslim / Gel Al') ?></span>
                            </label>
                            <label class="sh-radio-label">
                                <input type="radio" name="transaction_type" value="koop" <?= $transactionType === 'koop' ? 'checked' : '' ?>>
                                <span><?= e(t('second_hand.delivery_shipping') ?: 'Kargo / Gönderim') ?></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="sh-submit-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span><?= e($label('common_search', 'Filtrele')) ?></span>
                    </button>
                </form>
            </aside>

            <!-- Product Grid & Toolbar -->
            <section class="sh-main">
                <!-- Toolbar -->
                <div class="sh-toolbar">
                    <div class="sh-toolbar-info">
                        <h2><?= e($selectedCategory ? $selectedCategory['name'] : (t('nav.property') ?: 'Tüm İkinci El İlanları')) ?></h2>
                        <span class="sh-total-badge"><?= number_format($total, 0, ',', '.') ?> <?= e(t('listing.listings_label') ?: 'ilan') ?></span>
                    </div>

                    <div class="sh-toolbar-controls">
                        <!-- Sort select -->
                        <form method="get" action="<?= e(BASE_URL) ?>/pages/property.php" class="sh-sort-form">
                            <?php if ($categoryId): ?><input type="hidden" name="category_id" value="<?= $categoryId ?>"><?php endif; ?>
                            <?php if ($countryId): ?><input type="hidden" name="country_id" value="<?= $countryId ?>"><?php endif; ?>
                            <?php if ($cityId): ?><input type="hidden" name="city_id" value="<?= $cityId ?>"><?php endif; ?>
                            <?php if ($minPrice): ?><input type="hidden" name="price_min" value="<?= $minPrice ?>"><?php endif; ?>
                            <?php if ($maxPrice): ?><input type="hidden" name="price_max" value="<?= $maxPrice ?>"><?php endif; ?>
                            <?php if ($searchQuery): ?><input type="hidden" name="q" value="<?= e($searchQuery) ?>"><?php endif; ?>
                            <?php if ($propertyType): ?><input type="hidden" name="property_type" value="<?= e($propertyType) ?>"><?php endif; ?>
                            <?php if ($transactionType): ?><input type="hidden" name="transaction_type" value="<?= e($transactionType) ?>"><?php endif; ?>
                            
                            <select name="sort" aria-label="<?= e($label('sort', 'Sıralama')) ?>">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e($label('common_sort_newest', 'En Yeni')) ?></option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>><?= e($label('common_sort_oldest', 'En Eski')) ?></option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= e($label('common_sort_price_low', 'Fiyat: Düşükten Yükseğe')) ?></option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= e($label('common_sort_price_high', 'Fiyat: Yüksekten Düşüğe')) ?></option>
                            </select>
                        </form>

                        <!-- Grid/List toggle -->
                        <div class="sh-view-toggle">
                            <button type="button" class="active" id="shViewGrid" aria-label="Grid görünümü">▦</button>
                            <button type="button" id="shViewList" aria-label="Liste görünümü">☰</button>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (!empty($items)): ?>
                    <div class="sh-grid" id="shProductsGrid">
                        <?php foreach ($items as $item): ?>
                            <?php 
                            $imgUrl = getPropertyImage($item);
                            $priceFmt = number_format((float)$item['price'], 0, ',', '.');
                            $itemHref = BASE_URL . '/pages/listing.php?id=' . (int)$item['id'];
                            $loc = trim(($item['city_name'] ?? '') . ', ' . ($item['country_name'] ?? ''), ', ');
                            ?>
                            <article class="sh-card">
                                <a href="<?= e($itemHref) ?>" class="sh-card-media">
                                    <img src="<?= e($imgUrl) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                                    <?php if ($item['is_premium']): ?>
                                        <span class="sh-badge-premium"><?= e(t('common.premium') ?: 'PREMIUM') ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['is_featured']): ?>
                                        <span class="sh-badge-top">TOP</span>
                                    <?php endif; ?>
                                </a>
                                <div class="sh-card-body">
                                    <div class="sh-card-header">
                                        <span class="sh-card-price">€ <?= $priceFmt ?></span>
                                        <span class="sh-card-category"><?= e($item['category_name'] ?: 'Emlak') ?></span>
                                    </div>
                                    <h3 class="sh-card-title">
                                        <a href="<?= e($itemHref) ?>"><?= e($item['title']) ?></a>
                                    </h3>
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="sh-card-desc"><?= e(mb_substr($item['description'], 0, 90)) . (mb_strlen($item['description']) > 90 ? '...' : '') ?></p>
                                    <?php endif; ?>
                                    <div class="sh-card-footer">
                                        <div class="sh-card-loc">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <span><?= e($loc ?: 'Avrupa') ?></span>
                                        </div>
                                        <span class="sh-card-date"><?= date('d.m.Y', strtotime($item['created_at'])) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="sh-empty-state">
                        <div class="sh-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </div>
                        <h3><?= e(t('search.no_results') ?: 'Aradığınız kriterlere uygun ilan bulunamadı.') ?></h3>
                        <p><?= e(t('search.no_results_desc') ?: 'Filtreleri temizleyerek veya arama kelimenizi değiştirerek tekrar deneyebilirsiniz.') ?></p>
                        <a href="<?= e(BASE_URL) ?>/pages/property.php" class="sh-reset-btn"><?= e($label('clear_filters', 'Filtreleri Sıfırla')) ?></a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof window.__propertyScrollY === 'number') {
        window.scrollTo(0, window.__propertyScrollY);
    }
    // Lucide icon replacement
    const iconMap = {
        'smartphone': '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'tablet': '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'watch': '<circle cx="12" cy="12" r="7"/><polyline points="12 9 12 12 13.5 13.5"/><path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.83 1h4.35a2 2 0 0 1 2 1.82l.35 3.83"/>',
        'laptop': '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="20" x2="22" y2="20"/>',
        'monitor': '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
        'cpu': '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="15" x2="23" y2="15"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="15" x2="4" y2="15"/>',
        'sofa': '<path d="M5 11V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3"/><path d="M4 11h16v6H4z"/><path d="M6 17v2m12-2v2"/>',
        'shirt': '<path d="m8 5 4 2 4-2 4 4-3 3v8H7v-8L4 9z"/>',
        'dumbbell': '<path d="M6 4v4m12-4v4M3 7h18M6 16v4m12-4v4M3 17h18"/>',
        'tv': '<rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/>',
        'headphones': '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
        'camera': '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'refrigerator': '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="9" y1="6" x2="9" y2="8"/><line x1="9" y1="14" x2="9" y2="17"/>',
        'baby': '<circle cx="12" cy="12" r="10"/><path d="M8 15s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
        'gamepad-2': '<line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><line x1="15" y1="13" x2="15.01" y2="13"/><line x1="18" y1="11" x2="18.01" y2="11"/><rect x="2" y="6" width="20" height="12" rx="2"/>',
        'music': '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'book-open': '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'wrench': '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'bike': '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-3 11.5L9 9l4.5-3.5L16 9h4"/>',
        'tag': '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'
    };

    document.querySelectorAll('[data-lucide]').forEach(function (icon) {
        const name = icon.getAttribute('data-lucide');
        icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + (iconMap[name] || iconMap['tag']) + '</svg>';
        icon.removeAttribute('data-lucide');
    });

    // Country & City dependent dropdown
    const countrySel = document.getElementById('shCountrySelect');
    const citySel = document.getElementById('shCitySelect');
    if (countrySel && citySel) {
        countrySel.addEventListener('change', function () {
            const cid = this.value;
            citySel.disabled = !cid;
            Array.from(citySel.options).forEach(function (opt) {
                if (!opt.value) return;
                const match = opt.dataset.countryId === cid;
                opt.hidden = !match;
                opt.style.display = match ? '' : 'none';
            });
            if (cid && !citySel.querySelector('option:checked:not([hidden])')) {
                citySel.value = '';
            }
        });
        if (countrySel.value) {
            countrySel.dispatchEvent(new Event('change'));
        }
    }

    // Preserve the current position when a server-side category link reloads the page.
    document.querySelectorAll('.sh-cat-card, .sh-cat-tree-link, .sh-cat-sub-link').forEach(function (link) {
        link.addEventListener('click', function () {
            try { sessionStorage.setItem('property_scroll_y', String(window.scrollY)); } catch (e) {}
        });
    });

    // Category tree accordion toggle
    document.querySelectorAll('.sh-cat-tree-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const parent = this.closest('.sh-cat-tree-item');
            parent.classList.toggle('open');
        });
    });

    // Grid / List View Toggle
    const gridBtn = document.getElementById('shViewGrid');
    const listBtn = document.getElementById('shViewList');
    const gridContainer = document.getElementById('shProductsGrid');
    if (gridBtn && listBtn && gridContainer) {
        let savedView = 'grid';
        try { savedView = localStorage.getItem('property-view') || 'grid'; } catch (e) {}
        const setView = function (view) {
            const isList = view === 'list';
            gridContainer.classList.toggle('is-list', isList);
            gridBtn.classList.toggle('active', !isList);
            listBtn.classList.toggle('active', isList);
            try { localStorage.setItem('property-view', view); } catch (e) {}
        };
        setView(savedView === 'list' ? 'list' : 'grid');
        gridBtn.addEventListener('click', function () { setView('grid'); });
        listBtn.addEventListener('click', function () { setView('list'); });
    }
});
</script>

<script src="<?= asset('js/listing-ajax.js') ?>" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
