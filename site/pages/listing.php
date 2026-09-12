<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");
$lang = currentLang();
$userId = $_SESSION['user_id'] ?? null;

$id = (int)($_GET['id'] ?? 0);
$slug = $_GET['slug'] ?? '';

// Ülke adı dile göre
$countryNameCol = 'name_tr';
if ($lang === 'nl') $countryNameCol = 'name_nl';
elseif ($lang === 'en') $countryNameCol = 'name_en';

// Load the listing first. A property listing must not inherit the vehicle route.
$baseSql = "SELECT l.*, c.{$countryNameCol} AS country_name, ct.name AS city_name
            FROM listings l
            LEFT JOIN countries c ON c.id = l.country_id
            LEFT JOIN cities ct ON ct.id = l.city_id
            WHERE ";
if ($id > 0) {
    $stmt = $pdo->prepare($baseSql . "l.id = ?");
    $stmt->execute([$id]);
} elseif ($slug) {
    $stmt = $pdo->prepare($baseSql . "l.slug = ?");
    $stmt->execute([$slug]);
} else {
    header('Location: ' . BASE_URL . '/pages/search-vehicles.php'); exit;
}

$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) { header('HTTP/1.0 404 Not Found'); echo '<h1>'.t('error.not_found').'</h1><a href="search-vehicles.php">'.t('common.back').'</a>'; exit; }

$isProperty = (($item['module'] ?? '') === 'emlak');

// Load only the module data that belongs to this listing.
$moduleSql = $isProperty
    ? "SELECT * FROM listing_emlak WHERE listing_id = ? LIMIT 1"
    : "SELECT * FROM listing_arac WHERE listing_id = ? LIMIT 1";
$moduleStmt = $pdo->prepare($moduleSql);
$moduleStmt->execute([$item['id']]);
$moduleData = $moduleStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$mainListingId = (int)$item['id'];
unset($moduleData['id'], $moduleData['listing_id']);
$item = array_merge($item, $moduleData);
$item['id'] = $mainListingId;

// Vehicle fields are optional for property listings. Keep the shared template
// warning-free instead of reading missing array keys.
foreach ([
    'vehicle_type', 'brand', 'model', 'build_year', 'mileage_km',
    'fuel_type', 'transmission', 'power_hp', 'color', 'doors', 'apk_tot'
] as $optionalField) {
    if (!array_key_exists($optionalField, $item)) {
        $item[$optionalField] = null;
    }
}

$pdo->prepare("UPDATE listings SET view_count = view_count + 1 WHERE id = ?")->execute([$item['id']]);

$imgStmt = $pdo->prepare("SELECT filename FROM listing_images WHERE listing_id = ? ORDER BY is_cover DESC, sort_order ASC");
$imgStmt->execute([$mainListingId]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($images) && !empty($item['image'])) { $images = [$item['image']]; }

$imagePaths = [];
$searchDirs = [
    __DIR__ . '/../uploads/listings/' . $mainListingId . '/',
    __DIR__ . '/../uploads/listings/',
    __DIR__ . '/../uploads/vehicles/' . $mainListingId . '/',
    __DIR__ . '/../uploads/vehicles/',
    __DIR__ . '/../uploads/'
];

foreach ($images as $img) {
    $imgClean = ltrim(str_replace(['listings/', 'vehicles/'], '', (string)$img), '/');
    $matched = false;
    foreach ($searchDirs as $dir) {
        if (is_file($dir . $imgClean)) {
            $rel = str_replace(__DIR__ . '/../uploads/', '', $dir . $imgClean);
            $imagePaths[] = BASE_URL . '/uploads/' . str_replace('%2F', '/', rawurlencode($rel));
            $matched = true;
            break;
        }
    }
    if (!$matched && !empty($img)) {
        $imagePaths[] = BASE_URL . '/uploads/' . ltrim((string)$img, '/');
    }
}

if (empty($imagePaths)) {
    $placeholder = ($item['module'] === 'arac') ? 'listings/car-placeholder.png' : 'listings/property-placeholder.png';
    $imagePaths[] = BASE_URL . '/uploads/' . $placeholder;
}

