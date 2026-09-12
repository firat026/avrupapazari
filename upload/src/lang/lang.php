<?php
/**
 * AvrupaPazari - Multi-Language System
 * Supports: TR, NL, EN, DE
 * 
 * KURULUM:
 * 1. Bu dosyayı includes/lang.php olarak kaydet
 * 2. lang/ klasörü oluştur, içine tr.php, nl.php, en.php koy
 * 3. Her sayfanın başında: require_once 'includes/lang.php'; (zaten config varsa config'e ekle)
 * 4. Metinleri t('key') ile çağır
 * 
 * KULLANIM:
 * echo t('nav.home');              // "Ana Sayfa" veya "Home" vs.
 * echo t('listing.views', ['count' => 150]);  // "150 görüntülenme"
 * echo currentLang();              // "tr"
 * echo langSwitcher();             // HTML dropdown
 * echo langSwitcher('inline');     // HTML inline links
 */

define('SUPPORTED_LANGS', ['tr', 'nl', 'en', 'de']);
define('DEFAULT_LANG', 'tr');
define('LANG_COOKIE_NAME', 'avrupa_lang');
define('LANG_COOKIE_DAYS', 365);

/**
 * Detect and set current language
 * Priority: URL param > Cookie > Session > Browser > Default
 */
function detectLanguage(): string {
    // 1. URL parameter (highest priority, also sets cookie)
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
        $lang = $_GET['lang'];
        setLangCookie($lang);
        return $lang;
    }

    // 2. Cookie (remembered choice)
    if (isset($_COOKIE[LANG_COOKIE_NAME]) && in_array($_COOKIE[LANG_COOKIE_NAME], SUPPORTED_LANGS)) {
        return $_COOKIE[LANG_COOKIE_NAME];
    }

    // 3. Session
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS)) {
        return $_SESSION['lang'];
    }

    // 4. Browser Accept-Language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        if (in_array($browserLang, SUPPORTED_LANGS)) {
            return $browserLang;
        }
    }

    // 5. Default
    return DEFAULT_LANG;
}

/**
 * Set language cookie
 */
function setLangCookie(string $lang): void {
    if (!headers_sent()) {
        setcookie(LANG_COOKIE_NAME, $lang, [
            'expires' => time() + (86400 * LANG_COOKIE_DAYS),
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
    }
    $_COOKIE[LANG_COOKIE_NAME] = $lang;
}

/**
 * Load translation file
 */
function loadTranslations(string $lang): array {
    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (file_exists($file)) {
        return require $file;
    }
    // Keep the catalog empty for a supported language that is DB-backed.
    // This prevents German pages from silently displaying Turkish text.
    $fallback = __DIR__ . '/../lang/' . DEFAULT_LANG . '.php';
    if ($lang !== 'de' && file_exists($fallback)) {
        return require $fallback;
    }
    return [];
}

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect and store language
$GLOBALS['current_lang'] = detectLanguage();
$_SESSION['lang'] = $GLOBALS['current_lang'];
$GLOBALS['translations'] = loadTranslations($GLOBALS['current_lang']);

/**
 * Translate a key
 * Supports dot notation: t('nav.home')
 * Supports placeholders: t('results.count', ['count' => 5]) where translation has :count
 * Falls back to key name if translation not found
 */
function t(string $key, array $params = []): string {
    $keys = explode('.', $key);
    $value = $GLOBALS['translations'];

    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            $value = null;
            break;
        }
    }

    // The translations table is the source of truth for DB-backed locales.
    // getDB() is available after config.php has finished loading.
    if (!is_string($value) && function_exists('getDB')) {
        static $dbTranslations = [];
        $cacheKey = currentLang() . '|' . $key;
        if (array_key_exists($cacheKey, $dbTranslations)) {
            $value = $dbTranslations[$cacheKey];
        } else {
            try {
                [$group, $name] = array_pad(explode('.', $key, 2), 2, '');
                $stmt = getDB()->prepare(
                    'SELECT value FROM translations WHERE lang = ? AND key_group = ? AND key_name = ? LIMIT 1'
                );
                $stmt->execute([currentLang(), $group, $name]);
                $dbTranslations[$cacheKey] = $stmt->fetchColumn();
                $value = $dbTranslations[$cacheKey];
            } catch (Throwable $e) {
                $value = null;
            }
        }
    }

    if (!is_string($value) || $value === '') {
        return $key;
    }

    // Replace :placeholder with values
    foreach ($params as $placeholder => $replacement) {
        $value = str_replace(':' . $placeholder, (string)$replacement, $value);
    }

    return $value;
}

