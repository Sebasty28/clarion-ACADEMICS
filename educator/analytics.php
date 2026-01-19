<?php
require_once __DIR__ . '/../core/auth.php';
require_role(['SUPER_ADMIN', 'EDUCATOR']);
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics - <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= e(BASE_URL) ?>/logo/clarion_logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/footer.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/responsive.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #F8F9FA; }
    .navbar { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  </style>
</head>
<body style="background:#F8F9FA;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-3 py-md-4 px-3 px-md-0">
  <h1 style="font-family:'Inter',sans-serif;font-size:30px;font-weight:700;color:#0f172a;margin-bottom:24px;">Analytics Overview</h1>

  <!-- Summary Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div style="font-family:'Inter',sans-serif;font-size:13px;color:#697483;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Total Posts</div>
              <div style="font-family:'Inter',sans-serif;font-size:36px;font-weight:700;color:#0f172a;" id="totalPosts">0</div>
            </div>
            <div style="width:56px;height:56px;background:#f0f7ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-file-text" style="font-size:28px;color:#2E5FA8;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div style="font-family:'Inter',sans-serif;font-size:13px;color:#697483;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Total Likes</div>
              <div style="font-family:'Inter',sans-serif;font-size:36px;font-weight:700;color:#0f172a;" id="totalLikes">0</div>
            </div>
            <div style="width:56px;height:56px;background:#fff0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-heart-fill" style="font-size:28px;color:#dc3545;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div style="font-family:'Inter',sans-serif;font-size:13px;color:#697483;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Total Bookmarks</div>
              <div style="font-family:'Inter',sans-serif;font-size:36px;font-weight:700;color:#0f172a;" id="totalBookmarks">0</div>
            </div>
            <div style="width:56px;height:56px;background:#fff8e6;border-radius:12px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-bookmark-fill" style="font-size:28px;color:#ffc107;"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
        <div class="card-body p-4">
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;">Likes by Subject</h2>
          <canvas id="chartBySubject" height="130"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
        <div class="card-body p-4">
          <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;">Engagement Over Time</h2>
          <canvas id="chartOverTime" height="130"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-4">
      <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;">Top Performing Posts</h2>
      <div class="table-responsive">
        <table class="table align-middle" style="font-family:'Inter',sans-serif;">
          <thead style="background:#f8f9fa;">
            <tr>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Title</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Subject</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Likes</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Bookmarks</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Date</th>
            </tr>
          </thead>
          <tbody id="topPostsBody">
            <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mt-4" style="border-radius:12px;" id="topEducatorsCard">
    <div class="card-body p-4">
      <h2 style="font-family:'Inter',sans-serif;font-size:18px;font-weight:600;color:#0f172a;margin-bottom:20px;">Top Performing Educators</h2>
      <div class="table-responsive">
        <table class="table align-middle" style="font-family:'Inter',sans-serif;">
          <thead style="background:#f8f9fa;">
            <tr>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;">Educator</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Total Likes</th>
              <th style="border:none;padding:12px;font-size:13px;font-weight:600;color:#697483;text-align:center;">Total Bookmarks</th>
            </tr>
          </thead>
          <tbody id="topEducatorsBody">
            <tr><td colspan="3" class="text-center text-muted" style="padding:40px;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
  window.CLARION_BASE_URL = "<?= e(BASE_URL) ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let chart1 = null;
let chart2 = null;

async function loadAnalytics() {
  const res = await fetch(`${window.CLARION_BASE_URL}/api/analytics.php`, { credentials: "same-origin" });
  const data = await res.json();

  if (!data.ok) {
    alert(data.error || "Failed to load analytics");
    return;
  }

  document.getElementById("totalPosts").textContent = data.totals.total_posts;
  document.getElementById("totalLikes").textContent = data.totals.total_likes;
  document.getElementById("totalBookmarks").textContent = data.totals.total_bookmarks;

  // Likes by subject
  const subjLabels = data.by_subject.map(x => x.subject);
  const subjLikes  = data.by_subject.map(x => x.likes);

  const ctx1 = document.getElementById("chartBySubject");
  if (chart1) chart1.destroy();
  chart1 = new Chart(ctx1, {
    type: "bar",
    data: {
      labels: subjLabels,
      datasets: [{ label: "Likes", data: subjLikes, backgroundColor: '#2E5FA8', borderRadius: 8 }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
    }
  });

  // Likes over time
  const timeLabels = data.over_time.map(x => x.day);
  const timeLikes  = data.over_time.map(x => x.likes);

  const ctx2 = document.getElementById("chartOverTime");
  if (chart2) chart2.destroy();
  chart2 = new Chart(ctx2, {
    type: "line",
    data: {
      labels: timeLabels,
      datasets: [{ label: "Likes", data: timeLikes, tension: 0.3, borderColor: '#2E5FA8', backgroundColor: 'rgba(46, 95, 168, 0.1)', fill: true }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
    }
  });

  // Top posts table
  const tbody = document.getElementById("topPostsBody");
  tbody.innerHTML = "";

  if (!data.top_posts.length) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No data yet.</td></tr>`;
  } else {
    for (const p of data.top_posts) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td style="padding:12px;font-size:14px;color:#0f172a;font-weight:500;">${escapeHtml(p.title)}</td>
        <td style="padding:12px;font-size:14px;color:#697483;">${escapeHtml(p.subject)}</td>
        <td style="padding:12px;text-align:center;"><span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#fff0f0;color:#dc3545;border-radius:12px;font-size:13px;font-weight:600;"><i class="bi bi-heart-fill"></i> ${p.likes}</span></td>
        <td style="padding:12px;text-align:center;"><span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#fff8e6;color:#ffc107;border-radius:12px;font-size:13px;font-weight:600;"><i class="bi bi-bookmark-fill"></i> ${p.bookmarks}</span></td>
        <td style="padding:12px;font-size:14px;color:#697483;">${new Date(p.created_at).toLocaleDateString()}</td>
      `;
      tbody.appendChild(tr);
    }
  }

  // Top educators table (admin only)
  if (data.top_educators && data.top_educators.length > 0) {
    const educatorsBody = document.getElementById("topEducatorsBody");
    educatorsBody.innerHTML = "";
    
    for (const e of data.top_educators) {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td style="padding:12px;font-size:14px;color:#0f172a;font-weight:500;">${escapeHtml(e.name)}</td>
        <td style="padding:12px;text-align:center;"><span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#fff0f0;color:#dc3545;border-radius:12px;font-size:13px;font-weight:600;"><i class="bi bi-heart-fill"></i> ${e.total_likes}</span></td>
        <td style="padding:12px;text-align:center;"><span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#fff8e6;color:#ffc107;border-radius:12px;font-size:13px;font-weight:600;"><i class="bi bi-bookmark-fill"></i> ${e.total_bookmarks}</span></td>
      `;
      educatorsBody.appendChild(tr);
    }
  } else {
    // Hide educators card if not admin
    const educatorsCard = document.getElementById('topEducatorsCard');
    if (educatorsCard) educatorsCard.style.display = 'none';
  }
}

function escapeHtml(str) {
  return String(str)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

loadAnalytics();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
