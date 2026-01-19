<?php
require_once __DIR__ . '/../core/auth.php';
require_role(['SUPER_ADMIN']);

$error = null;
$success = null;

// Add subject
if (is_post() && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = "Subject name is required.";
    } else {
        $chk = db()->prepare("SELECT id FROM subjects WHERE name = ? LIMIT 1");
        $chk->execute([$name]);
        if ($chk->fetch()) {
            $error = "Subject already exists.";
        } else {
            $ins = db()->prepare("INSERT INTO subjects (name, description, is_active) VALUES (?, ?, 1)");
            $ins->execute([$name, $desc !== '' ? $desc : null]);
            $success = "Subject added.";
        }
    }
}

// Edit subject
if (is_post() && ($_POST['action'] ?? '') === 'edit') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($id <= 0 || $name === '') {
        $error = "Invalid subject data.";
    } else {
        $chk = db()->prepare("SELECT id FROM subjects WHERE name = ? AND id <> ? LIMIT 1");
        $chk->execute([$name, $id]);
        if ($chk->fetch()) {
            $error = "Another subject already has that name.";
        } else {
            $up = db()->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
            $up->execute([$name, $desc !== '' ? $desc : null, $id]);
            $success = "Subject updated.";
        }
    }
}

// Toggle active
if (is_post() && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("SELECT id, is_active FROM subjects WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $s = $stmt->fetch();

    if ($s) {
        $new = ($s['is_active'] ? 0 : 1);
        $up = db()->prepare("UPDATE subjects SET is_active = ? WHERE id = ?");
        $up->execute([$new, $id]);
        $success = "Subject status updated.";
    } else {
        $error = "Subject not found.";
    }
}

// Delete
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cnt = db()->prepare("SELECT COUNT(*) AS c FROM posts WHERE subject_id = ?");
    $cnt->execute([$id]);
    $c = (int)($cnt->fetch()['c'] ?? 0);

    if ($c > 0) {
        $error = "Cannot delete. This subject is already used in posts. Deactivate instead.";
    } else {
        $del = db()->prepare("DELETE FROM subjects WHERE id = ?");
        $del->execute([$id]);
        $success = "Subject deleted.";
    }
}

$subjects = db()->query("SELECT * FROM subjects ORDER BY created_at DESC")->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = db()->prepare("SELECT * FROM subjects WHERE id = ? LIMIT 1");
    $st->execute([$editId]);
    $editRow = $st->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Subjects - <?= e(APP_NAME) ?></title>
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
    <h1 style="font-family:'Inter',sans-serif;font-size:24px;font-weight:700;color:#0f172a;margin:0;" class="mb-3 mb-md-0">Manage Subjects</h1>
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
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;"><i class="bi bi-<?= $editRow ? 'pencil' : 'plus-circle' ?> me-2" style="color:#2E5FA8;"></i><?= $editRow ? "Edit Subject" : "Add Subject" ?></h2>

          <?php if ($editRow): ?>
            <form method="post">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">

              <div class="mb-3">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Name</label>
                <input name="name" class="form-control" required value="<?= e($editRow['name']) ?>" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
              </div>

              <div class="mb-3">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Description (optional)</label>
                <textarea name="description" class="form-control" rows="3" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;"><?= e($editRow['description'] ?? '') ?></textarea>
              </div>

              <button class="btn w-100 mb-2" type="submit" style="background:#2E5FA8;color:white;border:none;border-radius:8px;padding:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
              <a class="btn w-100" href="<?= e(BASE_URL) ?>/admin/subjects.php" style="background:#f8f9fa;color:#64748b;border:1px solid #e5e7eb;border-radius:8px;padding:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;text-decoration:none;display:block;text-align:center;"><i class="bi bi-x-circle me-2"></i>Cancel</a>
            </form>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="action" value="add">

              <div class="mb-3">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Name</label>
                <input name="name" class="form-control" required style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;">
              </div>

              <div class="mb-3">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#64748b;">Description (optional)</label>
                <textarea name="description" class="form-control" rows="3" style="border-radius:8px;border:1px solid #e5e7eb;padding:10px 12px;font-family:'Inter',sans-serif;"></textarea>
              </div>

              <button class="btn w-100" type="submit" style="background:#2E5FA8;color:white;border:none;border-radius:8px;padding:12px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Add Subject</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;"><i class="bi bi-book me-2" style="color:#2E5FA8;"></i>Subject List</h2>

          <div class="table-responsive">
            <table class="table align-middle" style="font-family:'Inter',sans-serif;">
              <thead style="background:#f8f9fa;">
                <tr>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Name</th>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Status</th>
                  <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if (!$subjects): ?>
                <tr><td colspan="3" class="text-center" style="padding:40px;color:#94a3b8;font-size:14px;"><i class="bi bi-inbox" style="font-size:48px;display:block;margin-bottom:8px;"></i>No subjects yet.</td></tr>
              <?php endif; ?>

              <?php foreach ($subjects as $s): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                  <td style="padding:16px 12px;">
                    <div style="font-family:'Inter',sans-serif;font-size:14px;font-weight:500;color:#0f172a;"><?= e($s['name']) ?></div>
                    <?php if (!empty($s['description'])): ?>
                      <div style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;margin-top:4px;"><?= e($s['description']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="padding:16px 12px;text-align:center;">
                    <?php if ((int)$s['is_active'] === 1): ?>
                      <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#d4edda;color:#155724;border-radius:12px;font-size:12px;font-weight:600;"><i class="bi bi-check-circle-fill"></i> Active</span>
                    <?php else: ?>
                      <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#f8f9fa;color:#6c757d;border-radius:12px;font-size:12px;font-weight:600;"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:16px 12px;text-align:center;">
                    <div class="d-flex gap-2 justify-content-center">
                      <a class="btn btn-sm" href="<?= e(BASE_URL) ?>/admin/subjects.php?edit=<?= (int)$s['id'] ?>" style="background:#fff;border:1px solid #2E5FA8;color:#2E5FA8;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:500;text-decoration:none;"><i class="bi bi-pencil"></i></a>

                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <?php if ((int)$s['is_active'] === 1): ?>
                          <button class="btn btn-sm" type="submit" style="background:#fff;border:1px solid #ffc107;color:#ffc107;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:500;"><i class="bi bi-pause-circle"></i></button>
                        <?php else: ?>
                          <button class="btn btn-sm" type="submit" style="background:#fff;border:1px solid #28a745;color:#28a745;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:500;"><i class="bi bi-play-circle"></i></button>
                        <?php endif; ?>
                      </form>

                      <form method="post" class="d-inline" onsubmit="return confirm('Delete this subject? This only works if no posts use it.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button class="btn btn-sm" type="submit" style="background:#fff;border:1px solid #dc3545;color:#dc3545;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:500;"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
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
