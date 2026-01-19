<?php
require_once __DIR__ . '/../core/auth.php';
require_login();
require_once __DIR__ . '/../core/security.php';

$user = auth_user();

if ($user['role'] === 'SUPER_ADMIN') {
    header('Location: ' . BASE_URL . '/account/personal_info.php');
    exit;
}
$error = null;
$success = null;

if (is_post()) {
    require_csrf();

    $current = $_POST['current_password'] ?? '';
    $new1 = $_POST['new_password'] ?? '';
    $new2 = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new1 === '' || $new2 === '') {
        $error = "All fields are required.";
    } elseif (strlen($new1) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $new1)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $new1)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $new1)) {
        $error = "Password must contain at least one number.";
    } elseif ($new1 !== $new2) {
        $error = "Passwords do not match.";
    } elseif ($current === $new1) {
        $error = "New password must be different from current password.";
    } else {
        $stmt = db()->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!$row || empty($row['password_hash']) || !password_verify($current, $row['password_hash'])) {
            $error = "Current password is incorrect.";
        } else {
            $hash = password_hash($new1, PASSWORD_DEFAULT);
            $up = db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $up->execute([$hash, $user['id']]);
            $success = "Password updated successfully.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Change Password - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
  </style>
</head>
<body style="background:#F8F9FA;">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="position:sticky;top:0;z-index:1000;">
  <div class="container-fluid px-3 px-lg-5">
    <a class="navbar-brand d-flex align-items-center ms-0 ms-lg-5" href="<?= e(BASE_URL) ?>/<?= $user['role'] === 'SUPER_ADMIN' ? 'admin/super_dashboard.php' : ($user['role'] === 'STUDENT' ? 'student/feed.php' : 'educator/dashboard.php') ?>">
      <i class="fa-solid fa-graduation-cap" style="color:#2E5FA8;font-size:24px;margin-right:8px;"></i>
      <span style="font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#2E5FA8;" class="d-none d-sm-inline"><?= e(strtoupper(APP_NAME)) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto me-0 me-lg-5">
        <?php if ($user['role'] === 'SUPER_ADMIN'): ?>
          <a href="<?= e(BASE_URL) ?>/admin/super_dashboard.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Dashboard</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/admin/educators.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Educators</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/admin/subjects.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Subjects</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/analytics.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Analytics</a>
        <?php elseif ($user['role'] === 'STUDENT'): ?>
          <a href="<?= e(BASE_URL) ?>/student/feed.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Feed</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/student/liked.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Liked</a>
        <?php else: ?>
          <a href="<?= e(BASE_URL) ?>/educator/dashboard.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Dashboard</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/create_post.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Create Post</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/my_posts.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">My Posts</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/analytics.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Analytics</a>
        <?php endif; ?>
        <div class="dropdown ms-lg-2">
          <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="dropdown" style="color:#2E5FA8;font-size:24px;">
            <i class="bi bi-person-circle"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/account/personal_info.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <?php if ($user['role'] !== 'SUPER_ADMIN'): ?>
              <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/account/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
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
  .navbar-collapse .dropdown {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
  }
}
</style>

<div class="container py-3 py-md-4 px-3 px-md-0" style="min-height:calc(100vh - 80px);display:flex;flex-direction:column;align-items:center;justify-content:center;">
  <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin-bottom:24px;text-align:center;">Change Password</h1>

  <?php if ($error): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
  <?php endif; ?>
  <?php if ($success): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius:12px;max-width:600px;width:100%;">
    <div class="card-body p-4">
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Current Password</label>
          <div class="position-relative">
            <input type="password" name="current_password" id="currentPassword" class="form-control" required style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 40px 10px 12px;font-family:'Inter',sans-serif;">
            <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('currentPassword', this)" style="right:4px;top:50%;transform:translateY(-50%);padding:4px 8px;text-decoration:none;">
              <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">New Password</label>
          <div class="position-relative">
            <input type="password" name="new_password" id="newPassword" class="form-control" required style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 40px 10px 12px;font-family:'Inter',sans-serif;">
            <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('newPassword', this)" style="right:4px;top:50%;transform:translateY(-50%);padding:4px 8px;text-decoration:none;">
              <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
            </button>
          </div>
          <div class="form-text" style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;">Must be 8+ characters with uppercase, lowercase, and number.</div>
          <div id="passwordStrength" style="margin-top:8px;display:none;">
            <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;">
              <div id="strengthBar" style="height:100%;width:0%;transition:all 0.3s;"></div>
            </div>
            <small id="strengthText" style="font-family:'Inter',sans-serif;font-size:11px;margin-top:4px;display:block;"></small>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Confirm New Password</label>
          <div class="position-relative">
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 40px 10px 12px;font-family:'Inter',sans-serif;">
            <button type="button" class="btn btn-link position-absolute" onclick="togglePassword('confirmPassword', this)" style="right:4px;top:50%;transform:translateY(-50%);padding:4px 8px;text-decoration:none;">
              <i class="bi bi-eye" style="color:#697483;font-size:16px;"></i>
            </button>
          </div>
          <small id="matchStatus" style="font-family:'Inter',sans-serif;font-size:11px;margin-top:4px;display:none;"></small>
        </div>

        <button class="btn w-100" type="submit" style="background:#2E5FA8;color:white;border:none;border-radius:8px;padding:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;"><i class="bi bi-check-circle me-2"></i>Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

const newPassword = document.getElementById('newPassword');
const confirmPassword = document.getElementById('confirmPassword');
const strengthDiv = document.getElementById('passwordStrength');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');
const matchStatus = document.getElementById('matchStatus');

newPassword.addEventListener('input', function() {
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

confirmPassword.addEventListener('input', checkMatch);

function checkMatch() {
  const pwd = newPassword.value;
  const confirm = confirmPassword.value;
  
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
</script>
</body>
</html>
