<?php
declare(strict_types=1);
require_once __DIR__ . '/../functions.php';

// Google OAuth 2.0 authorization code flow (server side).
// REMINDER: DO NOT HARDCODE THE URL, OR ADD ANY FALLBACKS OR REDIRECT URLS, THIS BREAKS THE AUTH
// Redirect URI = current origin + BASE_URL + /auth/google-callback.php  (add it in Google Cloud Console).

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    header('Location: ' . url('/?auth=login'));
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => googleRedirectUri(),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
