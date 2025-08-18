<?php
session_start();
require_once '../../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$chapter_id = intval($_POST['chapter_id']);
$is_completed = intval($_POST['is_completed']);
$completed_at = $is_completed ? date("Y-m-d H:i:s") : NULL;

$sql = "UPDATE chapters SET is_completed = ?, completed_at = ? WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("isi", $is_completed, $completed_at, $chapter_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}
$stmt->close();
$mysqli->close();
?>