<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

// REMINDER: DO NOT HARDCODE THE URL, OR ADD ANY FALLBACKS OR REDIRECT URLS, THIS BREAKS THE AUTH

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
if ($code === '' || $state === '' || !hash_equals((string)($_SESSION['google_oauth_state'] ?? ''), $state)) {
    header('Location: ' . url('/?auth=login&error=google'));
    exit;
}
unset($_SESSION['google_oauth_state']);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => googleRedirectUri(),
        'grant_type' => 'authorization_code',
    ]),
]);
$token = json_decode((string)curl_exec($ch), true) ?: [];
curl_close($ch);

$idToken = (string)($token['id_token'] ?? '');
if ($idToken === '') {
    header('Location: ' . url('/?auth=login&error=google'));
    exit;
}

// Google validates the signature for us.
$info = json_decode((string)file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken)), true) ?: [];
if (($info['aud'] ?? '') !== GOOGLE_CLIENT_ID || empty($info['email']) || ($info['email_verified'] ?? 'false') !== 'true') {
    header('Location: ' . url('/?auth=login&error=google'));
    exit;
}

$email = strtolower((string)$info['email']);
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id, is_active, is_banned FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $firstName = trim((string)($info['given_name'] ?? '')) ?: 'Google';
    $lastName = trim((string)($info['family_name'] ?? '')) ?: 'User';
    $slug = slugify($firstName . ' ' . $lastName) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    $pdo->prepare('INSERT INTO users (email, password, first_name, last_name, slug, profile_image, role, email_verified, is_active, is_banned, preferred_lang, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, 0, ?, NOW(), NOW())')
        ->execute([$email, password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), $firstName, $lastName, $slug, (string)($info['picture'] ?? ''), 'user', currentLang()]);
    $userId = (int)$pdo->lastInsertId();
} elseif (!(int)$user['is_active'] || (int)$user['is_banned']) {
    header('Location: ' . url('/?auth=login&error=disabled'));
    exit;
} else {
    $userId = (int)$user['id'];
}

loginUser($userId);
header('Location: ' . url('account.php'));
exit;
