<?php
require_once __DIR__ . '/../core/auth.php';
require_role(['EDUCATOR']);
$user = auth_user();
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #F8F9FA;
    }
    .navbar {
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .educator-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      background: #fff3cd;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
      color: #856404;
    }
    .educator-badge i {
      font-size: 18px;
    }
    .welcome-card {
      background: white;
      border-radius: 12px;
      padding: 32px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 32px;
    }
    .dashboard-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      height: 100%;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .dashboard-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .dashboard-card h3 {
      font-size: 18px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 8px;
    }
    .dashboard-card p {
      font-size: 14px;
      color: #697483;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white" style="position:sticky;top:0;z-index:1000;">
    <div class="container-fluid px-3 px-lg-5">
      <a class="navbar-brand d-flex align-items-center ms-0 ms-lg-5" href="<?= e(BASE_URL) ?>/educator/dashboard.php">
        <i class="fa-solid fa-graduation-cap" style="color:#2E5FA8;font-size:24px;margin-right:8px;"></i>
        <span style="font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#2E5FA8;" class="d-none d-sm-inline"><?= e(strtoupper(APP_NAME)) ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto me-0 me-lg-5">
          <a href="<?= e(BASE_URL) ?>/educator/dashboard.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Dashboard</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/create_post.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Create Post</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/my_posts.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">My Posts</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/educator/analytics.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Analytics</a>
          <div class="dropdown ms-lg-2">
            <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="dropdown" style="color:#2E5FA8;font-size:24px;">
              <i class="bi bi-person-circle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/account/personal_info.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/account/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
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
    
    <div class="welcome-card">
      <h1 style="font-size:32px;font-weight:700;color:#0f172a;margin-bottom:12px;">Hi, <?= e($user['first_name']) ?>!</h1>
      <div class="educator-badge">
        <i class="bi bi-person-badge-fill"></i>
        <span>Educator</span>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="dashboard-card">
          <h3>Create Post</h3>
          <p>Upload a PDF with a thumbnail, title, and description.</p>
          <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/educator/create_post.php" style="font-family:'Inter',sans-serif;font-size:14px;">
            <i class="bi bi-plus-circle me-2"></i>Create Post
          </a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="dashboard-card">
          <h3>My Posts</h3>
          <p>View your uploaded posts and like counts.</p>
          <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/educator/my_posts.php" style="font-family:'Inter',sans-serif;font-size:14px;">
            <i class="bi bi-file-text me-2"></i>View My Posts
          </a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="dashboard-card">
          <h3>Analytics</h3>
          <p>See likes, trends, and top posts.</p>
          <a class="btn btn-primary" href="<?= e(BASE_URL) ?>/educator/analytics.php" style="font-family:'Inter',sans-serif;font-size:14px;">
            <i class="bi bi-graph-up me-2"></i>View Analytics
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>
</body>
</html>
