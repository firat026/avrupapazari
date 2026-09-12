<?php
/**
 * ═══════════════════════════════════════════════════════
 * /admin/homepage.php — Anasayfa Yönetimi (Full Control)
 * ═══════════════════════════════════════════════════════
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Anasayfa Yönetimi';
$currentPage = 'homepage';
$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");
$lang = $_SESSION['lang'] ?? 'tr';
$msg = '';
$msgType = 'success';

// ═══════ SETTINGS TABLE DETECTION ═══════
$settingsKeyCol = 'key_name';
$settingsValCol = 'value';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    $keyOptions = ['key_name', 'setting_key', 'name', 'key', 'option_name'];
    $valOptions = ['value', 'setting_value', 'val', 'option_value'];
    foreach ($keyOptions as $k) { if (in_array($k, $cols)) { $settingsKeyCol = $k; break; } }
    foreach ($valOptions as $v) { if (in_array($v, $cols)) { $settingsValCol = $v; break; } }
} catch(Exception $e) {}

// ═══════ HELPERS ═══════
function saveSetting($pdo, $key, $value, $keyCol, $valCol) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `{$keyCol}` = ?");
    $stmt->execute([$key]);
    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->prepare("UPDATE settings SET `{$valCol}` = ? WHERE `{$keyCol}` = ?")->execute([$value, $key]);
    } else {
        $pdo->prepare("INSERT INTO settings (`{$keyCol}`, `{$valCol}`) VALUES (?, ?)")->execute([$key, $value]);
    }
}
$ss = function($key, $value) use ($pdo, $settingsKeyCol, $settingsValCol) { saveSetting($pdo, $key, $value, $settingsKeyCol, $settingsValCol); };
$gs = function($key, $default = '') use ($pdo, $settingsKeyCol, $settingsValCol) {
    try {
        $stmt = $pdo->prepare("SELECT `{$settingsValCol}` FROM settings WHERE `{$settingsKeyCol}` = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null && $val !== '') ? $val : $default;
    } catch(Exception $e) { return $default; }
};

// ═══════ ENSURE TABLES ═══════
try { $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_sections (id INT AUTO_INCREMENT PRIMARY KEY, section_key VARCHAR(50) NOT NULL UNIQUE, title VARCHAR(255) NOT NULL, icon VARCHAR(50) DEFAULT 'layout-grid', sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE categories ADD COLUMN color_1 VARCHAR(20) DEFAULT '#1a5c3a'"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE categories ADD COLUMN color_2 VARCHAR(20) DEFAULT '#0d3d25'"); } catch(Exception $e) {}
try { $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) {}

// Seed sections
$defaultSections = [['bento','Bento Kategoriler','layout-grid',1],['popular','Popüler İlanlar','flame',2],['featured','Öne Çıkan Esnaflar','award',3],['howto','Nasıl Çalışır','footprints',4],['cta','CTA Banner','megaphone',5]];
try {
    $check = $pdo->query("SELECT COUNT(*) FROM homepage_sections")->fetchColumn();
    if ((int)$check === 0) {
        $ins = $pdo->prepare("INSERT INTO homepage_sections (section_key, title, icon, sort_order, is_active) VALUES (?,?,?,?,1)");
        foreach ($defaultSections as $s) { $ins->execute([$s[0],$s[1],$s[2],$s[3]]); }
    }
} catch(Exception $e) {}

// Upload dir
$uploadDir = dirname(__DIR__) . '/uploads/categories/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// ═══════ POST HANDLERS ═══════

// Sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sections'])) {
    try {
        if (!empty($_POST['sections'])) {
            $stmt = $pdo->prepare("UPDATE homepage_sections SET sort_order = ?, is_active = ? WHERE section_key = ?");
            foreach ($_POST['sections'] as $key => $data) {
                $stmt->execute([(int)($data['sort'] ?? 0), isset($data['active']) ? 1 : 0, $key]);
            }
        }
        $msg = 'Bölüm ayarları kaydedildi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// Bento + Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bento'])) {
    try {
        if (!empty($_POST['cat'])) {
            foreach ($_POST['cat'] as $id => $data) {
                $updateFields = "sort_order = ?, icon = ?, color_1 = ?, color_2 = ?";
                $params = [(int)($data['sort'] ?? 0), trim($data['icon'] ?? ''), trim($data['color1'] ?? '#1a5c3a'), trim($data['color2'] ?? '#0d3d25')];
                
                // Image upload
                if (!empty($_FILES['cat_image']['name'][$id]) && $_FILES['cat_image']['error'][$id] === UPLOAD_ERR_OK) {
                    $tmpFile = $_FILES['cat_image']['tmp_name'][$id];
                    $origName = $_FILES['cat_image']['name'][$id];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $newName = 'categories/cat-' . (int)$id . '-' . time() . '.' . $ext;
                        $destPath = dirname(__DIR__) . '/uploads/' . $newName;
                        
                        if (move_uploaded_file($tmpFile, $destPath)) {
                            $updateFields .= ", image = ?";
                            $params[] = $newName;
                        }
                    }
                }
                
                $params[] = (int)$id;
                $pdo->prepare("UPDATE categories SET {$updateFields} WHERE id = ?")->execute($params);
            }
        }
        $ss('bento_count', (int)($_POST['bento_count'] ?? 4));
        $msg = 'Bento kategoriler güncellendi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// Popular
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_popular'])) {
    try {
        foreach (['popular_title','popular_count','popular_link_text'] as $f) { $ss($f, trim($_POST[$f] ?? '')); }
        $msg = 'Popüler ilanlar ayarları kaydedildi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// Featured
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_featured'])) {
    try {
        foreach (['featured_title','featured_count','featured_link_text'] as $f) { $ss($f, trim($_POST[$f] ?? '')); }
        $msg = 'Esnaf ayarları kaydedildi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// Howto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_howto'])) {
    try {
        for ($i=1;$i<=3;$i++) { $ss("step{$i}_title", trim($_POST["step{$i}_title"] ?? '')); $ss("step{$i}_desc", trim($_POST["step{$i}_desc"] ?? '')); }
        $msg = 'Nasıl Çalışır bölümü güncellendi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// CTA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cta'])) {
    try {
        foreach (['cta_title','cta_desc','cta_btn','cta_url'] as $f) { $ss($f, trim($_POST[$f] ?? '')); }
        $msg = 'CTA banner güncellendi.';
    } catch(Exception $e) { $msg = 'Hata: ' . $e->getMessage(); $msgType = 'error'; }
}

// ═══════ FETCH DATA ═══════
$sections = [];
try { $sections = $pdo->query("SELECT * FROM homepage_sections ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) {}

$categories = [];
try {
    $stmt = $pdo->prepare("SELECT c.*, ct.name FROM categories c LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = ? WHERE c.is_active = 1 ORDER BY c.sort_order ASC");
    $stmt->execute([$lang]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$esnafCount = 0;
try { $esnafCount = (int)$pdo->query("SELECT COUNT(*) FROM esnaf WHERE is_featured = 1")->fetchColumn(); } catch(Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<style>
.ha-page { --h: 155; --ease: cubic-bezier(0.16, 1, 0.3, 1); }
.ha-toast { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 0.84rem; font-weight: 650; animation: haSlide 0.35s var(--ease); }
.ha-toast.ok { background: oklch(94% 0.035 var(--h)); border: 1px solid oklch(84% 0.06 var(--h)); color: oklch(32% 0.1 var(--h)); }
.ha-toast.err { background: oklch(94% 0.04 25); border: 1px solid oklch(82% 0.06 25); color: oklch(38% 0.12 25); }
@keyframes haSlide { from { opacity:0; transform: translateY(-8px); } to { opacity:1; transform: translateY(0); } }

.ha-section { background: oklch(99.5% 0.003 var(--h)); border: 1px solid oklch(92% 0.015 var(--h)); border-radius: 16px; margin-bottom: 24px; overflow: hidden; transition: box-shadow 0.2s var(--ease); }
.ha-section:hover { box-shadow: 0 4px 20px oklch(50% 0.06 var(--h) / 0.05); }
.ha-section-head { padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; background: oklch(97% 0.008 var(--h)); border-bottom: 1px solid oklch(93% 0.012 var(--h)); }
.ha-section-title { font-size: 0.92rem; font-weight: 750; color: oklch(22% 0.04 var(--h)); display: flex; align-items: center; gap: 10px; }
.ha-section-title-icon { width: 32px; height: 32px; background: oklch(42% 0.12 var(--h)); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: oklch(98% 0.01 var(--h)); }
.ha-badge { font-size: 0.66rem; font-weight: 700; padding: 4px 10px; border-radius: 100px; background: oklch(92% 0.035 var(--h)); color: oklch(38% 0.1 var(--h)); text-transform: uppercase; letter-spacing: 0.04em; }
.ha-section-body { padding: 24px; }

.ha-order-list { display: flex; flex-direction: column; gap: 8px; }
.ha-order-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px; background: oklch(98.5% 0.005 var(--h)); border: 1px solid oklch(92% 0.015 var(--h)); border-radius: 10px; transition: all 0.15s var(--ease); cursor: grab; }
.ha-order-item:hover { background: oklch(96% 0.015 var(--h)); border-color: oklch(86% 0.03 var(--h)); }
.ha-order-item.dragging { opacity: 0.4; transform: scale(0.97); }
.ha-order-grip { color: oklch(72% 0.02 var(--h)); cursor: grab; flex-shrink: 0; }
.ha-order-icon { width: 34px; height: 34px; background: oklch(92% 0.03 var(--h)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: oklch(40% 0.1 var(--h)); flex-shrink: 0; }
.ha-order-name { font-size: 0.84rem; font-weight: 650; color: oklch(25% 0.03 var(--h)); flex: 1; }
.ha-order-sort { display: none; }

.ha-toggle { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
.ha-toggle input { opacity: 0; width: 0; height: 0; }
.ha-toggle-track { position: absolute; inset: 0; background: oklch(85% 0.015 var(--h)); border-radius: 12px; cursor: pointer; transition: background 0.2s var(--ease); }
.ha-toggle-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: oklch(99% 0.005 var(--h)); border-radius: 50%; transition: transform 0.2s var(--ease); box-shadow: 0 1px 3px oklch(0% 0 0 / 0.15); }
.ha-toggle input:checked + .ha-toggle-track { background: oklch(50% 0.13 var(--h)); }
.ha-toggle input:checked + .ha-toggle-track::after { transform: translateX(18px); }

/* Bento Items with Image Upload */
.ha-bento-list { display: flex; flex-direction: column; gap: 12px; }
.ha-bento-item { display: grid; grid-template-columns: 80px 1fr; gap: 16px; padding: 16px; border-radius: 12px; border: 1px solid oklch(93% 0.012 var(--h)); background: oklch(99% 0.004 var(--h)); transition: all 0.15s var(--ease); }
.ha-bento-item:hover { background: oklch(96.5% 0.012 var(--h)); border-color: oklch(88% 0.025 var(--h)); }
.ha-bento-thumb { width: 80px; height: 80px; border-radius: 10px; overflow: hidden; position: relative; cursor: pointer; border: 2px dashed oklch(85% 0.02 var(--h)); transition: border-color 0.2s; }
.ha-bento-thumb:hover { border-color: oklch(50% 0.1 var(--h)); }
.ha-bento-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ha-bento-thumb-empty { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: oklch(95% 0.01 var(--h)); color: oklch(60% 0.04 var(--h)); font-size: 0.65rem; gap: 4px; }
.ha-bento-thumb input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.ha-bento-thumb-overlay { position: absolute; inset: 0; background: oklch(20% 0.02 var(--h) / 0.6); display: flex; align-items: center; justify-content: center; color: #fff; opacity: 0; transition: opacity 0.2s; border-radius: 8px; }
.ha-bento-thumb:hover .ha-bento-thumb-overlay { opacity: 1; }
.ha-bento-details { display: flex; flex-direction: column; gap: 8px; }
.ha-bento-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.ha-bento-name { font-size: 0.88rem; font-weight: 700; color: oklch(20% 0.03 var(--h)); }
.ha-bento-mod { font-size: 0.7rem; color: oklch(55% 0.04 var(--h)); }

.ha-input { width: 100%; height: 40px; border: 1px solid oklch(90% 0.015 var(--h)); border-radius: 9px; padding: 0 12px; font-size: 0.82rem; color: oklch(22% 0.03 var(--h)); background: oklch(99.5% 0.003 var(--h)); outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
.ha-input:focus { border-color: oklch(52% 0.12 var(--h)); box-shadow: 0 0 0 3px oklch(52% 0.12 var(--h) / 0.08); }
.ha-input-sm { max-width: 80px; text-align: center; font-weight: 700; }
.ha-input-icon { max-width: 130px; font-family: monospace; font-size: 0.78rem; }
.ha-input-color { width: 36px; height: 36px; padding: 3px; border: 1px solid oklch(90% 0.015 var(--h)); border-radius: 8px; cursor: pointer; background: transparent; }
.ha-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: oklch(48% 0.04 var(--h)); margin-bottom: 4px; display: block; }
.ha-row { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; align-items: end; }
.ha-col { flex: 1; min-width: 200px; }
.ha-col-sm { flex: 0 0 auto; min-width: 80px; }

.ha-steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
.ha-step { padding: 20px; border-radius: 12px; border: 1px solid oklch(92% 0.015 var(--h)); background: oklch(98.5% 0.006 var(--h)); }
.ha-step-num { width: 28px; height: 28px; background: oklch(42% 0.12 var(--h)); color: oklch(98% 0.01 var(--h)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 800; margin-bottom: 12px; }

.ha-cta-preview { border-radius: 14px; padding: 32px; text-align: center; margin-bottom: 20px; position: relative; overflow: hidden; background: oklch(25% 0.06 var(--h)); }
.ha-cta-preview::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 60% 50% at 20% 80%, oklch(45% 0.1 var(--h) / 0.2), transparent), radial-gradient(ellipse 50% 40% at 85% 15%, oklch(55% 0.12 var(--h) / 0.12), transparent); pointer-events: none; }
.ha-cta-preview-tag { position: absolute; top: 10px; right: 12px; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: oklch(70% 0.05 var(--h)); background: oklch(20% 0.02 var(--h) / 0.5); padding: 3px 8px; border-radius: 4px; }
.ha-cta-preview h3 { font-size: 1.2rem; font-weight: 800; color: oklch(97% 0.01 var(--h)); margin-bottom: 6px; position: relative; text-wrap: balance; }
.ha-cta-preview p { font-size: 0.84rem; color: oklch(82% 0.03 var(--h)); margin-bottom: 16px; position: relative; }
.ha-cta-preview-btn { display: inline-block; background: oklch(98% 0.01 var(--h)); color: oklch(30% 0.08 var(--h)); font-weight: 700; font-size: 0.8rem; padding: 9px 22px; border-radius: 8px; position: relative; }

