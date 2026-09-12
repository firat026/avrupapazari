<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");
$lang = currentLang();
$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { header('Location: ' . BASE_URL . '/'); exit; }

$stmt = $pdo->prepare("SELECT c.*, ct.name, ct.description FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? WHERE c.id = ? AND c.is_active = 1 LIMIT 1");
$stmt->execute([$lang, $id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

$name = $category['name'] ?: ($category['module'] ?: 'Kategori');
$module = strtolower((string)($category['module'] ?? ''));
$isBusiness = in_array($module, ['esnaf', 'business', 'bedrijf', 'rehber'], true);
$isProperty = ($module === 'emlak');
if ($isProperty) {
    header('Location: ' . BASE_URL . '/pages/property.php');
    exit;
}

$items = [];

if ($isBusiness) {
    try {
        $q = $pdo->query("SELECT * FROM esnaf WHERE status = 'active' ORDER BY is_featured DESC, rating DESC, name ASC");
    } catch (Exception $e) {
        $q = $pdo->query("SELECT * FROM esnaf ORDER BY rating DESC, name ASC");
    }
    $items = $q->fetchAll(PDO::FETCH_ASSOC);
} else {
    $q = $pdo->prepare("SELECT l.*, ci.name AS city_name FROM listings l LEFT JOIN cities ci ON ci.id = l.city_id WHERE l.category_id = ? AND l.status = 'active' ORDER BY l.is_premium DESC, l.created_at DESC");
    $q->execute([$id]);
    $items = $q->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = e($name) . ' - AvrupaPazari';
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/kategori.css?v=1">
<main class="category-page">
  <section class="category-hero"><div class="category-hero-glow"></div><div class="category-hero-inner"><span class="category-kicker"><?= e($name) ?></span><h1><?= e($name) ?></h1><p><?= e($category['description'] ?: ($isBusiness ? 'Avrupa’daki yerel işletmeleri ve hizmet veren esnafları keşfet.' : 'Bu kategorideki güncel ilanları keşfet.')) ?></p></div></section>
  <section class="category-content"><div class="category-heading"><div><span class="category-overline"><?= count($items) ?> <?= e($lang === 'nl' ? 'resultaten' : ($lang === 'en' ? 'results' : 'sonuç')) ?></span><h2><?= e($isBusiness ? 'Esnaf Rehberi' : $name) ?></h2></div><a href="<?= BASE_URL ?>/" class="category-back">← <?= e($lang === 'nl' ? 'Terug' : ($lang === 'en' ? 'Back' : 'Geri')) ?></a></div>
<?php if (!$items): ?><div class="category-empty"><h3><?= e($isBusiness ? 'Henüz işletme yok' : 'İlan bulunamadı') ?></h3><p>Bu kategoride henüz aktif kayıt bulunmuyor.</p></div><?php elseif ($isBusiness): ?><div class="business-directory-grid"><?php foreach ($items as $b): $bn = $b['name'] ?? $b['business_name'] ?? 'İşletme'; $bi = $b['image'] ?? ''; ?><a class="business-directory-card" href="<?= BASE_URL ?>/esnaf.php?slug=<?= e($b['slug'] ?? slugify($bn)) ?>"><div class="business-directory-cover" <?= $bi ? 'style="background-image:url(\'' . e(imageUrl($bi)) . '\')"' : '' ?>><span><?= e(mb_strtoupper(mb_substr($bn, 0, 2))) ?></span></div><div class="business-directory-body"><h3><?= e($bn) ?></h3><p><?= e($b['city'] ?? $b['location'] ?? '') ?></p><?php if ((float)($b['rating'] ?? 0) > 0): ?><strong>★ <?= number_format((float)$b['rating'], 1, ',', '.') ?></strong><?php endif; ?></div></a><?php endforeach; ?></div><?php else: ?><div class="category-listings-grid"><?php foreach ($items as $l): $li = $l['image'] ?? ''; ?><a class="category-listing-card" href="<?= BASE_URL ?>/listing.php?slug=<?= e($l['slug'] ?? '') ?>"><div class="category-listing-image" <?= $li ? 'style="background-image:url(\'' . e(imageUrl($li)) . '\')"' : '' ?>></div><div class="category-listing-body"><strong>€ <?= number_format((float)($l['price'] ?? 0), 0, ',', '.') ?></strong><h3><?= e($l['title'] ?? '') ?></h3><p><?= e($l['city_name'] ?? '') ?></p></div></a><?php endforeach; ?></div><?php endif; ?></section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
