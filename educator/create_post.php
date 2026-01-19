<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/supabase_storage.php';
require_role(['EDUCATOR']);

$user = auth_user();
$error = null;
$success = null;

// Fetch active subjects for dropdown
$subjects = db()->query("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Helpers for upload
function ensure_dir(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function random_filename(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

function mime_type_of(string $tmpPath): string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath) ?: '';
    finfo_close($finfo);
    return $mime;
}

function upload_file(array $file, array $allowedExt, int $maxBytes, string $destDirAbs): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return [false, null, "Invalid upload."];
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [false, null, "File too large. Check your PHP upload_max_filesize and post_max_size settings."];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, null, "Upload failed (error code {$file['error']})."];
    }

    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        return [false, null, "File too large. Max " . round($maxBytes / 1024 / 1024, 1) . " MB."];
    }

    $original = $file['name'] ?? '';
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return [false, null, "Invalid file type. Allowed: " . implode(', ', $allowedExt)];
    }

    ensure_dir($destDirAbs);

    $newName = random_filename($ext);
    $destAbs = rtrim($destDirAbs, '/\\') . DIRECTORY_SEPARATOR . $newName;

    // Basic MIME validation
    $mime = mime_type_of($file['tmp_name']);

    if (in_array('pdf', $allowedExt, true)) {
        // Accept common PDF MIME
        if ($mime !== 'application/pdf') {
            return [false, null, "Invalid PDF MIME type: $mime"];
        }
    } else {
        // For images allow common types
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return [false, null, "Invalid image MIME type: $mime"];
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        return [false, null, "Failed to save uploaded file."];
    }

    return [true, $newName, null];
}

