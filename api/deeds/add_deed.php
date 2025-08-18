<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $activity_type = trim($_POST['activity_type']);
    $details = trim($_POST['details']);
    $date = date("Y-m-d H:i:s");

    if (empty($activity_type)) {
        echo json_encode(['success' => false, 'message' => 'Activity type is required.']);
        exit;
    }

    $sql = "INSERT INTO daily_activities (user_id, activity_type, details, date) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("isss", $userId, $activity_type, $details, $date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Deed added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add deed.']);
    }

    $stmt->close();
    $mysqli->close();
}
?>