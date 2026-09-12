<?php
/**
 * ═══════════════════════════════════════════
 * DOSYA 3: /admin/includes/sidebar.php
 * ═══════════════════════════════════════════
 */
// admin/includes/sidebar.php
?>
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <span class="sidebar-logo">avrupa<em>pazari</em></span>
    <span class="sidebar-badge">Admin</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-group">
      <div class="nav-group-title">Genel</div>
      <a href="<?= BASE_URL ?>/admin/index.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
        Dashboard
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">Anasayfa</div>
      <a href="<?= BASE_URL ?>/admin/homepage.php" class="nav-item <?= $currentPage==='homepage'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
        Anasayfa Yönetimi
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">İçerik</div>
      <a href="<?= BASE_URL ?>/admin/listings.php" class="nav-item <?= $currentPage==='listings'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></span>
        İlanlar
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">Sistem</div>
      <a href="<?= BASE_URL ?>/admin/site-mode.php" class="nav-item <?= $currentPage==='site-mode'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="M8 3.5 6.5 2M16 3.5 17.5 2"/></svg></span>
        Site Online / Offline
      </a>
    </div>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-item logout">
      <span class="icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
      Çıkış
    </a>
  </div>
</aside>
