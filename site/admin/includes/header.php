<?php
/**
 * ═══════════════════════════════════════════
 * DOSYA 2: /admin/includes/header.php
 * ═══════════════════════════════════════════
 */
// admin/includes/header.php
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - AvrupaPazari</title>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-layout">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="admin-main">
<header class="admin-header">
  <div class="admin-header-left">
    <h1 class="admin-page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
  </div>
  <div class="admin-header-right">
    <a href="<?= BASE_URL ?>/" target="_blank" class="admin-site-link">
      <span class="icon"><svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span>
      Siteyi Gör
    </a>
    <div class="admin-user">
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
      <span><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
    </div>
  </div>
</header>
<div class="admin-content">
<?php // Sayfa içeriği buraya ?>
