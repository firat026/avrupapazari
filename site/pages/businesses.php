<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$currentPage = 'businesses';
$pdo = getDB();
$pdo->exec('SET NAMES utf8mb4');
$lang = currentLang();

$searchQuery = trim((string)($_GET['q'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$countryId = (int)($_GET['country_id'] ?? 0);
$cityId = (int)($_GET['city_id'] ?? 0);
$sort = ($_GET['sort'] ?? 'featured') === 'rating' ? 'rating' : 'featured';

$labels = [];
$categories = [];
$allCategories = [];
$countries = [];
$cities = [];
$businesses = [];
$total = 0;
$databaseError = false;

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

try {
    $query = $pdo->prepare("SELECT key_group, key_name, value FROM translations WHERE lang = ? AND key_group IN ('businesses', 'search', 'common', 'listing', 'nav', 'second_hand', 'property', 'map')");
    $query->execute([$lang]);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $labels[$row['key_group'] . '.' . $row['key_name']] = (string)$row['value'];
    }

    $query = $pdo->prepare("SELECT c.id, c.parent_id, c.icon, c.image, COALESCE(ct.name, en.name, tr.name, c.slug) AS name 
        FROM categories c 
        LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? 
        LEFT JOIN category_translations en ON en.category_id = c.id AND en.lang = 'en' 
        LEFT JOIN category_translations tr ON tr.category_id = c.id AND tr.lang = 'tr' 
        WHERE c.module = 'esnaf' AND c.is_active = 1 
        ORDER BY c.parent_id IS NOT NULL, c.sort_order, c.id");
    $query->execute([$lang]);
    $allCategories = $query->fetchAll(PDO::FETCH_ASSOC);

    $byParent = [];
    foreach ($allCategories as $category) {
        $byParent[(int)($category['parent_id'] ?? 0)][] = $category;
    }

    $rootItems = $byParent[1] ?? ($byParent[0] ?? []);
    foreach ($rootItems as $parent) {
        $parent['children'] = $byParent[(int)$parent['id']] ?? [];
        $categories[] = $parent;
    }

    $countryColumn = $lang === 'nl' ? 'name_nl' : ($lang === 'en' ? 'name_en' : 'name_tr');
    $countries = $pdo->query("SELECT id, code, {$countryColumn} AS name FROM countries WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
    $cities = $pdo->query('SELECT id, name, country_id FROM cities WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

    $where = ["b.status = 'active'"];
    $parameters = ['language' => $lang];

    if ($searchQuery !== '') {
        $where[] = '(b.name LIKE :name OR b.description LIKE :description OR b.city LIKE :search_city)';
        $parameters += [
            'name' => "%{$searchQuery}%",
            'description' => "%{$searchQuery}%",
            'search_city' => "%{$searchQuery}%"
        ];
    }

    if ($countryId > 0) {
        foreach ($countries as $country) {
            if ((int)$country['id'] === $countryId) {
                $where[] = 'b.country = :country';
                $parameters['country'] = $country['code'];
                break;
            }
        }
    }

    if ($cityId > 0) {
        foreach ($cities as $city) {
            if ((int)$city['id'] === $cityId) {
                $where[] = 'b.city = :city';
                $parameters['city'] = $city['name'];
                break;
            }
        }
    }

    if ($categoryId > 0) {
        $ids = [$categoryId];
        foreach ($allCategories as $category) {
            if ((int)$category['parent_id'] === $categoryId) {
                $ids[] = (int)$category['id'];
            }
        }
        $holders = [];
        foreach ($ids as $i => $id) {
            $key = 'category_' . $i;
            $holders[] = ':' . $key;
            $parameters[$key] = $id;
        }
        $where[] = 'b.category_id IN (' . implode(', ', $holders) . ')';
    }

    $countParams = $parameters;
    unset($countParams['language']);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM esnaf b WHERE " . implode(' AND ', $where));
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    $order = $sort === 'rating' ? 'b.rating DESC, b.name ASC' : 'b.is_featured DESC, b.rating DESC, b.name ASC';

    $query = $pdo->prepare("SELECT b.id, b.name, b.slug, b.description, b.city, b.country, b.image, b.rating, b.review_count, b.is_featured, 
        c.icon AS category_icon, c.image AS category_image, c.id AS parent_cat_id,
        COALESCE(ct.name, en.name, tr.name, c.slug) AS category_name 
        FROM esnaf b 
        LEFT JOIN categories c ON c.id = b.category_id 
        LEFT JOIN category_translations ct ON ct.category_id = b.category_id AND ct.lang = :language 
        LEFT JOIN category_translations en ON en.category_id = b.category_id AND en.lang = 'en' 
        LEFT JOIN category_translations tr ON tr.category_id = b.category_id AND tr.lang = 'tr' 
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY {$order} 
        LIMIT {$perPage} OFFSET {$offset}");
    $query->execute($parameters);
    $businesses = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    $databaseError = true;
    $totalPages = 1;
}

$text = static function (string $key, string $fallback = '') use ($labels, $lang): string {
    if (isset($labels[$key]) && trim($labels[$key]) !== '') {
        return (string)$labels[$key];
    }
    
    $aliasMap = [
        'businesses.filters' => ['search.filters'],
        'businesses.clear_filters' => ['search.clear_filters'],
        'businesses.all' => ['second_hand.all', 'common.all'],
        'businesses.all_countries' => ['second_hand.all_countries', 'property.all_countries'],
        'businesses.all_cities' => ['second_hand.all_cities', 'property.all_cities'],
        'businesses.location' => ['listing.location', 'second_hand.location', 'listing.country'],
        'businesses.country' => ['listing.country'],
        'businesses.city' => ['listing.city'],
        'businesses.results_word' => ['common.results', 'listing.listings_label'],
    ];

    if (isset($aliasMap[$key])) {
        foreach ($aliasMap[$key] as $aliasKey) {
            if (isset($labels[$aliasKey]) && trim($labels[$aliasKey]) !== '') {
                return (string)$labels[$aliasKey];
            }
        }
    }

    $dict = [
        'businesses.page_title' => [
            'tr' => 'İşletmeler',
            'nl' => 'Bedrijven',
            'en' => 'Businesses',
            'de' => 'Unternehmen'
        ],
        'businesses.title' => [
            'tr' => 'İhtiyacın olan işletmeyi bul',
            'nl' => 'Vind het juiste bedrijf',
            'en' => 'Find the right business',
            'de' => 'Das passende Unternehmen finden'
        ],
        'businesses.subtitle' => [
            'tr' => 'Avrupa genelinde güvenilir işletmeleri keşfet ve doğrudan iletişime geç.',
            'nl' => 'Ontdek betrouwbare bedrijven in Europa en neem direct contact op.',
            'en' => 'Discover trusted businesses across Europe and contact them directly.',
            'de' => 'Entdecke vertrauenswürdige Unternehmen in Europa und kontaktiere sie direkt.'
        ],
        'businesses.directory_title' => [
            'tr' => 'Yerel işletmeler',
            'nl' => 'Lokale bedrijven',
            'en' => 'Local businesses',
            'de' => 'Lokale Unternehmen'
        ],
        'businesses.search_placeholder' => [
            'tr' => 'İşletme, hizmet veya uzman ara',
            'nl' => 'Zoek een bedrijf, dienst of specialist',
            'en' => 'Search a business, service or professional',
            'de' => 'Unternehmen, Dienstleistung oder Fachkraft suchen'
        ],
        'businesses.category_filter' => [
            'tr' => 'Kategori',
            'nl' => 'Categorie',
            'en' => 'Category',
            'de' => 'Kategorie'
        ],
        'businesses.sort_featured' => [
            'tr' => 'Öne çıkanlar',
            'nl' => 'Uitgelicht eerst',
            'en' => 'Featured first',
            'de' => 'Empfohlen zuerst'
        ],
        'businesses.sort_rating' => [
            'tr' => 'Puana göre (Yüksek)',
            'nl' => 'Hoogst beoordeeld',
            'en' => 'Highest rated',
            'de' => 'Bestbewertet'
        ],
        'businesses.all' => [
            'tr' => 'Tümü',
            'nl' => 'Alles',
            'en' => 'All',
            'de' => 'Alle'
        ],
        'businesses.all_countries' => [
            'tr' => 'Tüm ülkeler',
            'nl' => 'Alle landen',
            'en' => 'All countries',
            'de' => 'Alle Länder'
        ],
        'businesses.all_cities' => [
            'tr' => 'Tüm şehirler',
            'nl' => 'Alle steden',
            'en' => 'All cities',
            'de' => 'Alle Städte'
        ],
        'businesses.location' => [
            'tr' => 'Konum',
            'nl' => 'Locatie',
            'en' => 'Location',
            'de' => 'Standort'
        ],
        'businesses.filters' => [
            'tr' => 'Filtreler',
            'nl' => 'Filters',
            'en' => 'Filters',
            'de' => 'Filter'
        ],
        'businesses.clear_filters' => [
            'tr' => 'Filtreleri temizle',
            'nl' => 'Filters wissen',
            'en' => 'Clear filters',
            'de' => 'Filter löschen'
        ],
        'businesses.load_error' => [
            'tr' => 'İşletmeler yüklenemedi',
            'nl' => 'Bedrijven konden niet worden geladen',
            'en' => 'Businesses could not be loaded',
            'de' => 'Unternehmen konnten nicht geladen werden'
        ],
        'businesses.load_error_text' => [
            'tr' => 'Lütfen veritabanı kayıtlarını kontrol edip tekrar deneyin.',
            'nl' => 'Controleer de database en probeer het opnieuw.',
            'en' => 'Please check the database records and try again.',
            'de' => 'Bitte überprüfen Sie die Datenbankeinträge und versuchen Sie es erneut.'
        ],
        'businesses.empty_title' => [
            'tr' => 'İşletme bulunamadı',
            'nl' => 'Geen bedrijven gevonden',
            'en' => 'No businesses found',
            'de' => 'Keine Unternehmen gefunden'
        ],
        'businesses.empty_text' => [
            'tr' => 'Aramanı veya filtrelerini değiştir.',
            'nl' => 'Pas je zoekopdracht of filters aan.',
            'en' => 'Try changing your search or filters.',
            'de' => 'Ändere deine Suche oder Filter.'
        ],
        'businesses.reset' => [
            'tr' => 'Tüm işletmeleri göster',
            'nl' => 'Alle bedrijven tonen',
            'en' => 'Show all businesses',
            'de' => 'Alle Unternehmen anzeigen'
        ],
        'businesses.featured' => [
            'tr' => 'Öne çıkan',
            'nl' => 'Uitgelicht',
            'en' => 'Featured',
            'de' => 'Empfohlen'
        ],
        'businesses.default_category' => [
            'tr' => 'Yerel işletme',
            'nl' => 'Lokaal bedrijf',
            'en' => 'Local business',
            'de' => 'Lokales Unternehmen'
        ],
        'businesses.view_profile' => [
            'tr' => 'Profili görüntüle',
            'nl' => 'Profiel bekijken',
            'en' => 'View profile',
            'de' => 'Profil ansehen'
        ],
        'businesses.results_word' => [
            'tr' => 'sonuç',
            'nl' => 'resultaten',
            'en' => 'results',
            'de' => 'Ergebnisse'
        ]
    ];

    if (isset($dict[$key][$lang])) {
        return $dict[$key][$lang];
    }
    if (isset($dict[$key]['en'])) {
        return $dict[$key]['en'];
    }

    return $fallback !== '' ? $fallback : $key;
};

function getBusinessImageUrl(array $business): string {
    $img = trim((string)($business['image'] ?? ''));
    if ($img !== '') {
        return imageUrl($img);
    }
    $catImg = trim((string)($business['category_image'] ?? ''));
    if ($catImg !== '') {
        return imageUrl($catImg);
    }
    
    $catMap = [
        600 => 'categories/cat-18.jpg',
        601 => 'categories/cat-16.jpg',
        602 => 'categories/cat-17.jpg',
        603 => 'categories/cat-18.jpg',
        604 => 'categories/cat-18.jpg',
        605 => 'categories/cat-10.jpg',
        606 => 'categories/cat-19.jpg',
        607 => 'categories/cat-19.jpg',
        608 => 'categories/cat-20.jpg',
        609 => 'categories/cat-21.jpg',
        610 => 'categories/cat-22.jpg',
        611 => 'categories/cat-1.jpg',
        16  => 'categories/cat-16.jpg',
        17  => 'categories/cat-17.jpg',
        18  => 'categories/cat-18.jpg',
        19  => 'categories/cat-19.jpg',
        20  => 'categories/cat-20.jpg',
        21  => 'categories/cat-21.jpg',
        22  => 'categories/cat-22.jpg',
    ];
    
    $catId = (int)($business['parent_cat_id'] ?? 0);
    if (isset($catMap[$catId])) {
        return imageUrl($catMap[$catId]);
    }
    
    if ($catId >= 620 && $catId <= 623) return imageUrl('categories/cat-18.jpg');
    if ($catId >= 624 && $catId <= 627) return imageUrl('categories/cat-16.jpg');
    if ($catId >= 628 && $catId <= 631) return imageUrl('categories/cat-17.jpg');
    if ($catId >= 632 && $catId <= 635) return imageUrl('categories/cat-18.jpg');
    if ($catId >= 636 && $catId <= 639) return imageUrl('categories/cat-18.jpg');
    if ($catId >= 640 && $catId <= 643) return imageUrl('categories/cat-10.jpg');
    if ($catId >= 644 && $catId <= 647) return imageUrl('categories/cat-19.jpg');
    if ($catId >= 648 && $catId <= 651) return imageUrl('categories/cat-19.jpg');
    if ($catId >= 652 && $catId <= 655) return imageUrl('categories/cat-20.jpg');
    if ($catId >= 656 && $catId <= 659) return imageUrl('categories/cat-21.jpg');
    if ($catId >= 660 && $catId <= 663) return imageUrl('categories/cat-22.jpg');
    if ($catId >= 664 && $catId <= 667) return imageUrl('categories/cat-1.jpg');

    return imageUrl('categories/cat-1.jpg');
}

$pageTitle = $text('businesses.page_title', 'Businesses');
$pageStyles = ['businesses.css', 'listing-pages.css'];
include __DIR__ . '/../includes/header.php';
?>
<script>
(function () {
    try {
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        var y = sessionStorage.getItem('avrupa_businesses_scroll_y');
        sessionStorage.removeItem('avrupa_businesses_scroll_y');
        window.__bizScrollY = y === null ? null : (parseInt(y, 10) || 0);
    } catch (e) {}
})();
</script>

<main class="sh-page businesses-page">
    <div class="sh-container">
        <nav class="sh-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(BASE_URL) ?>/"><?= e($text('nav.home', 'Home')) ?></a>
            <span class="sep">/</span>
            <span class="current"><?= e($pageTitle) ?></span>
        </nav>
    </div>

    <!-- Hero Title Header -->
    <section class="sh-hero-categories">
        <div class="sh-container">
            <div class="sh-hero-head">
                <div class="sh-hero-title">
                    <h1><?= e($text('businesses.title', 'Find the right business')) ?></h1>
                    <p><?= e($text('businesses.subtitle', 'Discover trusted businesses across Europe and contact them directly.')) ?></p>
                </div>
                <div class="sh-hero-actions">
                    <a href="<?= e(BASE_URL) ?>/pages/map.php?module=esnaf<?= $categoryId > 0 ? '&category_id=' . $categoryId : '' ?>" class="sh-map-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                            <line x1="8" y1="2" x2="8" y2="18"></line>
                            <line x1="16" y1="6" x2="16" y2="22"></line>
                        </svg>
                        <span><?= e($text('nav.map_search', 'Search listings on the map')) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="sh-container">
        <div class="sh-layout">
            <!-- Sidebar Filters -->
            <aside class="sh-sidebar">
                <form class="sh-filter-form" method="get">
                    <div class="sh-filter-header">
                        <div class="sh-filter-header-title">
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            <strong><?= e($text('businesses.filters', 'Filters')) ?></strong>
                        </div>
                        <a href="<?= e(BASE_URL) ?>/pages/businesses.php" class="sh-clear-link"><?= e($text('businesses.clear_filters', 'Clear filters')) ?></a>
                    </div>

                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e($text('businesses.location', 'Location')) ?></label>
                        <div class="sh-select-wrapper">
                            <select name="country_id">
                                <option value="0"><?= e($text('businesses.all_countries', 'All countries')) ?></option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?= (int)$country['id'] ?>" <?= $countryId === (int)$country['id'] ? 'selected' : '' ?>><?= e($country['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sh-select-wrapper" style="margin-top: 8px;">
                            <select name="city_id">
                                <option value="0"><?= e($text('businesses.all_cities', 'All cities')) ?></option>
                                <?php foreach ($cities as $cityOption): ?>
                                    <option value="<?= (int)$cityOption['id'] ?>" <?= $cityId === (int)$cityOption['id'] ? 'selected' : '' ?>><?= e($cityOption['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="sh-filter-group">
                        <div class="sh-search-input-wrapper">
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="<?= e($text('businesses.search_placeholder', 'Search businesses')) ?>">
                        </div>
                    </div>

                    <div class="sh-filter-group">
                        <label class="sh-group-label"><?= e($text('businesses.category_filter', 'Category')) ?></label>
                        <div class="sh-cat-tree">
                            <?php foreach ($categories as $parent): ?>
                                <?php 
                                $isParentActive = ($categoryId === (int)$parent['id'] || in_array($categoryId, array_column($parent['children'], 'id'), true));
                                ?>
                                <div class="sh-cat-tree-item <?= $isParentActive ? 'open active' : '' ?>">
                                    <div class="sh-cat-tree-header">
                                        <a href="<?= e(BASE_URL) ?>/pages/businesses.php?category_id=<?= (int)$parent['id'] ?>" class="sh-cat-tree-link <?= $categoryId === (int)$parent['id'] ? 'current' : '' ?>">
                                            <i data-lucide="<?= e($parent['icon'] ?: 'tag') ?>"></i>
                                            <span><?= e($parent['name']) ?></span>
                                        </a>
                                        <?php if (!empty($parent['children'])): ?>
                                            <span class="sh-cat-tree-toggle" onclick="this.closest('.sh-cat-tree-item').classList.toggle('open'); event.stopPropagation();">⌄</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($parent['children'])): ?>
                                        <div class="sh-cat-sub-list">
                                            <?php foreach ($parent['children'] as $child): ?>
                                                <a href="<?= e(BASE_URL) ?>/pages/businesses.php?category_id=<?= (int)$child['id'] ?>" class="sh-cat-sub-link <?= $categoryId === (int)$child['id'] ? 'current' : '' ?>">
                                                    <span><?= e($child['name']) ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <button type="submit" class="sh-submit-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span><?= e($text('common.search', 'Apply filters')) ?></span>
                    </button>
                </form>
            </aside>

            <!-- Main Listings Section -->
            <section class="sh-main">
                <!-- Sticky / Floating Modern Category Filter Nav (Grid Width) -->
                <div class="sh-main-cats-sticky">
                    <div class="sh-cats-slider">
                        <a href="<?= e(BASE_URL) ?>/pages/businesses.php" class="sh-cat-card <?= $categoryId === 0 ? 'active' : '' ?>">
                            <span class="sh-cat-card-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                </svg>
                            </span>
                            <span><?= e($text('businesses.all', 'All')) ?></span>
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="<?= e(BASE_URL) ?>/pages/businesses.php?category_id=<?= (int)$category['id'] ?>" class="sh-cat-card <?= $categoryId === (int)$category['id'] ? 'active' : '' ?>">
                                <span class="sh-cat-card-icon"><i data-lucide="<?= e($category['icon'] ?: 'tag') ?>"></i></span>
                                <span><?= e($category['name']) ?></span>
                                <?php if (!empty($category['children'])): ?>
                                    <span class="sh-cat-card-count"><?= count($category['children']) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sh-toolbar">
                    <div class="sh-toolbar-info">
                        <h2><?= e($text('businesses.directory_title', 'Local businesses')) ?></h2>
                        <span class="sh-total-badge"><?= $total ?> <?= e($text('businesses.results_word', 'results')) ?></span>
                    </div>
                    <form class="sh-sort-form" method="get">
                        <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                        <input type="hidden" name="category_id" value="<?= $categoryId ?>">
                        <input type="hidden" name="country_id" value="<?= $countryId ?>">
                        <input type="hidden" name="city_id" value="<?= $cityId ?>">
                        <select name="sort">
                            <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>><?= e($text('businesses.sort_featured', 'Featured first')) ?></option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>><?= e($text('businesses.sort_rating', 'Highest rated')) ?></option>
                        </select>
                    </form>
                </div>

                <?php if ($databaseError || empty($businesses)): ?>
                    <div class="sh-empty-state">
                        <div class="sh-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </div>
                        <h3><?= e($databaseError ? $text('businesses.load_error', 'Businesses could not be loaded') : $text('businesses.empty_title', 'No businesses found')) ?></h3>
                        <p><?= e($databaseError ? $text('businesses.load_error_text', 'Please check the database records and try again.') : $text('businesses.empty_text', 'Try changing your search or filters.')) ?></p>
                        <a href="<?= e(BASE_URL) ?>/pages/businesses.php" class="sh-reset-btn"><?= e($text('businesses.reset', 'Show all businesses')) ?></a>
                    </div>
                <?php else: ?>
                    <div class="sh-grid" id="businessesGrid">
                        <?php foreach ($businesses as $business): 
                            $name = trim((string)$business['name']);
                            $imageUrl = getBusinessImageUrl($business);
                            $categoryName = (string)($business['category_name'] ?: $text('businesses.default_category', 'Local business'));
                            $rating = (float)($business['rating'] ?? 0);
                            $reviewCount = (int)($business['review_count'] ?? 0);
                            $slug = (string)($business['slug'] ?? '');
                            $city = (string)($business['city'] ?? '');
                            $initials = mb_strtoupper(mb_substr($name, 0, 2));
                        ?>
                            <article class="sh-card business-card">
                                <a href="<?= e(BASE_URL) ?>/pages/business.php?slug=<?= e($slug) ?>" class="sh-card-media business-media">
                                    <img src="<?= e($imageUrl) ?>" alt="<?= e($name) ?>" loading="lazy" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                    <div class="business-fallback-avatar" style="display:none;">
                                        <span><?= e($initials) ?></span>
                                    </div>
                                    <?php if (!empty($business['is_featured'])): ?>
                                        <span class="sh-badge-premium"><?= e($text('businesses.featured', 'Featured')) ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="sh-card-body">
                                    <div class="sh-card-header">
                                        <span class="sh-card-category"><?= e($categoryName) ?></span>
                                        <?php if ($rating > 0): ?>
                                            <span class="business-rating-badge">★ <?= e(number_format($rating, 1)) ?><?php if ($reviewCount > 0): ?> <small>(<?= $reviewCount ?>)</small><?php endif; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="sh-card-title">
                                        <a href="<?= e(BASE_URL) ?>/pages/business.php?slug=<?= e($slug) ?>"><?= e($name) ?></a>
                                    </h3>
                                    <?php if (!empty($business['description'])): ?>
                                        <p class="sh-card-desc"><?= e(mb_strimwidth((string)$business['description'], 0, 110, '...')) ?></p>
                                    <?php endif; ?>
                                    <div class="sh-card-footer">
                                        <span class="sh-card-loc">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                            <?= e($city ?: $text('businesses.all_cities', 'All cities')) ?>
                                        </span>
                                        <a class="business-profile-link" href="<?= e(BASE_URL) ?>/pages/business.php?slug=<?= e($slug) ?>">
                                            <?= e($text('businesses.view_profile', 'View profile')) ?> &rarr;
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="sh-pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="sh-page-btn">&laquo;</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="sh-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="sh-page-btn">&raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.__bizScrollY === 'number') {
        window.scrollTo(0, window.__bizScrollY);
    }

    document.querySelectorAll('.sh-cat-card, .sh-cat-tree-link, .sh-cat-sub-link, .sh-page-btn').forEach(function (link) {
        link.addEventListener('click', function () {
            try {
                sessionStorage.setItem('avrupa_businesses_scroll_y', String(window.scrollY));
            } catch (e) {}
        });
    });

    const iconMap = {
        'hard-hat': '<path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v2z"/><path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"/><path d="M4 15v-3a8 8 0 0 1 16 0v3"/>',
        'utensils': '<path d="M18 2v6a3 3 0 0 1-3 3 3 3 0 0 1-3-3V2"/><path d="M15 2v18"/><path d="M6 2v7a3 3 0 0 0 6 0V2"/><path d="M9 2v20"/>',
        'heart-pulse': '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>',
        'car': '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        'truck': '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14v10Z"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'laptop': '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16"/>',
        'briefcase-business': '<path d="M12 12h.01"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M22 13a18.15 18.15 0 0 1-20 0"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
        'building-2': '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
        'graduation-cap': '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'house': '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'shopping-bag': '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'bed-double': '<path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><path d="M12 4v6"/><path d="M2 18h20"/>',
        'scissors': '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
        'wrench': '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'scale': '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        'sparkles': '<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>',
        'shopping-cart': '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'store': '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/>',
        'tag': '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>'
    };

    document.querySelectorAll('[data-lucide]').forEach(function (icon) {
        const name = icon.getAttribute('data-lucide');
        const svgContent = iconMap[name] || iconMap['tag'];
        icon.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + svgContent + '</svg>';
        icon.removeAttribute('data-lucide');
    });

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>

<script src="<?= asset('js/listing-ajax.js') ?>" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
