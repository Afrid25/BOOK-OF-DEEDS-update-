<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$userId = $_SESSION['user_id'];
$topicId = $_POST['topic_id'] ?? 0;
$isCompleted = $_POST['is_completed'] ?? 0;

if (empty($topicId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid topic ID.']);
    exit;
}

// Security: Verify the topic belongs to the logged-in user before updating
$sql = "UPDATE course_topics ct
        JOIN course_chapters cc ON ct.chapter_id = cc.id
        JOIN courses c ON cc.course_id = c.id
        SET ct.is_completed = ?, ct.completed_at = ?
        WHERE ct.id = ? AND c.user_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $completedAt = $isCompleted ? date("Y-m-d H:i:s") : null;
    $stmt->bind_param("isii", $isCompleted, $completedAt, $topicId, $userId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Status updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Permission denied or topic not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    $stmt->close();
}
$mysqli->close();
?>