<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
require_role(['EDUCATOR']);
$user = auth_user();

$error = null;
$success = null;

// Handle actions
if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);
    
    if ($action === 'hide') {
        $up = db()->prepare("UPDATE posts SET is_published = 0 WHERE id = ? AND creator_id = ?");
        $up->execute([$postId, $user['id']]);
        $success = "Post hidden successfully.";
    } elseif ($action === 'unhide') {
        $up = db()->prepare("UPDATE posts SET is_published = 1 WHERE id = ? AND creator_id = ?");
        $up->execute([$postId, $user['id']]);
        $success = "Post unhidden successfully.";
    } elseif ($action === 'delete') {
        $del = db()->prepare("DELETE FROM posts WHERE id = ? AND creator_id = ?");
        $del->execute([$postId, $user['id']]);
        $success = "Post deleted successfully.";
    }
}

// Search + pagination
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

$params = [$user['id']];
$where = "WHERE p.creator_id = ?";

if ($q !== '') {
    $where .= " AND (p.title LIKE ? OR p.description LIKE ? OR s.name LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$countSql = "SELECT COUNT(*) AS c FROM posts p JOIN subjects s ON s.id = p.subject_id $where";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)($countStmt->fetch()['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $limit));

$sql = "
  SELECT p.id, p.title, p.description, p.pdf_url, p.thumbnail_url, p.like_count, p.created_at, p.is_published,
         s.name AS subject_name,
         (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id) AS bookmark_count
  FROM posts p
  JOIN subjects s ON s.id = p.subject_id
  $where
  ORDER BY p.created_at DESC
  LIMIT $limit OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Fetch all subjects
$subjectsStmt = db()->query("SELECT id, name FROM subjects ORDER BY name ASC");
$subjects = $subjectsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Posts - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/student/feed.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/toast.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center gap-3">
        <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin:0;">My Posts</h1>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="input-group" style="width:350px;">
          <span class="input-group-text bg-white border-end-0" style="border-color:#e5e7eb;">
            <i class="bi bi-search" style="color:#94a3b8;"></i>
          </span>
          <input type="text" class="form-control border-start-0" name="q" id="searchInput" placeholder="Search posts..." value="<?= e($q) ?>" style="font-family:'Inter',sans-serif;font-size:14px;border-color:#e5e7eb;">
        </div>
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
            <option value="published">Published Only</option>
            <option value="hidden">Hidden Only</option>
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

    <?php if ($error): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($error) ?>', 'error'));</script>
    <?php endif; ?>
    <?php if ($success): ?>
      <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($success) ?>'));</script>
    <?php endif; ?>

    <?php if (!$posts): ?>
      <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size:80px;color:#94a3b8;"></i>
        <p class="mt-3" style="font-family:'Inter',sans-serif;color:#94a3b8;">No posts found</p>
      </div>
    <?php endif; ?>

    <div class="row g-4" id="postsContainer">
      <?php foreach ($posts as $p): ?>
        <div class="col-md-6 col-lg-4" data-date="<?= strtotime($p['created_at']) ?>" data-published="<?= (int)$p['is_published'] ?>">
          <div class="material-card">
            <div class="material-card-thumbnail">
              <img src="<?= e($p['thumbnail_url']) ?>" alt="<?= e($p['title']) ?>">
              <span class="badge position-absolute top-0 end-0 m-2 <?= (int)$p['is_published'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                <?= (int)$p['is_published'] === 1 ? 'Published' : 'Hidden' ?>
              </span>
            </div>
            <div class="material-card-body">
              <h3 class="material-title"><?= e($p['title']) ?></h3>
              <?php if (!empty($p['description'])): ?>
                <p class="material-description"><?= e(mb_strimwidth($p['description'], 0, 120, '...')) ?></p>
              <?php endif; ?>
              <div class="material-meta">
                <span class="subject-badge"><?= e($p['subject_name']) ?></span>
                <div class="material-actions">
                  <span class="btn-icon" title="<?= (int)$p['like_count'] ?> likes">
                    <i class="bi bi-heart-fill"></i> <?= (int)$p['like_count'] ?>
                  </span>
                  <span class="btn-icon" title="<?= (int)$p['bookmark_count'] ?> bookmarks">
                    <i class="bi bi-bookmark-fill"></i> <?= (int)$p['bookmark_count'] ?>
                  </span>
                </div>
              </div>
              <div style="font-family:'Inter',sans-serif;font-size:12px;color:#94a3b8;margin-top:8px;">
                <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($p['created_at'])) ?>
              </div>
              <div class="d-flex gap-2 mt-3">
                <a class="btn btn-sm btn-outline-primary flex-fill" target="_blank" href="<?= e($p['pdf_url']) ?>">
                  <i class="bi bi-file-pdf me-1"></i>View PDF
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.location.href='<?= e(BASE_URL) ?>/educator/edit_post.php?id=<?= (int)$p['id'] ?>'" title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <?php if ((int)$p['is_published'] === 1): ?>
                  <button class="btn btn-sm btn-outline-warning flex-fill" onclick="confirmAction('hide', <?= (int)$p['id'] ?>, 'Are you sure you want to hide this post?')">
                    <i class="bi bi-eye-slash me-1"></i>Hide
                  </button>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-success flex-fill" onclick="confirmAction('unhide', <?= (int)$p['id'] ?>, 'Are you sure you want to unhide this post?')">
                    <i class="bi bi-eye me-1"></i>Unhide
                  </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('delete', <?= (int)$p['id'] ?>, 'Are you sure you want to delete this post? This cannot be undone.')">
                  <i class="bi bi-trash me-1"></i>
                </button>
              </div>
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
  </div>

  <form id="actionForm" method="post" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="post_id" id="actionPostId">
  </form>

  <script>
    function confirmAction(action, postId, message) {
      const modal = document.createElement('div');
      modal.className = 'bookmark-modal';
      
      let icon, title, confirmText, confirmColor;
      if (action === 'delete') {
        icon = 'bi-trash';
        title = 'Delete this post?';
        confirmText = 'Delete';
        confirmColor = '#dc3545';
      } else if (action === 'hide') {
        icon = 'bi-eye-slash';
        title = 'Hide this post?';
        confirmText = 'Hide';
        confirmColor = '#ffc107';
      } else {
        icon = 'bi-eye';
        title = 'Unhide this post?';
        confirmText = 'Unhide';
        confirmColor = '#28a745';
      }
      
      modal.innerHTML = `
        <div class="bookmark-modal-content">
          <i class="bi ${icon}" style="font-size:48px;color:${confirmColor};margin-bottom:16px;"></i>
          <h3 style="font-family:'Inter',sans-serif;font-size:20px;font-weight:600;color:#0f172a;margin-bottom:8px;">${title}</h3>
          <p style="font-family:'Inter',sans-serif;font-size:14px;color:#697483;margin-bottom:24px;">${message}</p>
          <div style="display:flex;gap:12px;">
            <button class="cancel-btn" style="flex:1;padding:10px;border:1px solid #e5e7eb;background:white;color:#697483;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">Cancel</button>
            <button class="confirm-btn" style="flex:1;padding:10px;border:none;background:${confirmColor};color:white;border-radius:8px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;cursor:pointer;">${confirmText}</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      setTimeout(() => modal.classList.add('show'), 10);
      
      modal.querySelector('.cancel-btn').onclick = () => {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
      };
      
      modal.querySelector('.confirm-btn').onclick = () => {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
        document.getElementById('actionType').value = action;
        document.getElementById('actionPostId').value = postId;
        document.getElementById('actionForm').submit();
      };
      
      modal.onclick = (e) => {
        if (e.target === modal) {
          modal.classList.remove('show');
          setTimeout(() => modal.remove(), 300);
        }
      };
    }
  </script>

  <style>
    .bookmark-modal {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .bookmark-modal.show {
      opacity: 1;
    }
    .bookmark-modal-content {
      background: white;
      border-radius: 12px;
      padding: 32px;
      max-width: 400px;
      text-align: center;
      transform: scale(0.9);
      transition: transform 0.3s;
    }
    .bookmark-modal.show .bookmark-modal-content {
      transform: scale(1);
    }
    .list-view .col-md-6 {
      flex: 0 0 100%;
      max-width: 100%;
    }
    .list-view .material-card {
      display: flex;
      flex-direction: row;
      padding: 16px;
    }
    .list-view .material-card-thumbnail {
      width: 120px;
      height: 90px;
      flex-shrink: 0;
      margin-right: 16px;
    }
    .list-view .material-card-body {
      flex: 1;
      padding: 0;
    }
    .list-view .material-title {
      font-size: 16px;
      margin-bottom: 4px;
    }
    .list-view .material-description {
      font-size: 13px;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .list-view .material-meta {
      margin-bottom: 8px;
    }
    .list-view .d-flex.gap-2 {
      gap: 8px !important;
    }
    .list-view .btn-sm {
      padding: 4px 8px;
      font-size: 12px;
    }
    .list-view .badge {
      font-size: 10px;
      padding: 2px 6px;
    }
  </style>

  <script>
    function setView(view) {
      const container = document.getElementById('postsContainer');
      const gridBtn = document.getElementById('gridViewBtn');
      const listBtn = document.getElementById('listViewBtn');
      
      if (view === 'list') {
        container.classList.add('list-view');
        listBtn.style.background = '#2E5FA8';
        listBtn.style.color = 'white';
        gridBtn.style.background = 'white';
        gridBtn.style.color = '#697483';
        localStorage.setItem('myPostsView', 'list');
      } else {
        container.classList.remove('list-view');
        gridBtn.style.background = '#2E5FA8';
        gridBtn.style.color = 'white';
        listBtn.style.background = 'white';
        listBtn.style.color = '#697483';
        localStorage.setItem('myPostsView', 'grid');
      }
    }
    
    // Load saved view preference
    document.addEventListener('DOMContentLoaded', () => {
      const savedView = localStorage.getItem('myPostsView') || 'grid';
      setView(savedView);
    });
    
    // Sort functionality
    const subjectFilter = document.getElementById('subjectFilter');
    subjectFilter.addEventListener('change', function() {
      const selectedSubject = this.value.toLowerCase();
      const cards = document.querySelectorAll('.col-md-6');
      cards.forEach(card => {
        const subject = card.querySelector('.subject-badge')?.textContent.toLowerCase() || '';
        if (selectedSubject === '' || subject === selectedSubject) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
    
    document.getElementById('sortSelect').addEventListener('change', function() {
      const sortValue = this.value;
      const container = document.getElementById('postsContainer');
      const cards = Array.from(container.querySelectorAll('.col-md-6'));
      
      // Filter by published/hidden
      if (sortValue === 'published' || sortValue === 'hidden') {
        const showPublished = sortValue === 'published';
        cards.forEach(card => {
          const isPublished = card.dataset.published === '1';
          card.style.display = (isPublished === showPublished) ? '' : 'none';
        });
        return;
      }
      
      // Show all cards for sorting
      cards.forEach(card => card.style.display = '');
      
      cards.sort((a, b) => {
        const titleA = a.querySelector('.material-title')?.textContent || '';
        const titleB = b.querySelector('.material-title')?.textContent || '';
        const subjectA = a.querySelector('.subject-badge')?.textContent || '';
        const subjectB = b.querySelector('.subject-badge')?.textContent || '';
        
        if (sortValue === 'name-asc') return titleA.localeCompare(titleB);
        if (sortValue === 'name-desc') return titleB.localeCompare(titleA);
        if (sortValue === 'subject-asc') return subjectA.localeCompare(subjectB);
        if (sortValue === 'subject-desc') return subjectB.localeCompare(subjectA);
        if (sortValue === 'date-asc') return a.dataset.date - b.dataset.date;
        return b.dataset.date - a.dataset.date; // date-desc default
      });
      
      cards.forEach(card => container.appendChild(card));
    });
    
    // Live search functionality
    const searchInput = document.getElementById('searchInput');
    const materialCards = document.querySelectorAll('.material-card');
    
    if (searchInput && materialCards.length > 0) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        materialCards.forEach(card => {
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
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/toast.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
