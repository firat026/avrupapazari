<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
header('Location: ' . BASE_URL . '/admin/login.php');
exit;