$isFav = false;
if ($userId) { try { $fCheck = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?"); $fCheck->execute([$userId, $item['id']]); $isFav = (bool)$fCheck->fetch(); } catch (Exception $e) {} }

$owner = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, created_at FROM users WHERE id = ?");
$owner->execute([$item['user_id']]);
$seller = $owner->fetch(PDO::FETCH_ASSOC);

$fuelTypes = ['benzine'=>t('vehicle.fuel_benzine'),'diesel'=>t('vehicle.fuel_diesel'),'elektrisch'=>t('vehicle.fuel_elektrisch'),'hybride'=>t('vehicle.fuel_hybride'),'lpg'=>t('vehicle.fuel_lpg')];
$transmissions = ['automaat'=>t('vehicle.trans_automaat'),'handgeschakeld'=>t('vehicle.trans_handgeschakeld')];
$vehicleTypes = ['auto'=>t('vehicle.type_auto'),'motor'=>t('vehicle.type_motor'),'bestelwagen'=>t('vehicle.type_bestelwagen'),'vrachtwagen'=>t('vehicle.type_vrachtwagen')];
$fuelLabel = $fuelTypes[$item['fuel_type']] ?? ucfirst($item['fuel_type'] ?? '');
$transLabel = $transmissions[$item['transmission']] ?? ucfirst($item['transmission'] ?? '');
$typeLabel = $vehicleTypes[$item['vehicle_type']] ?? ucfirst($item['vehicle_type'] ?? '');
$isSold = ($item['status'] === 'sold');
$isReserved = ($item['status'] === 'reserved');

// Breadcrumb labels come from the translations table, not hard-coded PHP text.
$listingBackUrl = $isProperty
    ? BASE_URL . '/pages/property.php'
    : BASE_URL . '/pages/search-vehicles.php';
$listingBackKey = $isProperty ? 'property.listings_label' : 'listing.vehicle_listings';
$listingBackLabel = t($listingBackKey);
try {
    $translation = $pdo->prepare(
        'SELECT value FROM translations WHERE lang = ? AND key_group = ? AND key_name = ? LIMIT 1'
    );
    [$translationGroup, $translationName] = explode('.', $listingBackKey, 2);
    $translation->execute([$lang, $translationGroup, $translationName]);
    $dbLabel = $translation->fetchColumn();
    if ($dbLabel !== false && $dbLabel !== '') {
        $listingBackLabel = $dbLabel;
    }
} catch (Throwable $e) {
    // Keep the existing catalog fallback if the optional translation row is absent.
}

$pageTitle = $item['title'] . ' - ' . setting('site_name', 'AvrupaPazari');
$displayTitle = $isProperty
    ? ($item['title'] ?? '')
    : trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''));
