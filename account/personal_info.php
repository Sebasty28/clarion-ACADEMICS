<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

require_login();
$user = auth_user();

$error = null;
$success = null;

function initials(string $first, string $last): string {
  $a = $first !== '' ? strtoupper(mb_substr($first, 0, 1)) : '';
  $b = $last !== '' ? strtoupper(mb_substr($last, 0, 1)) : '';
  $out = trim($a . $b);
  return $out !== '' ? $out : 'U';
}

if (is_post()) {
  require_csrf();

  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');

  if ($first === '' || $last === '') {
    $error = "First name and last name are required.";
  } elseif (preg_match('/\d/', $first) || preg_match('/\d/', $last)) {
    $error = "Names cannot contain numbers.";
  } elseif ($first === $user['first_name'] && $last === $user['last_name']) {
    $error = "No changes detected. The name is already saved.";
  } else {
    $stmt = db()->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
    $stmt->execute([$first, $last, (int)$user['id']]);

    // Update session with new name
    $_SESSION['user']['first_name'] = $first;
    $_SESSION['user']['last_name'] = $last;
    
    // Refresh local user variable
    $user['first_name'] = $first;
    $user['last_name'] = $last;

    $success = "Saved successfully.";
  }
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$roleLabel = ucfirst(strtolower(str_replace('_', ' ', $user['role'] ?? 'User')));
$avatar = initials($user['first_name'] ?? '', $user['last_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth/account_settings.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body>

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

  <div class="container py-4" style="min-height:calc(100vh - 80px);display:flex;flex-direction:column;align-items:center;justify-content:center;">
    <div class="mb-4" style="width:100%;max-width:600px;">
      <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;text-align:center;">Account Settings</h1>
    </div>

    <div class="settings-card" style="width:100%;max-width:600px;">
      <p class="text-muted mb-3" style="font-family:'Inter',sans-serif;font-size:13px;"><i class="bi bi-info-circle me-1"></i>Only your name fields are editable. Email and role cannot be changed.</p>
      
      <?php if ($error): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
      <?php endif; ?>
      <?php if ($success): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
      <?php endif; ?>

      <form method="post" autocomplete="off" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($user['first_name'] ?? '') ?>" pattern="[A-Za-z\s]+" required>
            <div class="invalid-feedback">Please enter a valid first name (letters only).</div>
          </div>

          <div class="col-md-6">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($user['last_name'] ?? '') ?>" pattern="[A-Za-z\s]+" required>
            <div class="invalid-feedback">Please enter a valid last name (letters only).</div>
          </div>

          <div class="col-12">
            <label for="email" class="form-label text-muted">Email</label>
            <input type="email" class="form-control" id="email" value="<?= e($user['email'] ?? '') ?>" readonly>
          </div>

          <div class="col-12">
            <label class="form-label text-muted">Role</label>
            <input type="text" class="form-control" value="<?= e($roleLabel) ?>" readonly>
          </div>

          <div class="col-12">
            <button class="btn btn-primary" type="submit">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
