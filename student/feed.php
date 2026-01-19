<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_role(['STUDENT']);
$user = auth_user();

// Fetch latest posts (published)

require_once __DIR__ . '/../core/security.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

$params = [];
$where = "WHERE p.is_published = 1";

if ($q !== '') {
    $where .= " AND (p.title LIKE ? OR p.description LIKE ? OR s.name LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like];
}

// Count total
$countSql = "
  SELECT COUNT(*) AS c
  FROM posts p
  JOIN subjects s ON s.id = p.subject_id
  $where
";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)($countStmt->fetch()['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $limit));

// Fetch page
$sql = "
  SELECT p.id, p.title, p.description, p.thumbnail_url, p.like_count, p.created_at, p.updated_at,
         s.name AS subject_name,
         CONCAT(u.first_name,' ',u.last_name) AS creator_name
  FROM posts p
  JOIN subjects s ON s.id = p.subject_id
  JOIN users u ON u.id = p.creator_id
  $where
  ORDER BY p.created_at DESC
  LIMIT $limit OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get which posts this student already liked
$likedMap = [];
$likesStmt = db()->prepare("SELECT post_id FROM likes WHERE student_id = ?");
$likesStmt->execute([$user['id']]);
foreach ($likesStmt->fetchAll() as $row) {
    $likedMap[(int)$row['post_id']] = true;
}

// Get which posts this student already bookmarked
$bookmarkedMap = [];
$bookmarksStmt = db()->prepare("SELECT post_id FROM bookmarks WHERE student_id = ?");
$bookmarksStmt->execute([$user['id']]);
foreach ($bookmarksStmt->fetchAll() as $row) {
    $bookmarkedMap[(int)$row['post_id']] = true;
}

// Get total bookmark count
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