if (is_post()) {
    require_csrf();
    $subject_id  = (int)($_POST['subject_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($subject_id <= 0) {
        $error = "Please choose a subject.";
    } elseif ($title === '') {
        $error = "Title is required.";
    } elseif ($description === '') {
        $error = "Description is required.";
    } elseif (!isset($_FILES['pdf'])) {
        $error = "PDF file is required.";
    } else {
        // Absolute directories for uploads (local staging)
        $pdfDirAbs   = __DIR__ . '/../assets/uploads/pdf';
        $thumbDirAbs = __DIR__ . '/../assets/uploads/thumbs';

        // Upload rules (local first)
        [$okPdf, $pdfName, $pdfErr] = upload_file(
            $_FILES['pdf'],
            ['pdf'],
            20 * 1024 * 1024, // 20MB
            $pdfDirAbs
        );

        if (!$okPdf) {
            $error = $pdfErr;
        } else {

            // Defaults (local)
            $pdfProvider = 'LOCAL';
            $thumbProvider = 'LOCAL';
            $pdfUrl = BASE_URL . "/assets/uploads/pdf/" . $pdfName;

            $thumbUrl = BASE_URL . "/assets/images/pdf-placeholder.svg";

            // Thumbnail (optional) local first
            $thumbName = null;
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
                [$okThumb, $thumbName, $thumbErr] = upload_file(
                    $_FILES['thumbnail'],
                    ['jpg', 'jpeg', 'png', 'webp'],
                    5 * 1024 * 1024,
                    $thumbDirAbs
                );

                if (!$okThumb) {
                    @unlink($pdfDirAbs . DIRECTORY_SEPARATOR . $pdfName);
                    $error = $thumbErr;
                } else {
                    $thumbUrl = BASE_URL . "/assets/uploads/thumbs/" . $thumbName;
                }
            }

            // If Supabase is configured, upload staged files to Supabase Storage and switch URLs/providers
            if (!$error && sb_enabled()) {
                $pdfLocalPath = $pdfDirAbs . DIRECTORY_SEPARATOR . $pdfName;

                // Put files under a folder to keep bucket organized:
                // e.g. posts/<userId>/<filename>
                $pdfPathInBucket = "posts/" . $user['id'] . "/" . $pdfName;

                [$sbOkPdf, $sbPdfUrl, $sbPdfErr] = sb_upload_public(
                    sb_bucket_pdf(),
                    $pdfPathInBucket,
                    $pdfLocalPath,
                    "application/pdf"
                );

                if (!$sbOkPdf) {
                    // rollback local files
                    @unlink($pdfLocalPath);
                    if ($thumbName) @unlink($thumbDirAbs . DIRECTORY_SEPARATOR . $thumbName);
                    $error = $sbPdfErr;
                } else {
                    $pdfUrl = $sbPdfUrl;
                    $pdfProvider = 'SUPABASE';

                    // Upload thumbnail only if provided (otherwise keep placeholder)
                    if ($thumbName) {
                        $thumbLocalPath = $thumbDirAbs . DIRECTORY_SEPARATOR . $thumbName;
                        $thumbPathInBucket = "thumbs/" . $user['id'] . "/" . $thumbName;

                        // Detect content type by extension (simple + safe enough for your allowed list)
                        $ext = strtolower(pathinfo($thumbName, PATHINFO_EXTENSION));
                        $ct = "image/png";
                        if ($ext === "jpg" || $ext === "jpeg") $ct = "image/jpeg";
                        if ($ext === "webp") $ct = "image/webp";

                        [$sbOkThumb, $sbThumbUrl, $sbThumbErr] = sb_upload_public(
                            sb_bucket_thumbs(),
                            $thumbPathInBucket,
                            $thumbLocalPath,
                            $ct
                        );

                        if (!$sbOkThumb) {
                            // rollback everything (also delete pdf local staged)
                            @unlink($pdfLocalPath);
                            @unlink($thumbLocalPath);
                            $error = $sbThumbErr;
                        } else {
                            $thumbUrl = $sbThumbUrl;
                            $thumbProvider = 'SUPABASE';
                        }
                    }

                    // cleanup staged local files (optional)
                    @unlink($pdfLocalPath);
                    if ($thumbName) @unlink($thumbDirAbs . DIRECTORY_SEPARATOR . $thumbName);
                }
            }

            if (!$error) {
                // Insert post
                $ins = db()->prepare("
                    INSERT INTO posts
                    (subject_id, creator_id, title, description, pdf_url, thumbnail_url, pdf_storage_provider, thumb_storage_provider, is_published)
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $ins->execute([
                    $subject_id,
                    $user['id'],
                    $title,
                    $description,
                    $pdfUrl,
                    $thumbUrl,
                    $pdfProvider,
                    $thumbProvider
                ]);

                $success = "Post created successfully!";
                unset($_SESSION['csrf_token']); // Regenerate token after success
                $_POST = [];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Post - <?= e(APP_NAME) ?></title>
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

  <div class="container py-3 py-md-4 px-3 px-md-0" style="max-width:800px;">
    <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin-bottom:24px;">Create Post</h1>

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <?= csrf_field() ?>

          <div class="mb-4">
            <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;">Subject *</label>
            <select name="subject_id" class="form-select" required style="font-family:'Inter',sans-serif;font-size:14px;">
              <option value="">-- Select subject --</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= ((int)($_POST['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                  <?= e($s['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!$subjects): ?>
              <div class="form-text text-danger"><i class="bi bi-exclamation-circle me-1"></i>No active subjects. Contact admin to create subjects.</div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;">Title *</label>
            <input name="title" class="form-control" required value="<?= e($_POST['title'] ?? '') ?>" placeholder="Enter post title" style="font-family:'Inter',sans-serif;font-size:14px;">
          </div>

          <div class="mb-4">
            <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;">Description *</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Add a description" required style="font-family:'Inter',sans-serif;font-size:14px;"><?= e($_POST['description'] ?? '') ?></textarea>
            <div class="form-text">Provide additional context about this material</div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;">PDF File *</label>
              <div class="upload-box" onclick="document.getElementById('pdfInput').click()" style="border:2px dashed #e5e7eb;border-radius:12px;padding:32px;text-align:center;cursor:pointer;transition:all 0.2s;">
                <i class="bi bi-file-pdf" style="font-size:48px;color:#dc3545;margin-bottom:12px;"></i>
                <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;margin:0;">Click to upload PDF</p>
                <p style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;margin-top:4px;">Max 20MB</p>
                <input type="file" id="pdfInput" name="pdf" class="d-none" accept="application/pdf" required onchange="showFileName(this, 'pdfName')">
                <div id="pdfName" style="font-family:'Inter',sans-serif;font-size:13px;color:#2E5FA8;margin-top:8px;font-weight:500;"></div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-family:'Inter',sans-serif;font-weight:600;color:#0f172a;">Thumbnail Image <span style="font-weight:400;color:#94a3b8;">(Optional)</span></label>
              <div class="upload-box" onclick="document.getElementById('thumbInput').click()" style="border:2px dashed #e5e7eb;border-radius:12px;padding:32px;text-align:center;cursor:pointer;transition:all 0.2s;">
                <i class="bi bi-image" style="font-size:48px;color:#2E5FA8;margin-bottom:12px;"></i>
                <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;margin:0;">Click to upload image</p>
                <p style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;margin-top:4px;">Max 5MB • JPG/PNG/WEBP</p>
                <input type="file" id="thumbInput" name="thumbnail" class="d-none" accept="image/*" onchange="showPreview(this, 'thumbPreview')">
                <img id="thumbPreview" style="max-width:100%;max-height:120px;margin-top:12px;border-radius:8px;display:none;">
              </div>
            </div>
          </div>

        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" type="button" id="previewBtn" onclick="showPreviewModal(event)" style="font-family:'Inter',sans-serif;font-size:16px;font-weight:600;padding:12px 24px;display:none;">
            <i class="bi bi-eye me-2"></i>Preview Post
          </button>

          <button class="btn btn-primary flex-fill" type="submit" style="font-family:'Inter',sans-serif;font-size:16px;font-weight:600;padding:12px;border-radius:10px;">
            <i class="bi bi-upload me-2"></i>Create Post
          </button>
        </div>

          <div class="mt-3 text-center" style="font-size:12px;color:#64748b;">
            <?= sb_enabled() ? "Supabase Storage: ENABLED" : "Supabase Storage: NOT CONFIGURED (using local uploads)" ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>

 <script>
  function showFileName(input, targetId) {
    const target = document.getElementById(targetId);
    if (input.files && input.files[0]) {
      const file = input.files[0];
      target.textContent = '✓ ' + file.name;
      input.closest('.upload-box').style.borderColor = '#2E5FA8';
      input.closest('.upload-box').style.backgroundColor = '#f0f7ff';

      // Auto title from PDF filename
      extractPdfMetadata(file);
    }
    checkPreviewButton();
  }

  async function extractPdfMetadata(file) {
    try {
      // Use filename without extension as title
      const title = file.name.replace(/\.pdf$/i, '').replace(/[_-]/g, ' ');

      const titleInput = document.querySelector('[name="title"]');
      if (title && !titleInput.value) {
        titleInput.value = title;
        if (typeof showToast === 'function') showToast('Title extracted from PDF filename');
      }
      checkPreviewButton();
    } catch (e) {
      console.error('PDF extraction error:', e);
    }
  }

  function showPreview(input, targetId) {
    const target = document.getElementById(targetId);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        target.src = e.target.result;
        target.style.display = 'block';
        input.closest('.upload-box').querySelector('i').style.display = 'none';
        input.closest('.upload-box').querySelectorAll('p').forEach(p => p.style.display = 'none');
      };
      reader.readAsDataURL(input.files[0]);
      input.closest('.upload-box').style.borderColor = '#2E5FA8';
      input.closest('.upload-box').style.backgroundColor = '#f0f7ff';
    }
    checkPreviewButton();
  }

  function checkPreviewButton() {
    const subject = document.querySelector('[name="subject_id"]').value;
    const title = document.querySelector('[name="title"]').value;
    const description = document.querySelector('[name="description"]').value;
    const pdf = document.getElementById('pdfInput').files.length > 0;

    const previewBtn = document.getElementById('previewBtn');
    if (!previewBtn) return;

    if (subject && title && description && pdf) {
      previewBtn.style.display = 'block';
    } else {
      previewBtn.style.display = 'none';
    }
  }

  function showPreviewModal(e) {
    if (e) e.preventDefault();

    const subject = document.querySelector('[name="subject_id"] option:checked').text;
    const title = document.querySelector('[name="title"]').value;
    const description = document.querySelector('[name="description"]').value;

    const thumbPreview = document.getElementById('thumbPreview');
    const thumbSrc =
      thumbPreview && thumbPreview.style.display === 'block'
        ? thumbPreview.src
        : '<?= e(BASE_URL) ?>/assets/images/pdf-placeholder.svg';

    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `
      <div style="background:white;border-radius:12px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;">
        <div style="padding:24px;border-bottom:1px solid #e5e7eb;">
          <h3 style="font-family:'Inter',sans-serif;font-size:20px;font-weight:600;margin:0;">Post Preview</h3>
        </div>
        <div style="padding:24px;">
          <img src="${thumbSrc}" style="width:100%;height:200px;object-fit:cover;border-radius:8px;margin-bottom:16px;">
          <div style="display:inline-block;padding:4px 10px;background:#D4E3F7;color:#2E5FA8;border-radius:12px;font-size:11px;font-weight:500;margin-bottom:12px;">${subject}</div>
          <h4 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:8px;">${title}</h4>
          <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;line-height:1.6;">${description}</p>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #e5e7eb;text-align:right;">
          <button onclick="this.closest('div').parentElement.parentElement.remove()"
            style="padding:8px 16px;background:#2E5FA8;color:white;border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Close</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    modal.onclick = (ev) => { if (ev.target === modal) modal.remove(); };
  }

  // Hover styling like your original
  document.querySelectorAll('.upload-box').forEach(box => {
    box.addEventListener('mouseenter', function() {
      if (!this.style.backgroundColor) {
        this.style.borderColor = '#2E5FA8';
        this.style.backgroundColor = '#fafbfc';
      }
    });
    box.addEventListener('mouseleave', function() {
      if (this.style.backgroundColor !== 'rgb(240, 247, 255)') {
        this.style.borderColor = '#e5e7eb';
        this.style.backgroundColor = '';
      }
    });
  });

  // Recompute preview button on field changes
  document.querySelector('[name="subject_id"]').addEventListener('change', checkPreviewButton);
  document.querySelector('[name="title"]').addEventListener('input', checkPreviewButton);
  document.querySelector('[name="description"]').addEventListener('input', checkPreviewButton);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
