<?php
/**
 * ═══════════════════════════════════════════
 * DOSYA 1: /admin/includes/auth.php
 * Her admin sayfasının en üstüne require edilir
 * ═══════════════════════════════════════════
 */
// admin/includes/auth.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}
?>
