<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

logoutUser();
header('Location: ' . url('/'));
exit;