.ha-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s var(--ease); }
.ha-btn-primary { background: oklch(42% 0.12 var(--h)); color: oklch(98% 0.01 var(--h)); box-shadow: 0 2px 8px oklch(42% 0.12 var(--h) / 0.2); }
.ha-btn-primary:hover { background: oklch(38% 0.13 var(--h)); transform: translateY(-1px); }
.ha-btn-primary:active { transform: translateY(0); }
.ha-footer { display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1px solid oklch(93% 0.01 var(--h)); margin-top: 16px; }
.ha-info-box { padding: 12px 16px; border-radius: 8px; background: oklch(95% 0.02 40); border: 1px solid oklch(88% 0.04 40); font-size: 0.78rem; color: oklch(35% 0.06 40); margin-top: 12px; display: flex; align-items: center; gap: 8px; }

@media (max-width: 768px) {
    .ha-bento-item { grid-template-columns: 1fr; }
    .ha-bento-thumb { width: 100%; height: 120px; }
    .ha-steps { grid-template-columns: 1fr; }
    .ha-row { flex-direction: column; gap: 12px; }
}
</style>

<div class="ha-page">

<?php if($msg): ?>
<div class="ha-toast <?= $msgType === 'success' ? 'ok' : 'err' ?>">
    <i data-lucide="<?= $msgType === 'success' ? 'check-circle-2' : 'alert-triangle' ?>" style="width:18px;height:18px;flex-shrink:0"></i>
    <?= e($msg) ?>
