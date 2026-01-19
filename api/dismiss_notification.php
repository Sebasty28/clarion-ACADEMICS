<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_login();
$user = auth_user();

$data = json_decode(file_get_contents('php://input'), true);
$postId = (int)($data['post_id'] ?? 0);
$type = $data['type'] ?? '';

if (!$postId || !in_array($type, ['deleted', 'hidden', 'edited'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Store dismissed notification in localStorage key format
$key = "dismissed_{$type}_{$postId}";

// For server-side persistence, we'll use a simple approach:
// Delete the bookmark/like if it's a deleted post notification
if ($type === 'deleted') {
    $stmt = db()->prepare("DELETE FROM bookmarks WHERE student_id = ? AND post_id = ?");
    $stmt->execute([$user['id'], $postId]);
    
    $stmt = db()->prepare("DELETE FROM likes WHERE student_id = ? AND post_id = ?");
    $stmt->execute([$user['id'], $postId]);
}

echo json_encode(['success' => true]);