/**
 * Get current language code
 */
function currentLang(): string {
    return $GLOBALS['current_lang'];
}

/**
 * Get HTML lang attribute value
 */
function htmlLang(): string {
    $map = ['tr' => 'tr', 'nl' => 'nl', 'en' => 'en', 'de' => 'de'];
    return $map[$GLOBALS['current_lang']] ?? 'tr';
}

/**
 * Build URL with lang parameter preserved
 */
function langUrl(string $url, ?string $lang = null): string {
    $lang = $lang ?? currentLang();
    $parts = parse_url($url);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) { parse_str($parts['query'], $query); }
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}

/**
 * Generate language switcher HTML (dropdown style)
 */
function langSwitcher(string $style = 'dropdown'): string {
    $current = currentLang();
    $uri = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;

    $langs = [
        'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe', 'short' => 'TR'],
        'nl' => ['flag' => '🇳🇱', 'name' => 'Nederlands', 'short' => 'NL'],
        'en' => ['flag' => '🇬🇧', 'name' => 'English', 'short' => 'EN'],
        'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch', 'short' => 'DE']
    ];

    if ($style === 'inline') {
        $html = '<div class="lang-switcher lang-inline">';
        foreach (SUPPORTED_LANGS as $lang) {
            $queryParams['lang'] = $lang;
            $url = $uri . '?' . http_build_query($queryParams);
            $active = ($lang === $current) ? ' active' : '';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="lang-item' . $active . '">';
            $html .= $langs[$lang]['flag'] . ' ' . $langs[$lang]['short'];
            $html .= '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    // Dropdown
    $html = '<div class="lang-switcher lang-dropdown">';
    $html .= '<button type="button" class="lang-current" onclick="this.parentElement.classList.toggle(\'open\')">';
    $html .= $langs[$current]['flag'] . ' ' . $langs[$current]['short'];
    $html .= ' <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
    $html .= '</button>';
    $html .= '<div class="lang-menu">';

    foreach (SUPPORTED_LANGS as $lang) {
        if ($lang === $current) continue;
        $queryParams['lang'] = $lang;
        $url = $uri . '?' . http_build_query($queryParams);
        $html .= '<a href="' . htmlspecialchars($url) . '" class="lang-option">';
        $html .= $langs[$lang]['flag'] . ' ' . $langs[$lang]['name'];
        $html .= '</a>';
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Language switcher CSS - include once in your header
 */
function langSwitcherCSS(): string {
    return '<style>
.lang-switcher{position:relative;display:inline-flex;align-items:center}
.lang-inline{gap:4px}
.lang-inline .lang-item{padding:6px 10px;border-radius:6px;text-decoration:none;font-size:13px;color:#94a3b8;transition:all .2s}
.lang-inline .lang-item:hover{color:#fff;background:rgba(255,255,255,.08)}
.lang-inline .lang-item.active{color:#fff;background:rgba(99,102,241,.2);font-weight:600}
.lang-dropdown .lang-current{display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05);color:#e2e8f0;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s}
.lang-dropdown .lang-current:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2)}
.lang-dropdown .lang-menu{position:absolute;top:calc(100% + 6px);right:0;min-width:160px;background:#1e293b;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:6px;opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s;box-shadow:0 10px 40px rgba(0,0,0,.4);z-index:1000}
.lang-dropdown.open .lang-menu{opacity:1;visibility:visible;transform:translateY(0)}
.lang-dropdown .lang-option{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:7px;text-decoration:none;color:#cbd5e1;font-size:14px;transition:all .15s}
.lang-dropdown .lang-option:hover{background:rgba(99,102,241,.15);color:#fff}
</style>
<script>document.addEventListener("click",function(e){document.querySelectorAll(".lang-dropdown.open").forEach(function(d){if(!d.contains(e.target))d.classList.remove("open")})});</script>';
}
