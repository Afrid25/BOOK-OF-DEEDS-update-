<?php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { exit; }

$userId = $_SESSION['user_id'];
$topicId = $_POST['topic_id'] ?? 0;
$topicName = trim($_POST['topic_name'] ?? '');
$timeSpent = $_POST['time_spent'] ?? 0;

if (empty($topicId) || empty($topicName)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

// Security: Verify user owns the topic before updating
$sql = "UPDATE course_topics ct
        JOIN course_chapters cc ON ct.chapter_id = cc.id
        JOIN courses c ON cc.course_id = c.id
        SET ct.topic_name = ?, ct.time_spent = ?
        WHERE ct.id = ? AND c.user_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("siii", $topicName, $timeSpent, $topicId, $userId);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed or permission denied.']);
    }
    $stmt->close();
}
$mysqli->close();
?>