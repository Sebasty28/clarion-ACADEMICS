<?php
require_once __DIR__ . '/core/auth.php';

if (is_logged_in()) {
    $user = auth_user();
    redirect(role_home($user['role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to <?= e(APP_NAME) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
  <style>
    body{font-family:'Inter',sans-serif;margin:0;padding:0;}
    .hero{background:linear-gradient(135deg,#2E5FA8 0%,#1a3d6b 100%);color:white;padding:60px 20px;text-align:center;}
    .hero h1{font-size:48px;font-weight:800;margin-bottom:20px;}
    .hero p{font-size:20px;margin-bottom:40px;color:#e6eef8;}
    .hero .btn{padding:14px 32px;font-size:16px;font-weight:600;border-radius:8px;text-decoration:none;display:inline-block;margin:0 8px;}
    @media (max-width: 768px) {
      .hero{padding:40px 15px;}
      .hero h1{font-size:32px;}
      .hero p{font-size:16px;margin-bottom:30px;}
      .hero .btn{display:block;margin:8px auto;width:100%;max-width:280px;}
    }
    .btn-primary{background:white;color:#2E5FA8;}
    .btn-secondary{background:transparent;color:white;border:2px solid white;}
    .features{padding:80px 20px;background:#f8f9fa;}
    .features h2{text-align:center;font-size:36px;font-weight:700;margin-bottom:60px;color:#0f172a;}
    .feature-card{background:white;padding:40px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center;height:100%;}
    .feature-card i{font-size:48px;color:#2E5FA8;margin-bottom:20px;}
    .feature-card h3{font-size:20px;font-weight:600;margin-bottom:12px;color:#0f172a;}
    .feature-card p{font-size:15px;color:#64748b;line-height:1.6;}
    .about{padding:80px 20px;}
    .about h2{text-align:center;font-size:36px;font-weight:700;margin-bottom:40px;color:#0f172a;}
    .about p{text-align:center;font-size:18px;color:#64748b;max-width:800px;margin:0 auto 20px;line-height:1.8;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="position:sticky;top:0;z-index:1000;">
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

<section class="hero">
  <h1>Welcome to Clarion</h1>
  <p>Your Educational Resource Sharing Platform</p>
  <a href="<?= e(BASE_URL) ?>/auth/register.php" class="btn btn-primary">Get Started</a>
  <a href="<?= e(BASE_URL) ?>/auth/login.php" class="btn btn-secondary">Login</a>
</section>

<section class="features">
  <div class="container">
    <h2>Why Choose Clarion?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card">
          <i class="fa-solid fa-book-open"></i>
          <h3>Rich Content Library</h3>
          <p>Access a vast collection of educational resources shared by educators worldwide.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <i class="fa-solid fa-users"></i>
          <h3>Collaborative Learning</h3>
          <p>Connect with educators and students to share knowledge and grow together.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <i class="fa-solid fa-chart-line"></i>
          <h3>Track Progress</h3>
          <p>Monitor engagement and analytics to understand learning patterns and improve.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="about">
  <div class="container">
    <h2>About Clarion</h2>
    <p>Clarion is a modern educational platform designed to bridge the gap between educators and students. We provide a seamless environment for sharing educational resources, fostering collaboration, and enhancing the learning experience.</p>
    <p>Whether you're an educator looking to share your knowledge or a student seeking quality learning materials, Clarion offers the tools and community to support your educational journey.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
