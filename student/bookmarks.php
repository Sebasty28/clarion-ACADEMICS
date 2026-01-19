<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_role(['STUDENT']);
$user = auth_user();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Count total bookmarks (including deleted posts)
$countStmt = db()->prepare("SELECT COUNT(*) as c FROM bookmarks WHERE student_id = ?");
$countStmt->execute([$user['id']]);
$totalRows = (int)($countStmt->fetch()['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $limit));

// Get total liked count
$likedCountStmt = db()->prepare("SELECT COUNT(*) as count FROM likes WHERE student_id = ?");
$likedCountStmt->execute([$user['id']]);
$likedCount = (int)($likedCountStmt->fetch()['count'] ?? 0);

// Fetch all subjects
$subjectsStmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
$subjects = $subjectsStmt->fetchAll();

// Get notifications for bookmarks page
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
    SELECT 'edited' as type, b.post_id, p.updated_at as event_date, 'Post edited in saved items' as message
    FROM bookmarks b
    JOIN posts p ON p.id = b.post_id
    WHERE b.student_id = ? AND p.updated_at IS NOT NULL AND p.updated_at != p.created_at AND p.is_published = 1
  ) n
  LEFT JOIN posts p ON p.id = n.post_id
  ORDER BY n.event_date DESC
  LIMIT 10
");
$notificationsStmt->execute([$user['id'], $user['id'], $user['id']]);
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
$hiddenStmt = db()->prepare("
  SELECT COUNT(*) as c 
  FROM bookmarks b 
  JOIN posts p ON p.id = b.post_id 
  WHERE b.student_id = ? AND p.is_published = 0
");
$hiddenStmt->execute([$user['id']]);
$hiddenCount = (int)($hiddenStmt->fetch()['c'] ?? 0);

// Count liked posts with deleted posts
$likedDeletedStmt = db()->prepare("
  SELECT COUNT(*) as c 
  FROM likes l 
  LEFT JOIN posts p ON p.id = l.post_id 
  WHERE l.student_id = ? AND (p.id IS NULL OR p.is_published = 0)
");
$likedDeletedStmt->execute([$user['id']]);
$likedDeletedCount = (int)($likedDeletedStmt->fetch()['c'] ?? 0);

// Fetch bookmarked posts (including deleted)
$sql = "
  SELECT b.post_id, b.created_at AS bookmarked_at,
         p.id, p.title, p.description, p.thumbnail_url, p.like_count, p.created_at, p.updated_at, p.is_published,
         s.name AS subject_name,
         CONCAT(u.first_name,' ',u.last_name) AS creator_name
  FROM bookmarks b
  LEFT JOIN posts p ON p.id = b.post_id
  LEFT JOIN subjects s ON s.id = p.subject_id
  LEFT JOIN users u ON u.id = p.creator_id
  WHERE b.student_id = ?
  ORDER BY b.created_at DESC
  LIMIT $limit OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute([$user['id']]);
$posts = $stmt->fetchAll();

// Get liked posts
$likedMap = [];
$likesStmt = db()->prepare("SELECT post_id FROM likes WHERE student_id = ?");
$likesStmt->execute([$user['id']]);
foreach ($likesStmt->fetchAll() as $row) {
    $likedMap[(int)$row['post_id']] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Items - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/student/feed.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
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

  <div class="container-fluid">
    <div class="row">
      <nav class="col-md-3 col-lg-2 sidebar d-none d-md-block">
        <div class="position-sticky pt-3">
          <h6 class="sidebar-heading px-3 mb-3 text-muted">
            <span>MY LIBRARY</span>
          </h6>
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link active" href="<?= e(BASE_URL) ?>/student/bookmarks.php">
                <i class="bi bi-bookmark-star me-2"></i>
                Saved Items
                <span class="badge bg-primary rounded-pill ms-auto" id="bookmarkCount"><?= $totalRows ?></span>
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
        <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
          <div class="d-flex align-items-center gap-3">
            <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin:0;">Saved Items</h1>
            <div class="input-group" style="width:350px;">
              <span class="input-group-text bg-white border-end-0" style="border-color:#e5e7eb;">
                <i class="bi bi-search" style="color:#94a3b8;"></i>
              </span>
              <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search posts..." style="font-family:'Inter',sans-serif;font-size:14px;border-color:#e5e7eb;">
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
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
            <button type="button" class="btn btn-sm" id="filterBtn" onclick="toggleFilter()" style="border:1px solid #e5e7eb;border-radius:8px;padding:8px 16px;background:white;color:#697483;font-family:'Inter',sans-serif;font-size:14px;">
              <i class="bi bi-funnel"></i> Hide Issues
            </button>
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
            <i class="bi bi-bookmark" style="font-size:80px;color:#94a3b8;"></i>
            <p class="mt-3" style="font-family:'Inter',sans-serif;color:#94a3b8;">No saved items yet</p>
            <a href="<?= e(BASE_URL) ?>/student/feed.php" class="btn btn-primary mt-2">Browse Materials</a>
          </div>
        <?php endif; ?>

        <div class="row g-4" id="postsContainer">
          <?php foreach ($posts as $p): 
            $postId = (int)$p['post_id'];
            $isDeleted = empty($p['id']);
            $isHidden = !$isDeleted && (int)$p['is_published'] !== 1;
            $isLiked = !empty($likedMap[$postId]);
            $isEdited = !$isDeleted && !empty($p['updated_at']) && $p['updated_at'] !== $p['created_at'] && strtotime($p['updated_at']) > strtotime($p['created_at']);
          ?>
            <div class="col-md-6 col-lg-4" data-hidden="<?= $isHidden ? '1' : '0' ?>" data-deleted="<?= $isDeleted ? '1' : '0' ?>">
              <div class="material-card <?= $isDeleted ? 'deleted-post' : ($isHidden ? 'hidden-post' : '') ?>">
                <?php if ($isDeleted): ?>
                  <div class="deleted-overlay">
                    <i class="bi bi-trash" style="font-size:48px;color:#dc3545;margin-bottom:12px;"></i>
                    <p style="font-family:'Inter',sans-serif;font-size:14px;font-weight:600;color:#dc3545;margin:0;">Post Deleted</p>
                    <button class="btn btn-sm btn-danger mt-2" data-bookmark-btn="<?= $postId ?>" onclick="toggleBookmark(<?= $postId ?>)" style="font-size:12px;">
                      <i class="bi bi-trash me-1"></i>Remove
                    </button>
                  </div>
                <?php elseif ($isHidden): ?>
                  <div class="deleted-overlay" style="background:rgba(255,193,7,0.95);">
                    <i class="bi bi-eye-slash" style="font-size:48px;color:#856404;margin-bottom:12px;"></i>
                    <p style="font-family:'Inter',sans-serif;font-size:14px;font-weight:600;color:#856404;margin:0;">Post Hidden</p>
                    <p style="font-family:'Inter',sans-serif;font-size:12px;color:#856404;margin-top:4px;">Not visible to others</p>
                  </div>
                <?php endif; ?>
                <div class="material-card-thumbnail">
                  <img src="<?= e($p['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>" alt="<?= e($p['title'] ?? 'Deleted Post') ?>">
                </div>
                <div class="material-card-body">
                  <h3 class="material-title">
                    <?= e($p['title'] ?? 'Deleted Post') ?>
                    <?php if ($isEdited): ?>
                      <span style="font-size:11px;color:#94a3b8;font-weight:400;">(Edited)</span>
                    <?php endif; ?>
                  </h3>
                  <?php if (!empty($p['description'])): ?>
                    <p class="material-description"><?= e(mb_strimwidth($p['description'], 0, 120, '...')) ?></p>
                  <?php endif; ?>
                  <div class="material-meta">
                    <span class="subject-badge"><?= e($p['subject_name'] ?? 'Unknown') ?></span>
                    <?php if (!$isDeleted && !$isHidden): ?>
                    <div class="material-actions">
                      <button class="btn-icon <?= $isLiked ? 'liked' : '' ?>" data-like-btn="<?= $postId ?>" onclick="toggleLike(<?= $postId ?>)" type="button" title="<?= $isLiked ? 'Liked' : 'Like' ?>">
                        <i class="bi bi-heart<?= $isLiked ? '-fill' : '' ?>"></i>
                      </button>
                      <button class="btn-icon bookmarked" data-bookmark-btn="<?= $postId ?>" onclick="toggleBookmark(<?= $postId ?>)" type="button" title="Remove Bookmark">
                        <i class="bi bi-bookmark-fill"></i>
                      </button>
                      <a class="btn-icon" href="<?= e(BASE_URL) ?>/student/post.php?id=<?= $postId ?>" title="View">
                        <i class="bi bi-eye"></i>
                      </a>
                    </div>
                    <?php endif; ?>
                  </div>
                  <?php if (!$isDeleted && !empty($p['created_at'])): ?>
                    <p style="font-family:'Inter',sans-serif;font-size:11px;color:#94a3b8;margin:8px 0 0 0;"><?= date('M j, Y', strtotime($p['created_at'])) ?></p>
                  <?php endif; ?>
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
              ?>
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $prev ?>">Prev</a>
              </li>
              <li class="page-item disabled">
                <span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span>
              </li>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $next ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <style>
    .list-view .col-md-6 { flex: 0 0 100%; max-width: 100%; }
    .list-view .material-card { display: flex; flex-direction: row; padding: 16px; }
    .list-view .material-card-thumbnail { width: 120px; height: 90px; flex-shrink: 0; margin-right: 16px; }
    .list-view .material-card-thumbnail img { width: 100%; height: 100%; }
    .list-view .material-card-body { flex: 1; }
    .list-view .material-title { font-size: 16px; margin-bottom: 6px; }
    .list-view .material-description { font-size: 13px; margin-bottom: 8px; }
    .list-view .material-meta { margin-top: 8px; }
    .hidden-post { position: relative; opacity: 0.7; pointer-events: none; }
    .hidden-post .deleted-overlay { pointer-events: none; }
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
        const col = card.closest('.col-md-6');
        const subject = card.querySelector('.subject-badge')?.textContent.toLowerCase() || '';
        const isHidden = col.dataset.hidden === '1';
        const isDeleted = col.dataset.deleted === '1';
        const shouldHide = filterActive && (isHidden || isDeleted);
        
        if ((selectedSubject === '' || subject === selectedSubject) && !shouldHide) {
          col.style.display = '';
        } else {
          col.style.display = 'none';
        }
      });
    });

    // Live search
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        document.querySelectorAll('.material-card').forEach(card => {
          const col = card.closest('.col-md-6');
          const isHidden = col.dataset.hidden === '1';
          const isDeleted = col.dataset.deleted === '1';
          const title = card.querySelector('.material-title')?.textContent.toLowerCase() || '';
          const description = card.querySelector('.material-description')?.textContent.toLowerCase() || '';
          const subject = card.querySelector('.subject-badge')?.textContent.toLowerCase() || '';
          
          const matchesSearch = query === '' || title.includes(query) || description.includes(query) || subject.includes(query);
          const shouldHide = filterActive && (isHidden || isDeleted);
          
          if (matchesSearch && !shouldHide) {
            col.style.display = '';
          } else {
            col.style.display = 'none';
          }
        });
      });
    }

    // Override toggleBookmark to handle deleted posts dynamically
    const originalToggleBookmark = window.toggleBookmark;
    window.toggleBookmark = async function(postId) {
      const card = document.querySelector(`[data-bookmark-btn="${postId}"]`)?.closest('.col-md-6');
      const isDeleted = card?.querySelector('.deleted-post');
      const bookmarkCountEl = document.getElementById('bookmarkCount');
      const likedCountEl = document.getElementById('likedCount');
      
      await originalToggleBookmark(postId);
      
      // If it was a deleted post and we just removed it, hide the card
      if (isDeleted && card) {
        card.style.display = 'none';
        
        // Update deleted count
        const deletedAlert = document.getElementById('deletedAlert');
        const deletedCountText = document.getElementById('deletedCountText');
        const deletedPlural = document.getElementById('deletedPlural');
        
        if (deletedCountText) {
          const currentCount = parseInt(deletedCountText.textContent);
          const newCount = currentCount - 1;
          
          if (newCount <= 0 && deletedAlert) {
            deletedAlert.style.display = 'none';
          } else {
            deletedCountText.textContent = newCount;
            deletedPlural.textContent = newCount > 1 ? 's' : '';
          }
        }
      }
    };
    
    // Override toggleLike to update liked count
    const originalToggleLike = window.toggleLike;
    window.toggleLike = async function(postId) {
      const btn = document.querySelector(`[data-like-btn="${postId}"]`);
      const wasLiked = btn?.classList.contains('liked');
      const likedCountEl = document.getElementById('likedCount');
      
      await originalToggleLike(postId);
      
      // Update liked count badge
      if (likedCountEl) {
        const newLiked = btn?.classList.contains('liked');
        if (newLiked && !wasLiked) {
          likedCountEl.textContent = parseInt(likedCountEl.textContent || 0) + 1;
        } else if (!newLiked && wasLiked) {
          likedCountEl.textContent = Math.max(0, parseInt(likedCountEl.textContent || 0) - 1);
        }
      }
    };

    let filterActive = false;
    function toggleFilter() {
      filterActive = !filterActive;
      const btn = document.getElementById('filterBtn');
      
      document.querySelectorAll('.col-md-6').forEach(col => {
        const isHidden = col.dataset.hidden === '1';
        const isDeleted = col.dataset.deleted === '1';
        
        if (filterActive && (isHidden || isDeleted)) {
          col.style.display = 'none';
        } else {
          col.style.display = '';
        }
      });
      
      if (filterActive) {
        btn.innerHTML = '<i class="bi bi-funnel-fill"></i> Show All';
        btn.style.background = '#2E5FA8';
        btn.style.color = 'white';
      } else {
        btn.innerHTML = '<i class="bi bi-funnel"></i> Hide Issues';
        btn.style.background = 'white';
        btn.style.color = '#697483';
      }
      
      // Trigger subject filter
      subjectFilter.dispatchEvent(new Event('change'));
      
      // Trigger search to respect filter
      if (searchInput.value) {
        searchInput.dispatchEvent(new Event('input'));
      }
    }

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

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
