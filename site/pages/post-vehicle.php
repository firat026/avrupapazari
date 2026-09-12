<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?auth=login');
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->set_charset('utf8mb4');

// Language
$lang = currentLang();

// Translations
$tr = translationGroup('post_vehicle');


// Vehicle features list
$featuresList = [
    '360_camera', 'abs', 'adaptive_cruise_control', 'alarm', 'auto_climate',
    'bi_xenon', 'board_computer', 'cruise_control', 'blind_spot', 'electric_mirrors',
    'electric_parking_brake', 'high_beam_assist', 'wireless_charging', 'full_led_headlights',
    'laser_light', 'leather', 'sunroof_visor', 'multifunction_steering', 'sunroof',
    'parking_camera', 'rain_sensor', 'sound_system', 'voice_control', 'start_stop_button',
    'heated_seats', 'towbar', 'traffic_sign_recognition', 'rear_heated_seats', 'winter_package',
    '4x4', 'rear_camera', 'airbags', 'android_auto', 'autonomous_driving',
    'bluetooth', 'central_locking', 'roof_rails', 'esp', 'electric_windows',
    'emergency_brake', 'head_up_display', 'isofix', 'lane_departure', 'led_daytime',
    'alloy_wheels', 'metallic_paint', 'navigation', 'panoramic_roof', 'parking_sensors',
    'sliding_door', 'sport_package', 'auxiliary_heater', 'seat_massage', 'heated_steering',
    'usb', 'fatigue_detection', 'dash_cam', 'xenon',
    'adapted_disabled', 'adaptive_lights', 'airconditioning', 'apple_carplay', 'bidirectional_charging',
    'cornering_lights', 'climate_control', 'dab_radio', 'electric_tailgate', 'electric_seats',
    'full_digital_cluster', 'hill_hold', 'keyless_entry', 'lane_keeping', 'led_lighting',
    'light_sensor', 'fog_lights', 'night_vision', 'park_assist', 'radio',
    'ski_hatch', 'sport_seats', 'start_stop_system', 'seat_ventilation', 'traction_control',
    'auto_dimming_headlights', 'heated_mirrors', 'heat_pump'
];

