<footer class="clarion-footer">
  <div class="footer-container">
    <div class="footer-brand">
      <div class="brand">
        <img src="<?= e(BASE_URL) ?>/logo/clarion_logo.png" alt="Clarion Logo" class="logo">
        <span class="name">CLARION</span>
      </div>
      <p class="tagline">
        A centralized academic content platform designed for students and educators.
      </p>
    </div>
    <div class="footer-links">
      <h4>Platform</h4>
      <a href="<?= e(BASE_URL) ?>/about.php">About</a>
      <a href="<?= e(BASE_URL) ?>/terms.php">Terms & Conditions</a>
      <a href="<?= e(BASE_URL) ?>/privacy.php">Privacy Policy</a>
    </div>
    <div class="footer-links">
      <h4>Account</h4>
      <?php if (isset($user)): ?>
        <a href="<?= e(BASE_URL) ?>/account/personal_info.php">My Profile</a>
        <?php if ($user['role'] !== 'SUPER_ADMIN'): ?>
          <a href="<?= e(BASE_URL) ?>/account/change_password.php">Change Password</a>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>/auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= e(BASE_URL) ?>/auth/login.php">Login</a>
        <a href="<?= e(BASE_URL) ?>/auth/register.php">Sign Up</a>
      <?php endif; ?>
    </div>
    <div class="footer-links">
      <h4>Support</h4>
      <a href="<?= e(BASE_URL) ?>/help.php">Contact Support</a>
      <a href="<?= e(BASE_URL) ?>/help.php">Help Center</a>
    </div>
  </div>
  <div class="footer-bottom">
    © <?= date('Y') ?> Clarion. All rights reserved.
  </div>
</footer>
