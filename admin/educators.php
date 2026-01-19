<?php
require_once __DIR__ . '/../core/auth.php';
require_role(['SUPER_ADMIN']);

$flash_error = get_flash('error');
$flash_success = get_flash('success');

$error = null;
$success = null;

// Handle create educator
if (is_post() && ($_POST['action'] ?? '') === 'create') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($first === '' || $last === '' || $email === '' || $pass === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $check = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Email already exists.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = db()->prepare("
                INSERT INTO users (role, first_name, last_name, email, password_hash, is_active)
                VALUES ('EDUCATOR', ?, ?, ?, ?, 1)
            ");
            $ins->execute([$first, $last, $email, $hash]);
            $success = "Educator created successfully.";
        }
    }
}

// Handle toggle active
if (is_post() && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);

    // prevent toggling yourself? (optional)
    $stmt = db()->prepare("SELECT id, is_active FROM users WHERE id = ? AND role = 'EDUCATOR' LIMIT 1");
    $stmt->execute([$id]);
    $u = $stmt->fetch();

    if ($u) {
        $new = ($u['is_active'] ? 0 : 1);
        $up = db()->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $up->execute([$new, $id]);
        $success = "Educator status updated.";
    } else {
        $error = "Educator not found.";
    }
}

// Fetch educators
$list = db()->query("
    SELECT id, first_name, last_name, email, is_active, created_at
    FROM users
    WHERE role = 'EDUCATOR'
    ORDER BY created_at DESC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Educators - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body style="background:#F8F9FA;">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="position:sticky;top:0;z-index:1000;">
  <div class="container-fluid px-3 px-lg-5">
    <a class="navbar-brand d-flex align-items-center ms-0 ms-lg-5" href="<?= e(BASE_URL) ?>/admin/super_dashboard.php">
      <i class="fa-solid fa-graduation-cap" style="color:#2E5FA8;font-size:24px;margin-right:8px;"></i>
      <span style="font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#2E5FA8;" class="d-none d-sm-inline"><?= e(strtoupper(APP_NAME)) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto me-0 me-lg-5">
        <a href="<?= e(BASE_URL) ?>/admin/super_dashboard.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Dashboard</a>
        <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
        <a href="<?= e(BASE_URL) ?>/admin/educators.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Educators</a>
        <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
        <a href="<?= e(BASE_URL) ?>/admin/subjects.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Subjects</a>
        <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
        <a href="<?= e(BASE_URL) ?>/educator/analytics.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Analytics</a>
        <div class="dropdown ms-lg-2">
          <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="dropdown" style="color:#2E5FA8;font-size:24px;">
            <i class="bi bi-person-circle"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/account/personal_info.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<div class="container py-3 py-md-4 px-3 px-md-0">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4">
    <h1 style="font-family:'Inter',sans-serif;font-size:24px;font-weight:700;color:#0f172a;margin:0;" class="mb-3 mb-md-0">Manage Educators</h1>
  </div>

  <?php if ($error): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
  <?php endif; ?>
  <?php if ($success): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
  <?php endif; ?>

  <div class="row g-3 g-md-4">
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;"><i class="bi bi-person-plus me-2" style="color:#2E5FA8;"></i>Add Educator</h2>

          <form method="post" autocomplete="off">
            <input type="hidden" name="action" value="create">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">First name</label>
                <input name="first_name" class="form-control" required value="<?= e($_POST['first_name'] ?? '') ?>" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
              </div>
              <div class="col-md-6">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Last name</label>
                <input name="last_name" class="form-control" required value="<?= e($_POST['last_name'] ?? '') ?>" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Email</label>
              <input name="email" type="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
            </div>

            <div class="mb-3">
              <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Temporary Password</label>
              <input name="password" type="password" class="form-control" required style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
              <div class="form-text" style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;">Educator can change later.</div>
            </div>

            <button class="btn w-100" type="submit" style="background:#2E5FA8;color:white;border:none;border-radius:8px;padding:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Create Educator</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;"><i class="bi bi-people me-2" style="color:#2E5FA8;"></i>Educator List</h2>

          <div class="table-responsive">
            <table class="table align-middle" style="font-family:'Inter',sans-serif;">
              <thead style="background:#f8f9fa;">
                <tr>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Name</th>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Email</th>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Status</th>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Action</th>
                </tr>
              </thead>
              <tbody>
              <?php if (!$list): ?>
                <tr><td colspan="4" class="text-center" style="padding:40px;color:#94a3b8;font-size:14px;"><i class="bi bi-inbox" style="font-size:48px;display:block;margin-bottom:8px;"></i>No educators yet.</td></tr>
              <?php endif; ?>

              <?php foreach ($list as $ed): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                  <td style="padding:16px 12px;font-size:14px;color:#0f172a;font-weight:500;"><?= e($ed['first_name'] . ' ' . $ed['last_name']) ?></td>
                  <td style="padding:16px 12px;font-size:14px;color:#64748b;"><?= e($ed['email']) ?></td>
                  <td style="padding:16px 12px;text-align:center;">
                    <?php if ((int)$ed['is_active'] === 1): ?>
                      <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#d4edda;color:#155724;border-radius:12px;font-size:12px;font-weight:600;"><i class="bi bi-check-circle-fill"></i> Active</span>
                    <?php else: ?>
                      <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#f8f9fa;color:#6c757d;border-radius:12px;font-size:12px;font-weight:600;"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:16px 12px;text-align:center;">
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
                      <?php if ((int)$ed['is_active'] === 1): ?>
                        <button class="btn btn-sm" type="submit" style="background:#fff;border:1px solid #ffc107;color:#ffc107;border-radius:8px;padding:6px 16px;font-size:13px;font-weight:500;"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                      <?php else: ?>
                        <button class="btn btn-sm" type="submit" style="background:#fff;border:1px solid #28a745;color:#28a745;border-radius:8px;padding:6px 16px;font-size:13px;font-weight:500;"><i class="bi bi-play-circle me-1"></i>Activate</button>
                      <?php endif; ?>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>

              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>
</body>
</html>
