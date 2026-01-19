<?php
// core/helpers.php
require_once __DIR__ . '/../config/config.php';

// Force HTTPS on Render (proxy sets X-Forwarded-Proto)
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    header("Location: https://{$host}{$uri}", true, 301);
    exit;
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (defined('SESSION_NAME') && SESSION_NAME) {
            session_name(SESSION_NAME);
        }

        session_start();
    }
}

// ✅ Start session immediately so CSRF always has a session
start_session();

// TEMP DEBUG (remove later)
error_log("SESSION_ID=" . session_id());

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
    $_SESSION['flash'][$key] = $value;
}

function get_flash(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $val = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    return null;
}
