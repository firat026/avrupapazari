<?php
declare(strict_types=1);

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfValid(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function currentUser(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        try {
            $stmt = getDB()->prepare('SELECT id, email, first_name, last_name, phone, role, profile_image, preferred_lang, created_at FROM users WHERE id = ? AND is_active = 1 AND is_banned = 0 LIMIT 1');
            $stmt->execute([(int)$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            $user = null;
        }
        if (!$user) {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    try {
        getDB()->prepare('UPDATE users SET last_login = NOW(), last_seen = NOW() WHERE id = ?')->execute([$userId]);
    } catch (Throwable $e) {
    }
}

function logoutUser(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        header('Location: ' . BASE_URL . '/?auth=login');
        exit;
    }
    return $user;
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function googleRedirectUri(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/auth/google-callback.php';
}
