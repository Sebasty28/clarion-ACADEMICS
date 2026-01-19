<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

$msg = null;
$error = null;

if (is_post()) {
  require_csrf();
  $email = trim($_POST['email'] ?? '');

  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $u = db()->prepare("SELECT id,email FROM users WHERE email=? AND is_active=1 LIMIT 1");
    $u->execute([$email]);
    $user = $u->fetch();

    if ($user) {
      $token = bin2hex(random_bytes(32));
      $hash  = hash('sha256', $token);
      $expires = date('Y-m-d H:i:s', time() + 60*30); // 30 minutes

      db()->prepare("INSERT INTO password_resets(user_id, token_hash, expires_at) VALUES(?,?,?)")
        ->execute([(int)$user['id'], $hash, $expires]);

      $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . BASE_URL . "/auth/reset_password.php?token=" . urlencode($token);

      $msg = "Reset link: <a href='$link' target='_blank'>Click here to reset</a>";
    } else {
      $error = "This email address is not registered in our system.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password | <?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/forgot_password.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

<main class="container">
  <div class="forgot-card">
    <h1>Forgot password?</h1>
    <p class="subtitle">No worries! Happens to the best of us!</p>

    <div class="alert alert-info" role="alert">
      <i class="fa-solid fa-info-circle"></i> <strong>Development Stage:</strong> Email system not configured. Reset link will be displayed on this page.
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-success" role="alert">
        <?= $msg ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
        <label for="email">Email</label>
        <div class="invalid-feedback">Please enter a valid email address.</div>
      </div>
      <button type="submit" class="reset-btn">Reset password</button>
    </form>

    <a href="<?= e(BASE_URL) ?>/auth/login.php" class="back-link">← Back to log in</a>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
