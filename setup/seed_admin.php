<?php
/**
 * Super Admin Seeding Script
 * Run this once to create the initial super admin account
 * Usage: php seed_admin.php
 */

require_once __DIR__ . '/../core/db.php';

echo "=== Super Admin Account Seeder ===\n\n";

// Check if super admin already exists
$check = db()->prepare("SELECT id FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1");
$check->execute();
if ($check->fetch()) {
    echo "ERROR: A super admin account already exists.\n";
    echo "Use reset_admin_password.php to reset the password.\n";
    exit(1);
}

// Prompt for details
echo "Enter super admin details:\n";
echo "First Name: ";
$firstName = trim(fgets(STDIN));

echo "Last Name: ";
$lastName = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "ERROR: Invalid email format.\n";
    exit(1);
}

// Generate secure random password
$password = bin2hex(random_bytes(16)); // 32 character random password
echo "\nGenerated secure password: $password\n";
echo "IMPORTANT: Save this password securely. It cannot be recovered.\n\n";

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert super admin
$stmt = db()->prepare("
    INSERT INTO users (role, first_name, last_name, email, password_hash, provider, is_active, created_at)
    VALUES ('SUPER_ADMIN', ?, ?, ?, ?, 'LOCAL', 1, NOW())
");

try {
    $stmt->execute([$firstName, $lastName, $email, $passwordHash]);
    echo "SUCCESS: Super admin account created!\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "\nYou can change this password after logging in.\n";
} catch (Exception $e) {
    echo "ERROR: Failed to create account: " . $e->getMessage() . "\n";
    exit(1);
}
