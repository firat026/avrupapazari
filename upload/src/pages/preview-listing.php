<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/giris.php');
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->set_charset('utf8mb4');

// Language
$lang = $_SESSION['lang'] ?? 'tr';
$allowed_langs = ['tr', 'nl', 'en'];
if (!in_array($lang, $allowed_langs)) $lang = 'tr';

// Check session data
if (empty($_SESSION['preview_listing'])) {
    header('Location: ' . BASE_URL . '/pages/post-vehicle.php');
    exit;
}

$data = $_SESSION['preview_listing'];

// Format description: clean up excessive whitespace
function formatDescription($text) {
    if (empty($text)) return '';
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    // Trim each line
    $lines = explode("\n", $text);
    $lines = array_map('trim', $lines);
    $text = implode("\n", $lines);
    // Collapse 3+ blank lines into 2 (one visible gap between paragraphs)
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    $text = trim($text);
    // Split into paragraphs
    $paragraphs = preg_split('/\n\n/', $text);
    $html = '';
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;
        // Check if this paragraph looks like a list (short lines)
        $paraLines = explode("\n", $para);
        if (count($paraLines) > 2 && isListParagraph($paraLines)) {
            $html .= '<ul class="desc-list">';
            foreach ($paraLines as $line) {
                $line = ltrim($line, '•-*· ');
                $line = trim($line);
                if (!empty($line)) {
                    $html .= '<li>' . htmlspecialchars($line) . '</li>';
                }
            }
            $html .= '</ul>';
        } else {
            $paraHtml = nl2br(htmlspecialchars($para));
            $html .= '<p>' . $paraHtml . '</p>';
        }
    }
    return $html;
}

function isListParagraph($lines) {
    $shortCount = 0;
    $bulletCount = 0;
    $total = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $total++;
        if (mb_strlen($line) < 80) $shortCount++;
        if (preg_match('/^[•\-\*·]/', $line)) $bulletCount++;
    }
    if ($total === 0) return false;
    return ($bulletCount / $total > 0.5) || ($shortCount / $total > 0.8 && $total > 3);
}