</div>
<?php endif; ?>

<!-- 1. BÖLÜM SIRASI -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="layers" style="width:16px;height:16px"></i></span> Bölüm Sırası & Görünürlük</div>
        <span class="ha-badge">Sürükle & Bırak</span>
    </div>
    <div class="ha-section-body">
        <form method="POST">
            <input type="hidden" name="save_sections" value="1">
            <div class="ha-order-list" id="orderList">
                <?php foreach($sections as $sec): ?>
                <div class="ha-order-item" draggable="true">
                    <div class="ha-order-grip"><i data-lucide="grip-vertical" style="width:16px;height:16px"></i></div>
                    <div class="ha-order-icon"><i data-lucide="<?= e($sec['icon'] ?? 'square') ?>" style="width:16px;height:16px"></i></div>
                    <span class="ha-order-name"><?= e($sec['title']) ?></span>
                    <input type="hidden" class="ha-order-sort" name="sections[<?= e($sec['section_key']) ?>][sort]" value="<?= (int)$sec['sort_order'] ?>">
                    <label class="ha-toggle"><input type="checkbox" name="sections[<?= e($sec['section_key']) ?>][active]" value="1" <?= $sec['is_active'] ? 'checked' : '' ?>><span class="ha-toggle-track"></span></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

<!-- 2. BENTO + GÖRSEL UPLOAD -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="layout-grid" style="width:16px;height:16px"></i></span> Bento Kategoriler</div>
        <span class="ha-badge">Görsel + Renk + İkon</span>
    </div>
    <div class="ha-section-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_bento" value="1">
            <div class="ha-row" style="margin-bottom:20px">
                <div class="ha-col-sm">
                    <label class="ha-label">Adet</label>
                    <input class="ha-input ha-input-sm" type="number" name="bento_count" value="<?= $gs('bento_count', '4') ?>" min="2" max="8">
                </div>
            </div>

            <div class="ha-bento-list">
                <?php foreach($categories as $cat):
                    $c1 = !empty($cat['color_1']) ? $cat['color_1'] : '#1a5c3a';
                    $c2 = !empty($cat['color_2']) ? $cat['color_2'] : '#0d3d25';
                    $catImg = !empty($cat['image']) ? '../uploads/' . $cat['image'] : '';
                ?>
                <div class="ha-bento-item">
                    <div class="ha-bento-thumb">
                        <?php if ($catImg): ?>
                        <img src="<?= e($catImg) ?>?t=<?= time() ?>" alt="">
                        <div class="ha-bento-thumb-overlay"><i data-lucide="camera" style="width:20px;height:20px"></i></div>
                        <?php else: ?>
                        <div class="ha-bento-thumb-empty">
                            <i data-lucide="image-plus" style="width:20px;height:20px"></i>
                            <span>Yükle</span>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="cat_image[<?= $cat['id'] ?>]" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="ha-bento-details">
                        <div>
                            <span class="ha-bento-name"><?= e($cat['name'] ?? $cat['module']) ?></span>
                            <span class="ha-bento-mod"> — <?= e($cat['module']) ?></span>
                        </div>
                        <div class="ha-bento-row">
                            <div>
                                <label class="ha-label">İkon</label>
                                <input class="ha-input ha-input-icon" type="text" name="cat[<?= $cat['id'] ?>][icon]" value="<?= e($cat['icon'] ?? '') ?>" placeholder="store, home...">
                            </div>
                            <div>
                                <label class="ha-label">Renk 1</label>
                                <input class="ha-input-color" type="color" name="cat[<?= $cat['id'] ?>][color1]" value="<?= e($c1) ?>">
                            </div>
                            <div>
                                <label class="ha-label">Renk 2</label>
                                <input class="ha-input-color" type="color" name="cat[<?= $cat['id'] ?>][color2]" value="<?= e($c2) ?>">
                            </div>
                            <div>
                                <label class="ha-label">Sıra</label>
                                <input class="ha-input ha-input-sm" type="number" name="cat[<?= $cat['id'] ?>][sort]" value="<?= (int)$cat['sort_order'] ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