if ($displayTitle === '') { $displayTitle = (string)$item['title']; }
$pageStyles = ['listing.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="ld-container">
    <nav class="ld-breadcrumb">
        <a href="<?= htmlspecialchars(BASE_URL . '/') ?>"><?= t('nav.home') ?></a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        <a href="<?= htmlspecialchars(BASE_URL . '/pages/map.php') ?>"><?= t('nav.map_search') ?></a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        <a href="<?= htmlspecialchars($listingBackUrl) ?>"><?= htmlspecialchars($listingBackLabel) ?></a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        <span><?=htmlspecialchars($displayTitle)?></span>
    </nav>

    <div class="ld-grid">
        <div>
            <?php if (!empty($imagePaths)): ?>
            <div class="ld-gallery">
                <div class="ld-gallery-main" id="galleryMain">
                    <img src="<?=htmlspecialchars($imagePaths[0])?>" alt="<?=htmlspecialchars($item['title'])?>" id="mainImage">
                    <?php if (count($imagePaths) > 1): ?>
                    <button class="ld-gallery-nav ld-gallery-prev" onclick="prevImage(event)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></button>
                    <button class="ld-gallery-nav ld-gallery-next" onclick="nextImage(event)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></button>
                    <div class="ld-gallery-counter"><span id="imgCurrent">1</span> / <?=count($imagePaths)?></div>
                    <?php endif; ?>
                    <div class="ld-gallery-badge">
                        <?php if ($item['is_premium']): ?><span class="ld-badge-premium"><?= t('common.premium') ?></span><?php endif; ?>
                        <?php if ($item['is_featured']): ?><span class="ld-badge-top">TOP</span><?php endif; ?>
                    </div>
                    <?php if ($isSold): ?><div class="ld-ribbon ld-ribbon-sold"><?= t('listing.status_sold') ?></div><?php endif; ?>
                    <?php if ($isReserved): ?><div class="ld-ribbon ld-ribbon-reserved"><?= t('listing.status_reserved') ?></div><?php endif; ?>
                </div>
                <?php if (count($imagePaths) > 1): ?>
                <div class="ld-gallery-thumbs">
                    <?php foreach ($imagePaths as $i => $img): ?>
                    <div class="ld-thumb <?=$i===0?'active':''?>" onclick="goToImage(<?=$i?>)"><img src="<?=htmlspecialchars($img)?>" alt=""></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="ld-sections">
                <?php if (!$isProperty): ?>
                <div class="ld-section">
                    <div class="ld-section-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg><?= t('vehicle.specs') ?></div>
                    <div class="ld-specs-grid">
                        <div>
                            <?php if ($typeLabel): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.vehicle_type') ?></span><span class="ld-specs-value"><?=$typeLabel?></span></div><?php endif; ?>
                            <?php if ($item['brand']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.brand') ?></span><span class="ld-specs-value"><?=htmlspecialchars($item['brand'])?></span></div><?php endif; ?>
                            <?php if ($item['model']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.model') ?></span><span class="ld-specs-value"><?=htmlspecialchars($item['model'])?></span></div><?php endif; ?>
                            <?php if ($item['build_year']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.build_year') ?></span><span class="ld-specs-value"><?=$item['build_year']?></span></div><?php endif; ?>
                            <?php if ($item['mileage_km']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.mileage') ?></span><span class="ld-specs-value"><?=number_format($item['mileage_km'],0,',','.')?> km</span></div><?php endif; ?>
                            <?php if ($fuelLabel): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.fuel') ?></span><span class="ld-specs-value"><?=$fuelLabel?></span></div><?php endif; ?>
                        </div>
                        <div>
                            <?php if ($transLabel): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.transmission') ?></span><span class="ld-specs-value"><?=$transLabel?></span></div><?php endif; ?>
                            <?php if ($item['power_hp']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.power') ?></span><span class="ld-specs-value"><?=$item['power_hp']?> HP</span></div><?php endif; ?>
                            <?php if ($item['color']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.color') ?></span><span class="ld-specs-value"><?=htmlspecialchars($item['color'])?></span></div><?php endif; ?>
                            <?php if ($item['doors']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.doors') ?></span><span class="ld-specs-value"><?=$item['doors']?></span></div><?php endif; ?>
                            <?php if ($item['apk_tot']): ?><div class="ld-specs-row"><span class="ld-specs-label"><?= t('vehicle.apk') ?></span><span class="ld-specs-value"><?=date('d.m.Y', strtotime($item['apk_tot']))?></span></div><?php endif; ?>
                            <div class="ld-specs-row"><span class="ld-specs-label"><?= t('listing.listing_no') ?></span><span class="ld-specs-value">#<?=$item['id']?></span></div>
                        </div>
                    </div>
                    <?php if ($item['apk_tot'] && strtotime($item['apk_tot']) > time()): ?>
                    <div class="ld-apk-bar" style="margin-top:16px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg><span><?= t('listing.apk_valid') ?>: <?=date('d.m.Y', strtotime($item['apk_tot']))?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="ld-section">
                    <div class="ld-section-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg><?= t('vehicle.description') ?></div>
                    <div class="ld-description"><?=nl2br(htmlspecialchars($item['description']))?></div>
                </div>

                <div class="ld-section">
                    <div class="ld-section-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><?= t('listing.location') ?></div>
                    <p style="font-size:14px;color:#374151;"><?=htmlspecialchars(($item['city_name']??'').', '.($item['country_name']??''))?><?php if($item['postal_code']):?> - <?=htmlspecialchars($item['postal_code'])?><?php endif;?></p>
                </div>
            </div>
        </div>

        <aside class="ld-info">
            <div class="ld-info-card">
                <div class="ld-price <?=$isSold?'sold':''?>">&euro; <?=number_format($item['price'],0,',','.')?></div>
                <?php if($item['price_type']==='negotiable'&&!$isSold):?><div class="ld-price-type"><?= t('listing.price_negotiable') ?></div><?php endif;?>
                <h1 class="ld-title"><?=htmlspecialchars($displayTitle)?></h1>
                <?php if ($displayTitle !== (string)$item['title']): ?><p class="ld-subtitle"><?=htmlspecialchars($item['title'])?></p><?php endif; ?>
                <div class="ld-quick-specs">
                    <?php if($item['build_year']):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><div><div class="ld-quick-spec-label"><?= t('vehicle.year') ?></div><div class="ld-quick-spec-value"><?=$item['build_year']?></div></div></div><?php endif;?>
                    <?php if($item['mileage_km']):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg><div><div class="ld-quick-spec-label">KM</div><div class="ld-quick-spec-value"><?=number_format($item['mileage_km'],0,',','.')?></div></div></div><?php endif;?>
                    <?php if($item['power_hp']):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg><div><div class="ld-quick-spec-label"><?= t('vehicle.power') ?></div><div class="ld-quick-spec-value"><?=$item['power_hp']?> HP</div></div></div><?php endif;?>
                    <?php if($fuelLabel):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V6a2 2 0 012-2h6a2 2 0 012 2v16"/><path d="M13 10h4a2 2 0 012 2v8"/><circle cx="7" cy="10" r="1"/></svg><div><div class="ld-quick-spec-label"><?= t('vehicle.fuel') ?></div><div class="ld-quick-spec-value"><?=$fuelLabel?></div></div></div><?php endif;?>
                    <?php if($transLabel):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><path d="M6 8v10M18 8v4a2 2 0 01-2 2H8"/></svg><div><div class="ld-quick-spec-label"><?= t('vehicle.transmission') ?></div><div class="ld-quick-spec-value"><?=$transLabel?></div></div></div><?php endif;?>
                    <?php if($item['color']):?><div class="ld-quick-spec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg><div><div class="ld-quick-spec-label"><?= t('vehicle.color') ?></div><div class="ld-quick-spec-value"><?=htmlspecialchars($item['color'])?></div></div></div><?php endif;?>
                </div>
                <div class="ld-actions">
                    <?php if(!$isSold&&!$isReserved):?>
                    <button class="ld-btn ld-btn-primary" type="button" id="msgOpenBtn" data-testid="message-seller-button" onclick="openMsgForm()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg><?= t('listing.send_message') ?></button>
                    <button class="ld-btn ld-btn-secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg><?= t('listing.call') ?></button>
                    <?php endif;?>
                    <button class="ld-btn ld-btn-favorite <?=$isFav?'active':''?>" id="favBtn" onclick="toggleFav()"><svg viewBox="0 0 24 24" fill="<?=$isFav?'currentColor':'none'?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg><span id="favText"><?=$isFav? t('listing.saved') : t('listing.add_favorite') ?></span></button>
                </div>
            </div>
            <div class="ld-info-card">
                <div class="ld-seller"><div class="ld-seller-avatar"><?=mb_substr($seller['name']??'?',0,1)?></div><div><div class="ld-seller-name"><?=htmlspecialchars($seller['name']??t('listing.seller'))?></div><div class="ld-seller-meta"><?= t('listing.member_since', ['date' => ($seller ? date('Y', strtotime($seller['created_at'])) : '')]) ?></div></div></div>
                <div class="ld-seller-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><?=htmlspecialchars(($item['city_name']??'').', '.($item['country_name']??''))?></div>
            </div>
            <div class="ld-info-card">
                <div class="ld-section-title" style="margin-bottom:12px;font-size:14px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98"/></svg><?= t('common.share') ?></div>
                <div class="ld-share">
                    <button class="ld-share-btn" onclick="navigator.clipboard.writeText(window.location.href);alert('<?= t('common.copied') ?>')" title="<?= t('common.copy_link') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                    <a class="ld-share-btn" href="https://wa.me/?text=<?=urlencode($item['title'].' - http://157.90.130.252/~avrupa/listing.php?id='.$item['id'])?>" target="_blank"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></a>
                    <a class="ld-share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?=urlencode('http://157.90.130.252/~avrupa/listing.php?id='.$item['id'])?>" target="_blank"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
                </div>
            </div>
            <div class="ld-info-card" style="padding:16px 24px;"><div style="display:flex;justify-content:space-between;font-size:12px;color:#9ca3af;"><span><?= t('listing.views_count') ?>: <?=$item['view_count']?></span><span><?= t('listing.added') ?>: <?=date('d.m.Y',strtotime($item['created_at']))?></span></div></div>
        </aside>
    </div>
</div>

<!-- LIGHTBOX -->
<div class="ld-lightbox" id="lightbox">
    <button class="ld-lightbox-close" onclick="closeLightbox()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <button class="ld-lightbox-nav ld-lightbox-prev" onclick="lbPrev()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></button>
    <img src="" alt="" id="lbImage">
    <button class="ld-lightbox-nav ld-lightbox-next" onclick="lbNext()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></button>
    <div class="ld-lightbox-thumbs" id="lbThumbs"></div>
    <div class="ld-lightbox-counter"><span id="lbCounter"></span></div>
</div>


<script>
var images = <?=json_encode($imagePaths)?>;
var currentIndex = 0;
var LANG_SAVED = '<?= t('listing.saved') ?>';
var LANG_ADD_FAV = '<?= t('listing.add_favorite') ?>';

function showImage(index) {
    if (index < 0) index = images.length - 1;
    if (index >= images.length) index = 0;
    currentIndex = index;
    var mainImg = document.getElementById('mainImage');
    if (mainImg) mainImg.src = images[index];
    var counter = document.getElementById('imgCurrent');
    if (counter) counter.textContent = index + 1;
    document.querySelectorAll('.ld-thumb').forEach(function(t, i) { t.classList.toggle('active', i === index); });
}
function nextImage(e) { if(e)e.stopPropagation(); showImage(currentIndex + 1); }
function prevImage(e) { if(e)e.stopPropagation(); showImage(currentIndex - 1); }
function goToImage(i) { showImage(i); }

function openLightbox() {
    document.getElementById('lightbox').classList.add('active');
    document.getElementById('lbImage').src = images[currentIndex];
    document.getElementById('lbCounter').textContent = (currentIndex+1)+' / '+images.length;
    document.body.style.overflow = 'hidden';
    var thumbsDiv = document.getElementById('lbThumbs');
    thumbsDiv.innerHTML = '';
    images.forEach(function(img, i) {
        var div = document.createElement('div');
        div.className = 'ld-lb-thumb' + (i === currentIndex ? ' active' : '');
        div.onclick = function() { lbGoTo(i); };
        div.innerHTML = '<img src="' + img + '" alt="">';
        thumbsDiv.appendChild(div);
    });
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('active'); document.body.style.overflow = ''; }
function lbGoTo(i) { currentIndex=i; document.getElementById('lbImage').src=images[i]; document.getElementById('lbCounter').textContent=(i+1)+' / '+images.length; document.querySelectorAll('.ld-lb-thumb').forEach(function(t,idx){t.classList.toggle('active',idx===i)}); showImage(i); }
function lbNext() { showImage(currentIndex+1); document.getElementById('lbImage').src=images[currentIndex]; document.getElementById('lbCounter').textContent=(currentIndex+1)+' / '+images.length; document.querySelectorAll('.ld-lb-thumb').forEach(function(t,i){t.classList.toggle('active',i===currentIndex)}); }
function lbPrev() { showImage(currentIndex-1); document.getElementById('lbImage').src=images[currentIndex]; document.getElementById('lbCounter').textContent=(currentIndex+1)+' / '+images.length; document.querySelectorAll('.ld-lb-thumb').forEach(function(t,i){t.classList.toggle('active',i===currentIndex)}); }

var galleryMain = document.getElementById('galleryMain');
if (galleryMain) { galleryMain.addEventListener('click', function(e) { if (e.target.tagName === 'IMG') openLightbox(); }); }

document.addEventListener('keydown', function(e) {
    var lb = document.getElementById('lightbox');
    if (lb && lb.classList.contains('active')) { if (e.key==='Escape') closeLightbox(); if (e.key==='ArrowRight') lbNext(); if (e.key==='ArrowLeft') lbPrev(); }
    else { if (e.key==='ArrowRight') nextImage(); if (e.key==='ArrowLeft') prevImage(); }
});
document.getElementById('lightbox').addEventListener('click', function(e) { if (e.target === this) closeLightbox(); });

function toggleFav() {
    fetch('<?= BASE_URL ?>/ajax/toggle-favorite.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, credentials:'same-origin', body:'listing_id=<?=$item['id']?>' })
    .then(function(r){return r.json();}).then(function(data){
        var btn=document.getElementById('favBtn'),txt=document.getElementById('favText');
        if(data.status==='login_required'){if(window.openAuth)window.openAuth('login');else window.location='<?= BASE_URL ?>/?auth=login';return;}
        if(data.status==='added'){btn.classList.add('active');btn.querySelector('svg').setAttribute('fill','currentColor');txt.textContent=LANG_SAVED;}
        else{btn.classList.remove('active');btn.querySelector('svg').setAttribute('fill','none');txt.textContent=LANG_ADD_FAV;}
    });
}
</script>
<?php $ownListing = $userId && (int)$item['user_id'] === (int)$userId; ?>
<div class="ld-msg-overlay" id="msgOverlay" data-testid="message-modal">
  <form class="ld-msg-card" id="msgForm">
    <button type="button" class="ld-msg-close" onclick="closeMsgForm()" aria-label="close">&times;</button>
    <h3><?= e(t('msg.send')) ?></h3>
    <p class="ld-msg-sub"><?= e($item['title']) ?></p>
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="listing_id" value="<?= (int)$item['id'] ?>">
    <textarea name="body" rows="4" required placeholder="<?= e(t('msg.placeholder')) ?>" data-testid="message-body"></textarea>
    <p class="ld-msg-error" id="msgError" hidden data-testid="message-error"></p>
    <button type="submit" class="ld-btn ld-btn-primary" data-testid="message-submit"><?= e(t('msg.send')) ?></button>
  </form>
</div>
<script>
var MSG_OWN = <?= $ownListing ? 'true' : 'false' ?>;
function openMsgForm(){ if(!window.SITE_USER){ if(window.openAuth) window.openAuth('login'); return; } if(MSG_OWN){ alert('<?= e(t('msg.own_listing')) ?>'); return; } document.getElementById('msgOverlay').classList.add('show'); setTimeout(function(){document.querySelector('#msgForm textarea').focus();},100); }
function closeMsgForm(){ document.getElementById('msgOverlay').classList.remove('show'); }
document.getElementById('msgOverlay').addEventListener('click', function(e){ if(e.target===this) closeMsgForm(); });
document.getElementById('msgForm').addEventListener('submit', function(e){
  e.preventDefault(); var f=this, err=document.getElementById('msgError'), btn=f.querySelector('button[type=submit]'); err.hidden=true; btn.disabled=true;
  fetch('<?= url('ajax/send-message.php') ?>', {method:'POST', body:new FormData(f), credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){
    if(d.status==='login_required'){ closeMsgForm(); if(window.openAuth) window.openAuth('login'); return; }
    if(!d.ok){ err.textContent=d.error||'Error'; err.hidden=false; btn.disabled=false; return; }
    window.location.href=d.redirect;
  }).catch(function(){ err.textContent='Network error'; err.hidden=false; btn.disabled=false; });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
