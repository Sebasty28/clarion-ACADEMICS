<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_role(['STUDENT']);
$user = auth_user();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo "Post not found.";
    exit;
}

$stmt = db()->prepare("
  SELECT p.*, s.name AS subject_name,
         CONCAT(u.first_name,' ',u.last_name) AS creator_name
  FROM posts p
  JOIN subjects s ON s.id = p.subject_id
  JOIN users u ON u.id = p.creator_id
  WHERE p.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo "Post not found.";
    exit;
}

// If post is hidden, check if student has access
$isHidden = (int)$post['is_published'] !== 1;
if ($isHidden) {
    $accessStmt = db()->prepare("
        SELECT COUNT(*) as c FROM (
            SELECT 1 FROM bookmarks WHERE post_id = ? AND student_id = ?
            UNION ALL
            SELECT 1 FROM likes WHERE post_id = ? AND student_id = ?
        ) AS access
    ");
    $accessStmt->execute([$id, $user['id'], $id, $user['id']]);
    $accessCount = (int)($accessStmt->fetch()['c'] ?? 0);
    
    if ($accessCount === 0) {
        http_response_code(403);
        echo "This post is currently hidden and not available.";
        exit;
    }
}

$likedStmt = db()->prepare("SELECT id FROM likes WHERE post_id = ? AND student_id = ? LIMIT 1");
$likedStmt->execute([$id, $user['id']]);
$isLiked = (bool)$likedStmt->fetch();

$bookmarkedStmt = db()->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND student_id = ? LIMIT 1");
$bookmarkedStmt->execute([$id, $user['id']]);
$isBookmarked = (bool)$bookmarkedStmt->fetch();

$bookmarkCountStmt = db()->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE student_id = ?");
$bookmarkCountStmt->execute([$user['id']]);
$bookmarkCount = (int)($bookmarkCountStmt->fetch()['count'] ?? 0);

// Get total liked count
$likedCountStmt = db()->prepare("SELECT COUNT(*) as count FROM likes WHERE student_id = ?");
$likedCountStmt->execute([$user['id']]);
$likedCount = (int)($likedCountStmt->fetch()['count'] ?? 0);

// Get notifications for this student
$notificationsStmt = db()->prepare("
  SELECT n.*, p.title as post_title
  FROM (
    SELECT 'deleted' as type, b.post_id, b.created_at as event_date, 'Post deleted from saved items' as message
    FROM bookmarks b
    LEFT JOIN posts p ON p.id = b.post_id
    WHERE b.student_id = ? AND p.id IS NULL
    UNION ALL
    SELECT 'hidden' as type, b.post_id, p.updated_at as event_date, 'Post hidden in saved items' as message
    FROM bookmarks b
    JOIN posts p ON p.id = b.post_id
    WHERE b.student_id = ? AND p.is_published = 0
    UNION ALL
    SELECT 'hidden' as type, l.post_id, p.updated_at as event_date, 'Post hidden in liked posts' as message
    FROM likes l
    JOIN posts p ON p.id = l.post_id
    WHERE l.student_id = ? AND p.is_published = 0
    UNION ALL
    SELECT 'edited' as type, b.post_id, p.updated_at as event_date, 'Post edited in saved items' as message
    FROM bookmarks b
    JOIN posts p ON p.id = b.post_id
    WHERE b.student_id = ? AND p.updated_at IS NOT NULL AND p.updated_at != p.created_at AND p.is_published = 1
    UNION ALL
    SELECT 'edited' as type, l.post_id, p.updated_at as event_date, 'Post edited in liked posts' as message
    FROM likes l
    JOIN posts p ON p.id = l.post_id
    WHERE l.student_id = ? AND p.updated_at IS NOT NULL AND p.updated_at != p.created_at AND p.is_published = 1
  ) n
  LEFT JOIN posts p ON p.id = n.post_id
  ORDER BY n.event_date DESC
  LIMIT 10
");
$notificationsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$notifications = $notificationsStmt->fetchAll();

// Count deleted posts in bookmarks
$deletedStmt = db()->prepare("
  SELECT COUNT(*) as c 
  FROM bookmarks b 
  LEFT JOIN posts p ON p.id = b.post_id 
  WHERE b.student_id = ? AND p.id IS NULL
");
$deletedStmt->execute([$user['id']]);
$deletedCount = (int)($deletedStmt->fetch()['c'] ?? 0);

// Count hidden posts in bookmarks
$hiddenBookmarksStmt = db()->prepare("
  SELECT COUNT(*) as c 
  FROM bookmarks b 
  JOIN posts p ON p.id = b.post_id 
  WHERE b.student_id = ? AND p.is_published = 0
");
$hiddenBookmarksStmt->execute([$user['id']]);
$hiddenBookmarksCount = (int)($hiddenBookmarksStmt->fetch()['c'] ?? 0);

// Count hidden posts in liked
$hiddenLikedStmt = db()->prepare("
  SELECT COUNT(*) as c 
  FROM likes l 
  JOIN posts p ON p.id = l.post_id 
  WHERE l.student_id = ? AND p.is_published = 0
");
$hiddenLikedStmt->execute([$user['id']]);
$hiddenLikedCount = (int)($hiddenLikedStmt->fetch()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($post['title']) ?> - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/student/post.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-3 px-lg-5">
      <a class="navbar-brand d-flex align-items-center ms-0 ms-lg-5" href="<?= e(BASE_URL) ?>/student/feed.php">
        <i class="fa-solid fa-graduation-cap" style="color:#2E5FA8;font-size:24px;margin-right:8px;"></i>
        <span style="font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#2E5FA8;" class="d-none d-sm-inline"><?= e(strtoupper(APP_NAME)) ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <div class="d-flex align-items-center gap-2 gap-lg-3 ms-auto me-0 me-lg-5">
          <a href="<?= e(BASE_URL) ?>/student/feed.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Feed</a>
          <span class="d-none d-lg-inline" style="color:#94a3b8;font-size:16px;">|</span>
          <a href="<?= e(BASE_URL) ?>/student/liked.php" class="text-decoration-none nav-link-mobile" style="font-family:'Inter',sans-serif;color:#0f172a;font-weight:500;font-size:16px;padding:8px 0;">Liked</a>
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

  <div class="container-fluid">
    <div class="row">
      <nav class="col-md-3 col-lg-2 sidebar d-none d-md-block">
        <div class="position-sticky pt-3">
          <h6 class="sidebar-heading px-3 mb-3 text-muted">
            <span>MY LIBRARY</span>
          </h6>
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link" href="<?= e(BASE_URL) ?>/student/bookmarks.php">
                <i class="bi bi-bookmark-star me-2"></i>
                Saved Items
                <span class="badge bg-primary rounded-pill ms-auto" id="bookmarkCount"><?= $bookmarkCount ?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= e(BASE_URL) ?>/student/liked.php">
                <i class="bi bi-heart-fill me-2"></i>
                Liked Posts
                <span class="badge bg-danger rounded-pill ms-auto" id="likedCount"><?= $likedCount ?></span>
              </a>
            </li>
          </ul>
          <h6 class="sidebar-heading px-3 mb-3 mt-4 text-muted">
            <span>NOTIFICATIONS</span>
          </h6>
          <div class="px-3" id="notificationsContainer">
            <?php if ($notifications): ?>
              <?php 
              $displayLimit = 5;
              $hasMore = count($notifications) > $displayLimit;
              $displayNotifications = array_slice($notifications, 0, $displayLimit);
              ?>
              <?php foreach ($displayNotifications as $notif): 
                $notifId = md5($notif['post_id'] . $notif['type'] . $notif['message']);
              ?>
                <div class="mb-2 notification-item" style="border:1px solid #e5e7eb;border-radius:6px;padding:10px;background:white;position:relative;" data-notif-id="<?= $notifId ?>">
                  <button onclick="removeNotification(this)" style="position:absolute;top:8px;right:8px;border:none;background:none;color:#94a3b8;cursor:pointer;padding:0;width:16px;height:16px;display:flex;align-items:center;justify-content:center;" title="Dismiss">
                    <i class="bi bi-x" style="font-size:16px;"></i>
                  </button>
                  <div style="display:flex;align-items:start;gap:8px;padding-right:20px;">
                    <i class="bi bi-<?= $notif['type'] === 'deleted' ? 'trash' : ($notif['type'] === 'hidden' ? 'eye-slash' : 'pencil') ?>" style="font-size:14px;color:#64748b;margin-top:2px;"></i>
                    <div style="flex:1;">
                      <p style="font-family:'Inter',sans-serif;font-size:11px;color:#0f172a;margin:0;line-height:1.4;"><?= e($notif['message']) ?></p>
                      <p style="font-family:'Inter',sans-serif;font-size:10px;color:#94a3b8;margin:4px 0 0 0;"><?= e($notif['post_title'] ?? 'Unknown Post') ?></p>
                      <p style="font-family:'Inter',sans-serif;font-size:9px;color:#cbd5e1;margin:2px 0 0 0;"><?= date('M j, Y g:i A', strtotime($notif['event_date'])) ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if ($hasMore): ?>
                <button onclick="toggleOlderNotifications()" id="toggleOlderBtn" style="width:100%;border:1px solid #e5e7eb;border-radius:6px;padding:8px;background:white;color:#64748b;font-family:'Inter',sans-serif;font-size:11px;cursor:pointer;margin-top:8px;">
                  <i class="bi bi-chevron-down" id="toggleIcon"></i> See Older Notifications
                </button>
                <div id="olderNotifications" style="display:none;margin-top:8px;">
                  <?php foreach (array_slice($notifications, $displayLimit) as $notif): 
                    $notifId = md5($notif['post_id'] . $notif['type'] . $notif['message']);
                  ?>
                    <div class="mb-2 notification-item" style="border:1px solid #e5e7eb;border-radius:6px;padding:10px;background:white;position:relative;" data-notif-id="<?= $notifId ?>">
                      <button onclick="removeNotification(this)" style="position:absolute;top:8px;right:8px;border:none;background:none;color:#94a3b8;cursor:pointer;padding:0;width:16px;height:16px;display:flex;align-items:center;justify-content:center;" title="Dismiss">
                        <i class="bi bi-x" style="font-size:16px;"></i>
                      </button>
                      <div style="display:flex;align-items:start;gap:8px;padding-right:20px;">
                        <i class="bi bi-<?= $notif['type'] === 'deleted' ? 'trash' : ($notif['type'] === 'hidden' ? 'eye-slash' : 'pencil') ?>" style="font-size:14px;color:#64748b;margin-top:2px;"></i>
                        <div style="flex:1;">
                          <p style="font-family:'Inter',sans-serif;font-size:11px;color:#0f172a;margin:0;line-height:1.4;"><?= e($notif['message']) ?></p>
                          <p style="font-family:'Inter',sans-serif;font-size:10px;color:#94a3b8;margin:4px 0 0 0;"><?= e($notif['post_title'] ?? 'Unknown Post') ?></p>
                          <p style="font-family:'Inter',sans-serif;font-size:9px;color:#cbd5e1;margin:2px 0 0 0;"><?= date('M j, Y g:i A', strtotime($notif['event_date'])) ?></p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <p style="font-family:'Inter',sans-serif;font-size:13px;color:#94a3b8;margin:0;">No notifications</p>
            <?php endif; ?>
          </div>
        </div>
      </nav>

      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="pt-3 mb-3">
          <a class="text-decoration-none" href="<?= e(BASE_URL) ?>/student/feed.php" style="font-family:'Inter',sans-serif;color:#2E5FA8;font-size:14px;">
            <i class="bi bi-arrow-left me-1"></i>Back to feed
          </a>
        </div>

        <div class="card border-0 shadow-sm">
          <?php if ($isHidden): ?>
            <div class="alert alert-warning m-3" style="font-size:14px;">
              <i class="bi bi-eye-slash me-2"></i>
              <strong>This post is currently hidden</strong> - It's not visible to other students, but you can still access it because you have it saved or liked.
            </div>
          <?php endif; ?>
          <img src="<?= e($post['thumbnail_url']) ?>" class="card-img-top" alt="<?= e($post['title']) ?>" style="height:360px;object-fit:cover;">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <h1 style="font-family:'Inter',sans-serif;font-size:28px;font-weight:700;color:#0f172a;margin-bottom:8px;">
                  <?= e($post['title']) ?>
                  <?php if (!empty($post['updated_at']) && $post['updated_at'] !== $post['created_at'] && strtotime($post['updated_at']) > strtotime($post['created_at'])): ?>
                    <span style="font-size:14px;color:#94a3b8;font-weight:400;">(Edited)</span>
                  <?php endif; ?>
                </h1>
                <div style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;">
                  <span class="subject-badge"><?= e($post['subject_name']) ?></span>
                  <span class="mx-2">•</span>
                  by <?= e($post['creator_name']) ?>
                  <span class="mx-2">•</span>
                  <?= e(date('M d, Y', strtotime($post['created_at']))) ?>
                  <span class="mx-2">•</span>
                  <span data-like-count="<?= (int)$post['id'] ?>"><?= (int)$post['like_count'] ?></span> likes
                </div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn-icon <?= $isLiked ? 'liked' : '' ?>" data-like-btn="<?= (int)$post['id'] ?>" onclick="toggleLike(<?= (int)$post['id'] ?>)" type="button" title="<?= $isLiked ? 'Liked' : 'Like' ?>">
                  <i class="bi bi-heart<?= $isLiked ? '-fill' : '' ?>"></i>
                </button>
                <button class="btn-icon <?= $isBookmarked ? 'bookmarked' : '' ?>" data-bookmark-btn="<?= (int)$post['id'] ?>" onclick="toggleBookmark(<?= (int)$post['id'] ?>)" type="button" title="<?= $isBookmarked ? 'Bookmarked' : 'Bookmark' ?>">
                  <i class="bi bi-bookmark<?= $isBookmarked ? '-fill' : '' ?>"></i>
                </button>
              </div>
            </div>

            <?php if (!empty($post['description'])): ?>
              <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;line-height:1.6;"><?= nl2br(e($post['description'])) ?></p>
            <?php endif; ?>

            <div class="d-flex gap-2 mt-4">
              <a class="btn btn-primary" target="_blank" href="<?= e($post['pdf_url']) ?>" style="font-family:'Inter',sans-serif;font-size:14px;">
                <i class="bi bi-file-pdf me-2"></i>Open PDF
              </a>
              <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/student/feed.php" style="font-family:'Inter',sans-serif;font-size:14px;">
                <i class="bi bi-arrow-left me-2"></i>Back
              </a>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
          <div class="card-body p-0">
            <iframe src="<?= e($post['pdf_url']) ?>" style="width:100%;height:800px;border:none;" title="PDF Preview"></iframe>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    window.CLARION_BASE_URL = "<?= e(BASE_URL) ?>";
    window.CLARION_CSRF_TOKEN = "<?= e(csrf_token()) ?>";

    function toggleOlderNotifications() {
      const older = document.getElementById('olderNotifications');
      const btn = document.getElementById('toggleOlderBtn');
      if (older.style.display === 'none') {
        older.style.display = 'block';
        btn.innerHTML = '<i class="bi bi-chevron-up" id="toggleIcon"></i> Hide Older Notifications';
      } else {
        older.style.display = 'none';
        btn.innerHTML = '<i class="bi bi-chevron-down" id="toggleIcon"></i> See Older Notifications';
      }
    }

    function removeNotification(btn) {
      const item = btn.closest('.notification-item');
      const notifId = item.dataset.notifId;
      
      localStorage.setItem(`notif_dismissed_${notifId}`, '1');
      
      item.style.opacity = '0';
      item.style.transition = 'opacity 0.3s';
      setTimeout(() => {
        item.remove();
        const container = document.getElementById('notificationsContainer');
        const remaining = container.querySelectorAll('.notification-item');
        if (remaining.length === 0) {
          container.innerHTML = '<p style="font-family:\'Inter\',sans-serif;font-size:13px;color:#94a3b8;margin:0;">No notifications</p>';
        }
      }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.notification-item').forEach(item => {
        const notifId = item.dataset.notifId;
        if (localStorage.getItem(`notif_dismissed_${notifId}`)) {
          item.remove();
        }
      });
      
      const container = document.getElementById('notificationsContainer');
      const remaining = container.querySelectorAll('.notification-item');
      if (remaining.length === 0) {
        container.innerHTML = '<p style="font-family:\'Inter\',sans-serif;font-size:13px;color:#94a3b8;margin:0;">No notifications</p>';
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/main.js"></script>
</body>
</html>
