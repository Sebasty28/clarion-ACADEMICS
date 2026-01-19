<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/csrf.php';

$error = null;
$success = null;

if (is_post()) {
  require_csrf();
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $email2= trim($_POST['email_confirm'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password_confirm'] ?? '';
  $role  = $_POST['role'] ?? 'STUDENT';
  $agree = ($_POST['agree'] ?? '') === '1';

  if ($first==='' || $last==='' || $email==='' || $email2==='' || $pass==='' || $pass2==='') $error="All fields are required.";
  elseif (preg_match('/\d/', $first)) $error="First name cannot contain numbers.";
  elseif (preg_match('/\d/', $last)) $error="Last name cannot contain numbers.";
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error="Invalid email.";
  elseif ($email !== $email2) $error="Emails do not match.";
  elseif (strlen($pass) < 8) $error="Password must be at least 8 characters.";
  elseif (!preg_match('/[A-Z]/', $pass)) $error="Password must contain at least one uppercase letter.";
  elseif (!preg_match('/[a-z]/', $pass)) $error="Password must contain at least one lowercase letter.";
  elseif (!preg_match('/[0-9]/', $pass)) $error="Password must contain at least one number.";
  elseif ($pass !== $pass2) $error="Passwords do not match.";
  elseif (!in_array($role, ['STUDENT', 'EDUCATOR'])) $error="Invalid role selected.";
  elseif (!$agree) $error="You must agree to the terms.";
  else {
    $chk = db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) $error="Email already exists.";
    else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $ins = db()->prepare("
        INSERT INTO users(role, first_name, last_name, email, password_hash, provider, is_active)
        VALUES(?,?,?,?,?, 'LOCAL', 1)
      ");
      $ins->execute([$role,$first,$last,$email,$hash]);
      header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create an account | <?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/signup.css">
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
    <h1>Create an account</h1>
    <p class="subtitle">Let's get you started, choose how you'd like to sign up</p>

    <a class="social-btn google" href="<?= e(BASE_URL) ?>/auth/google_start.php" style="text-decoration:none;">
      <i class="bi bi-google" style="color:#4285F4;"></i>
      Sign up with Google
    </a>

    <div class="divider">Or sign up with email</div>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
    <?php endif; ?>

    <!-- Roles -->
    <div class="roles">
      <div class="role active" data-role="STUDENT">
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

    <form method="post" autocomplete="off" class="needs-validation" novalidate enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="role" id="roleInput" value="STUDENT">

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" pattern="[A-Za-z\s]+" required>
            <label for="first_name">First Name</label>
            <div class="invalid-feedback">Please enter a valid first name (letters only).</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" pattern="[A-Za-z\s]+" required>
            <label for="last_name">Last Name</label>
            <div class="invalid-feedback">Please enter a valid last name (letters only).</div>
          </div>
        </div>
      </div>

      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
        <label for="email">Email</label>
        <div class="invalid-feedback">Please enter a valid email.</div>
      </div>

      <div class="form-floating mb-3">
        <input type="email" class="form-control" id="email_confirm" name="email_confirm" placeholder="Confirm Email" required>
        <label for="email_confirm">Confirm Email</label>
        <div class="invalid-feedback">Please confirm your email.</div>
      </div>

      <div class="form-floating mb-3" style="position:relative;">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="8" required style="padding-right:40px;">
        <label for="password">Password</label>
        <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('password', this)" style="right:4px;top:12px;padding:4px 8px;text-decoration:none;z-index:10;">
          <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
        </button>
        <div class="invalid-feedback">Password must be at least 8 characters.</div>
      </div>
      <small class="form-text text-muted" style="font-size:11px;margin-top:-12px;margin-bottom:8px;display:block;">Must include uppercase, lowercase, and number.</small>
      <div id="passwordStrength" style="margin-bottom:16px;display:none;">
        <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
          <div id="strengthBar" style="height:100%;width:0%;transition:all 0.3s;"></div>
        </div>
        <small id="strengthText" style="font-size:11px;margin-top:4px;display:block;"></small>
      </div>

      <div class="form-floating mb-3" style="position:relative;">
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm Password" minlength="8" required style="padding-right:40px;">
        <label for="password_confirm">Confirm Password</label>
        <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('password_confirm', this)" style="right:4px;top:12px;padding:4px 8px;text-decoration:none;z-index:10;">
          <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
        </button>
        <div class="invalid-feedback">Please confirm your password.</div>
      </div>
      <small id="matchStatus" style="font-size:11px;margin-top:-12px;margin-bottom:16px;display:none;"></small>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="agree" name="agree" value="1" required>
        <label class="form-check-label" for="agree">
          I agree to the Terms and Conditions
        </label>
        <div class="invalid-feedback">You must agree before submitting.</div>
      </div>

      <button class="primary-btn" type="submit">Create</button>
    </form>

    <p class="footer-text">
      Already have an account? <a href="<?= e(BASE_URL) ?>/auth/login.php">Sign in</a>
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

const password = document.getElementById('password');
const passwordConfirm = document.getElementById('password_confirm');
const strengthDiv = document.getElementById('passwordStrength');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');
const matchStatus = document.getElementById('matchStatus');

password.addEventListener('input', function() {
  const pwd = this.value;
  if (pwd.length === 0) {
    strengthDiv.style.display = 'none';
    return;
  }
  
  strengthDiv.style.display = 'block';
  let strength = 0;
  
  if (pwd.length >= 8) strength++;
  if (/[a-z]/.test(pwd)) strength++;
  if (/[A-Z]/.test(pwd)) strength++;
  if (/[0-9]/.test(pwd)) strength++;
  if (/[^a-zA-Z0-9]/.test(pwd)) strength++;
  
  const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
  const labels = ['Weak', 'Fair', 'Good', 'Strong'];
  const widths = ['25%', '50%', '75%', '100%'];
  
  const index = Math.min(strength - 1, 3);
  if (index >= 0) {
    strengthBar.style.width = widths[index];
    strengthBar.style.background = colors[index];
    strengthText.textContent = labels[index];
    strengthText.style.color = colors[index];
  }
  
  checkMatch();
});

passwordConfirm.addEventListener('input', checkMatch);

function checkMatch() {
  const pwd = password.value;
  const confirm = passwordConfirm.value;
  
  if (confirm.length === 0) {
    matchStatus.style.display = 'none';
    return;
  }
  
  matchStatus.style.display = 'block';
  if (pwd === confirm) {
    matchStatus.textContent = '✓ Passwords match';
    matchStatus.style.color = '#28a745';
  } else {
    matchStatus.textContent = '✗ Passwords do not match';
    matchStatus.style.color = '#dc3545';
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
  
  // Role selection
  const roles = document.querySelectorAll('.role');
  const roleInput = document.getElementById('roleInput');
  roles.forEach(role => {
    role.addEventListener('click', function() {
      roles.forEach(r => r.classList.remove('active'));
      this.classList.add('active');
      roleInput.value = this.dataset.role;
    });
  });
})();
</script>
</body>
</html>