// Fetch all subjects
$subjectsStmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
$subjects = $subjectsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clarion - Student Portal</title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/student/feed.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="position:sticky;top:0;z-index:1000;">
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

      <main class="col-12 col-md-9 ms-sm-auto col-lg-10 px-3 px-md-4">
        <?php 
        $flash_error = get_flash('error');
        $flash_success = get_flash('success');
        if ($flash_error): ?>
          <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_error) ?>', 'error'));</script>
        <?php endif; ?>
        <?php if ($flash_success): ?>
          <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($flash_success) ?>'));</script>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
          <div class="d-flex align-items-center gap-3">
            <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin:0;">Learning Materials</h1>
            <div class="input-group" style="width:350px;">
              <span class="input-group-text bg-white border-end-0" style="border-color:#e5e7eb;">
                <i class="bi bi-search" style="color:#94a3b8;"></i>
              </span>
              <input type="text" class="form-control border-start-0" name="q" id="searchInput" placeholder="Search posts..." value="<?= e($q) ?>" style="font-family:'Inter',sans-serif;font-size:14px;border-color:#e5e7eb;">
            </div>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-funnel" style="color:#697483;font-size:18px;"></i>
              <select class="form-select form-select-sm" id="subjectFilter" style="width:auto;font-family:'Inter',sans-serif;border-color:#e5e7eb;padding:6px 32px 6px 12px;">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $subj): ?>
                  <option value="<?= e($subj['name']) ?>"><?= e($subj['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-sort-down" style="color:#697483;font-size:18px;"></i>
              <select class="form-select form-select-sm" id="sortSelect" style="width:auto;font-family:'Inter',sans-serif;border-color:#e5e7eb;padding:6px 32px 6px 12px;">
                <option value="date-desc">Newest First</option>
                <option value="date-asc">Oldest First</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="subject-asc">Subject (A-Z)</option>
              </select>
            </div>
            <div class="btn-group" role="group" style="box-shadow:0 1px 3px rgba(0,0,0,0.1);border-radius:8px;">
              <button type="button" class="btn btn-sm" id="gridViewBtn" onclick="setView('grid')" style="border:1px solid #e5e7eb;border-radius:8px 0 0 8px;padding:8px 16px;background:white;color:#697483;transition:all 0.2s;">
                <i class="bi bi-grid-3x3-gap"></i>
              </button>
              <button type="button" class="btn btn-sm" id="listViewBtn" onclick="setView('list')" style="border:1px solid #e5e7eb;border-left:none;border-radius:0 8px 8px 0;padding:8px 16px;background:white;color:#697483;transition:all 0.2s;">
                <i class="bi bi-list-ul"></i>
              </button>
            </div>
          </div>
        </div>

        <?php if (!$posts): ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size:80px;color:#94a3b8;"></i>
            <p class="mt-3" style="font-family:'Inter',sans-serif;color:#94a3b8;">No materials found</p>
          </div>
        <?php endif; ?>

        <div class="row g-4" id="postsContainer">
          <?php foreach ($posts as $p): 
            $postId = (int)$p['id'];
            $isLiked = !empty($likedMap[$postId]);
            $isBookmarked = !empty($bookmarkedMap[$postId]);
            $isEdited = !empty($p['updated_at']) && $p['updated_at'] !== $p['created_at'] && strtotime($p['updated_at']) > strtotime($p['created_at']);
          ?>
            <div class="col-md-6 col-lg-4">
              <div class="material-card">
                <div class="material-card-thumbnail">
                  <img src="<?= e($p['thumbnail_url']) ?>" alt="<?= e($p['title']) ?>">
                </div>
                <div class="material-card-body">
                  <h3 class="material-title">
                    <?= e($p['title']) ?>
                    <?php if ($isEdited): ?>
                      <span style="font-size:11px;color:#94a3b8;font-weight:400;">(Edited)</span>
                    <?php endif; ?>
                  </h3>
                  <?php if (!empty($p['description'])): ?>
                    <p class="material-description"><?= e(mb_strimwidth($p['description'], 0, 120, '...')) ?></p>
                  <?php endif; ?>
                  <div class="material-meta">
                    <span class="subject-badge"><?= e($p['subject_name']) ?></span>
                    <div class="material-actions">
                      <button class="btn-icon <?= $isLiked ? 'liked' : '' ?>" data-like-btn="<?= $postId ?>" onclick="toggleLike(<?= $postId ?>)" type="button" title="<?= $isLiked ? 'Liked' : 'Like' ?>">
                        <i class="bi bi-heart<?= $isLiked ? '-fill' : '' ?>"></i>
                      </button>
                      <button class="btn-icon <?= $isBookmarked ? 'bookmarked' : '' ?>" data-bookmark-btn="<?= $postId ?>" onclick="toggleBookmark(<?= $postId ?>)" type="button" title="<?= $isBookmarked ? 'Bookmarked' : 'Bookmark' ?>">
                        <i class="bi bi-bookmark<?= $isBookmarked ? '-fill' : '' ?>"></i>
                      </button>
                      <a class="btn-icon" href="<?= e(BASE_URL) ?>/student/post.php?id=<?= $postId ?>" title="View">
                        <i class="bi bi-eye"></i>
                      </a>
                    </div>
                  </div>
                  <p style="font-family:'Inter',sans-serif;font-size:11px;color:#94a3b8;margin:8px 0 0 0;"><?= date('M j, Y', strtotime($p['created_at'])) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="mt-4">
            <ul class="pagination justify-content-center">
              <?php
              $prev = max(1, $page - 1);
              $next = min($totalPages, $page + 1);
              $qs = $q !== '' ? '&q=' . urlencode($q) : '';
              ?>
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $prev ?><?= $qs ?>">Prev</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span>
              </li>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $next ?><?= $qs ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <style>
    .list-view .col-md-6 { flex: 0 0 100%; max-width: 100%; }
    .list-view .material-card { display: flex; flex-direction: row; padding: 16px; }
    .list-view .material-card-thumbnail { width: 120px; height: 90px; flex-shrink: 0; margin-right: 16px; }
    .list-view .material-card-thumbnail img { width: 100%; height: 100%; }
    .list-view .material-card-body { flex: 1; }
    .list-view .material-title { font-size: 16px; margin-bottom: 6px; }
    .list-view .material-description { font-size: 13px; margin-bottom: 8px; }
    .list-view .material-meta { margin-top: 8px; }
  </style>
  <script>
    window.CLARION_BASE_URL = "<?= e(BASE_URL) ?>";
    window.CLARION_CSRF_TOKEN = "<?= e(csrf_token()) ?>";

    let currentView = localStorage.getItem('studentViewMode') || 'grid';
    const container = document.getElementById('postsContainer');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    const sortSelect = document.getElementById('sortSelect');
    const searchInput = document.getElementById('searchInput');

    function setView(view) {
      currentView = view;
      localStorage.setItem('studentViewMode', view);
      if (view === 'list') {
        container.classList.add('list-view');
        listBtn.style.background = '#2E5FA8';
        listBtn.style.color = 'white';
        gridBtn.style.background = 'white';
        gridBtn.style.color = '#697483';
      } else {
        container.classList.remove('list-view');
        gridBtn.style.background = '#2E5FA8';
        gridBtn.style.color = 'white';
        listBtn.style.background = 'white';
        listBtn.style.color = '#697483';
      }
    }

    setView(currentView);

    function sortPosts() {
      const cards = Array.from(container.children);
      const sortValue = sortSelect.value;
      
      cards.sort((a, b) => {
        const titleA = a.querySelector('.material-title')?.textContent || '';
        const titleB = b.querySelector('.material-title')?.textContent || '';
        const subjectA = a.querySelector('.subject-badge')?.textContent || '';
        const subjectB = b.querySelector('.subject-badge')?.textContent || '';
        
        if (sortValue === 'name-asc') return titleA.localeCompare(titleB);
        if (sortValue === 'name-desc') return titleB.localeCompare(titleA);
        if (sortValue === 'subject-asc') return subjectA.localeCompare(subjectB);
        if (sortValue === 'date-asc') return a.dataset.date - b.dataset.date;
        return b.dataset.date - a.dataset.date;
      });
      
      cards.forEach(card => container.appendChild(card));
    }

    sortSelect.addEventListener('change', sortPosts);

    // Add data-date to cards
    document.querySelectorAll('.col-md-6').forEach((col, idx) => {
      col.dataset.date = idx;
    });

    // Subject filter
    const subjectFilter = document.getElementById('subjectFilter');
    subjectFilter.addEventListener('change', function() {
      const selectedSubject = this.value.toLowerCase();
      document.querySelectorAll('.material-card').forEach(card => {
        const subject = card.querySelector('.subject-badge')?.textContent.toLowerCase() || '';
        if (selectedSubject === '' || subject === selectedSubject) {
          card.closest('.col-md-6').style.display = '';
        } else {
          card.closest('.col-md-6').style.display = 'none';
        }
      });
    });

    // Live search
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        document.querySelectorAll('.material-card').forEach(card => {
          const title = card.querySelector('.material-title')?.textContent.toLowerCase() || '';
          const description = card.querySelector('.material-description')?.textContent.toLowerCase() || '';
          const subject = card.querySelector('.subject-badge')?.textContent.toLowerCase() || '';
          
          if (query === '' || title.includes(query) || description.includes(query) || subject.includes(query)) {
            card.closest('.col-md-6').style.display = '';
            visibleCount++;
          } else {
            card.closest('.col-md-6').style.display = 'none';
          }
        });
      });
    }

    function toggleOlderNotifications() {
      const older = document.getElementById('olderNotifications');
      const btn = document.getElementById('toggleOlderBtn');
      const icon = document.getElementById('toggleIcon');
      if (older.style.display === 'none') {
        older.style.display = 'block';
        icon.className = 'bi bi-chevron-up';
        btn.innerHTML = '<i class="bi bi-chevron-up" id="toggleIcon"></i> Hide Older Notifications';
      } else {
        older.style.display = 'none';
        icon.className = 'bi bi-chevron-down';
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
  <script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/main.js"></script>
</body>
</html>