$featuresLabels = [
    'tr' => [
        '360_camera' => '360° Kamera', 'abs' => 'ABS', 'adaptive_cruise_control' => 'Adaptif Hız Sabitleme',
        'alarm' => 'Alarm', 'auto_climate' => 'Otomatik Klima', 'bi_xenon' => 'Bi-Xenon Far',
        'board_computer' => 'Yol Bilgisayarı', 'cruise_control' => 'Hız Sabitleme', 'blind_spot' => 'Kör Nokta Algılama',
        'electric_mirrors' => 'Elektrikli Aynalar', 'electric_parking_brake' => 'Elektronik El Freni',
        'high_beam_assist' => 'Uzun Far Asistanı', 'wireless_charging' => 'Kablosuz Şarj',
        'full_led_headlights' => 'Full LED Far', 'laser_light' => 'Lazer Far', 'leather' => 'Deri Döşeme',
        'sunroof_visor' => 'Güneşlik', 'multifunction_steering' => 'Çok Fonksiyonlu Direksiyon',
        'sunroof' => 'Açılır Tavan', 'parking_camera' => 'Park Kamerası', 'rain_sensor' => 'Yağmur Sensörü',
        'sound_system' => 'Ses Sistemi', 'voice_control' => 'Sesli Kontrol', 'start_stop_button' => 'Start/Stop Butonu',
        'heated_seats' => 'Isıtmalı Koltuk', 'towbar' => 'Çeki Demiri', 'traffic_sign_recognition' => 'Trafik İşareti Tanıma',
        'rear_heated_seats' => 'Arka Isıtmalı Koltuk', 'winter_package' => 'Kış Paketi',
        '4x4' => '4x4', 'rear_camera' => 'Geri Görüş Kamerası', 'airbags' => 'Hava Yastıkları',
        'android_auto' => 'Android Auto', 'autonomous_driving' => 'Otonom Sürüş', 'bluetooth' => 'Bluetooth',
        'central_locking' => 'Merkezi Kilit', 'roof_rails' => 'Tavan Rayları', 'esp' => 'ESP',
        'electric_windows' => 'Elektrikli Camlar', 'emergency_brake' => 'Acil Fren Asistanı',
        'head_up_display' => 'Head-up Display', 'isofix' => 'Isofix', 'lane_departure' => 'Şerit Takip Uyarısı',
        'led_daytime' => 'LED Gündüz Farı', 'alloy_wheels' => 'Hafif Alaşım Jant', 'metallic_paint' => 'Metalik Boya',
        'navigation' => 'Navigasyon', 'panoramic_roof' => 'Panoramik Tavan', 'parking_sensors' => 'Park Sensörü',
        'sliding_door' => 'Sürgülü Kapı', 'sport_package' => 'Spor Paket', 'auxiliary_heater' => 'Webasto',
        'seat_massage' => 'Koltuk Masajı', 'heated_steering' => 'Isıtmalı Direksiyon', 'usb' => 'USB',
        'fatigue_detection' => 'Yorgunluk Algılama', 'dash_cam' => 'Ön Kamera', 'xenon' => 'Xenon Far',
        'adapted_disabled' => 'Engelli Uyumlu', 'adaptive_lights' => 'Adaptif Far', 'airconditioning' => 'Klima',
        'apple_carplay' => 'Apple CarPlay', 'bidirectional_charging' => 'Çift Yönlü Şarj',
        'cornering_lights' => 'Viraj Farı', 'climate_control' => 'Klima Kontrolü', 'dab_radio' => 'Dijital Radyo',
        'electric_tailgate' => 'Elektrikli Bagaj', 'electric_seats' => 'Elektrikli Koltuk',
        'full_digital_cluster' => 'Dijital Gösterge', 'hill_hold' => 'Yokuş Kalkış', 'keyless_entry' => 'Anahtarsız Giriş',
        'lane_keeping' => 'Şerit Koruma', 'led_lighting' => 'LED Aydınlatma', 'light_sensor' => 'Işık Sensörü',
        'fog_lights' => 'Sis Farı', 'night_vision' => 'Gece Görüş', 'park_assist' => 'Park Asistanı',
        'radio' => 'Radyo', 'ski_hatch' => 'Kayak Geçişi', 'sport_seats' => 'Spor Koltuk',
        'start_stop_system' => 'Start-Stop Sistemi', 'seat_ventilation' => 'Koltuk Havalandırma',
        'traction_control' => 'Çekiş Kontrolü', 'auto_dimming_headlights' => 'Otomatik Kararan Far',
        'heated_mirrors' => 'Isıtmalı Ayna', 'heat_pump' => 'Isı Pompası'
    ],
    'nl' => [
        '360_camera' => '360° camera', 'abs' => 'ABS', 'adaptive_cruise_control' => 'Adaptive Cruise Control',
        'alarm' => 'Alarm', 'auto_climate' => 'Automatische klimaatregeling', 'bi_xenon' => 'Bi-Xenon koplampen',
        'board_computer' => 'Boordcomputer', 'cruise_control' => 'Cruise Control', 'blind_spot' => 'Dodehoekdetectie',
        'electric_mirrors' => 'Elektrische buitenspiegels', 'electric_parking_brake' => 'Elektronische parkeerrem',
        'high_beam_assist' => 'Grootlichtassistent', 'wireless_charging' => 'Inductieladen voor smartphones',
        'full_led_headlights' => 'Koplamp volledig LED', 'laser_light' => 'Laserlicht', 'leather' => 'Lederen bekleding',
        'sunroof_visor' => 'Luifel', 'multifunction_steering' => 'Multifunctioneel stuurwiel',
        'sunroof' => 'Open dak', 'parking_camera' => 'Parkeercamera', 'rain_sensor' => 'Regensensor',
        'sound_system' => 'Sound system', 'voice_control' => 'Spraakbediening', 'start_stop_button' => 'Startonderbreker',
        'heated_seats' => 'Stoelverwarming', 'towbar' => 'Trekhaak', 'traffic_sign_recognition' => 'Verkeersbordherkenning',
        'rear_heated_seats' => 'Verwarming stoelen achter', 'winter_package' => 'Winterpakket',
        '4x4' => '4x4', 'rear_camera' => 'Achteruitrijcamera', 'airbags' => 'Airbags',
        'android_auto' => 'Android Auto', 'autonomous_driving' => 'Autonomous Driving', 'bluetooth' => 'Bluetooth',
        'central_locking' => 'Centrale vergrendeling', 'roof_rails' => 'Dakrails', 'esp' => 'ESP',
        'electric_windows' => 'Elektrische ramen', 'emergency_brake' => 'Emergency brake assist',
        'head_up_display' => 'Head-up Display', 'isofix' => 'Isofix', 'lane_departure' => 'Lane Departure Warning',
        'led_daytime' => 'LED dagrijverlichting', 'alloy_wheels' => 'Lichtmetalen velgen', 'metallic_paint' => 'Metallic lak',
        'navigation' => 'Navigatiesysteem', 'panoramic_roof' => 'Panoramadak', 'parking_sensors' => 'Parkeersensor',
        'sliding_door' => 'Schuifdeur', 'sport_package' => 'Sportpakket', 'auxiliary_heater' => 'Standkachel',
        'seat_massage' => 'Stoelmassage', 'heated_steering' => 'Stuurwielverwarming', 'usb' => 'USB',
        'fatigue_detection' => 'Vermoeidheidsdetectie', 'dash_cam' => 'Vooruitrijcamera', 'xenon' => 'Xenon verlichting',
        'adapted_disabled' => 'Aangepast voor mindervaliden', 'adaptive_lights' => 'Adaptieve lichten',
        'airconditioning' => 'Airconditioning', 'apple_carplay' => 'Apple Carplay', 'bidirectional_charging' => 'Bi-directioneel laden',
        'cornering_lights' => 'Bochtverlichting', 'climate_control' => 'Climate control', 'dab_radio' => 'Digitale radio-ontvangst',
        'electric_tailgate' => 'Elektrische achterklep', 'electric_seats' => 'Elektrische stoelverstelling',
        'full_digital_cluster' => 'Geheel digitaal combi-instrument', 'hill_hold' => 'Hill-Hold Control',
        'keyless_entry' => 'Keyless entry', 'lane_keeping' => 'Lane Keeping Assist', 'led_lighting' => 'LED verlichting',
        'light_sensor' => 'Lichtsensor', 'fog_lights' => 'Mistlampen', 'night_vision' => 'Night view assist',
        'park_assist' => 'Parkeerassistent', 'radio' => 'Radio', 'ski_hatch' => 'Skiluik',
        'sport_seats' => 'Sportstoelen', 'start_stop_system' => 'Start-stop-systeem', 'seat_ventilation' => 'Stoelventilatie',
        'traction_control' => 'Traction-control', 'auto_dimming_headlights' => 'Verblindingsvrij grootlicht',
        'heated_mirrors' => 'Verwarmde buitenspiegels', 'heat_pump' => 'Warmtepomp'
    ],
    'en' => [
        '360_camera' => '360° Camera', 'abs' => 'ABS', 'adaptive_cruise_control' => 'Adaptive Cruise Control',
        'alarm' => 'Alarm', 'auto_climate' => 'Automatic Climate Control', 'bi_xenon' => 'Bi-Xenon Headlights',
        'board_computer' => 'Board Computer', 'cruise_control' => 'Cruise Control', 'blind_spot' => 'Blind Spot Detection',
        'electric_mirrors' => 'Electric Mirrors', 'electric_parking_brake' => 'Electronic Parking Brake',
        'high_beam_assist' => 'High Beam Assist', 'wireless_charging' => 'Wireless Charging',
        'full_led_headlights' => 'Full LED Headlights', 'laser_light' => 'Laser Light', 'leather' => 'Leather Upholstery',
        'sunroof_visor' => 'Sun Visor', 'multifunction_steering' => 'Multifunction Steering Wheel',
        'sunroof' => 'Sunroof', 'parking_camera' => 'Parking Camera', 'rain_sensor' => 'Rain Sensor',
        'sound_system' => 'Sound System', 'voice_control' => 'Voice Control', 'start_stop_button' => 'Start/Stop Button',
        'heated_seats' => 'Heated Seats', 'towbar' => 'Towbar', 'traffic_sign_recognition' => 'Traffic Sign Recognition',
        'rear_heated_seats' => 'Rear Heated Seats', 'winter_package' => 'Winter Package',
        '4x4' => '4x4', 'rear_camera' => 'Rear Camera', 'airbags' => 'Airbags',
        'android_auto' => 'Android Auto', 'autonomous_driving' => 'Autonomous Driving', 'bluetooth' => 'Bluetooth',
        'central_locking' => 'Central Locking', 'roof_rails' => 'Roof Rails', 'esp' => 'ESP',
        'electric_windows' => 'Electric Windows', 'emergency_brake' => 'Emergency Brake Assist',
        'head_up_display' => 'Head-up Display', 'isofix' => 'Isofix', 'lane_departure' => 'Lane Departure Warning',
        'led_daytime' => 'LED Daytime Lights', 'alloy_wheels' => 'Alloy Wheels', 'metallic_paint' => 'Metallic Paint',
        'navigation' => 'Navigation', 'panoramic_roof' => 'Panoramic Roof', 'parking_sensors' => 'Parking Sensors',
        'sliding_door' => 'Sliding Door', 'sport_package' => 'Sport Package', 'auxiliary_heater' => 'Auxiliary Heater',
        'seat_massage' => 'Seat Massage', 'heated_steering' => 'Heated Steering Wheel', 'usb' => 'USB',
        'fatigue_detection' => 'Fatigue Detection', 'dash_cam' => 'Dash Cam', 'xenon' => 'Xenon Lights',
        'adapted_disabled' => 'Adapted for Disabled', 'adaptive_lights' => 'Adaptive Lights',
        'airconditioning' => 'Air Conditioning', 'apple_carplay' => 'Apple CarPlay', 'bidirectional_charging' => 'Bidirectional Charging',
        'cornering_lights' => 'Cornering Lights', 'climate_control' => 'Climate Control', 'dab_radio' => 'DAB Radio',
        'electric_tailgate' => 'Electric Tailgate', 'electric_seats' => 'Electric Seats',
        'full_digital_cluster' => 'Full Digital Cluster', 'hill_hold' => 'Hill Hold Control',
        'keyless_entry' => 'Keyless Entry', 'lane_keeping' => 'Lane Keeping Assist', 'led_lighting' => 'LED Lighting',
        'light_sensor' => 'Light Sensor', 'fog_lights' => 'Fog Lights', 'night_vision' => 'Night Vision',
        'park_assist' => 'Park Assist', 'radio' => 'Radio', 'ski_hatch' => 'Ski Hatch',
        'sport_seats' => 'Sport Seats', 'start_stop_system' => 'Start-Stop System', 'seat_ventilation' => 'Seat Ventilation',
        'traction_control' => 'Traction Control', 'auto_dimming_headlights' => 'Auto-Dimming Headlights',
        'heated_mirrors' => 'Heated Mirrors', 'heat_pump' => 'Heat Pump'
    ]
];

