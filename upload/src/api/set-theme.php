<?php
/**
 * ═══════════════════════════════════════════
 * DOSYA 1: /api/set-theme.php
 * ═══════════════════════════════════════════
 */

// api/set-theme.php
session_start();
$theme = $_GET['theme'] ?? 'light';
if (in_array($theme, ['light', 'dark'])) {
    $_SESSION['theme'] = $theme;
}
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'theme' => $theme]);
exit;