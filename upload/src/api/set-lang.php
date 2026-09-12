<?php
// api/set-lang.php
session_start();
$lang = $_GET['lang'] ?? 'tr';
$redirect = $_GET['redirect'] ?? '/';
if (in_array($lang, ['tr', 'nl', 'en'])) {
    $_SESSION['lang'] = $lang; setcookie('avrupa_lang', $lang, time() + 31536000, '/');
}
header('Location: ' . $redirect);
exit;