<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

$error = null;

// Get flash messages (e.g., logout message)
$flash_error = get_flash('error');
$flash_success = get_flash('success');

if (is_post()) {
  require_csrf();
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if ($email === '' || $pass === '') {
    $error = "Email and password are required.";
  } else {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'SUPER_ADMIN' LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || (int)$u['is_active'] !== 1 || empty($u['password_hash']) || !password_verify($pass, $u['password_hash'])) {
      $error = "Invalid credentials.";
    } else {
      login_user($u);
      // Set welcome message to override any old flash messages
      set_flash('success', 'Welcome back, ' . e($u['first_name']) . '!');
      header('Location: ' . BASE_URL . '/admin/super_dashboard.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login | <?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/login.css">
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
        <a href="<?= e(BASE_URL) ?>/auth/login.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Student/Educator Login</a>
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

<main class="login-wrapper">
  <div class="login-box">
    <h1>Admin Login</h1>
    <p class="subtitle">Administrator Access Only</p>

    <div class="alert alert-info" style="font-family:'Inter',sans-serif;font-size:13px;margin-bottom:20px;">
      <i class="bi bi-info-circle me-2"></i>
      <strong>Security Note:</strong> There is no default password. The administrator account is securely seeded with a hashed password and can only be reset manually or via a secure password reset process.
    </div>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    
    <?php if ($flash_success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_success) ?>'));</script>
    <?php endif; ?>
    
    <?php if ($flash_error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_error) ?>', 'error'));</script>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="needs-validation" novalidate>
      <?= csrf_field() ?>

      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
        <label for="email">Email</label>
        <div class="invalid-feedback">Please enter a valid email.</div>
      </div>

      <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        <label for="password">Password</label>
        <div class="invalid-feedback">Please enter your password.</div>
      </div>

      <button class="primary-btn" type="submit">Login</button>
    </form>

    <p class="footer-text">
      <a href="<?= e(BASE_URL) ?>/auth/login.php">← Back to regular login</a>
    </p>
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
