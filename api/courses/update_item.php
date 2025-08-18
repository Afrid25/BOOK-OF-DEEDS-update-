<?php
// Handles inline editing of chapter and topic names
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"])) { echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? ''; $id = $data['id'] ?? 0; $value = trim($data['value'] ?? ''); $userId = $_SESSION['user_id'];

if (empty($type) || empty($id) || empty($value)) exit;

$table = ($type === 'chapter') ? 'course_chapters' : 'course_topics';
$column = ($type === 'chapter') ? 'chapter_name' : 'topic_name';

// Security check and Update
$sql = "UPDATE $table t JOIN course_chapters cc ON ($table = 'course_chapters' AND t.id = cc.id) OR ($table = 'course_topics' AND t.chapter_id = cc.id) JOIN courses c ON cc.course_id = c.id SET t.$column = ? WHERE t.id = ? AND c.user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sii", $value, $id, $userId);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or permission denied.']);
}
$stmt->close();
$mysqli->close();
?>