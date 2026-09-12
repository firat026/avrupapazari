<?php
// Legacy URL kept for old links -> pages/category.php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: category.php' . ($query ? '?' . $query : ''), true, 301);
exit;
