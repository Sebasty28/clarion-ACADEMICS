<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_role(['EDUCATOR']);

$user = auth_user();
$error = null;
$success = null;

$postId = (int)($_GET['id'] ?? 0);

// Fetch post
$stmt = db()->prepare("SELECT * FROM posts WHERE id = ? AND creator_id = ?");
$stmt->execute([$postId, $user['id']]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: ' . BASE_URL . '/educator/my_posts.php');
    exit;
}

// Fetch active subjects
$subjects = db()->query("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

if (is_post()) {
    require_csrf();
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($subject_id <= 0) {
        $error = "Please choose a subject.";
    } elseif ($title === '') {
        $error = "Title is required.";
    } elseif ($description === '') {
        $error = "Description is required.";
    } elseif ($subject_id === (int)$post['subject_id'] && $title === $post['title'] && $description === $post['description']) {
        $error = "No changes detected. Please modify at least one field to update.";
    } else {
        $upd = db()->prepare("UPDATE posts SET subject_id = ?, title = ?, description = ? WHERE id = ? AND creator_id = ?");
        $upd->execute([$subject_id, $title, $description, $postId, $user['id']]);
        $success = "Post updated successfully!";
        
        // Refresh post data
        $post['subject_id'] = $subject_id;
        $post['title'] = $title;
        $post['description'] = $description;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Post - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #F8F9FA; }
    .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container py-3 py-md-4 px-3 px-md-0" style="max-width:900px;">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 mb-md-4 gap-3">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
        <a href="<?= e(BASE_URL) ?>/educator/my_posts.php" class="btn btn-outline-secondary" style="border-radius:8px;padding:8px 16px;white-space:nowrap;">
          <i class="bi bi-arrow-left me-1"></i>Back to My Posts
        </a>
        <h1 style="font-family:'Inter',sans-serif;font-size:24px;font-weight:700;color:#0f172a;margin:0;">Edit Post</h1>
      </div>
    </div>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <form method="post" autocomplete="off" id="editForm">
              <?= csrf_field() ?>
              
              <div class="mb-4">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;font-size:14px;">Subject *</label>
                <select name="subject_id" class="form-select" required style="font-family:'Inter',sans-serif;font-size:14px;border-radius:8px;padding:10px;">
                  <option value="">-- Select subject --</option>
                  <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ((int)$post['subject_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                      <?= e($s['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-4">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;font-size:14px;">Title *</label>
                <input name="title" class="form-control" required value="<?= e($post['title']) ?>" placeholder="Enter post title" style="font-family:'Inter',sans-serif;font-size:14px;border-radius:8px;padding:10px;">
              </div>

              <div class="mb-4">
                <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;font-size:14px;">Description *</label>
                <textarea name="description" class="form-control" rows="6" placeholder="Add a description" required style="font-family:'Inter',sans-serif;font-size:14px;border-radius:8px;padding:10px;line-height:1.6;"><?= e($post['description']) ?></textarea>
                <div class="form-text" style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;margin-top:8px;">
                  <i class="bi bi-info-circle me-1"></i>Provide detailed information about this material
                </div>
              </div>

              <div class="d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit" style="font-family:'Inter',sans-serif;font-size:16px;font-weight:600;padding:12px;border-radius:8px;">
                  <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
                <a href="<?= e(BASE_URL) ?>/educator/my_posts.php" class="btn btn-outline-secondary" style="font-family:'Inter',sans-serif;font-size:16px;font-weight:600;padding:12px;border-radius:8px;">
                  Cancel
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="position:sticky;top:20px;">
          <div class="card-body p-4">
            <h6 style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;font-size:16px;margin-bottom:16px;">Current Files</h6>
            
            <div class="mb-3">
              <label style="font-family:'Inter',sans-serif;font-size:12px;color:#697483;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">Thumbnail</label>
              <div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">
                <img src="<?= e($post['thumbnail_url']) ?>" style="width:100%;height:150px;object-fit:cover;">
              </div>
            </div>

            <div class="mb-3">
              <label style="font-family:'Inter',sans-serif;font-size:12px;color:#697483;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">PDF File</label>
              <a href="<?= e($post['pdf_url']) ?>" target="_blank" class="btn btn-outline-primary w-100 mt-2" style="font-family:'Inter',sans-serif;font-size:14px;border-radius:8px;padding:10px;">
                <i class="bi bi-file-pdf me-2"></i>View PDF
              </a>
            </div>

            <div class="alert alert-info" style="font-family:'Inter',sans-serif;font-size:13px;background:#f0f7ff;border:1px solid #d4e3f7;border-radius:8px;padding:12px;margin:0;">
              <i class="bi bi-info-circle me-2"></i>Files cannot be changed. Only title, subject, and description can be edited.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