// Translations
$t = [
    'tr' => [
        'page_title' => 'İlan Önizleme',
        'preview_badge' => 'Önizleme',
        'preview_desc' => 'İlanınız yayınlandığında böyle görünecek. Bilgileri kontrol edin.',
        'edit' => 'Düzelt',
        'confirm' => 'Onayla ve Yayınla',
        'vehicle_info' => 'Araç Bilgileri',
        'description' => 'Açıklama',
        'features' => 'Özellikler',
        'location' => 'Konum',
        'price_label' => 'Fiyat',
        'mileage' => 'Kilometre',
        'transmission' => 'Vites',
        'fuel' => 'Yakıt',
        'power' => 'Güç',
        'color' => 'Renk',
        'doors' => 'Kapı',
        'year' => 'Yıl',
        'apk' => 'APK Bitiş',
        'brand' => 'Marka',
        'model' => 'Model',
        'fixed' => 'Sabit Fiyat',
        'negotiable' => 'Pazarlıklı',
        'exchange' => 'Takas',
        'free' => 'Ücretsiz',
        'offer' => 'Teklif Verin',
        'handgeschakeld' => 'Manuel',
        'automaat' => 'Otomatik',
        'pending_note' => 'Admin onayı sonrası yayınlanacaktır.',
        'success_title' => 'Başarılı!',
        'success_msg' => 'İlanınız başarıyla oluşturuldu! Onay sonrası yayınlanacaktır.',
        'redirecting' => 'Anasayfaya yönlendiriliyorsunuz...',
        'no_description' => 'Açıklama eklenmedi.',
        'no_features' => 'Özellik seçilmedi.',
        'photos' => 'Fotoğraflar',
        'no_photos' => 'Fotoğraf yüklenmedi.',
        'maintenance' => 'Bakım',
        'dealer_maintained' => 'Yetkili servis bakımlı',
        'maintenance_book' => 'Bakım defteri mevcut',
        'btw_deductible' => 'KDV düşülebilir',
    ],
    'nl' => [
        'page_title' => 'Advertentie Voorbeeld',
        'preview_badge' => 'Voorbeeld',
        'preview_desc' => 'Zo ziet uw advertentie eruit na publicatie. Controleer de gegevens.',
        'edit' => 'Bewerken',
        'confirm' => 'Bevestigen & Plaatsen',
        'vehicle_info' => 'Voertuiggegevens',
        'description' => 'Beschrijving',
        'features' => 'Opties',
        'location' => 'Locatie',
        'price_label' => 'Prijs',
        'mileage' => 'Kilometerstand',
        'transmission' => 'Transmissie',
        'fuel' => 'Brandstof',
        'power' => 'Vermogen',
        'color' => 'Kleur',
        'doors' => 'Deuren',
        'year' => 'Bouwjaar',
        'apk' => 'APK tot',
        'brand' => 'Merk',
        'model' => 'Model',
        'fixed' => 'Vaste Prijs',
        'negotiable' => 'Onderhandelbaar',
        'exchange' => 'Ruilen',
        'free' => 'Gratis',
        'offer' => 'Bieden',
        'handgeschakeld' => 'Handgeschakeld',
        'automaat' => 'Automaat',
        'pending_note' => 'Na goedkeuring wordt deze gepubliceerd.',
        'success_title' => 'Gelukt!',
        'success_msg' => 'Uw advertentie is succesvol aangemaakt! Na goedkeuring wordt deze gepubliceerd.',
        'redirecting' => 'U wordt doorgestuurd naar de homepagina...',
        'no_description' => 'Geen beschrijving toegevoegd.',
        'no_features' => 'Geen opties geselecteerd.',
        'photos' => 'Foto\'s',
        'no_photos' => 'Geen foto\'s geüpload.',
        'maintenance' => 'Onderhoud',
        'dealer_maintained' => 'Dealer onderhouden',
        'maintenance_book' => 'Onderhoudsboekje',
        'btw_deductible' => 'BTW verrekenbaar',
    ],
    'en' => [
        'page_title' => 'Listing Preview',
        'preview_badge' => 'Preview',
        'preview_desc' => 'This is how your listing will look once published. Review the details.',
        'edit' => 'Edit',
        'confirm' => 'Confirm & Publish',
        'vehicle_info' => 'Vehicle Information',
        'description' => 'Description',
        'features' => 'Features',
        'location' => 'Location',
        'price_label' => 'Price',
        'mileage' => 'Mileage',
        'transmission' => 'Transmission',
        'fuel' => 'Fuel',
        'power' => 'Power',
        'color' => 'Color',
        'doors' => 'Doors',
        'year' => 'Year',
        'apk' => 'APK until',
        'brand' => 'Brand',
        'model' => 'Model',
        'fixed' => 'Fixed Price',
        'negotiable' => 'Negotiable',
        'exchange' => 'Exchange',
        'free' => 'Free',
        'offer' => 'Make an Offer',
        'handgeschakeld' => 'Manual',
        'automaat' => 'Automatic',
        'pending_note' => 'Will be published after admin approval.',
        'success_title' => 'Success!',
        'success_msg' => 'Your listing has been created successfully! It will be published after approval.',
        'redirecting' => 'Redirecting to homepage...',
        'no_description' => 'No description added.',
        'no_features' => 'No features selected.',
        'photos' => 'Photos',
        'no_photos' => 'No photos uploaded.',
        'maintenance' => 'Maintenance',
        'dealer_maintained' => 'Dealer maintained',
        'maintenance_book' => 'Service book available',
        'btw_deductible' => 'VAT deductible',
    ],
];

$tr = $t[$lang];

// Features labels
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
        'high_beam_assist' => 'Grootlichtassistent', 'wireless_charging' => 'Inductieladen',
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
        'cornering_lights' => 'Bochtverlichting', 'climate_control' => 'Climate control', 'dab_radio' => 'Digitale radio',
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

$currentLabels = $featuresLabels[$lang];

