<?php
// Handles deleting courses, chapters, and topics
session_start();
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';

if (!isset($_SESSION["loggedin"])) { echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? ''; $id = $data['id'] ?? 0; $userId = $_SESSION['user_id'];

if (empty($type) || empty($id)) exit;

$table = ''; $sql_check_ownership = '';
switch($type) {
    case 'course':
        $table = 'courses';
        $sql_check_ownership = "SELECT id FROM courses WHERE id = ? AND user_id = ?";
        break;
    case 'chapter':
        $table = 'course_chapters';
        $sql_check_ownership = "SELECT cc.id FROM course_chapters cc JOIN courses c ON cc.course_id = c.id WHERE cc.id = ? AND c.user_id = ?";
        break;
    case 'topic':
        $table = 'course_topics';
        $sql_check_ownership = "SELECT ct.id FROM course_topics ct JOIN course_chapters cc ON ct.chapter_id = cc.id JOIN courses c ON cc.course_id = c.id WHERE ct.id = ? AND c.user_id = ?";
        break;
    default: exit;
}

// Security Check & Delete
$stmt_check = $mysqli->prepare($sql_check_ownership);
$stmt_check->bind_param("ii", $id, $userId);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    $stmt_delete = $mysqli->prepare("DELETE FROM $table WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    $stmt_delete->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed or permission denied.']);
}
?>