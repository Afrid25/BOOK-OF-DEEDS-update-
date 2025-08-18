<?php
session_start();
require_once '../../includes/db_connect.php';
header('Content-Type: application/json');

// Security checks
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$userId = $_SESSION['user_id'];
$deedId = isset($_POST['deed_id']) ? intval($_POST['deed_id']) : 0;

if ($deedId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Deed ID.']);
    exit;
}

// Prepare SQL to delete the deed, ensuring it belongs to the logged-in user
$sql = "DELETE FROM daily_activities WHERE id = ? AND user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $deedId, $userId);

if ($stmt->execute()) {
    // Check if a row was actually deleted
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Deed deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Deed not found or you do not have permission to delete it.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

$stmt->close();
$mysqli->close();
?>