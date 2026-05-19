<?php
session_start();
require_once '../../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$labels = [];
$data = [];

// Create a list of the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $labels[] = date("D, M j", strtotime($date));
    $data[$date] = 0; // Initialize with 0
}

// Fetch deeds from the last 7 days
$sql = "SELECT DATE(date) as activity_date, COUNT(id) as count 
        FROM daily_activities 
        WHERE user_id = ? AND date >= CURDATE() - INTERVAL 6 DAY
        GROUP BY DATE(date)";
        
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $data[$row['activity_date']] = $row['count'];
}

echo json_encode(['success' => true, 'labels' => $labels, 'data' => array_values($data)]);
$stmt->close();
$mysqli->close();
?>