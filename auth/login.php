<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

// Redirect if already logged in
if (is_logged_in()) {
  $user = auth_user();
  header('Location: ' . BASE_URL . role_home($user['role']));
  exit;
}

$error = null;
$registered = isset($_GET['registered']);

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
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || (int)$u['is_active'] !== 1 || empty($u['password_hash']) || !password_verify($pass, $u['password_hash'])) {
      $error = "Invalid credentials.";
    } else {
      login_user($u);
      // Set welcome message to override any old flash messages
      set_flash('success', 'Welcome back, ' . e($u['first_name']) . '!');
      header('Location: ' . BASE_URL . role_home($u['role']));
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | <?= e(APP_NAME) ?></title>
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
  <style>
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none;
    }
    input[type="password"]::-webkit-credentials-auto-fill-button,
    input[type="password"]::-webkit-contacts-auto-fill-button {
      visibility: hidden;
      pointer-events: none;
      position: absolute;
      right: 0;
    }
    .form-floating {
      position: relative;
    }
    .form-floating .btn-link {
      z-index: 4;
    }
  </style>
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

<main class="login-wrapper">
  <div class="login-box">
    <h1>Login</h1>
    <p class="subtitle">Welcome back! Select method to login:</p>

    <?php if ($registered): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('Account created! You can now log in.'));</script>
    <?php endif; ?>
    
    <?php if ($flash_success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_success) ?>'));</script>
    <?php endif; ?>
    
    <?php if ($flash_error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_error) ?>', 'error'));</script>
    <?php endif; ?>

    <!-- Google -->
    <a class="social-btn google" href="<?= e(BASE_URL) ?>/auth/google_start.php" style="text-decoration:none;">
      <i class="bi bi-google" style="color:#4285F4;"></i>
      Login with Google
    </a>

    <div class="divider">Or login with email</div>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="needs-validation" novalidate>
      <?= csrf_field() ?>

      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
        <label for="email">Email</label>
        <div class="invalid-feedback">Please enter a valid email.</div>
      </div>

      <div class="form-floating mb-3">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required style="padding-right:40px;">
        <label for="password">Password</label>
        <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('password', this)" style="right:4px;top:50%;transform:translateY(-50%);padding:4px 8px;text-decoration:none;z-index:10;">
          <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
        </button>
        <div class="invalid-feedback">Please enter your password.</div>
      </div>

      <div class="forgot">
        <a href="<?= e(BASE_URL) ?>/auth/forgot_password.php">Forgot Password?</a>
      </div>

      <button class="primary-btn" type="submit">Submit</button>
    </form>

    <p class="footer-text">
      Don't have an account? <a href="<?= e(BASE_URL) ?>/auth/register.php">Sign up</a>
    </p>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>
<script>
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
  }
}

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
