<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in.";
    exit;
}

$userId = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'today';
$sql = "SELECT id, activity_type, details, date FROM daily_activities WHERE user_id = ?";

switch ($filter) {
    case 'weekly':
        $sql .= " AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $sql .= " AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        break;
    case 'all':
        break;
    case 'today':
    default:
        $sql .= " AND DATE(date) = CURDATE()";
        break;
}

$sql .= " ORDER BY date DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $icon_class = 'fa-question-circle';
        switch (strtolower($row['activity_type'])) {
            case 'studied': $icon_class = 'fa-book-reader'; break;
            case 'prayed': $icon_class = 'fa-praying-hands'; break;
            case 'helped a friend': $icon_class = 'fa-user-friends'; break;
            case 'learned something new': $icon_class = 'fa-lightbulb'; break;
            case 'avoided distractions': $icon_class = 'fa-shield-alt'; break;
            case 'exercise': $icon_class = 'fa-dumbbell'; break;
        }

        echo '
        <div class="deed-item-modern" data-id="' . $row['id'] . '">
            <div class="deed-icon-bg"><i class="fas ' . $icon_class . '"></i></div>
            <div class="deed-content-modern">
                <h4>' . htmlspecialchars($row['activity_type']) . '</h4>
                <p>' . htmlspecialchars($row['details']) . '</p>
            </div>
            <div class="deed-meta-modern">
                <span>' . date("d M, Y h:i A", strtotime($row['date'])) . '</span>
                <div class="deed-actions">
                    <button class="action-btn edit-btn"><i class="fas fa-edit"></i></button>
                    <button class="action-btn delete-btn"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        </div>';
    }
} else {
    echo '<p class="no-deeds">No deeds found for this period. Time to add one!</p>';
}
$stmt->close();
$mysqli->close();
?>