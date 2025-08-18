<?php
session_start();
require_once '../../includes/db_connect.php';

// Set the header to return JSON
header('Content-Type: application/json');

// 1. Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

// 2. Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// 3. Get and validate input
$userId = $_SESSION['user_id'];
$activity_type = isset($_POST['activity_type']) ? trim($_POST['activity_type']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : ''; // Details are optional
$date = date("Y-m-d H:i:s"); // Use server time for accuracy

if (empty($activity_type)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Activity type cannot be empty.'
    ]);
    exit;
}

// 4. Prepare and execute the SQL query to insert the activity
$sql = "INSERT INTO daily_activities (user_id, activity_type, details, date) VALUES (?, ?, ?, ?)";

if ($stmt = $mysqli->prepare($sql)) {
    // Bind parameters: i for integer, s for string
    $stmt->bind_param("isss", $userId, $activity_type, $details, $date);

    // 5. Check for successful execution and send response
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Activity tracked successfully!'
        ]);
    } else {
        // For debugging: error_log("SQL Error: " . $stmt->error);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error. Could not track activity.'
        ]);
    }

    // 6. Clean up
    $stmt->close();
} else {
    // For debugging: error_log("SQL Prepare Error: " . $mysqli->error);
    echo json_encode([
        'success' => false, 
        'message' => 'Database query preparation failed.'
    ]);
}

$mysqli->close();
?>