// Get countries
$countries = [];
$res = $db->query("SELECT id, name_{$lang} as name FROM countries WHERE is_active = 1 ORDER BY sort_order ASC");
while ($row = $res->fetch_assoc()) $countries[] = $row;

// Handle form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_temp_image'])) {
        $removeIdx = (int)$_POST['remove_temp_image'];
        if (isset($_SESSION['preview_listing']['images'][$removeIdx])) {
            $imgPath = $_SESSION['preview_listing']['images'][$removeIdx]['path'];
            if (file_exists($imgPath)) unlink($imgPath);
            array_splice($_SESSION['preview_listing']['images'], $removeIdx, 1);
        }
        header('Location: ' . BASE_URL . '/pages/post-vehicle.php');
        exit;
    }

    $required = ['vehicle_type', 'brand', 'model', 'build_year', 'title', 'price', 'price_type', 'country_id', 'city_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = $tr['required'];
        }
    }

    if (empty($errors)) {
        $_SESSION['preview_listing'] = [
            'vehicle_type' => $_POST['vehicle_type'] ?? 'auto',
            'brand' => $_POST['brand'] ?? '',
            'model' => $_POST['model'] ?? '',
            'build_year' => $_POST['build_year'] ?? '',
            'fuel_type' => $_POST['fuel_type'] ?? '',
            'power_hp' => $_POST['power_hp'] ?? '',
            'color' => $_POST['color'] ?? '',
            'doors' => $_POST['doors'] ?? '',
            'apk_tot' => $_POST['apk_tot'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? '',
            'price_type' => $_POST['price_type'] ?? '',
            'mileage_km' => $_POST['mileage_km'] ?? '',
            'transmission' => $_POST['transmission'] ?? '',
            'country_id' => $_POST['country_id'] ?? '',
            'city_id' => $_POST['city_id'] ?? '',
            'address' => $_POST['address'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'features' => $_POST['features'] ?? [],
            'maintenance' => $_POST['maintenance'] ?? [],
            'btw' => isset($_POST['btw']) ? 1 : 0,
            'listing_plan' => $_POST['listing_plan'] ?? 'free',
            'lang' => $lang,
        ];

        $oldSessionImages = json_decode($_POST['existing_images_json'] ?? '[]', true) ?: [];

        $newImages = [];
        if (!empty($_FILES['images']['name'][0])) {
            $temp_dir = __DIR__ . '/../uploads/temp/' . session_id() . '/';
            if (!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);

            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            $currentCount = count($oldSessionImages);

            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (!in_array($_FILES['images']['type'][$i], $allowed_types)) continue;
                if ($_FILES['images']['size'][$i] > $max_size) continue;
                if (($currentCount + count($newImages)) >= 10) break;

                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('tmp_') . '.' . $ext;
                $filepath = $temp_dir . $filename;

                if (move_uploaded_file($tmp, $filepath)) {
                    $newImages[] = [
                        'path' => $filepath,
                        'filename' => $filename,
                        'original_name' => $_FILES['images']['name'][$i],
                    ];
                }
            }
        }

        $_SESSION['preview_listing']['images'] = array_merge($oldSessionImages, $newImages);

        header('Location: ' . BASE_URL . '/pages/preview-listing.php');
        exit;
    }
}

