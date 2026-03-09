<?php
/**
 * SMM Turk - Language detection (IP-based + cookie override) and translations
 * Supported: en, tr, de, fr
 */
class Lang {
    private static ?string $current = null;
    private static array $strings = [];
    private static array $allowed = ['en', 'tr', 'de', 'fr'];

    public static function init(): string {
        if (self::$current !== null) {
            return self::$current;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $lang = null;
        if (!empty($_GET['lang']) && in_array($_GET['lang'], self::$allowed, true)) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
            setcookie('lang', $lang, time() + 31536000, '/', '', true, true);
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $q = $_GET;
            unset($q['lang']);
            $redirect = $uri . ($q ? '?' . http_build_query($q) : '');
            header('Location: ' . $redirect, true, 302);
            exit;
        }
        if ($lang === null && !empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], self::$allowed, true)) {
            $lang = $_COOKIE['lang'];
        }
        if ($lang === null && !empty($_SESSION['lang']) && in_array($_SESSION['lang'], self::$allowed, true)) {
            $lang = $_SESSION['lang'];
        }
        if ($lang === null) {
            $lang = self::detectFromIp();
        }
        if ($lang === null) {
            $lang = self::detectFromBrowser();
        }
        self::$current = $lang ?: 'en';
        $_SESSION['lang'] = self::$current;
        self::load(self::$current);
        return self::$current;
    }

    private static function detectFromIp(): ?string {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = trim(explode(',', $ip)[0]);
        if ($ip === '' || $ip === '127.0.0.1' || strpos($ip, '192.168.') === 0) {
            return null;
        }
        if (!isset($_SESSION['ip_country'])) {
            $raw = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=countryCode', false, stream_context_create(['http' => ['timeout' => 2]]));
            $data = $raw ? json_decode($raw, true) : null;
            $_SESSION['ip_country'] = $data['countryCode'] ?? '';
        }
        $code = strtoupper($_SESSION['ip_country'] ?? '');
        $map = ['TR' => 'tr', 'DE' => 'de', 'AT' => 'de', 'CH' => 'de', 'FR' => 'fr', 'BE' => 'fr'];
        return $map[$code] ?? null;
    }

    private static function detectFromBrowser(): ?string {
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach (explode(',', $accept) as $part) {
            $tag = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
            if (in_array($tag, self::$allowed, true)) {
                return $tag;
            }
        }
        return null;
    }

    private static function load(string $code): void {
        $file = dirname(__DIR__) . '/lang/' . $code . '.php';
        self::$strings = is_file($file) ? (require $file) : (require dirname(__DIR__) . '/lang/en.php');
    }

    public static function get(string $key): string {
        return self::$strings[$key] ?? $key;
    }

    public static function current(): string {
        return self::$current ?? self::init();
    }

    public static function allowed(): array {
        return self::$allowed;
    }
}

function __(string $key): string {
    return Lang::get($key);
}
