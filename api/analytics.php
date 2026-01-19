<?php
require_once __DIR__ . '/../core/auth.php';
header('Content-Type: application/json');

try {
    // Allow SUPER_ADMIN and EDUCATOR to view analytics
    require_role(['SUPER_ADMIN', 'EDUCATOR']);
    $user = auth_user();

    // If educator, show only their posts analytics
    $onlyMine = ($user['role'] === 'EDUCATOR');

    // Totals: posts + likes + bookmarks
    if ($onlyMine) {
        $totalsStmt = db()->prepare("
            SELECT
              COUNT(*) AS total_posts,
              COALESCE(SUM(like_count), 0) AS total_likes,
              (SELECT COUNT(*) FROM bookmarks b JOIN posts p2 ON p2.id = b.post_id WHERE p2.creator_id = ?) AS total_bookmarks
            FROM posts
            WHERE creator_id = ? AND is_published = 1
        ");
        $totalsStmt->execute([$user['id'], $user['id']]);
    } else {
        $totalsStmt = db()->query("
            SELECT
              COUNT(*) AS total_posts,
              COALESCE(SUM(like_count), 0) AS total_likes,
              (SELECT COUNT(*) FROM bookmarks) AS total_bookmarks
            FROM posts
            WHERE is_published = 1
        ");
    }
    $totals = $totalsStmt->fetch();

    // Likes by subject
    if ($onlyMine) {
        $bySubjectStmt = db()->prepare("
            SELECT s.name AS subject, COALESCE(SUM(p.like_count),0) AS likes
            FROM subjects s
            LEFT JOIN posts p ON p.subject_id = s.id AND p.is_published = 1 AND p.creator_id = ?
            GROUP BY s.id
            ORDER BY likes DESC, subject ASC
        ");
        $bySubjectStmt->execute([$user['id']]);
    } else {
        $bySubjectStmt = db()->query("
            SELECT s.name AS subject, COALESCE(SUM(p.like_count),0) AS likes
            FROM subjects s
            LEFT JOIN posts p ON p.subject_id = s.id AND p.is_published = 1
            GROUP BY s.id
            ORDER BY likes DESC, subject ASC
        ");
    }
    $bySubject = $bySubjectStmt->fetchAll();

    // Likes over time (daily) based on likes.created_at
    // (This shows daily engagement, not cumulative.)
    if ($onlyMine) {
        $overTimeStmt = db()->prepare("
            SELECT DATE(l.created_at) AS day, COUNT(*) AS likes
            FROM likes l
            JOIN posts p ON p.id = l.post_id
            WHERE p.creator_id = ? AND p.is_published = 1
            GROUP BY DATE(l.created_at)
            ORDER BY day ASC
            LIMIT 90
        ");
        $overTimeStmt->execute([$user['id']]);
    } else {
        $overTimeStmt = db()->query("
            SELECT DATE(created_at) AS day, COUNT(*) AS likes
            FROM likes
            GROUP BY DATE(created_at)
            ORDER BY day ASC
            LIMIT 90
        ");
    }
    $overTime = $overTimeStmt->fetchAll();

    // Top posts with bookmarks
    if ($onlyMine) {
        $topStmt = db()->prepare("
            SELECT p.id, p.title, p.like_count, s.name AS subject, p.created_at,
                   (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id) AS bookmark_count
            FROM posts p
            JOIN subjects s ON s.id = p.subject_id
            WHERE p.creator_id = ? AND p.is_published = 1
            ORDER BY p.like_count DESC, p.created_at DESC
            LIMIT 10
        ");
        $topStmt->execute([$user['id']]);
    } else {
        $topStmt = db()->query("
            SELECT p.id, p.title, p.like_count, s.name AS subject, p.created_at,
                   (SELECT COUNT(*) FROM bookmarks WHERE post_id = p.id) AS bookmark_count
            FROM posts p
            JOIN subjects s ON s.id = p.subject_id
            WHERE p.is_published = 1
            ORDER BY p.like_count DESC, p.created_at DESC
            LIMIT 10
        ");
    }
    $topPosts = $topStmt->fetchAll();

    // Top educators by likes and bookmarks (admin only)
    $topEducators = [];
    if (!$onlyMine) {
        $topEducatorsStmt = db()->query("
            SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name,
                   COALESCE(SUM(p.like_count), 0) AS total_likes,
                   (SELECT COUNT(*) FROM bookmarks b JOIN posts p2 ON p2.id = b.post_id WHERE p2.creator_id = u.id) AS total_bookmarks
            FROM users u
            LEFT JOIN posts p ON p.creator_id = u.id AND p.is_published = 1
            WHERE u.role = 'EDUCATOR'
            GROUP BY u.id
            ORDER BY total_likes DESC, total_bookmarks DESC
            LIMIT 10
        ");
        $topEducators = $topEducatorsStmt->fetchAll();
    }

    echo json_encode([
        'ok' => true,
        'totals' => [
            'total_posts' => (int)($totals['total_posts'] ?? 0),
            'total_likes' => (int)($totals['total_likes'] ?? 0),
            'total_bookmarks' => (int)($totals['total_bookmarks'] ?? 0),
        ],
        'by_subject' => array_map(fn($r) => [
            'subject' => $r['subject'],
            'likes'   => (int)$r['likes'],
        ], $bySubject),
        'over_time' => array_map(fn($r) => [
            'day'   => $r['day'],
            'likes' => (int)$r['likes'],
        ], $overTime),
        'top_posts' => array_map(fn($r) => [
            'id'    => (int)$r['id'],
            'title' => $r['title'],
            'likes' => (int)$r['like_count'],
            'bookmarks' => (int)$r['bookmark_count'],
            'subject' => $r['subject'],
            'created_at' => $r['created_at'],
        ], $topPosts),
        'top_educators' => array_map(fn($r) => [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'total_likes' => (int)$r['total_likes'],
            'total_bookmarks' => (int)$r['total_bookmarks'],
        ], $topEducators),
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
}