$prefill = $_SESSION['preview_listing'] ?? [];
$existingTempImages = $prefill['images'] ?? [];
?>
<?php $pageTitle = $tr['page_title'] . ' - AvrupaPazari'; ?>
<?php $pageStyles = ['post-vehicle.css']; require __DIR__ . '/../includes/header.php'; ?>

<div class="page-wrapper">
    <div class="page-header">
        <h1><?= $tr['page_title'] ?></h1>
    </div>

    <div class="no-data-warning" id="noDataWarning"><p><?= $tr['no_data'] ?></p></div>

    <?php if (!empty($errors['general'])): ?>
    <div style="background: var(--error-bg); border: 1px solid oklch(80% 0.06 25); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px; color: oklch(40% 0.12 25); font-size: 0.85rem; font-weight: 500;"><?= $errors['general'] ?></div>
    <?php endif; ?>

    <div class="vehicle-summary" id="vehicleSummary" style="display:none;">
        <div class="vehicle-summary-header">
            <h2 id="summaryTitle"></h2>
            <span class="rdw-verified" id="rdwBadge" style="display:none;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                RDW
            </span>
        </div>
        <div class="vehicle-summary-grid" id="summaryGrid"></div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="listingForm" novalidate>
        <input type="hidden" name="vehicle_type" id="h_vehicle_type" value="<?= htmlspecialchars($prefill['vehicle_type'] ?? '') ?>">
        <input type="hidden" name="brand" id="h_brand" value="<?= htmlspecialchars($prefill['brand'] ?? '') ?>">
        <input type="hidden" name="model" id="h_model" value="<?= htmlspecialchars($prefill['model'] ?? '') ?>">
        <input type="hidden" name="build_year" id="h_build_year" value="<?= htmlspecialchars($prefill['build_year'] ?? '') ?>">
        <input type="hidden" name="fuel_type" id="h_fuel_type" value="<?= htmlspecialchars($prefill['fuel_type'] ?? '') ?>">
        <input type="hidden" name="power_hp" id="h_power_hp" value="<?= htmlspecialchars($prefill['power_hp'] ?? '') ?>">
        <input type="hidden" name="color" id="h_color" value="<?= htmlspecialchars($prefill['color'] ?? '') ?>">
        <input type="hidden" name="doors" id="h_doors" value="<?= htmlspecialchars($prefill['doors'] ?? '') ?>">
        <input type="hidden" name="apk_tot" id="h_apk_tot" value="<?= htmlspecialchars($prefill['apk_tot'] ?? '') ?>">
        <input type="hidden" name="country_id" id="h_country_id" value="<?= htmlspecialchars($prefill['country_id'] ?? '') ?>">
        <input type="hidden" name="city_id" id="h_city_id" value="<?= htmlspecialchars($prefill['city_id'] ?? '') ?>">
        <input type="hidden" name="keep_existing_images" value="<?= !empty($existingTempImages) ? '1' : '0' ?>">
        <input type="hidden" name="existing_images_json" id="existingImagesJson" value="<?= htmlspecialchars(json_encode($existingTempImages)) ?>">

        <div class="form-section">
            <h3 class="form-section-title"><?= $tr['listing_info'] ?></h3>
            <div class="form-grid">
                <div class="form-group full-width <?= isset($errors['title']) ? 'has-error' : '' ?>">
                    <label><?= $tr['title'] ?> <span class="required">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required placeholder="<?= $tr['title_placeholder'] ?>" value="<?= htmlspecialchars($prefill['title'] ?? $_POST['title'] ?? '') ?>">
                    <span class="form-error"><?= $errors['title'] ?? $tr['required'] ?></span>
                </div>
                <div class="form-group full-width">
                    <label><?= $tr['description'] ?></label>
                    <textarea name="description" id="description" class="form-control" placeholder="<?= $tr['desc_placeholder'] ?>"><?= htmlspecialchars($prefill['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group <?= isset($errors['price']) ? 'has-error' : '' ?>">
                    <label><?= $tr['price'] ?> <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" name="price" id="price" class="form-control" required placeholder="0" value="<?= htmlspecialchars($prefill['price'] ?? $_POST['price'] ?? '') ?>">
                    <span class="form-error"><?= $errors['price'] ?? $tr['required'] ?></span>
                </div>
                <div class="form-group <?= isset($errors['price_type']) ? 'has-error' : '' ?>">
                    <label><?= $tr['price_type'] ?> <span class="required">*</span></label>
                    <select name="price_type" id="price_type" class="form-control" required>
                        <option value=""><?= $tr['select'] ?></option>
                        <option value="fixed" <?= ($prefill['price_type'] ?? '') === 'fixed' ? 'selected' : '' ?>><?= $tr['fixed'] ?></option>
                        <option value="negotiable" <?= ($prefill['price_type'] ?? '') === 'negotiable' ? 'selected' : '' ?>><?= $tr['negotiable'] ?></option>
                        <option value="free" <?= ($prefill['price_type'] ?? '') === 'free' ? 'selected' : '' ?>><?= $tr['free'] ?></option>
                        <option value="exchange" <?= ($prefill['price_type'] ?? '') === 'exchange' ? 'selected' : '' ?>><?= $tr['exchange'] ?></option>
                        <option value="offer" <?= ($prefill['price_type'] ?? '') === 'offer' ? 'selected' : '' ?>><?= $lang === 'tr' ? 'Teklif Verin' : ($lang === 'nl' ? 'Bieden' : 'Make an Offer') ?></option>
                    </select>
                    <span class="form-error"><?= $errors['price_type'] ?? $tr['required'] ?></span>
                </div>
                <div class="form-group">
                    <label><?= $tr['mileage'] ?></label>
                    <input type="text" inputmode="numeric" name="mileage_km" id="mileage_km" class="form-control" placeholder="0" value="<?= htmlspecialchars($prefill['mileage_km'] ?? $_POST['mileage_km'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= $tr['transmission'] ?></label>
                    <select name="transmission" id="transmission" class="form-control">
                        <option value=""><?= $tr['select'] ?></option>
                        <option value="handgeschakeld" <?= ($prefill['transmission'] ?? '') === 'handgeschakeld' ? 'selected' : '' ?>><?= $tr['handgeschakeld'] ?></option>
                        <option value="automaat" <?= ($prefill['transmission'] ?? '') === 'automaat' ? 'selected' : '' ?>><?= $tr['automaat'] ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= $tr['location'] ?> <span class="rdw-badge"><?= $tr['from_modal'] ?></span></h3>
            <div class="form-grid">
                <div class="form-group">
                    <label><?= $tr['country'] ?> <span class="required">*</span></label>
                    <select name="country_id_display" id="country_id_display" class="form-control prefilled" required>
                        <option value=""><?= $tr['select'] ?></option>
                        <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($prefill['country_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $tr['city'] ?> <span class="required">*</span></label>
                    <select name="city_id_display" id="city_id_display" class="form-control prefilled" required>
                        <option value=""><?= $tr['select'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $tr['address'] ?></label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($prefill['address'] ?? $_POST['address'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= $tr['postal_code'] ?></label>
                    <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($prefill['postal_code'] ?? $_POST['postal_code'] ?? '') ?>" maxlength="10">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= $tr['features'] ?></h3>
            <div class="features-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="featureSearch" placeholder="<?= $lang === 'tr' ? 'Özellik ara...' : ($lang === 'nl' ? 'Zoek optie...' : 'Search feature...') ?>">
            </div>
            <div class="features-category">
                <div class="features-category-title"><?= $tr['features_options'] ?></div>
                <div class="features-grid" id="featuresGrid">
                    <?php
                    $currentLabels = $featuresLabels[$lang];
                    $checkedFeatures = $prefill['features'] ?? [];
                    foreach ($featuresList as $key):
                        $label = $currentLabels[$key] ?? $key;
                        $isChecked = in_array($key, $checkedFeatures) ? 'checked' : '';
                    ?>
                    <div class="feature-item" data-search="<?= strtolower($label) ?>">
                        <input type="checkbox" name="features[]" value="<?= $key ?>" id="f_<?= $key ?>" <?= $isChecked ?>>
                        <label for="f_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="features-category">
                <div class="features-category-title"><?= $tr['features_maintenance'] ?></div>
                <div class="features-grid">
                    <div class="feature-item">
                        <input type="checkbox" name="maintenance[]" value="dealer_maintained" id="f_dealer" <?= in_array('dealer_maintained', $prefill['maintenance'] ?? []) ? 'checked' : '' ?>>
                        <label for="f_dealer"><?= $tr['dealer_maintained'] ?></label>
                    </div>
                    <div class="feature-item">
                        <input type="checkbox" name="maintenance[]" value="maintenance_book" id="f_book" <?= in_array('maintenance_book', $prefill['maintenance'] ?? []) ? 'checked' : '' ?>>
                        <label for="f_book"><?= $tr['maintenance_book'] ?></label>
                    </div>
                </div>
            </div>
            <div class="features-category">
                <div class="features-category-title"><?= $tr['features_tax'] ?></div>
                <div class="features-grid">
                    <div class="feature-item">
                        <input type="checkbox" name="btw" value="1" id="f_btw" <?= !empty($prefill['btw']) ? 'checked' : '' ?>>
                        <label for="f_btw"><?= $tr['btw_deductible'] ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= $tr['images'] ?></h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;"><?= $tr['images_desc'] ?></p>

            <?php if (!empty($existingTempImages)): ?>
            <div class="existing-images-section">
                <div class="existing-images-title"><?= $tr['existing_images'] ?> (<?= count($existingTempImages) ?>)</div>
                <div class="existing-images-grid">
                    <?php foreach ($existingTempImages as $idx => $img): ?>
                    <div class="existing-img-item">
                        <img src="<?= htmlspecialchars($img['path']) ?>" alt="">
                        <button type="button" class="remove-existing" onclick="removeExistingImage(<?= $idx ?>)">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="upload-zone" id="uploadZone">
                <div class="upload-zone-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div class="upload-zone-text"><?= $tr['drag_drop'] ?></div>
                <div class="upload-zone-hint"><?= $tr['formats'] ?></div>
                <input type="file" name="images[]" id="imageInput" multiple accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="image-previews" id="imagePreviews"></div>
        </div>

        <div class="submit-section">
            <button type="submit" class="btn-submit"><?= $tr['submit'] ?></button>
        </div>
    </form>
</div>

<script>
function removeExistingImage(idx) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    var input = document.createElement('input');
    input.name = 'remove_temp_image';
    input.value = idx;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    var lang = '<?= $lang ?>';
    var labels = {
        tr: { brand: 'Marka', model: 'Model', year: 'Yıl', color: 'Renk', fuel: 'Yakıt', power: 'Güç', doors: 'Kapı', apk: 'APK' },
        nl: { brand: 'Merk', model: 'Model', year: 'Bouwjaar', color: 'Kleur', fuel: 'Brandstof', power: 'Vermogen', doors: 'Deuren', apk: 'APK' },
        en: { brand: 'Brand', model: 'Model', year: 'Year', color: 'Color', fuel: 'Fuel', power: 'Power', doors: 'Doors', apk: 'APK' }
    };
    var lbl = labels[lang] || labels['tr'];
    var hasPrefill = '<?= !empty($prefill['brand']) ? '1' : '0' ?>';
    var existingCount = <?= count($existingTempImages) ?>;
    var maxNew = 10 - existingCount;

    var raw = localStorage.getItem('vehicleData');
    if (!raw && hasPrefill === '0') { document.getElementById('noDataWarning').style.display = 'block'; return; }

    var data;
    if (hasPrefill === '1') {
        data = {
            brand: '<?= addslashes($prefill['brand'] ?? '') ?>',
            model: '<?= addslashes($prefill['model'] ?? '') ?>',
            build_year: '<?= addslashes($prefill['build_year'] ?? '') ?>',
            color: '<?= addslashes($prefill['color'] ?? '') ?>',
            fuel_type: '<?= addslashes($prefill['fuel_type'] ?? '') ?>',
            power_hp: '<?= addslashes($prefill['power_hp'] ?? '') ?>',
            doors: '<?= addslashes($prefill['doors'] ?? '') ?>',
            apk_tot: '<?= addslashes($prefill['apk_tot'] ?? '') ?>',
            vehicle_type: '<?= addslashes($prefill['vehicle_type'] ?? '') ?>',
            country_id: '<?= addslashes($prefill['country_id'] ?? '') ?>',
            city_id: '<?= addslashes($prefill['city_id'] ?? '') ?>',
            rdw_verified: false
        };
    } else {
        try { data = JSON.parse(raw); } catch(e) { document.getElementById('noDataWarning').style.display = 'block'; return; }
    }

    var summary = document.getElementById('vehicleSummary');
    summary.style.display = 'block';
    var brand = data.brand || '', model = data.model || '', year = data.build_year || '';
    document.getElementById('summaryTitle').textContent = (brand + ' ' + model + (year ? ' (' + year + ')' : '')).trim();
    if (data.rdw_verified) document.getElementById('rdwBadge').style.display = 'inline-flex';

    var grid = document.getElementById('summaryGrid');
    [{label:lbl.brand,value:brand},{label:lbl.model,value:model},{label:lbl.year,value:year},{label:lbl.color,value:data.color},{label:lbl.fuel,value:data.fuel_display || data.fuel_type},{label:lbl.power,value:data.power_hp?data.power_hp+' HP':''},{label:lbl.doors,value:data.doors},{label:lbl.apk,value:data.apk_tot}].forEach(function(f) {
        if (!f.value) return;
        var item = document.createElement('div');
        item.className = 'summary-item';
        item.innerHTML = '<div class="summary-item-label">'+f.label+'</div><div class="summary-item-value">'+f.value+'</div>';
        grid.appendChild(item);
    });

    if (hasPrefill === '0') {
        document.getElementById('h_vehicle_type').value = data.vehicle_type || 'auto';
        document.getElementById('h_brand').value = brand;
        document.getElementById('h_model').value = model;
        document.getElementById('h_build_year').value = year;
        document.getElementById('h_fuel_type').value = data.fuel_type || '';
        document.getElementById('h_power_hp').value = data.power_hp || '';
        document.getElementById('h_color').value = data.color || '';
        document.getElementById('h_doors').value = data.doors || '';
        document.getElementById('h_apk_tot').value = data.apk_tot || '';
        document.getElementById('h_country_id').value = data.country_id || '';
        document.getElementById('h_city_id').value = data.city_id || '';
    }

    var titleField = document.getElementById('title');
    if (!titleField.value && brand && model) titleField.value = (year + ' ' + brand + ' ' + model).trim();

    var countrySelect = document.getElementById('country_id_display');
    var citySelect = document.getElementById('city_id_display');

    var countryId = data.country_id || countrySelect.value;
    var cityId = data.city_id || '<?= addslashes($prefill['city_id'] ?? '') ?>';

    if (countryId) {
        countrySelect.value = countryId;
        document.getElementById('h_country_id').value = countryId;
        loadCities(countryId, cityId);
    }

    countrySelect.addEventListener('change', function() { document.getElementById('h_country_id').value = this.value; loadCities(this.value, null); });
    citySelect.addEventListener('change', function() { document.getElementById('h_city_id').value = this.value; });

    function loadCities(countryId, preselect) {
        citySelect.innerHTML = '<option value=""><?= $tr['select'] ?></option>';
        if (!countryId) return;
        fetch('<?= BASE_URL ?>/api/get-cities.php?country_id=' + countryId)
            .then(function(res) { return res.json(); })
            .then(function(cities) {
                if (!Array.isArray(cities)) return;
                cities.forEach(function(city) {
                    var opt = document.createElement('option');
                    opt.value = city.id;
                    opt.textContent = city.name;
                    if (preselect && (city.id == preselect || city.name == preselect)) opt.selected = true;
                    citySelect.appendChild(opt);
                });
            }).catch(function(e) { console.error(e); });
    }

    // Feature search
    document.getElementById('featureSearch').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#featuresGrid .feature-item').forEach(function(item) {
            item.style.display = item.dataset.search.includes(q) ? '' : 'none';
        });
    });

    // Image upload
    var uploadZone = document.getElementById('uploadZone');
    var imageInput = document.getElementById('imageInput');
    var previews = document.getElementById('imagePreviews');
    var selectedFiles = [];

    uploadZone.addEventListener('dragover', function(e) { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', function() { uploadZone.classList.remove('dragover'); });
    uploadZone.addEventListener('drop', function(e) { e.preventDefault(); uploadZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
    imageInput.addEventListener('change', function() { handleFiles(this.files); });

    function handleFiles(files) {
        for (var i = 0; i < files.length; i++) {
            if (selectedFiles.length >= maxNew) break;
            var file = files[i];
            if (['image/jpeg','image/png','image/webp'].indexOf(file.type) === -1) continue;
            if (file.size > 5*1024*1024) continue;
            selectedFiles.push(file);
        }
        renderPreviews(); updateFileInput();
    }

    function renderPreviews() {
        previews.innerHTML = '';
        selectedFiles.forEach(function(file, idx) {
            var item = document.createElement('div'); item.className = 'image-preview-item';
            var img = document.createElement('img'); img.src = URL.createObjectURL(file);
            var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'remove-btn'; btn.innerHTML = '✕';
            btn.onclick = function() { selectedFiles.splice(idx, 1); renderPreviews(); updateFileInput(); };
            item.appendChild(img); item.appendChild(btn); previews.appendChild(item);
        });
    }

    function updateFileInput() {
        var dt = new DataTransfer();
        selectedFiles.forEach(function(f) { dt.items.add(f); });
        imageInput.files = dt.files;
    }

    // Validation
    document.getElementById('listingForm').addEventListener('submit', function(e) {
        var hasError = false;
        this.querySelectorAll('[required]').forEach(function(field) {
            var group = field.closest('.form-group');
            if (!field.value.trim()) { group.classList.add('has-error'); hasError = true; }
            else { group.classList.remove('has-error'); }
        });
        document.getElementById('h_country_id').value = countrySelect.value;
        document.getElementById('h_city_id').value = citySelect.value;
        if (!countrySelect.value) { countrySelect.closest('.form-group').classList.add('has-error'); hasError = true; }
        if (!citySelect.value) { citySelect.closest('.form-group').classList.add('has-error'); hasError = true; }
        if (hasError) { e.preventDefault(); var first = this.querySelector('.form-group.has-error'); if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    });

    document.querySelectorAll('.form-control').forEach(function(el) {
        el.addEventListener('input', function() { if(this.closest('.form-group')) this.closest('.form-group').classList.remove('has-error'); });
        el.addEventListener('change', function() { if(this.closest('.form-group')) this.closest('.form-group').classList.remove('has-error'); });
    });

    // ★ FIX: Fiyat ve KM sadece rakam kabul etsin
    ['price', 'mileage_km'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        el.addEventListener('paste', function(e) {
            setTimeout(function() {
                el.value = el.value.replace(/[^0-9]/g, '');
            }, 0);
        });
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