// Get country and city names
$countryName = '';
$cityName = '';
if (!empty($data['country_id'])) {
    $stmt = $db->prepare("SELECT name_{$lang} as name FROM countries WHERE id = ?");
    $stmt->bind_param('i', $data['country_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $countryName = $row['name'];
}
if (!empty($data['city_id'])) {
    $stmt = $db->prepare("SELECT name FROM cities WHERE id = ?");
    $stmt->bind_param('i', $data['city_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $cityName = $row['name'];
}

// Price type label
$priceTypeLabels = [
    'fixed' => $tr['fixed'],
    'negotiable' => $tr['negotiable'],
    'exchange' => $tr['exchange'],
    'free' => $tr['free'],
    'offer' => $tr['offer'],
];
$priceTypeLabel = $priceTypeLabels[$data['price_type']] ?? $data['price_type'];

// Transmission label
$transLabels = ['handgeschakeld' => $tr['handgeschakeld'], 'automaat' => $tr['automaat']];
$transLabel = $transLabels[$data['transmission']] ?? $data['transmission'];

// Handle confirmation (DB insert)
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm') {
    $db->begin_transaction();
    try {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($data['title'])));
        $slug = trim($slug, '-') . '-' . uniqid();

        $stmt = $db->prepare("INSERT INTO listings (user_id, category_id, module, title, slug, description, price, price_type, country_id, city_id, address, postal_code, status, lang, created_at) VALUES (?, NULL, 'arac', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
        $stmt->bind_param('isssdsissss',
            $_SESSION['user_id'],
            $data['title'],
            $slug,
            $data['description'],
            $data['price'],
            $data['price_type'],
            $data['country_id'],
            $data['city_id'],
            $data['address'],
            $data['postal_code'],
            $data['lang']
        );
        $stmt->execute();
        $listing_id = $db->insert_id;

        // Handle listing plan
        $plan = $data['listing_plan'] ?? 'free';
        if ($plan === 'plus') {
            $db->query("UPDATE listings SET is_featured = 1 WHERE id = {$listing_id}");
        } elseif ($plan === 'premium') {
            $db->query("UPDATE listings SET is_premium = 1, is_featured = 1 WHERE id = {$listing_id}");
        }

        // Features as JSON
        $btw = !empty($data['btw']) ? true : false;
        $allFeatures = [];
        if (!empty($data['features'])) $allFeatures['options'] = $data['features'];
        if (!empty($data['maintenance'])) $allFeatures['maintenance'] = $data['maintenance'];
        if ($btw) $allFeatures['btw'] = true;
        $featuresJson = !empty($allFeatures) ? json_encode($allFeatures) : null;

        $apk = !empty($data['apk_tot']) ? $data['apk_tot'] : null;
        $mileage = !empty($data['mileage_km']) ? (int)$data['mileage_km'] : null;
        $power = !empty($data['power_hp']) ? (int)$data['power_hp'] : null;
        $doors = !empty($data['doors']) ? (int)$data['doors'] : null;

        $stmt2 = $db->prepare("INSERT INTO listing_arac (listing_id, vehicle_type, brand, model, build_year, mileage_km, fuel_type, transmission, power_hp, color, doors, apk_tot, features) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param('isssisssisiss',
            $listing_id,
            $data['vehicle_type'],
            $data['brand'],
            $data['model'],
            $data['build_year'],
            $mileage,
            $data['fuel_type'],
            $data['transmission'],
            $power,
            $data['color'],
            $doors,
            $apk,
            $featuresJson
        );
        $stmt2->execute();

        // Move images from temp to final folder
        if (!empty($data['images'])) {
            $final_dir = __DIR__ . '/../uploads/listings/' . $listing_id . '/'
            if (!is_dir($final_dir)) mkdir($final_dir, 0755, true);

            $sort = 0;
            foreach ($data['images'] as $img) {
                if (!file_exists($img['path'])) continue;

                $ext = pathinfo($img['filename'], PATHINFO_EXTENSION);
                $newFilename = uniqid('img_') . '.' . $ext;
                $newPath = $final_dir . $newFilename;

                if (rename($img['path'], $newPath)) {
                    $stmt3 = $db->prepare("INSERT INTO listing_images (listing_id, filename, sort_order, is_cover) VALUES (?, ?, ?, ?)");
                    $isCover = ($sort === 0) ? 1 : 0;
                    $stmt3->bind_param('isis', $listing_id, $newPath, $sort, $isCover);
                    $stmt3->execute();

                    if ($sort === 0) {
                        $db->query("UPDATE listings SET image = '" . $db->real_escape_string($newFilename) . "' WHERE id = " . $listing_id);
                    }
                    $sort++;
                }
            }

            // Clean up temp directory
            $temp_dir = __DIR__ . '/../uploads/temp/' . session_id() . '/';
            if (is_dir($temp_dir)) {
                $remaining = glob($temp_dir . '*');
                foreach ($remaining as $f) { if (is_file($f)) unlink($f); }
                @rmdir($temp_dir);
            }
        }

        $db->commit();
        $success = true;

        // Clear session preview data
        unset($_SESSION['preview_listing']);

    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}
?>
<?php $pageTitle = $tr['page_title'] . ' - AvrupaPazari'; ?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --primary: oklch(55% 0.18 250);
    --primary-light: oklch(65% 0.14 250);
    --primary-dark: oklch(45% 0.2 250);
    --primary-bg: oklch(96% 0.02 250);
    --surface: oklch(99% 0.005 250);
    --surface-2: oklch(97% 0.008 250);
    --surface-3: oklch(94% 0.01 250);
    --text: oklch(20% 0.015 250);
    --text-secondary: oklch(45% 0.01 250);
    --text-muted: oklch(60% 0.008 250);
    --border: oklch(88% 0.012 250);
    --border-focus: oklch(55% 0.18 250);
    --success: oklch(55% 0.15 155);
    --success-bg: oklch(95% 0.03 155);
    --error: oklch(55% 0.2 25);
    --error-bg: oklch(95% 0.03 25);
    --warning: oklch(60% 0.16 80);
    --warning-bg: oklch(95% 0.04 80);
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --shadow-sm: 0 1px 3px oklch(20% 0.01 250 / 0.06);
    --shadow-md: 0 4px 12px oklch(20% 0.01 250 / 0.08);
    --shadow-lg: 0 8px 32px oklch(20% 0.01 250 / 0.1);
    --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
}

body {
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    background: var(--surface-2);
    color: var(--text);
    line-height: 1.6;
    font-size: 15px;
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
}

/* Preview Banner */
.preview-banner {
    background: var(--warning-bg);
    border-bottom: 1px solid oklch(85% 0.06 80);
    padding: 14px 20px;
    position: sticky;
    top: 0;
    z-index: 50;
}
.preview-banner-inner {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.preview-banner-left { display: flex; align-items: center; gap: 12px; }
.preview-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--warning); color: oklch(99% 0.005 80);
    font-size: 0.72rem; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: 0.06em;
}
.preview-badge svg { width: 12px; height: 12px; }
.preview-desc { font-size: 0.82rem; color: oklch(35% 0.08 80); font-weight: 500; }
.preview-banner-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.btn-edit {
    padding: 9px 20px; background: var(--surface); color: var(--text);
    border: 1.5px solid var(--border); border-radius: var(--radius-md);
    font-weight: 600; font-size: 0.82rem; cursor: pointer;
    transition: all 0.15s var(--ease-out); font-family: inherit;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
}
.btn-edit:hover { border-color: var(--text-muted); background: var(--surface-2); }
.btn-confirm {
    padding: 9px 20px; background: var(--success); color: oklch(99% 0.005 155);
    border: none; border-radius: var(--radius-md); font-weight: 600;
    font-size: 0.82rem; cursor: pointer; transition: all 0.15s var(--ease-out);
    font-family: inherit; display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 0 2px 8px oklch(55% 0.15 155 / 0.3);
}
.btn-confirm:hover { background: oklch(48% 0.15 155); }
.btn-confirm:active { transform: scale(0.97); }

.page-wrapper { max-width: 900px; margin: 0 auto; padding: 32px 20px 80px; }

/* Listing Card */
.listing-preview {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-md);
}

