<?php
// Site yapılandırması ve dil yönetimi
session_start();

// Desteklenen diller
$SUPPORTED_LANGS = ['tr', 'en', 'nl', 'de'];

// Dil değiştirme isteği kontrolü
if (isset($_GET['lang']) && in_array($_GET['lang'], $SUPPORTED_LANGS)) {
    $_SESSION['lang'] = $_GET['lang'];
    // Aynı sayfaya geri dön (lang parametresi olmadan)
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect");
    exit;
}

// Varsayılan dil
$current_lang = $_SESSION['lang'] ?? 'tr';
if (!in_array($current_lang, $SUPPORTED_LANGS)) $current_lang = 'tr';

// Dil dosyasını yükle
$lang_file = __DIR__ . "/lang/{$current_lang}.php";
if (file_exists($lang_file)) {
    $L = require $lang_file;
} else {
    $L = require __DIR__ . '/lang/tr.php';
}

// Yardımcı fonksiyon
function t($key) {
    global $L;
    return $L[$key] ?? $key;
}

// Kategori verileri (mock)
$CATEGORIES = [
    [
        'key' => 'vasitalar',
        'slug' => 'pages/vasitalar.php',
        'image' => 'https://images.unsplash.com/photo-1601929862217-f1bf94503333?crop=entropy&cs=srgb&fm=jpg&w=1200&q=80',
        'badge' => 'popular',
        'count' => 10,
        'size' => 'large'
    ],
    [
        'key' => 'emlak',
        'slug' => 'pages/emlak.php',
        'image' => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?crop=entropy&cs=srgb&fm=jpg&w=1200&q=80',
        'badge' => null,
        'count' => 32,
        'size' => 'large'
    ],
    [
        'key' => 'ikinci_el',
        'slug' => 'pages/ikinci-el.php',
        'image' => 'https://images.unsplash.com/photo-1615655406736-b37c4fabf923?crop=entropy&cs=srgb&fm=jpg&w=1000&q=80',
        'badge' => null,
        'count' => 18,
        'size' => 'small'
    ],
    [
        'key' => 'hizmetler',
        'slug' => 'pages/hizmetler.php',
        'image' => 'https://images.unsplash.com/photo-1578474846511-04ba529f0b88?crop=entropy&cs=srgb&fm=jpg&w=1000&q=80',
        'badge' => null,
        'count' => 9,
        'size' => 'small'
    ],
    [
        'key' => 'is_ilanlari',
        'slug' => 'pages/is-ilanlari.php',
        'image' => 'https://images.unsplash.com/photo-1594235048794-fae8583a5af5?crop=entropy&cs=srgb&fm=jpg&w=1000&q=80',
        'badge' => 'new',
        'count' => null,
        'size' => 'small'
    ]
];

// Son eklenen ilanlar (mock)
$LISTINGS = [
    ['title' => 'Porsche Panamera Turbo', 'price' => '€ 89.500', 'city' => 'Rotterdam', 'category' => 'vasitalar', 'image' => 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=600&q=80'],
    ['title' => 'Modern Villa 4+1', 'price' => '€ 785.000', 'city' => 'Antalya', 'category' => 'emlak', 'image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&q=80'],
    ['title' => 'MacBook Pro 14" M3', 'price' => '€ 1.850', 'city' => 'Amsterdam', 'category' => 'ikinci_el', 'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80'],
    ['title' => 'Elektrikçi Ustası', 'price' => '€ 45/saat', 'city' => 'Berlin', 'category' => 'hizmetler', 'image' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=600&q=80'],
    ['title' => 'Yazılım Geliştirici', 'price' => '€ 4.500/ay', 'city' => 'Utrecht', 'category' => 'is_ilanlari', 'image' => 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=600&q=80'],
    ['title' => 'BMW X5 M Paket', 'price' => '€ 62.000', 'city' => 'Köln', 'category' => 'vasitalar', 'image' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80'],
    ['title' => 'Daire 2+1 Merkez', 'price' => '€ 1.250/ay', 'city' => 'İstanbul', 'category' => 'emlak', 'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=600&q=80'],
    ['title' => 'PlayStation 5 + 3 Oyun', 'price' => '€ 480', 'city' => 'Den Haag', 'category' => 'ikinci_el', 'image' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=600&q=80']
];
