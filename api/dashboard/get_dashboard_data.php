<?php
// api/dashboard/get_dashboard_data.php

session_start();
header('Content-Type: application/json');

// --- 1. Security & Setup ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../includes/db_connect.php'; // Adjust path if needed
require_once '../../dashboard_logic.php'; // We need getLevelInfo()

$user_id = $_SESSION['user_id'];
$response = ['success' => true, 'data' => []];

try {
    // --- 2. Fetch All Stats with Correct Logic ---

    // Stat 1: Today's Progress (Activity Count)
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM daily_activities WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $response['data']['todaysActivityCount'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Stat 2: Chapters Done (Correct Logic)
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as totalCompletedChapters
        FROM (
            SELECT cc.id
            FROM course_chapters cc
            JOIN course_topics ct ON cc.id = ct.chapter_id
            JOIN courses c ON cc.course_id = c.id
            WHERE c.user_id = ? AND c.is_deleted = 0
            GROUP BY cc.id
            HAVING COUNT(ct.id) > 0 AND COUNT(ct.id) = SUM(ct.is_completed)
        ) AS completed_chapters
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $response['data']['totalCompletedChapters'] = $stmt->get_result()->fetch_assoc()['totalCompletedChapters'] ?? 0;
    $stmt->close();

    // Stat 3: Total Courses
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM courses WHERE user_id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $response['data']['totalCourses'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Stat 4: Weekly Score (Using new points_earned column)
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(points_earned), 0) as score 
        FROM daily_activities 
        WHERE user_id = ? AND created_at >= CURDATE() - INTERVAL 7 DAY
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $response['data']['weeklyScore'] = $stmt->get_result()->fetch_assoc()['score'] ?? 0;
    $stmt->close();

    // Data for Goal Progress Chart
    $user_stmt = $mysqli->prepare("SELECT points FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_points = $user_stmt->get_result()->fetch_assoc()['points'] ?? 0;
    $user_stmt->close();

    $level_info = getLevelInfo($user_points);
    $next_level_start = $level_info['next_level_start'];
    $current_level_start = $level_info['current_level_start'];
    
    $goal_completed = 0;
    $goal_remaining = 1; // Default to avoid division by zero
    
    if ($next_level_start > $current_level_start) {
        $points_in_level = $user_points - $current_level_start;
        $points_for_level = $next_level_start - $current_level_start;
        $goal_completed = $points_in_level;
        $goal_remaining = $points_for_level - $points_in_level;
    } else { // Max level reached
        $goal_completed = 1;
        $goal_remaining = 0;
    }

    $response['data']['goalProgress'] = [
        'completed' => $goal_completed,
        'remaining' => $goal_remaining,
    ];

    // Data for Recent Activities
    $stmt = $mysqli->prepare("
        SELECT activity_type, details, created_at 
        FROM daily_activities 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $activities_res = $stmt->get_result();
    $recent_activities = [];
    while($row = $activities_res->fetch_assoc()){
        $recent_activities[] = [
            'type' => htmlspecialchars($row['activity_type']),
            'details' => htmlspecialchars($row['details']),
            'time' => date('d M, h:i A', strtotime($row['created_at']))
        ];
    }
    $response['data']['recentActivities'] = $recent_activities;
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>