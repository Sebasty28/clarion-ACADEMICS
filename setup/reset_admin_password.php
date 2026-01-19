<?php
/**
 * Super Admin Password Reset Script
 * Run this manually to reset super admin password
 * Usage: php reset_admin_password.php
 */

require_once __DIR__ . '/../core/db.php';

echo "=== Super Admin Password Reset ===\n\n";

// List all super admins
$admins = db()->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'SUPER_ADMIN'")->fetchAll();

if (!$admins) {
    echo "ERROR: No super admin accounts found.\n";
    echo "Use seed_admin.php to create one.\n";
    exit(1);
}

echo "Available super admin accounts:\n";
foreach ($admins as $idx => $admin) {
    echo ($idx + 1) . ". {$admin['email']} ({$admin['first_name']} {$admin['last_name']})\n";
}

echo "\nSelect account number to reset: ";
$selection = (int)trim(fgets(STDIN));

if ($selection < 1 || $selection > count($admins)) {
    echo "ERROR: Invalid selection.\n";
    exit(1);
}

$selectedAdmin = $admins[$selection - 1];

echo "\nResetting password for: {$selectedAdmin['email']}\n";
echo "Choose reset method:\n";
echo "1. Generate secure random password\n";
echo "2. Enter custom password\n";
echo "Selection: ";
$method = (int)trim(fgets(STDIN));

if ($method === 1) {
    // Generate secure random password
    $password = bin2hex(random_bytes(16));
    echo "\nGenerated password: $password\n";
} elseif ($method === 2) {
    // Custom password
    echo "Enter new password (min 8 characters): ";
    $password = trim(fgets(STDIN));
    
    if (strlen($password) < 8) {
        echo "ERROR: Password must be at least 8 characters.\n";
        exit(1);
    }
    
    echo "Confirm password: ";
    $confirm = trim(fgets(STDIN));
    
    if ($password !== $confirm) {
        echo "ERROR: Passwords do not match.\n";
        exit(1);
    }
} else {
    echo "ERROR: Invalid method selection.\n";
    exit(1);
}

// Hash and update password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
try {
    $stmt->execute([$passwordHash, $selectedAdmin['id']]);
    echo "\nSUCCESS: Password reset complete!\n";
    echo "Email: {$selectedAdmin['email']}\n";
    echo "New Password: $password\n";
    echo "\nIMPORTANT: Save this password securely.\n";
} catch (Exception $e) {
    echo "ERROR: Failed to reset password: " . $e->getMessage() . "\n";
    exit(1);
}
