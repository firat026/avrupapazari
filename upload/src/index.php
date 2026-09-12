<?php
/**
 * ═══════════════════════════════════════════════════════
 * /index.php — Homepage (Admin'den tamamen kontrol edilir)
 * ═══════════════════════════════════════════════════════
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Public site switch controlled from /admin/site-mode.php.
$siteModeFile = __DIR__ . '/admin/.site_mode';
$siteMode = is_file($siteModeFile) ? trim((string)file_get_contents($siteModeFile)) : 'online';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($siteMode === 'offline' && strpos($requestPath, '/admin/') === false && basename($requestPath) !== 'maintenance.php') {
    header('Location: ' . BASE_URL . '/maintenance.php', true, 302);
    exit;
}

$pageTitle = 'AvrupaPazari - ' . t('footer.slogan');
$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");
$lang = currentLang();

// ═══════ SETTINGS ═══════
$settingsKeyCol = 'key_name';
$settingsValCol = 'value';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    $keyOptions = ['key_name', 'setting_key', 'name', 'key', 'option_name'];
    $valOptions = ['value', 'setting_value', 'val', 'option_value'];
    foreach ($keyOptions as $k) { if (in_array($k, $cols)) { $settingsKeyCol = $k; break; } }
    foreach ($valOptions as $v) { if (in_array($v, $cols)) { $settingsValCol = $v; break; } }
} catch(Exception $e) {}

$settings = [];
try {
    $stmt = $pdo->query("SELECT `{$settingsKeyCol}`, `{$settingsValCol}` FROM settings");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { $settings[$r[$settingsKeyCol]] = $r[$settingsValCol]; }
} catch(Exception $e) {}
$s = function($key, $default = '') use ($settings) { return (!empty($settings[$key])) ? $settings[$key] : $default; };

// ═══════ SECTIONS ═══════
$sections = [];
try {
    $stmt = $pdo->query("SELECT section_key FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC");
    $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) { $sections = ['bento', 'popular', 'featured', 'howto', 'cta']; }

// ═══════ DATA ═══════

// Bento
$categories = [];
if (in_array('bento', $sections)) {
    $bentoCount = (int)$s('bento_count', 4);
    try {
        $stmt = $pdo->query("
            SELECT c.id, c.module, c.slug, c.icon, c.color_1, c.color_2, c.image, ct.name, ct.description,
            (SELECT COUNT(*) FROM listings l WHERE (l.category_id = c.id OR l.category_id IN (SELECT id FROM categories WHERE parent_id = c.id)) AND l.status = 'active') as listing_count
            FROM categories c
            LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = '{$lang}'
            WHERE c.is_active = 1 AND c.parent_id IS NULL
            ORDER BY c.sort_order ASC
            LIMIT {$bentoCount}
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        usort($categories, static function (array $left, array $right): int {
            $order = ['arac' => 0, 'ikinci_el' => 1, 'emlak' => 2, 'esnaf' => 3];
            return ($order[$left['module'] ?? ''] ?? 99) <=> ($order[$right['module'] ?? ''] ?? 99);
        });
    } catch (Exception $e) { $categories = []; }
}

// Popular
$popularListings = [];
if (in_array('popular', $sections)) {
    $popularCount = (int)$s('popular_count', 4);
    try {
        $stmt = $pdo->query("
            SELECT l.*, ci.name as city_name
            FROM listings l
            LEFT JOIN cities ci ON ci.id = l.city_id
            WHERE l.status = 'active'
            ORDER BY l.is_premium DESC, l.view_count DESC, l.created_at DESC
            LIMIT {$popularCount}
        ");
        $popularListings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $popularListings = []; }
}

// Esnaf
$featuredEsnaf = [];
if (in_array('featured', $sections)) {
    $featuredCount = (int)$s('featured_count', 4);
    try {
        $stmt = $pdo->query("SELECT * FROM esnaf WHERE is_featured = 1 AND status = 'active' ORDER BY rating DESC LIMIT {$featuredCount}");
        $featuredEsnaf = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        try { $stmt = $pdo->query("SELECT * FROM esnaf WHERE is_featured = 1 ORDER BY rating DESC LIMIT {$featuredCount}"); $featuredEsnaf = $stmt->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e2) {
            try { $stmt = $pdo->query("SELECT * FROM esnaf ORDER BY rating DESC LIMIT {$featuredCount}"); $featuredEsnaf = $stmt->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e3) {}
        }
    }
}

$defaultColors = [['#1a5c3a','#0d3d25'],['#1a6b7a','#0d4a55'],['#7a5a3a','#5a3d22'],['#2a3548','#1a2233']];

include __DIR__ . '/includes/header.php';

foreach ($sections as $sectionKey):

// ─────── BENTO GRID ───────
if ($sectionKey === 'bento'): ?>
<div class="bento">
<?php if (!empty($categories)):
    foreach ($categories as $i => $cat):
        $c1 = !empty($cat['color_1']) ? $cat['color_1'] : $defaultColors[$i % 4][0];
        $c2 = !empty($cat['color_2']) ? $cat['color_2'] : $defaultColors[$i % 4][1];
        $isLarge = ($i === 0);
        $catName = $cat['name'] ?? $cat['module'];
        $catImg = !empty($cat['image']) ? BASE_URL . '/uploads/' . $cat['image'] : '';
?>
<?php
        $categoryUrl = match ($cat['module'] ?? '') {
            'emlak' => BASE_URL . '/pages/property.php',
            'arac' => BASE_URL . '/pages/search-vehicles.php',
            'ikinci_el' => BASE_URL . '/pages/second-hand.php',
            'esnaf' => BASE_URL . '/pages/businesses.php',
            default => BASE_URL . '/pages/kategori.php?id=' . (int)$cat['id'],
        };
?>
<a href="<?= e($categoryUrl) ?>" class="bento-card <?= $isLarge ? 'bento-card-large' : '' ?>">
        <?php if ($catImg): ?>
        <div class="bento-card-bg" style="background-image:url('<?= e($catImg) ?>');background-size:cover;background-position:center;"></div>
        <?php else: ?>
        <div class="bento-card-bg" style="background: linear-gradient(135deg, <?= e($c1) ?>, <?= e($c2) ?>);"></div>
        <?php endif; ?>
        <div class="bento-card-overlay"></div>
        <div class="bento-card-content">
            <div class="bento-card-tag"><i data-lucide="<?= e($cat['icon'] ?? 'circle') ?>" style="width:16px;height:16px"></i><?= $isLarge ? ' ' . t('common.popular') : '' ?></div>
            <div class="bento-card-title"><?= e($catName) ?></div>
            <div class="bento-card-count"><?= (int)$cat['listing_count'] ?>+ <?= t('home.listings') ?></div>
        </div>
    </a>
<?php endforeach; ?>
    <a href="<?= BASE_URL ?>/pages/kategori.php?slug=is-ilanlari" class="bento-card bento-card-jobs">
        <div class="bento-card-bg"></div>
        <div class="bento-card-overlay"></div>
        <div class="bento-card-content">
            <div class="bento-card-tag"><i data-lucide="briefcase-business" style="width:16px;height:16px"></i><?= e(t('home.jobs_badge')) ?></div>
            <div class="bento-card-title"><?= e(t('home.jobs_workers')) ?></div>
            <div class="bento-card-count"><?= e(t('home.jobs_workers_hint')) ?></div>
        </div>
    </a>
<?php else: ?>
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-3);"><?= t('home.no_categories') ?></div>
<?php endif; ?>
</div>

<?php
// ─────── POPÜLER İLANLAR ───────
elseif ($sectionKey === 'popular'): ?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title"><?= t('home.popular_listings') ?></h2>
        <a href="<?= BASE_URL ?>/pages/search-vehicles.php" class="section-link">
<?= t('common.show_all') ?>
<i data-lucide="chevron-right" style="width:16px;height:16px"></i></a>
    </div>
    <div class="listings-grid">
    <?php if (!empty($popularListings)):
        foreach ($popularListings as $listing):
            $img = !empty($listing['image']) ? BASE_URL . '/uploads/' . $listing['image'] : '';
            $cityName = $listing['city_name'] ?? '';
    ?>
        <a href="<?= BASE_URL ?>/pages/listing.php?slug=<?= e($listing['slug']) ?>" class="listing-card">
            <?php if (!empty($listing['is_premium'])): ?><span class="listing-badge"><?= t('common.premium') ?></span><?php endif; ?>
            <div class="listing-img" style="<?= $img ? "background-image:url('$img');background-size:cover;background-position:center;" : "background: linear-gradient(135deg, #2a5a4a, #1a3a30);" ?>"></div>
            <div class="listing-body">
                <div class="listing-price">€ <?= number_format($listing['price'], 0, ',', '.') ?><?= ($listing['price_type'] ?? '') === 'negotiable' ? ' <small style="font-size:0.7rem;color:var(--text-3)">○ ' . t('listing.price_negotiable') . '</small>' : '' ?></div>
                <div class="listing-title"><?= e($listing['title']) ?></div>
                <div class="listing-meta">
                    <span style="display:flex;align-items:center;gap:3px"><i data-lucide="map-pin" style="width:13px;height:13px"></i> <?= e($cityName) ?></span>
                    <span><?= timeAgo($listing['created_at']) ?></span>
                </div>
            </div>
        </a>
    <?php endforeach; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-3);"><?= t('home.no_listings') ?></div>
    <?php endif; ?>
    </div>
</div>

<?php
// ─────── ÖNE ÇIKAN ESNAFLAR ───────
elseif ($sectionKey === 'featured'): ?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title"><?= t('home.featured_businesses') ?></h2>
        <a href="<?= BASE_URL ?>/pages/esnaf-rehberi.php" class="section-link"><?= e($s('featured_link_text', t('common.show_all'))) ?> <i data-lucide="chevron-right" style="width:16px;height:16px"></i></a>
    </div>
    <div class="esnaf-grid">
    <?php if (!empty($featuredEsnaf)):
        $esnafColors = [['#1d7a4e','#156b42'],['#7a5a3a','#5a3d22'],['#1a6b7a','#0d4a55'],['#2a3548','#1a2233']];
        foreach ($featuredEsnaf as $i => $esnaf):
            $ec = $esnafColors[$i % 4];
            $parts = explode(' ', $esnaf['name'] ?? '');
            $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1)) . mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
            $esnafImg = !empty($esnaf['image']) ? BASE_URL . '/uploads/' . $esnaf['image'] : '';
    ?>
        <a href="<?= BASE_URL ?>/esnaf.php?slug=<?= e($esnaf['slug'] ?? '') ?>" class="esnaf-card">
            <?php if ($esnafImg): ?>
            <div class="esnaf-avatar" style="background-image:url('<?= e($esnafImg) ?>');background-size:cover;background-position:center;color:transparent;"><?= $initials ?></div>
            <?php else: ?>
            <div class="esnaf-avatar" style="background: linear-gradient(135deg, <?= $ec[0] ?>, <?= $ec[1] ?>);"><?= $initials ?></div>
            <?php endif; ?>
            <div class="esnaf-name"><?= e($esnaf['name'] ?? '') ?></div>
            <div class="esnaf-cat"><?= e($esnaf['city'] ?? '') ?><?= !empty($esnaf['country']) ? ', ' . e($esnaf['country']) : '' ?></div>
            <div class="esnaf-location"><i data-lucide="map-pin" style="width:13px;height:13px"></i> <?= e($esnaf['city'] ?? '') ?></div>
            <div class="esnaf-rating"><?= str_repeat('★', (int)round($esnaf['rating'] ?? 0)) ?><?= str_repeat('☆', 5 - (int)round($esnaf['rating'] ?? 0)) ?> <?= number_format($esnaf['rating'] ?? 0, 1) ?></div>
        </a>
    <?php endforeach; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-3);"><?= t('home.no_businesses') ?></div>
    <?php endif; ?>
    </div>
</div>

<?php
// ─────── NASIL ÇALIŞIR ───────
elseif ($sectionKey === 'howto'):
    $steps = [
        ['title' => $s('step1_title', t('home.step1_title')), 'desc' => $s('step1_desc', t('home.step1_desc'))],
        ['title' => $s('step2_title', t('home.step2_title')), 'desc' => $s('step2_desc', t('home.step2_desc'))],
        ['title' => $s('step3_title', t('home.step3_title')), 'desc' => $s('step3_desc', t('home.step3_desc'))],
    ];
?>
<div class="section">
    <div class="section-header">
        <h2 class="section-title"><?= t('home.how_it_works') ?></h2>
    </div>
    <div class="steps-grid">
        <?php foreach($steps as $idx => $step): ?>
        <div class="step-card">
            <div class="step-num"><?= $idx + 1 ?></div>
            <div class="step-title"><?= e($step['title']) ?></div>
            <div class="step-desc"><?= e($step['desc']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// ─────── CTA ───────
elseif ($sectionKey === 'cta'):
    $ctaUrl = $s('cta_url', '/ilan-ver.php');
?>
<div class="cta-banner">
    <div class="cta-inner">
        <div class="cta-content">
            <div class="cta-title"><?= e($s('cta_title', t('home.cta_title'))) ?></div>
            <div class="cta-desc"><?= e($s('cta_desc', t('home.cta_desc'))) ?></div>
        </div>
        <a href="<?= BASE_URL ?><?= e($ctaUrl) ?>" class="cta-btn"><?= e($s('cta_btn', t('home.cta_btn'))) ?></a>
    </div>
</div>

<?php endif;
endforeach; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
