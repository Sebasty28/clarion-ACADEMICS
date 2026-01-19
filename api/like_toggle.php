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

    $check = db()->prepare("SELECT id FROM posts WHERE id = ? AND is_published = 1 LIMIT 1");
    $check->execute([$post_id]);
    if (!$check->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Post not found.']);
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();

    $exists = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND student_id = ? LIMIT 1");
    $exists->execute([$post_id, $user['id']]);
    $likeRow = $exists->fetch();

    $liked = false;

    if ($likeRow) {
        $del = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND student_id = ?");
        $del->execute([$post_id, $user['id']]);
        $liked = false;
    } else {
        $ins = $pdo->prepare("INSERT INTO likes (post_id, student_id) VALUES (?, ?)");
        $ins->execute([$post_id, $user['id']]);
        $liked = true;
    }

    $pdo->commit();

    $stmt = db()->prepare("SELECT like_count FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $updated = $stmt->fetch();

    echo json_encode([
        'ok' => true,
        'liked' => $liked,
        'like_count' => (int)($updated['like_count'] ?? 0)
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
}
