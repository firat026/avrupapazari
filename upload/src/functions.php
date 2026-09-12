<?php
/**
 * functions.php
 * Konum: /functions.php
 */

require_once __DIR__ . '/config.php';

/**
 * HTML escape
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * URL oluştur
 */
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Zaman farkı (örn: "2 saat önce")
 */
function timeAgo(?string $datetime): string {
    if (!$datetime) return '';
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' yıl önce';
    if ($diff->m > 0) return $diff->m . ' ay önce';
    if ($diff->d > 0) return $diff->d . ' gün önce';
    if ($diff->h > 0) return $diff->h . ' saat önce';
    if ($diff->i > 0) return $diff->i . ' dk önce';
    return 'Az önce';
}

/**
 * Güvenli sayı formatı
 */
function formatNumber(int $num): string {
    if ($num >= 1000) {
        return number_format($num / 1000, 1, ',', '.') . 'K';
    }
    return (string)$num;
}

/**
 * Slug oluştur
 */
function slugify(string $text): string {
    $text = mb_strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Resim URL
 */
function imageUrl(?string $path): string {
    if (!$path) return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return BASE_URL . '/uploads/' . ltrim($path, '/');
}

/**
 * Aktif sayfa kontrolü
 */
function isActivePage(string $page): bool {
    global $currentPage;
    return ($currentPage ?? '') === $page;
}