<?php
// core/db.php
require_once __DIR__ . '/../config/config.php';

function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Build DSN with TCP proxy compatibility
    $host = DB_HOST;
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $name = DB_NAME;

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,

        // Helps ensure it uses TCP (useful for proxies)
        PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Clearer message (without exposing password)
        $msg = "Database connection failed. Check DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS.\n";
        $msg .= "Host: " . $host . "\n";
        $msg .= "Port: " . $port . "\n";
        $msg .= "DB: " . $name . "\n";
        $msg .= "User: " . DB_USER . "\n";
        $msg .= "PDO: " . $e->getMessage();
        throw new PDOException($msg, (int)$e->getCode());
    }

    return $pdo;
}
