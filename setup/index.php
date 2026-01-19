<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if super admin already exists
$check = db()->prepare("SELECT id FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1");
$check->execute();
if ($check->fetch()) {
    http_response_code(403);
    die('Setup already completed. Super admin account exists.');
}

$error = null;
$success = null;
$generatedPassword = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if ($firstName === '' || $lastName === '' || $email === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Generate secure random password
        $generatedPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt = db()->prepare("
                INSERT INTO users (role, first_name, last_name, email, password_hash, provider, is_active, created_at)
                VALUES ('SUPER_ADMIN', ?, ?, ?, ?, 'LOCAL', 1, NOW())
            ");
            $stmt->execute([$firstName, $lastName, $email, $passwordHash]);
            $success = true;
        } catch (Exception $e) {
            $error = 'Failed to create account: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Setup</title>
  <link rel="icon" type="image/png" href="/clarion/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .setup-card { max-width: 500px; width: 100%; background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
    .setup-header { text-align: center; margin-bottom: 30px; }
    .setup-header h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .setup-header p { color: #64748b; font-size: 14px; }
    .password-display { background: #f8fafc; border: 2px solid #2E5FA8; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .password-display .password { font-family: 'Courier New', monospace; font-size: 18px; font-weight: 700; color: #2E5FA8; word-break: break-all; margin: 10px 0; }
    .btn-copy { margin-top: 10px; }
  </style>
</head>
<body>

<div class="setup-card">
  <div class="setup-header">
    <i class="bi bi-shield-lock" style="font-size: 48px; color: #2E5FA8;"></i>
    <h1>Admin Setup</h1>
    <p>Create the initial administrator account</p>
  </div>

  <?php if ($success && $generatedPassword): ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle me-2"></i>
      <strong>Success!</strong> Admin account created.
    </div>
    
    <div class="password-display">
      <div style="font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 8px;">
        <i class="bi bi-key-fill me-2"></i>YOUR GENERATED PASSWORD
      </div>
      <div class="password" id="generatedPassword"><?= htmlspecialchars($generatedPassword) ?></div>
      <button class="btn btn-sm btn-outline-primary btn-copy" onclick="copyPassword()">
        <i class="bi bi-clipboard me-1"></i>Copy Password
      </button>
    </div>

    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <strong>Important:</strong> Save this password immediately. It cannot be recovered.
    </div>

    <div class="d-grid gap-2">
      <a href="../auth/admin_login.php" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Admin Login
      </a>
    </div>

    <div class="alert alert-info mt-3" style="font-size: 12px;">
      <strong>Security Note:</strong> This setup page is now disabled. Delete the <code>/setup</code> folder for additional security.
    </div>

  <?php else: ?>
    
    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="alert alert-info" style="font-size: 13px;">
        <i class="bi bi-info-circle me-2"></i>
        A secure random password will be generated automatically.
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-shield-check me-2"></i>Create Admin Account
      </button>
    </form>

  <?php endif; ?>
</div>

<script>
function copyPassword() {
  const password = document.getElementById('generatedPassword').textContent;
  navigator.clipboard.writeText(password).then(() => {
    const btn = document.querySelector('.btn-copy');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-success');
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.classList.remove('btn-success');
      btn.classList.add('btn-outline-primary');
    }, 2000);
  });
}
</script>

</body>
</html>
