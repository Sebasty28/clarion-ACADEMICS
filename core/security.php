<?php
// core/security.php
require_once __DIR__ . '/helpers.php';

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $t = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($t) . '">';
}

function require_csrf(): void {
    start_session();
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $real = $_SESSION['csrf_token'] ?? '';
    if (!$sent || !$real || !hash_equals($real, $sent)) {
        http_response_code(419);
        echo "419 CSRF token mismatch.";
        exit;
    }
}
