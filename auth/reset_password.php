<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

$error = null;
$success = null;

$token = trim($_GET['token'] ?? '');
if ($token === '') {
  http_response_code(400);
  echo "Invalid reset link.";
  exit;
}

if (is_post()) {
  require_csrf();
  $token = trim($_POST['token'] ?? '');
  $new1 = $_POST['new_password'] ?? '';
  $new2 = $_POST['confirm_password'] ?? '';

  if ($new1 === '' || $new2 === '') $error = "All fields are required.";
  elseif (strlen($new1) < 8) $error = "Password must be at least 8 characters.";
  elseif ($new1 !== $new2) $error = "Passwords do not match.";
  else {
    $hash = hash('sha256', $token);

    $stmt = db()->prepare("
      SELECT pr.id, pr.user_id
      FROM password_resets pr
      WHERE pr.token_hash = ?
        AND pr.used_at IS NULL
        AND pr.expires_at > NOW()
      ORDER BY pr.id DESC
      LIMIT 1
    ");
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if (!$row) {
      $error = "Reset link is invalid or expired.";
    } else {
      $pwd = password_hash($new1, PASSWORD_DEFAULT);

      db()->prepare("UPDATE users SET password_hash=? WHERE id=?")
        ->execute([$pwd, (int)$row['user_id']]);

      db()->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")
        ->execute([(int)$row['id']]);

      $success = "Password updated! You can now log in.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/forgot_password.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid px-3 px-lg-5">
    <a class="navbar-brand d-flex align-items-center ms-0 ms-lg-5" href="<?= e(BASE_URL) ?>/">
      <i class="fa-solid fa-graduation-cap" style="color:#2E5FA8;font-size:24px;margin-right:8px;"></i>
      <span style="font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#2E5FA8;" class="d-none d-sm-inline"><?= e(strtoupper(APP_NAME)) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto me-0 me-lg-5">
        <a href="<?= e(BASE_URL) ?>/auth/login.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Login</a>
        <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
        <a href="<?= e(BASE_URL) ?>/auth/register.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Sign Up</a>
      </div>
    </div>
  </div>
</nav>
<style>
@media (max-width: 991.98px) {
  .navbar-collapse {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }
  .nav-link-mobile {
    display: block;
    width: 100%;
  }
}
</style>

<main class="container" style="max-width:520px;padding-top:44px;">
  <div class="forgot-card">
    <h1>Reset Password</h1>
    <p class="subtitle">Enter your new password below.</p>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="form-floating mb-3">
        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" minlength="8" required>
        <label for="new_password">New Password</label>
        <div class="invalid-feedback">Password must be at least 8 characters.</div>
      </div>

      <div class="form-floating mb-3">
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>
        <label for="confirm_password">Confirm Password</label>
        <div class="invalid-feedback">Please confirm your password.</div>
      </div>

      <button class="reset-btn" type="submit">Update password</button>
    </form>
    <?php else: ?>
      <a class="reset-btn" style="display:block;text-align:center;text-decoration:none;" href="<?= e(BASE_URL) ?>/auth/login.php">Go to Login</a>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>
<script>
(function() {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
</body>
</html>
