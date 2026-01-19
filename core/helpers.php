<?php
// core/helpers.php
require_once __DIR__ . '/../config/config.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_name(SESSION_NAME);
        session_start();
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
