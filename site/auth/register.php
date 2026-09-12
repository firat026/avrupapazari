<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/?auth=register'));
    exit;
}
if (!csrfValid($_POST['csrf'] ?? null)) {
    jsonResponse(['ok' => false, 'error' => t('auth.session_expired')], 419);
}

$firstName = trim((string)($_POST['first_name'] ?? ''));
$lastName = trim((string)($_POST['last_name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($firstName === '' || $lastName === '' || mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
    jsonResponse(['ok' => false, 'error' => t('common.required')], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'error' => t('auth.invalid_email')], 422);
}
if (strlen($password) < 8) {
    jsonResponse(['ok' => false, 'error' => t('auth.password_min')], 422);
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['ok' => false, 'error' => t('auth.email_taken')], 409);
}

$slug = slugify($firstName . ' ' . $lastName) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
$pdo->prepare('INSERT INTO users (email, password, first_name, last_name, slug, role, email_verified, is_active, is_banned, preferred_lang, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, 1, 0, ?, NOW(), NOW())')
    ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $firstName, $lastName, $slug, 'user', currentLang()]);

loginUser((int)$pdo->lastInsertId());
jsonResponse(['ok' => true, 'redirect' => url('account.php')]);
