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
      <a href="<?= BASE_URL ?>/admin/categories.php" class="nav-item <?= $currentPage==='categories'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span>
        Kategoriler
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">İçerik</div>
      <a href="<?= BASE_URL ?>/admin/listings.php" class="nav-item <?= $currentPage==='listings'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></span>
        İlanlar
      </a>
      <a href="<?= BASE_URL ?>/admin/esnaf.php" class="nav-item <?= $currentPage==='esnaf'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
        Esnaflar
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-group-title">Sistem</div>
      <a href="<?= BASE_URL ?>/admin/site-mode.php" class="nav-item <?= $currentPage==='site-mode'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="M8 3.5 6.5 2M16 3.5 17.5 2"/></svg></span>
        Site Online / Offline
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php" class="nav-item <?= $currentPage==='users'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
        Kullanıcılar
      </a>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-item <?= $currentPage==='settings'?'active':'' ?>">
        <span class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
        Ayarlar
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
