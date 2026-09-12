<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path, string $version = '3'): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function timeAgo(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->y > 0) return t('time.years_ago', ['count' => $diff->y]);
    if ($diff->m > 0) return t('time.months_ago', ['count' => $diff->m]);
    if ($diff->d > 0) return t('time.days_ago', ['count' => $diff->d]);
    if ($diff->h > 0) return t('time.hours_ago', ['count' => $diff->h]);
    if ($diff->i > 0) return t('time.minutes_ago', ['count' => $diff->i]);
    return t('time.just_now');
}

function formatPrice(float|int|string|null $price): string
{
    $price = (float)$price;
    return $price > 0 ? '€ ' . number_format($price, 0, ',', '.') : t('common.free');
}

function formatNumber(int $num): string
{
    return $num >= 1000 ? number_format($num / 1000, 1, ',', '.') . 'K' : (string)$num;
}

function slugify(string $text): string
{
    $text = str_replace(['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'], $text);
    $text = mb_strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function imageUrl(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return url('uploads/' . ltrim($path, '/'));
}

function categoryUrl(array $category): string
{
    return match ($category['module'] ?? '') {
        'emlak' => url('pages/property.php'),
        'arac' => url('pages/search-vehicles.php'),
        'ikinci_el' => url('pages/second-hand.php'),
        'esnaf' => url('pages/businesses.php'),
        'jobs' => url('pages/jobs.php'),
        default => url('pages/category.php?id=' . (int)($category['id'] ?? 0)),
    };
}

function siteSettings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $settings = [];
    try {
        foreach (getDB()->query('SELECT key_name, value FROM settings') as $row) {
            $settings[$row['key_name']] = $row['value'];
        }
    } catch (Throwable $e) {
    }
    return $settings;
}

function setting(string $key, string $default = ''): string
{
    $value = siteSettings()[$key] ?? '';
    return $value !== '' && $value !== null ? (string)$value : $default;
}

function isActivePage(string $page): bool
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $page;
}
