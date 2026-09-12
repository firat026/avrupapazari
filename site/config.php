<?php
declare(strict_types=1);

// Site base path. Leave '' for auto-detect (works for root, sub folders and /~user URLs). Or set manually, e.g. '/subfolder'.
$baseUrl = '';
if ($baseUrl === '') {
    $script = str_replace('\\', '/', (string)realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $root = str_replace('\\', '/', (string)realpath(__DIR__));
    $name = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script !== '' && str_starts_with($script, $root)) {
        $rel = substr($script, strlen($root));
        if ($rel !== '' && str_ends_with($name, $rel)) {
            $baseUrl = substr($name, 0, strlen($name) - strlen($rel));
        }
    }
}
define('BASE_URL', rtrim($baseUrl, '/'));

// MySQL credentials (cPanel -> MySQL Databases)
define('DB_HOST', 'localhost');
define('DB_NAME', 'avrupa_db');
define('DB_USER', 'avrupa_pazari');
define('DB_PASS', 'localdev');

// Google Sign-In (Google Cloud Console -> OAuth 2.0 Client). Leave empty to hide the Google button.
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

define('DEFAULT_LANG', 'tr');
define('SUPPORTED_LANGS', ['tr', 'nl', 'en', 'de']);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    // SameSite=None (with Secure) keeps the session alive when the site is shown inside an iframe/preview.
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $secure, 'samesite' => $secure ? 'None' : 'Lax']);
    session_start();
}

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        exit('Database connection failed. Please check DB_HOST / DB_NAME / DB_USER / DB_PASS in config.php. (' . htmlspecialchars($exception->getMessage()) . ')');
    }
    return $pdo;
}

require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/auth.php';
