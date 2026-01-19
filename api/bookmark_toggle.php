<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';
header('Content-Type: application/json');

try {
    require_role(['STUDENT']);
    if (!is_post()) {
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
        exit;
    }

    require_csrf();

    $user = auth_user();
    $post_id = (int)($_POST['post_id'] ?? 0);
    if ($post_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Invalid post.']);
        exit;
    }

    // Check if post exists (allow bookmarking/unbookmarking even if deleted)
    $check = db()->prepare("SELECT id FROM posts WHERE id = ? LIMIT 1");
    $check->execute([$post_id]);
    $postExists = (bool)$check->fetch();

    $pdo = db();
    $pdo->beginTransaction();

    $exists = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND student_id = ? LIMIT 1");
    $exists->execute([$post_id, $user['id']]);
    $bookmarkRow = $exists->fetch();

    $bookmarked = false;

    if ($bookmarkRow) {
        // Always allow removing bookmark
        $del = $pdo->prepare("DELETE FROM bookmarks WHERE post_id = ? AND student_id = ?");
        $del->execute([$post_id, $user['id']]);
        $bookmarked = false;
    } else {
        // Only allow adding bookmark if post exists and is published
        if (!$postExists) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'Post not found.']);
            exit;
        }
        
        $checkPublished = db()->prepare("SELECT id FROM posts WHERE id = ? AND is_published = 1 LIMIT 1");
        $checkPublished->execute([$post_id]);
        if (!$checkPublished->fetch()) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'error' => 'Post not available.']);
            exit;
        }
        
        $ins = $pdo->prepare("INSERT INTO bookmarks (post_id, student_id) VALUES (?, ?)");
        $ins->execute([$post_id, $user['id']]);
        $bookmarked = true;
    }

    $pdo->commit();

    $countStmt = db()->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE student_id = ?");
    $countStmt->execute([$user['id']]);
    $bookmarkCount = (int)($countStmt->fetch()['count'] ?? 0);

    echo json_encode([
        'ok' => true,
        'bookmarked' => $bookmarked,
        'bookmark_count' => $bookmarkCount
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
}
