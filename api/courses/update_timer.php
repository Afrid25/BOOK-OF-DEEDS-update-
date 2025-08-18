<?php
// api/courses/update_timer.php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

function exit_json($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit_json(['success' => false, 'message' => 'Not authenticated.']);
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$topicId = $data['topic_id'] ?? 0;
$seconds = $data['seconds'] ?? -1;

if (empty($topicId) || $seconds < 0 || !is_numeric($seconds)) {
    exit_json(['success' => false, 'message' => 'Invalid data provided.']);
}

// Security Check: Verify user owns the topic before updating its timer
$sql_update = "UPDATE course_topics ct
               JOIN course_chapters cc ON ct.chapter_id = cc.id
               JOIN courses c ON cc.course_id = c.id
               SET ct.time_spent_seconds = ?
               WHERE ct.id = ? AND c.user_id = ?";

if ($stmt = $mysqli->prepare($sql_update)) {
    $stmt->bind_param("iii", $seconds, $topicId, $userId);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            exit_json(['success' => true, 'message' => 'Time saved.']);
        } else {
            // This case means the topic wasn't found OR the user doesn't own it.
            exit_json(['success' => false, 'message' => 'Permission denied or topic not found.']);
        }
    } else {
        exit_json(['success' => false, 'message' => 'Database update failed.']);
    }
    $stmt->close();
} else {
    exit_json(['success' => false, 'message' => 'Database error.']);
}
$mysqli->close();
?>