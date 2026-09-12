<?php
/**
 * admin/listings.php - İlan Yönetimi
 * Konum: /admin/listings.php
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'İlanlar';
$currentPage = 'listings';
$pdo = getDB();
$lang = $_SESSION['lang'] ?? 'tr';
$msg = '';

// SİL
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/listings.php?msg=deleted');
    exit;
}

// DURUM DEĞİŞTİR
if (isset($_GET['status_id']) && isset($_GET['new_status'])) {
    $allowed = ['active','pending','sold','expired'];
    if (in_array($_GET['new_status'], $allowed)) {
        $pdo->prepare("UPDATE listings SET status = ? WHERE id = ?")->execute([$_GET['new_status'], (int)$_GET['status_id']]);
    }
    header('Location: ' . BASE_URL . '/admin/listings.php?msg=updated');
    exit;
}

// PREMIUM TOGGLE
if (isset($_GET['premium_id'])) {
    $pdo->prepare("UPDATE listings SET is_premium = NOT is_premium WHERE id = ?")->execute([(int)$_GET['premium_id']]);
    header('Location: ' . BASE_URL . '/admin/listings.php?msg=updated');
    exit;
}

// Mesaj
if (isset($_GET['msg'])) {
    $msgs = ['deleted'=>'İlan silindi.','updated'=>'İlan güncellendi.','added'=>'İlan eklendi.'];
    $msg = $msgs[$_GET['msg']] ?? '';
}

// Filtre
$statusFilter = $_GET['filter'] ?? '';
$where = "1=1";
if ($statusFilter && in_array($statusFilter, ['active','pending','sold','expired'])) {
    $where = "l.status = '$statusFilter'";
}

// İlanlar
$listings = [];
try {
    $listings = $pdo->query("
        SELECT l.*, ct.name as category_name, ci.name as city_name
        FROM listings l
        LEFT JOIN categories c ON c.id = l.category_id
        LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = 'tr'
        LEFT JOIN cities ci ON ci.id = l.city_id
        WHERE $where
        ORDER BY l.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // city join hata verirse city'siz dene
    try {
        $listings = $pdo->query("
            SELECT l.*, ct.name as category_name
            FROM listings l
            LEFT JOIN categories c ON c.id = l.category_id
            LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = 'tr'
            WHERE $where
            ORDER BY l.created_at DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e2) { $listings = []; }
}

// Sayılar
$counts = ['all'=>0,'active'=>0,'pending'=>0,'sold'=>0];
try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
    $counts['active'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn();
    $counts['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='pending'")->fetchColumn();
    $counts['sold'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='sold'")->fetchColumn();
} catch(Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<style>
.filter-tabs{display:flex;gap:4px;margin-bottom:20px}
.filter-tab{padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:600;color:#4a6355;text-decoration:none;border:1px solid #dce8e2;background:#fff;transition:all .15s}
.filter-tab:hover{border-color:#b0c8bb;background:#f7faf8}
.filter-tab.active{background:#e8f5ee;border-color:#1d7a4e;color:#1d7a4e}
.filter-tab .count{font-size:.7rem;background:#f0f5f2;padding:2px 6px;border-radius:4px;margin-left:4px}
.filter-tab.active .count{background:rgba(29,122,78,.1)}
.listing-actions{display:flex;gap:4px;flex-wrap:wrap}
.listing-actions .btn-sm{padding:4px 8px;font-size:.7rem}
.img-thumb{width:48px;height:48px;border-radius:8px;object-fit:cover;background:#f0f5f2;border:1px solid #dce8e2}
.premium-star{color:#b8860b;cursor:pointer;font-size:1.1rem;text-decoration:none}
.premium-star.off{color:#dce8e2}
</style>

<?php if($msg): ?><div style="background:#e8f5ee;border:1px solid #d4edde;color:#1d7a4e;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.84rem;font-weight:600"><?= e($msg) ?></div><?php endif; ?>

<!-- Filtreler -->
<div class="filter-tabs">
  <a href="<?= BASE_URL ?>/admin/listings.php" class="filter-tab <?= !$statusFilter?'active':'' ?>">Tümü <span class="count"><?= $counts['all'] ?></span></a>
  <a href="<?= BASE_URL ?>/admin/listings.php?filter=active" class="filter-tab <?= $statusFilter==='active'?'active':'' ?>">Aktif <span class="count"><?= $counts['active'] ?></span></a>
  <a href="<?= BASE_URL ?>/admin/listings.php?filter=pending" class="filter-tab <?= $statusFilter==='pending'?'active':'' ?>">Bekleyen <span class="count"><?= $counts['pending'] ?></span></a>
  <a href="<?= BASE_URL ?>/admin/listings.php?filter=sold" class="filter-tab <?= $statusFilter==='sold'?'active':'' ?>">Satıldı <span class="count"><?= $counts['sold'] ?></span></a>
</div>

<div class="admin-table-wrap">
  <div class="admin-table-header">
    <span class="admin-table-title">İlanlar (<?= count($listings) ?>)</span>
    <a href="<?= BASE_URL ?>/admin/listing-edit.php" class="btn btn-primary btn-sm">
      <span class="icon" style="width:14px;height:14px"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
      Yeni İlan
    </a>
  </div>
  <table class="admin-table">
    <thead>
      <tr>
        <th>★</th>
        <th>İlan</th>
        <th>Kategori</th>
        <th>Fiyat</th>
        <th>Durum</th>
        <th>Tarih</th>
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($listings as $l): ?>
      <tr>
        <td><a href="<?= BASE_URL ?>/admin/listings.php?premium_id=<?= $l['id'] ?>" class="premium-star <?= $l['is_premium']?'':'off' ?>" title="Premium">★</a></td>
        <td>
          <strong style="font-size:.88rem"><?= e($l['title']) ?></strong>
          <?php if(!empty($l['city_name'])): ?><br><span style="font-size:.72rem;color:#7a9488"><?= e($l['city_name']) ?></span><?php endif; ?>
        </td>
        <td><span style="font-size:.78rem"><?= e($l['category_name'] ?? $l['module'] ?? '-') ?></span></td>
        <td style="font-weight:700;white-space:nowrap">€ <?= number_format($l['price'], 0, ',', '.') ?></td>
        <td>
          <span class="badge <?= $l['status']==='active'?'badge-green':($l['status']==='pending'?'badge-yellow':'badge-red') ?>">
            <?= $l['status']==='active'?'Aktif':($l['status']==='pending'?'Bekliyor':($l['status']==='sold'?'Satıldı':'Süresi Doldu')) ?>
          </span>
        </td>
        <td style="font-size:.75rem;color:#7a9488;white-space:nowrap"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
        <td>
          <div class="listing-actions">
            <a href="<?= BASE_URL ?>/admin/listing-edit.php?id=<?= $l['id'] ?>" class="btn btn-outline btn-sm">Düzenle</a>
            <?php if($l['status'] === 'pending'): ?>
            <a href="<?= BASE_URL ?>/admin/listings.php?status_id=<?= $l['id'] ?>&new_status=active" class="btn btn-primary btn-sm">Onayla</a>
            <?php elseif($l['status'] === 'active'): ?>
            <a href="<?= BASE_URL ?>/admin/listings.php?status_id=<?= $l['id'] ?>&new_status=sold" class="btn btn-outline btn-sm">Satıldı</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/admin/listings.php?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu ilanı silmek istediğine emin misin?')">Sil</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($listings)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:#7a9488">Hiç ilan bulunamadı.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>