<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/?auth=login'));
    exit;
}
if (!csrfValid($_POST['csrf'] ?? null)) {
    jsonResponse(['ok' => false, 'error' => t('auth.session_expired')], 419);
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    jsonResponse(['ok' => false, 'error' => t('auth.invalid_credentials')], 422);
}

$attemptKey = 'login_attempts_' . md5($email);
$attempts = $_SESSION[$attemptKey] ?? ['count' => 0, 'until' => 0];
if ($attempts['count'] >= 5 && $attempts['until'] > time()) {
    jsonResponse(['ok' => false, 'error' => t('auth.too_many_attempts')], 429);
}

$stmt = getDB()->prepare('SELECT id, password, is_active, is_banned FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string)$user['password'])) {
    $attempts['count']++;
    $attempts['until'] = time() + 300;
    $_SESSION[$attemptKey] = $attempts;
    jsonResponse(['ok' => false, 'error' => t('auth.invalid_credentials')], 401);
}
if (!(int)$user['is_active'] || (int)$user['is_banned']) {
    jsonResponse(['ok' => false, 'error' => t('auth.account_disabled')], 403);
}

unset($_SESSION[$attemptKey]);
loginUser((int)$user['id']);
jsonResponse(['ok' => true, 'redirect' => url('account.php')]);