<!-- 3. POPÜLER İLANLAR -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="flame" style="width:16px;height:16px"></i></span> Popüler İlanlar</div>
        <span class="ha-badge">Otomatik</span>
    </div>
    <div class="ha-section-body">
        <form method="POST">
            <input type="hidden" name="save_popular" value="1">
            <div class="ha-row">
                <div class="ha-col"><label class="ha-label">Başlık</label><input class="ha-input" type="text" name="popular_title" value="<?= e($gs('popular_title', 'Popüler İlanlar')) ?>"></div>
                <div class="ha-col-sm"><label class="ha-label">Adet</label><input class="ha-input ha-input-sm" type="number" name="popular_count" value="<?= e($gs('popular_count', '4')) ?>" min="2" max="12"></div>
                <div class="ha-col"><label class="ha-label">Link Metni</label><input class="ha-input" type="text" name="popular_link_text" value="<?= e($gs('popular_link_text', 'Tümünü Gör')) ?>" style="max-width:200px"></div>
            </div>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

<!-- 4. ÖNE ÇIKAN ESNAFLAR -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="award" style="width:16px;height:16px"></i></span> Öne Çıkan Esnaflar</div>
        <span class="ha-badge"><?= $esnafCount ?> aktif</span>
    </div>
    <div class="ha-section-body">
        <form method="POST">
            <input type="hidden" name="save_featured" value="1">
            <div class="ha-row">
                <div class="ha-col"><label class="ha-label">Başlık</label><input class="ha-input" type="text" name="featured_title" value="<?= e($gs('featured_title', 'Öne Çıkan Esnaflar')) ?>"></div>
                <div class="ha-col-sm"><label class="ha-label">Adet</label><input class="ha-input ha-input-sm" type="number" name="featured_count" value="<?= e($gs('featured_count', '4')) ?>" min="2" max="12"></div>
                <div class="ha-col"><label class="ha-label">Link Metni</label><input class="ha-input" type="text" name="featured_link_text" value="<?= e($gs('featured_link_text', 'Tümünü Gör')) ?>" style="max-width:200px"></div>
            </div>
            <?php if($esnafCount === 0): ?>
            <div class="ha-info-box"><i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0"></i> is_featured = 1 olan esnaf yok. Esnaflar sayfasından işaretle.</div>
            <?php endif; ?>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

