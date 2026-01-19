<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

start_session();

if (empty($_SESSION['google_temp'])) {
  header('Location: ' . BASE_URL . '/auth/login.php');
  exit;
}

$googleData = $_SESSION['google_temp'];
$error = null;

if (is_post()) {
  require_csrf();
  $role = $_POST['role'] ?? '';
  
  if (!in_array($role, ['STUDENT', 'EDUCATOR'])) {
    $error = "Please select a role.";
  } else {
    $stmt = db()->prepare("
      INSERT INTO users(role, first_name, last_name, email, password_hash, provider, google_sub, is_active)
      VALUES(?, ?, ?, ?, NULL, 'GOOGLE', ?, 1)
    ");
    $stmt->execute([
      $role,
      $googleData['given_name'] ?: 'User',
      $googleData['family_name'] ?: '',
      $googleData['email'],
      $googleData['sub']
    ]);
    
    $id = (int)db()->lastInsertId();
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    login_user($user);
    unset($_SESSION['google_temp']);
    set_flash('success', 'Welcome to Clarion, ' . e($user['first_name']) . '! Your account has been created.');
    header('Location: ' . BASE_URL . role_home($role));
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Complete Registration | <?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/complete_registration.css">
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

<main class="page">
  <div class="card">
    <h1>Complete Your Registration</h1>
    <p class="subtitle">Choose your role to get started</p>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" id="roleForm">
      <?= csrf_field() ?>
      <input type="hidden" name="role" id="roleInput" value="">

      <div class="roles">
        <div class="role" data-role="STUDENT">
          <div class="role-icon student">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <span>Student</span>
        </div>
        <div class="role" data-role="EDUCATOR">
          <div class="role-icon educator">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <span>Educator</span>
        </div>
      </div>

      <button class="primary-btn" type="submit" id="submitBtn" disabled>Continue</button>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
  'use strict';
  const roles = document.querySelectorAll('.role');
  const roleInput = document.getElementById('roleInput');
  const submitBtn = document.getElementById('submitBtn');
  
  roles.forEach(role => {
    role.addEventListener('click', function() {
      roles.forEach(r => r.classList.remove('active'));
      this.classList.add('active');
      roleInput.value = this.dataset.role;
      submitBtn.disabled = false;
    });
  });
})();
</script>
</body>
</html>
