<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$userId = $_SESSION['user_id'];
$courseId = $_GET['id'] ?? 0;

if (empty($courseId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID.']);
    exit;
}

$sql = "SELECT course_name, syllabus FROM courses WHERE id = ? AND user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($course = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $course]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
    }
    $stmt->close();
}
$mysqli->close();
?>