<!-- 5. NASIL ÇALIŞIR -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="footprints" style="width:16px;height:16px"></i></span> Nasıl Çalışır</div>
        <span class="ha-badge">3 Adım</span>
    </div>
    <div class="ha-section-body">
        <form method="POST">
            <input type="hidden" name="save_howto" value="1">
            <div class="ha-steps">
                <?php $sd=[1=>['Ücretsiz Kayıt Ol','E-posta veya telefon ile saniyeler içinde hesap oluştur.'],2=>['İlan Ver veya Keşfet','Hizmet sun, ürün sat veya ihtiyacını bul.'],3=>['İletişime Geç','Doğrudan mesaj ile anlaş. Güvenli, hızlı, kolay.']];
                for($i=1;$i<=3;$i++): ?>
                <div class="ha-step">
                    <div class="ha-step-num"><?= $i ?></div>
                    <div style="margin-bottom:10px"><label class="ha-label">Başlık</label><input class="ha-input" type="text" name="step<?= $i ?>_title" value="<?= e($gs("step{$i}_title", $sd[$i][0])) ?>"></div>
                    <div><label class="ha-label">Açıklama</label><input class="ha-input" type="text" name="step<?= $i ?>_desc" value="<?= e($gs("step{$i}_desc", $sd[$i][1])) ?>"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

