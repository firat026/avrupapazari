<?php
/**
 * ═══════════════════════════════════════════
 * DOSYA 1: /admin/index.php (Dashboard)
 * ═══════════════════════════════════════════
 */
// admin/index.php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$pdo = getDB();

$stats = [
    'listings' => 0,
    'esnaf' => 0,
    'users' => 0,
    'pending' => 0,
];
try {
    $stats['listings'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn();
    $stats['esnaf'] = (int)$pdo->query("SELECT COUNT(*) FROM esnaf WHERE status='active'")->fetchColumn();
    $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
    $stats['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='pending'")->fetchColumn();
} catch(Exception $e) {}

// Son ilanlar
$recentListings = [];
try {
    $recentListings = $pdo->query("SELECT id, title, price, status, is_premium, created_at FROM listings ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-card-label">Aktif İlanlar</div>
    <div class="stat-card-value"><?= number_format($stats['listings']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-label">Esnaflar</div>
    <div class="stat-card-value"><?= number_format($stats['esnaf']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-label">Kullanıcılar</div>
    <div class="stat-card-value"><?= number_format($stats['users']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-label">Onay Bekleyen</div>
    <div class="stat-card-value"><?= number_format($stats['pending']) ?></div>
    <?php if($stats['pending'] > 0): ?><div class="stat-card-change" style="color:#b8860b">⚠ <?= $stats['pending'] ?> ilan bekliyor</div><?php endif; ?>
  </div>
</div>

<div class="admin-table-wrap">
  <div class="admin-table-header">
    <span class="admin-table-title">Son İlanlar</span>
    <a href="<?= BASE_URL ?>/admin/listings.php" class="btn btn-outline btn-sm">Tümünü Gör</a>
  </div>
  <table class="admin-table">
    <thead>
      <tr><th>İlan</th><th>Fiyat</th><th>Durum</th><th>Tarih</th></tr>
    </thead>
    <tbody>
      <?php foreach($recentListings as $l): ?>
      <tr>
        <td><strong><?= e($l['title']) ?></strong><?= $l['is_premium'] ? ' <span class="badge badge-yellow">Premium</span>' : '' ?></td>
        <td>€ <?= number_format($l['price'], 0, ',', '.') ?></td>
        <td><span class="badge <?= $l['status']==='active'?'badge-green':($l['status']==='pending'?'badge-yellow':'badge-red') ?>"><?= ucfirst($l['status']) ?></span></td>
        <td style="color:#7a9488;font-size:0.78rem"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($recentListings)): ?>
      <tr><td colspan="4" style="text-align:center;color:#7a9488;padding:32px;">Henüz ilan yok.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
