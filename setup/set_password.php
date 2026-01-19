<?php
require_once __DIR__ . '/../core/auth.php';

$message = null;
$error = null;

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($email === '' || $new_password === '') {
        $error = "Email and password are required.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u) {
            $error = "No user found with that email.";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $up = db()->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $up->execute([$hash, $email]);
            $message = "Password set successfully for: " . e($email);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set Password - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <h1 class="h4 mb-3">Set Password (Dev Setup)</h1>

            <div class="alert alert-warning">
              Use this to set passwords for seeded accounts:
              <div class="small mt-2">
                <div><strong>superadmin@clarion.local</strong></div>
                <div><strong>educator@clarion.local</strong></div>
              </div>
            </div>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
              <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">User Email</label>
                <input name="email" type="email" class="form-control" required value="<?= e($_POST['email'] ?? 'superadmin@clarion.local') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input name="new_password" type="password" class="form-control" required>
                <div class="form-text">Minimum 8 characters.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input name="confirm_password" type="password" class="form-control" required>
              </div>
              <button class="btn btn-primary w-100" type="submit">Set Password</button>
            </form>

            <div class="mt-3">
              <a href="<?= e(BASE_URL) ?>/auth/login.php" class="link-secondary">Back to Login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
