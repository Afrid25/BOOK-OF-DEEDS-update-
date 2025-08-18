<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

require_once '../../includes/db_connect.php'; // Adjust path if needed

$userId = $_SESSION['user_id'];
$response = ['labels' => [], 'data' => []];

$sql = "SELECT 
            c.course_name,
            COUNT(ct.id) AS total_topics,
            SUM(CASE WHEN ct.is_completed = 1 THEN 1 ELSE 0 END) AS completed_topics
        FROM courses c
        LEFT JOIN course_chapters cc ON c.id = cc.course_id
        LEFT JOIN course_topics ct ON cc.id = ct.chapter_id
        WHERE c.user_id = ?
        GROUP BY c.id, c.course_name
        ORDER BY c.created_at DESC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['labels'][] = $row['course_name'];
        $response['data'][] = (int) $row['completed_topics'];
    }
    $stmt->close();
}

$mysqli->close();

echo json_encode($response);