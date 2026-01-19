<?php
require __DIR__ . "/config/config.php";
require __DIR__ . "/core/db.php";

header("Content-Type: text/plain");

try {
  $pdo = db();
  echo "DB OK\n";
  $stmt = $pdo->query("SELECT DATABASE() as db");
  print_r($stmt->fetch(PDO::FETCH_ASSOC));

  $stmt = $pdo->query("SHOW TABLES");
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
  echo "Tables count: " . count($tables) . "\n";
} catch (Throwable $e) {
  echo "DB FAIL: " . $e->getMessage();
}
