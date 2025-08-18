<?php
session_start();
require_once '../../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$course_id = intval($_POST['course_id']);
$chapter_name = trim($_POST['chapter_name']);

$sql = "INSERT INTO chapters (course_id, chapter_name) VALUES (?, ?)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("is", $course_id, $chapter_name);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'chapter_id' => $mysqli->insert_id, 'chapter_name' => $chapter_name]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add chapter.']);
}
$stmt->close();
$mysqli->close();
?>