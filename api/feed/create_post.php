<?php
// api/feed/create_post.php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$content = trim($_POST['post_content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Post content cannot be empty.']);
    exit;
}

$sql = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("is", $userId, $content);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Post created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create post.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
$mysqli->close();
?>