<?php
declare(strict_types=1);

// Translations are loaded from the `translations` table only. English is the fallback language.

function detectLanguage(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $lang = $_GET['lang'];
        if (!headers_sent()) {
            setcookie('site_lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax']);
        }
        $_COOKIE['site_lang'] = $lang;
        return $lang;
    }
    if (isset($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], SUPPORTED_LANGS, true)) {
        return $_COOKIE['site_lang'];
    }
    $browser = strtolower(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), 0, 2));
    return in_array($browser, SUPPORTED_LANGS, true) ? $browser : DEFAULT_LANG;
}

function currentLang(): string
{
    static $lang = null;
    return $lang ??= detectLanguage();
}

function translations(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [];
    try {
        $stmt = getDB()->prepare("SELECT lang, CONCAT(key_group, '.', key_name) AS k, value FROM translations WHERE lang IN (?, 'en')");
        $stmt->execute([currentLang()]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if ($row['lang'] === 'en') {
                $map[$row['k']] ??= $row['value'];
            }
        }
        foreach ($rows as $row) {
            if ($row['lang'] === currentLang()) {
                $map[$row['k']] = $row['value'];
            }
        }
    } catch (Throwable $e) {
        error_log('translations: ' . $e->getMessage());
    }
    return $map;
}

function t(string $key, array $params = []): string
{
    $value = translations()[$key] ?? $key;
    foreach ($params as $name => $replacement) {
        $value = str_replace(':' . $name, (string)$replacement, $value);
    }
    return $value;
}

function langUrl(string $lang): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $query = $_GET;
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}

function translationGroup(string $group): array
{
    $out = [];
    $prefix = $group . '.';
    foreach (translations() as $key => $value) {
        if (str_starts_with($key, $prefix)) {
            $out[substr($key, strlen($prefix))] = $value;
        }
    }
    return $out;
}
