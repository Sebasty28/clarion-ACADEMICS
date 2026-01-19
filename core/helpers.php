<?php
// core/helpers.php
require_once __DIR__ . '/../config/config.php';

function start_session(): void {
    // Start session safely (must happen before any output)
    if (session_status() === PHP_SESSION_NONE) {
        // If headers are already sent, we cannot change session ini settings or start session
        if (!headers_sent($file, $line)) {
            // Only set these before session_start()
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            // If you're on HTTPS (Render), make cookies secure + SameSite
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            ini_set('session.cookie_secure', $isHttps ? '1' : '0');
            ini_set('session.cookie_samesite', 'Lax');

            // Set your session name BEFORE starting session (if defined)
            if (defined('SESSION_NAME') && SESSION_NAME) {
                session_name(SESSION_NAME);
            }

            session_start();
        } else {
            // Optional: comment this out in production
            // error_log("Session headers already sent in $file:$line");
        }
    }
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function base_path(string $path = ''): string {
    $root = rtrim(realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'), DIRECTORY_SEPARATOR);
    if ($path === '') return $root;
    return $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function set_flash(string $key, string $value): void {
    start_session();
    $_SESSION['flash'][$key] = $value;
}

function get_flash(string $key): ?string {
    start_session();
    if (!empty($_SESSION['flash'][$key])) {
        $val = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    return null;
}
