<?php
// api/leaderboard/get_leaderboard_data.php (or similar)
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // You should probably send a proper error response, but exit works for now.
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

// --- 1. EFFICIENT DATE RANGE CALCULATION (in PHP) ---
// This avoids running functions on SQL columns, allowing the database to use indexes.
$filter = $_GET['filter'] ?? 'weekly';
$date_condition = "";

switch ($filter) {
    case 'monthly':
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        $date_condition_deeds = "AND d.date BETWEEN '$start_date' AND '$end_date'";
        $date_condition_topics = "AND ct.completed_at BETWEEN '$start_date' AND '$end_date'";
        break;
    case 'all_time':
        // No date condition needed, variables remain empty.
        $date_condition_deeds = "";
        $date_condition_topics = "";
        break;
    case 'weekly':
    default:
        // Get the start of the week (Monday) and end of the week (Sunday)
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $date_condition_deeds = "AND d.date BETWEEN '$start_date' AND '$end_date'";
        $date_condition_topics = "AND ct.completed_at BETWEEN '$start_date' AND '$end_date'";
        break;
}

// --- 2. HIGHLY EFFICIENT SQL QUERY (using JOINs and derived tables) ---
// This query runs only 3 fast, indexed operations instead of thousands of slow ones.
$sql = "
    SELECT
        u.id,
        u.user_name,
        (IFNULL(dp.points, 0) + IFNULL(cp.points, 0)) AS total_points
    FROM
        users u
    LEFT JOIN (
        -- Subquery to calculate points from daily activities
        SELECT user_id, (COUNT(*) * 5) AS points
        FROM daily_activities d
        WHERE 1=1 $date_condition_deeds
        GROUP BY user_id
    ) AS dp ON u.id = dp.user_id
    LEFT JOIN (
        -- Subquery to calculate points from completed topics
        -- FIX: Correctly queries course_topics, not the old chapters table
        SELECT c.user_id, (COUNT(*) * 10) AS points
        FROM course_topics ct
        JOIN course_chapters cc ON ct.chapter_id = cc.id
        JOIN courses c ON cc.course_id = c.id
        WHERE ct.is_completed = 1 $date_condition_topics
        GROUP BY c.user_id
    ) AS cp ON u.id = cp.user_id
    HAVING
        total_points > 0
    ORDER BY
        total_points DESC, u.user_name ASC
    LIMIT 100;
";

$result = $mysqli->query($sql);

// --- 3. HTML GENERATION (Largely the same, but with the corrected 'user_name' column) ---
if ($result && $result->num_rows > 0) {
    $rank = 1;
    $top_three_html = '';
    $rest_html = '';

    while ($row = $result->fetch_assoc()) {
        // FIX: Using 'user_name' for consistency with the corrected DB and other scripts.
        $user_initial = mb_strtoupper(mb_substr($row['user_name'], 0, 1, 'UTF-8'));
        $userName = htmlspecialchars($row['user_name']);
        $points = number_format($row['total_points']);

        if ($rank <= 3) {
            $rank_class = ['gold', 'silver', 'bronze'][$rank - 1];
            $rank_icon = ['fa-trophy', 'fa-medal', 'fa-award'][$rank - 1];
            $top_three_html .= "
            <div class='top-rank-card $rank_class'>
                <div class='rank-icon'><i class='fas $rank_icon'></i></div>
                <div class='avatar-placeholder-rank'>$user_initial</div>
                <h4 class='user-name'>$userName</h4>
                <p class='points'>$points Points</p>
            </div>";
        } else {
            $is_current_user = ($row['id'] == $_SESSION['user_id']) ? 'current-user' : '';
            $rest_html .= "
            <div class='leaderboard-item $is_current_user'>
                <div class='rank'>$rank</div>
                <div class='user-info'>
                    <div class='avatar-placeholder-rank small'>$user_initial</div>
                    <span class='user-name'>$userName</span>
                </div>
                <div class='points'>$points Pts</div>
            </div>";
        }
        $rank++;
    }

    // Combine and echo the final HTML
    echo '<div class="top-ranks-container">' . $top_three_html . '</div>';
    if (!empty($rest_html)) {
        echo '<div class="leaderboard-rest-list">' . $rest_html . '</div>';
    }

} else {
    echo '<p class="no-deeds glass-card">The leaderboard is empty. Be the first to score points!</p>';
}

$mysqli->close();
?>