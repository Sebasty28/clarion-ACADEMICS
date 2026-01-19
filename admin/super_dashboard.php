<?php
require_once __DIR__ . '/../core/auth.php';
require_role(['SUPER_ADMIN']);
$user = auth_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
</head>
<body class="bg-light">

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

<div class="container py-3 py-md-4 px-3 px-md-0">
  <?php 
  $flash_error = get_flash('error');
  $flash_success = get_flash('success');
  if ($flash_error): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_error) ?>', 'error'));</script>
  <?php endif; ?>
  <?php if ($flash_success): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_success) ?>'));</script>
  <?php endif; ?>
  
  <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin-bottom:24px;">Admin Dashboard</h1>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;transition:transform 0.2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'" onclick="window.location.href='<?= e(BASE_URL) ?>/admin/educators.php'">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div style="width:56px;height:56px;background:#f0f7ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-person-badge" style="font-size:28px;color:#2E5FA8;"></i>
            </div>
          </div>
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:8px;">Manage Educators</h2>
          <p style="font-family:'Inter',sans-serif;font-size:14px;color:#64748b;margin-bottom:16px;">Create educator accounts and manage their access</p>
          <a href="<?= e(BASE_URL) ?>/admin/educators.php" class="btn btn-sm" style="background:#2E5FA8;color:white;border-radius:8px;padding:8px 16px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;">Manage Educators →</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;transition:transform 0.2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'" onclick="window.location.href='<?= e(BASE_URL) ?>/admin/subjects.php'">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div style="width:56px;height:56px;background:#fff8e6;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-book" style="font-size:28px;color:#ffc107;"></i>
            </div>
          </div>
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:8px;">Manage Subjects</h2>
          <p style="font-family:'Inter',sans-serif;font-size:14px;color:#64748b;margin-bottom:16px;">Create and maintain your subjects list</p>
          <a href="<?= e(BASE_URL) ?>/admin/subjects.php" class="btn btn-sm" style="background:#ffc107;color:#000;border-radius:8px;padding:8px 16px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;">Manage Subjects →</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;transition:transform 0.2s;cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'" onclick="window.location.href='<?= e(BASE_URL) ?>/educator/analytics.php'">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div style="width:56px;height:56px;background:#f0fdf4;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-graph-up" style="font-size:28px;color:#10b981;"></i>
            </div>
          </div>
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:8px;">View Analytics</h2>
          <p style="font-family:'Inter',sans-serif;font-size:14px;color:#64748b;margin-bottom:16px;">Monitor platform engagement and statistics</p>
          <a href="<?= e(BASE_URL) ?>/educator/analytics.php" class="btn btn-sm" style="background:#10b981;color:white;border-radius:8px;padding:8px 16px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;">View Analytics →</a>
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