<!-- 6. CTA -->
<div class="ha-section">
    <div class="ha-section-head">
        <div class="ha-section-title"><span class="ha-section-title-icon"><i data-lucide="megaphone" style="width:16px;height:16px"></i></span> CTA Banner</div>
        <span class="ha-badge">Canlı Önizleme</span>
    </div>
    <div class="ha-section-body">
        <div class="ha-cta-preview">
            <span class="ha-cta-preview-tag">Önizleme</span>
            <h3 id="ctaPrevH"><?= e($gs('cta_title', 'Hemen İlan Ver, Binlerce Kişiye Ulaş')) ?></h3>
            <p id="ctaPrevP"><?= e($gs('cta_desc', 'Esnaf mısın? Ürün mü satıyorsun? İlk ilanın ücretsiz.')) ?></p>
            <span class="ha-cta-preview-btn" id="ctaPrevBtn"><?= e($gs('cta_btn', 'Ücretsiz İlan Ver')) ?></span>
        </div>
        <form method="POST">
            <input type="hidden" name="save_cta" value="1">
            <div class="ha-row"><div class="ha-col"><label class="ha-label">Başlık</label><input class="ha-input" type="text" name="cta_title" id="ctaInH" value="<?= e($gs('cta_title', 'Hemen İlan Ver, Binlerce Kişiye Ulaş')) ?>"></div></div>
            <div class="ha-row"><div class="ha-col"><label class="ha-label">Açıklama</label><input class="ha-input" type="text" name="cta_desc" id="ctaInP" value="<?= e($gs('cta_desc', 'Esnaf mısın? Ürün mü satıyorsun? İlk ilanın ücretsiz.')) ?>"></div></div>
            <div class="ha-row">
                <div class="ha-col" style="max-width:250px"><label class="ha-label">Buton Metni</label><input class="ha-input" type="text" name="cta_btn" id="ctaInBtn" value="<?= e($gs('cta_btn', 'Ücretsiz İlan Ver')) ?>"></div>
                <div class="ha-col"><label class="ha-label">Buton Linki</label><input class="ha-input" type="text" name="cta_url" value="<?= e($gs('cta_url', '/ilan-ver.php')) ?>" placeholder="/ilan-ver.php"></div>
            </div>
            <div class="ha-footer"><button type="submit" class="ha-btn ha-btn-primary"><i data-lucide="check" style="width:15px;height:15px"></i> Kaydet</button></div>
        </form>
    </div>
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    // CTA preview
    [['ctaInH','ctaPrevH'],['ctaInP','ctaPrevP'],['ctaInBtn','ctaPrevBtn']].forEach(([s,t]) => {
        const i = document.getElementById(s), p = document.getElementById(t);
        if (i && p) i.addEventListener('input', () => { p.textContent = i.value || '...'; });
    });

    // Drag & drop sections
    const ol = document.getElementById('orderList');
    if (ol) {
        let d = null;
        ol.querySelectorAll('.ha-order-item').forEach(item => {
            item.addEventListener('dragstart', function(e) { d = this; this.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
            item.addEventListener('dragend', function() { this.classList.remove('dragging'); d = null; ol.querySelectorAll('.ha-order-item').forEach((el, idx) => { el.querySelector('.ha-order-sort').value = idx + 1; }); });
            item.addEventListener('dragover', function(e) { e.preventDefault(); if (!d || d === this) return; const r = this.getBoundingClientRect(); if (e.clientY < r.top + r.height/2) ol.insertBefore(d, this); else ol.insertBefore(d, this.nextSibling); });
        });
    }

    // Image preview on file select
    document.querySelectorAll('.ha-bento-thumb input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const thumb = this.closest('.ha-bento-thumb');
                const reader = new FileReader();
                reader.onload = function(e) {
                    thumb.innerHTML = '<img src="' + e.target.result + '"><div class="ha-bento-thumb-overlay"><i data-lucide="check" style="width:20px;height:20px"></i></div><input type="file" name="' + input.name + '" accept="image/jpeg,image/png,image/webp">';
                    thumb.querySelector('input[type="file"]').files = input.files;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Color preview
    document.querySelectorAll('.ha-input-color').forEach(input => {
        input.addEventListener('input', function() {
            const item = this.closest('.ha-bento-item');
            if (!item) return;
            const box = item.querySelector('.ha-bento-thumb-empty');
            if (box) {
                const colors = item.querySelectorAll('.ha-input-color');
                if (colors.length === 2) box.style.background = `linear-gradient(135deg, ${colors[0].value}, ${colors[1].value})`;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