/* Gallery */
.gallery-section { position: relative; background: var(--surface-3); aspect-ratio: 16/7; overflow: hidden; }
.gallery-grid { display: grid; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; gap: 3px; height: 100%; }
.gallery-grid img { width: 100%; height: 100%; object-fit: cover; }
.gallery-grid .gallery-main { grid-row: 1 / -1; }
.gallery-placeholder { display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; color: var(--text-muted); height: 100%; }
.gallery-placeholder svg { width: 48px; height: 48px; opacity: 0.4; }
.gallery-placeholder span { font-size: 0.82rem; font-weight: 500; }
.gallery-count { position: absolute; bottom: 12px; right: 12px; background: oklch(15% 0.01 250 / 0.75); color: oklch(95% 0.005 250); font-size: 0.75rem; font-weight: 600; padding: 5px 12px; border-radius: 20px; display: flex; align-items: center; gap: 5px; }
.gallery-count svg { width: 14px; height: 14px; }

/* Content */
.listing-content { padding: 32px; }

/* Header */
.listing-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
.listing-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; line-height: 1.3; }
.listing-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; font-weight: 500; }
.listing-price-box { text-align: right; flex-shrink: 0; }
.listing-price { font-size: 1.6rem; font-weight: 800; color: var(--primary); letter-spacing: -0.02em; }
.listing-price-type { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

/* Specs */
.specs-section { margin-bottom: 28px; }
.specs-section-title { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 14px; }
.specs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.spec-card { background: var(--surface-2); border-radius: var(--radius-md); padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
.spec-icon { width: 36px; height: 36px; background: var(--primary-bg); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.spec-icon svg { width: 18px; height: 18px; color: var(--primary); }
.spec-info { min-width: 0; }
.spec-label { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.spec-value { font-size: 0.88rem; font-weight: 600; color: var(--text); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Description - FIXED */
.desc-section { margin-bottom: 28px; }
.desc-text {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.7;
    max-width: 70ch;
}
.desc-text p {
    margin-bottom: 12px;
}
.desc-text p:last-child {
    margin-bottom: 0;
}
.desc-text .desc-list {
    list-style: none;
    padding: 0;
    margin: 0 0 12px 0;
    display: grid;
    grid-template-columns: 1fr;
    gap: 4px;
}
.desc-text .desc-list:last-child {
    margin-bottom: 0;
}
.desc-text .desc-list li {
    position: relative;
    padding-left: 16px;
    font-size: 0.85rem;
    line-height: 1.5;
    color: var(--text-secondary);
}
.desc-text .desc-list li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--text-muted);
    font-weight: 700;
}
.desc-empty { font-size: 0.85rem; color: var(--text-muted); font-style: italic; }

/* Features */
.features-section { margin-bottom: 28px; }
.features-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
.feature-tag { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--surface-2); border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 500; color: var(--text); }
.feature-tag svg { width: 14px; height: 14px; color: var(--success); flex-shrink: 0; }
.features-empty { font-size: 0.85rem; color: var(--text-muted); font-style: italic; }

/* Location */
.location-section { margin-bottom: 0; }
.location-row { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: var(--text-secondary); }
.location-row svg { width: 16px; height: 16px; color: var(--text-muted); flex-shrink: 0; }

/* Bottom Actions (mobile) */
.bottom-actions { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); border-top: 1px solid var(--border); padding: 14px 20px; z-index: 50; gap: 10px; }
.bottom-actions .btn-edit, .bottom-actions .btn-confirm { flex: 1; justify-content: center; padding: 12px 16px; }

/* Success Overlay */
.success-overlay { position: fixed; inset: 0; background: oklch(20% 0.01 250 / 0.5); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 20px; animation: fadeIn 0.3s var(--ease-out); }
.success-card { background: var(--surface); border-radius: var(--radius-xl); padding: 48px 40px; text-align: center; max-width: 420px; width: 100%; box-shadow: var(--shadow-lg); animation: scaleIn 0.4s var(--ease-out); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
.success-card .check-circle { width: 64px; height: 64px; background: var(--success-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.success-card .check-circle svg { width: 32px; height: 32px; color: var(--success); }
.success-card h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
.success-card p { color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; }
.success-card .redirect-text { font-size: 0.78rem; color: var(--text-muted); margin-top: 16px; }
.success-card .progress-bar { width: 100%; height: 4px; background: var(--surface-3); border-radius: 2px; margin-top: 12px; overflow: hidden; }
.success-card .progress-fill { height: 100%; background: var(--success); border-radius: 2px; animation: progressShrink 3s linear forwards; }
@keyframes progressShrink { from { width: 100%; } to { width: 0%; } }

.error-bar { background: var(--error-bg); border: 1px solid oklch(80% 0.06 25); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px; color: oklch(40% 0.12 25); font-size: 0.85rem; font-weight: 500; }

@media (max-width: 768px) {
    .preview-banner-inner { flex-direction: column; align-items: flex-start; }
    .preview-banner-actions { display: none; }
    .bottom-actions { display: flex; }
    .listing-header { flex-direction: column; gap: 12px; }
    .listing-price-box { text-align: left; }
    .listing-content { padding: 24px 20px; }
    .specs-grid { grid-template-columns: repeat(2, 1fr); }
    .features-list { grid-template-columns: 1fr 1fr; }
    .gallery-section { aspect-ratio: 16/9; }
    .gallery-grid { grid-template-columns: 1fr; grid-template-rows: 1fr; }
    .gallery-grid .gallery-main { grid-row: auto; }
    .gallery-grid img:not(.gallery-main) { display: none; }
    .page-wrapper { padding-bottom: 100px; }
}
@media (max-width: 480px) {
    .specs-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .spec-card { padding: 10px 12px; gap: 8px; }
    .features-list { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<?php if ($success): ?>
<div class="success-overlay">
    <div class="success-card">
        <div class="check-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h3><?= $tr['success_title'] ?></h3>
        <p><?= $tr['success_msg'] ?></p>
        <div class="redirect-text"><?= $tr['redirecting'] ?></div>
        <div class="progress-bar"><div class="progress-fill"></div></div>
    </div>
</div>
<script>
    localStorage.removeItem('vehicleData');
    setTimeout(function() { window.location.href = 'index.php'; }, 3000);
</script>
<?php else: ?>

<!-- Preview Banner -->
<div class="preview-banner">
    <div class="preview-banner-inner">
        <div class="preview-banner-left">
            <span class="preview-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?= $tr['preview_badge'] ?>
            </span>
            <span class="preview-desc"><?= $tr['preview_desc'] ?></span>
        </div>
        <div class="preview-banner-actions">
            <a href="<?= BASE_URL ?>/pages/post-vehicle.php" class="btn-edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <?= $tr['edit'] ?>
            </a>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="btn-confirm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?= $tr['confirm'] ?>
                </button>
            </form>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="page-wrapper"><div class="error-bar"><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>

<div class="page-wrapper">
    <div class="listing-preview">
        <!-- Gallery -->
        <div class="gallery-section">
            <?php if (!empty($data['images'])): ?>
                <div class="gallery-grid">
                    <?php foreach (array_slice($data['images'], 0, 3) as $i => $img): ?>
                        <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($data['title']) ?>" class="<?= $i === 0 ? 'gallery-main' : '' ?>">
                    <?php endforeach; ?>
                </div>
                <?php if (count($data['images']) > 3): ?>
                <div class="gallery-count">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <?= count($data['images']) ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="gallery-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <span><?= $tr['no_photos'] ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="listing-content">
            <!-- Header -->
            <div class="listing-header">
                <div>
                    <h1 class="listing-title"><?= htmlspecialchars($data['title']) ?></h1>
                    <div class="listing-subtitle"><?= htmlspecialchars($data['brand'] . ' ' . $data['model'] . ' ' . $data['build_year']) ?></div>
                </div>
                <div class="listing-price-box">
                    <?php if ($data['price_type'] === 'free'): ?>
                        <div class="listing-price"><?= $tr['free'] ?></div>
                    <?php else: ?>
                        <div class="listing-price">&euro; <?= number_format((float)$data['price'], 0, ',', '.') ?></div>
                    <?php endif; ?>
                    <div class="listing-price-type"><?= $priceTypeLabel ?></div>
                </div>
            </div>

            <!-- Specs -->
            <div class="specs-section">
                <div class="specs-section-title"><?= $tr['vehicle_info'] ?></div>
                <div class="specs-grid">
                    <?php if (!empty($data['brand'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3l-4 4-4-4"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['brand'] ?></div><div class="spec-value"><?= htmlspecialchars($data['brand']) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['model'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M5 17H3v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/><path d="M9 17h6"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['model'] ?></div><div class="spec-value"><?= htmlspecialchars($data['model']) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['build_year'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['year'] ?></div><div class="spec-value"><?= htmlspecialchars($data['build_year']) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['mileage_km'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['mileage'] ?></div><div class="spec-value"><?= number_format((int)$data['mileage_km'], 0, '.', '.') ?> km</div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['fuel_type'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v17"/><path d="M13 10h4a2 2 0 0 1 2 2v4a2 2 0 0 0 2 2"/><path d="M21 13V8a1 1 0 0 0-1-1h-1"/><path d="M6 12h4"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['fuel'] ?></div><div class="spec-value"><?= htmlspecialchars(ucfirst($data['fuel_type'])) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['transmission'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="6" r="2"/><circle cx="12" cy="6" r="2"/><circle cx="19" cy="6" r="2"/><circle cx="5" cy="18" r="2"/><circle cx="19" cy="18" r="2"/><path d="M5 8v8"/><path d="M19 8v8"/><path d="M12 8v4h7"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['transmission'] ?></div><div class="spec-value"><?= $transLabel ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['power_hp'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['power'] ?></div><div class="spec-value"><?= htmlspecialchars($data['power_hp']) ?> HP</div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['color'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="13.5" r="2.5"/><circle cx="17.5" cy="13.5" r="2.5"/><circle cx="12" cy="12" r="9"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['color'] ?></div><div class="spec-value"><?= htmlspecialchars($data['color']) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['doors'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['doors'] ?></div><div class="spec-value"><?= htmlspecialchars($data['doors']) ?></div></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($data['apk_tot'])): ?>
                    <div class="spec-card">
                        <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div>
                        <div class="spec-info"><div class="spec-label"><?= $tr['apk'] ?></div><div class="spec-value"><?= htmlspecialchars($data['apk_tot']) ?></div></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description (FIXED: no more excessive spacing) -->
            <?php if (!empty($data['description'])): ?>
            <div class="desc-section">
                <div class="specs-section-title"><?= $tr['description'] ?></div>
                <div class="desc-text"><?= formatDescription($data['description']) ?></div>
            </div>
            <?php endif; ?>

            <!-- Features -->
            <?php
            $allSelectedFeatures = $data['features'] ?? [];
            $maintenanceSelected = $data['maintenance'] ?? [];
            $hasBtw = !empty($data['btw']);
            $hasAny = !empty($allSelectedFeatures) || !empty($maintenanceSelected) || $hasBtw;
            ?>
            <?php if ($hasAny): ?>
            <div class="features-section">
                <div class="specs-section-title"><?= $tr['features'] ?></div>
                <div class="features-list">
                    <?php foreach ($allSelectedFeatures as $key): ?>
                        <div class="feature-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?= htmlspecialchars($currentLabels[$key] ?? $key) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($maintenanceSelected as $m): ?>
                        <div class="feature-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php
                                if ($m === 'dealer_maintained') echo $tr['dealer_maintained'];
                                elseif ($m === 'maintenance_book') echo $tr['maintenance_book'];
                                else echo htmlspecialchars($m);
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($hasBtw): ?>
                        <div class="feature-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?= $tr['btw_deductible'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Location -->
            <?php if ($countryName || $cityName || !empty($data['address'])): ?>
            <div class="location-section">
                <div class="specs-section-title"><?= $tr['location'] ?></div>
                <div class="location-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php
                    $locationParts = [];
                    if (!empty($data['address'])) $locationParts[] = $data['address'];
                    if (!empty($data['postal_code'])) $locationParts[] = $data['postal_code'];
                    if ($cityName) $locationParts[] = $cityName;
                    if ($countryName) $locationParts[] = $countryName;
                    echo htmlspecialchars(implode(', ', $locationParts));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile Bottom Actions -->
<div class="bottom-actions">
    <a href="<?= BASE_URL ?>/pages/post-vehicle.php" class="btn-edit">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <?= $tr['edit'] ?>
    </a>
    <form method="POST" style="display:inline; flex:1;">
        <input type="hidden" name="action" value="confirm">
        <button type="submit" class="btn-confirm" style="width:100%; justify-content:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <?= $tr['confirm'] ?>
        </button>
    </form>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
