<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../helpers/ai_mentorship_service.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ai_service = new AIMentorshipService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_dashboard_data':
                // Get user level and progress
                $stmt = $pdo->prepare("SELECT * FROM ai_user_levels WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user_level = $stmt->fetch();
                
                // Get recent achievements
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_achievements 
                    WHERE user_id = ? 
                    ORDER BY unlocked_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $recent_achievements = $stmt->fetchAll();
                
                // Get performance summary
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_activities,
                        AVG(score) as average_score,
                        COUNT(CASE WHEN score >= 80 THEN 1 END) as high_scores,
                        COUNT(CASE WHEN score < 60 THEN 1 END) as low_scores
                    FROM ai_performance_tracking 
                    WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $stmt->execute([$user_id]);
                $performance_summary = $stmt->fetch();
                
                // Get study streak
                $stmt = $pdo->prepare("
                    SELECT streak_days, longest_streak, last_study_date 
                    FROM ai_user_levels 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                $streak_info = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'user_level' => $user_level,
                    'recent_achievements' => $recent_achievements,
                    'performance_summary' => $performance_summary,
                    'streak_info' => $streak_info
                ]);
                break;
                
            case 'get_performance_chart':
                $days = (int)($_GET['days'] ?? 30);
                $subject = $_GET['subject'] ?? '';
                
                $sql = "
                    SELECT DATE(completed_at) as date, AVG(score) as avg_score, COUNT(*) as activities
                    FROM ai_performance_tracking 
                    WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ";
                $params = [$user_id, $days];
                
                if (!empty($subject)) {
                    $sql .= " AND subject = ?";
                    $params[] = $subject;
                }
                
                $sql .= " GROUP BY DATE(completed_at) ORDER BY date";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $chart_data = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'chart_data' => $chart_data
                ]);
                break;
                
            case 'get_weaknesses':
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_weakness_analysis 
                    WHERE user_id = ? 
                    ORDER BY severity DESC, last_analyzed DESC
                ");
                $stmt->execute([$user_id]);
                $weaknesses = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'weaknesses' => $weaknesses
                ]);
                break;
                
            case 'get_achievements':
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_achievements 
                    WHERE user_id = ? 
                    ORDER BY unlocked_at DESC
                ");
                $stmt->execute([$user_id]);
                $achievements = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'achievements' => $achievements
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching progress data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching data']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'record_activity':
                $subject = $data['subject'] ?? '';
                $topic = $data['topic'] ?? '';
                $activity_type = $data['activity_type'] ?? 'practice';
                $score = $data['score'] ?? null;
                $time_spent = $data['time_spent'] ?? null;
                $difficulty_level = $data['difficulty_level'] ?? 'intermediate';
                
                if (empty($subject)) {
                    echo json_encode(['success' => false, 'message' => 'Subject is required']);
                    exit;
                }
                
                // Record performance
                $stmt = $pdo->prepare("
                    INSERT INTO ai_performance_tracking 
                    (user_id, subject, topic, activity_type, score, time_spent, difficulty_level)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $subject, $topic, $activity_type, $score, $time_spent, $difficulty_level]);
                
                // Update streak
                $this->updateStreak($user_id);
                
                // Check for achievements
                $new_achievements = $this->checkAchievements($user_id, $score, $activity_type);
                
                // Update XP and level
                $xp_gained = $this->calculateXP($score, $time_spent, $difficulty_level);
                $this->updateXP($user_id, $xp_gained);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Activity recorded successfully',
                    'xp_gained' => $xp_gained,
                    'new_achievements' => $new_achievements
                ]);
                break;
                
            case 'analyze_performance':
                $analysis = $ai_service->analyzePerformance($user_id);
                
                if ($analysis) {
                    echo json_encode([
                        'success' => true,
                        'analysis' => $analysis
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to analyze performance'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error in progress API: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Helper functions
function updateStreak($user_id) {
    global $pdo;
    
    $today = date('Y-m-d');
    
    // Get current streak info
    $stmt = $pdo->prepare("SELECT streak_days, longest_streak, last_study_date FROM ai_user_levels WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $streak_info = $stmt->fetch();
    
    if (!$streak_info) {
        // Initialize streak
        $stmt = $pdo->prepare("INSERT INTO ai_user_levels (user_id, streak_days, longest_streak, last_study_date) VALUES (?, 1, 1, ?)");
        $stmt->execute([$user_id, $today]);
        return;
    }
    
    $last_study = $streak_info['last_study_date'];
    $current_streak = $streak_info['streak_days'];
    $longest_streak = $streak_info['longest_streak'];
    
    if ($last_study == $today) {
        // Already studied today, no change needed
        return;
    }
    
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($last_study == $yesterday) {
        // Continue streak
        $new_streak = $current_streak + 1;
        $new_longest = max($longest_streak, $new_streak);
    } else {
        // Break streak
        $new_streak = 1;
        $new_longest = $longest_streak;
    }
    
    $stmt = $pdo->prepare("
        UPDATE ai_user_levels 
        SET streak_days = ?, longest_streak = ?, last_study_date = ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$new_streak, $new_longest, $today, $user_id]);
}

function checkAchievements($user_id, $score, $activity_type) {
    global $pdo;
    
    $new_achievements = [];
    
    // Check for high score achievement
    if ($score >= 90) {
        $achievement_name = "High Achiever";
        if (!achievementExists($user_id, $achievement_name)) {
            addAchievement($user_id, 'score', $achievement_name, "Scored 90% or higher on an activity", 50, 'star');
            $new_achievements[] = $achievement_name;
        }
    }
    
    // Check for streak achievements
    $stmt = $pdo->prepare("SELECT streak_days FROM ai_user_levels WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $streak_days = $stmt->fetchColumn();
    
    if ($streak_days >= 7 && !achievementExists($user_id, "Week Warrior")) {
        addAchievement($user_id, 'streak', "Week Warrior", "Maintained a 7-day study streak", 100, 'fire');
        $new_achievements[] = "Week Warrior";
    }
    
    if ($streak_days >= 30 && !achievementExists($user_id, "Monthly Master")) {
        addAchievement($user_id, 'streak', "Monthly Master", "Maintained a 30-day study streak", 500, 'crown');
        $new_achievements[] = "Monthly Master";
    }
    
    return $new_achievements;
}

function achievementExists($user_id, $achievement_name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM ai_achievements WHERE user_id = ? AND achievement_name = ?");
    $stmt->execute([$user_id, $achievement_name]);
    return $stmt->fetch() !== false;
}

function addAchievement($user_id, $type, $name, $description, $points, $icon) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO ai_achievements (user_id, achievement_type, achievement_name, description, points_earned, icon)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $type, $name, $description, $points, $icon]);
}

function calculateXP($score, $time_spent, $difficulty_level) {
    $base_xp = 10;
    
    // Score bonus
    if ($score !== null) {
        $base_xp += ($score / 10); // 1 XP per 10% score
    }
    
    // Time bonus (1 XP per 5 minutes)
    if ($time_spent !== null) {
        $base_xp += ($time_spent / 5);
    }
    
    // Difficulty bonus
    switch ($difficulty_level) {
        case 'beginner':
            $base_xp *= 0.8;
            break;
        case 'advanced':
            $base_xp *= 1.5;
            break;
        default:
            // intermediate - no multiplier
            break;
    }
    
    return round($base_xp);
}

function updateXP($user_id, $xp_gained) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE ai_user_levels 
        SET current_xp = current_xp + ?, total_xp = total_xp + ?, updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
    ");
    $stmt->execute([$xp_gained, $xp_gained, $user_id]);
    
    // Check for level up
    $stmt = $pdo->prepare("SELECT current_xp, current_level FROM ai_user_levels WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $level_info = $stmt->fetch();
    
    $xp_needed = $level_info['current_level'] * 100; // 100 XP per level
    
    if ($level_info['current_xp'] >= $xp_needed) {
        $new_level = $level_info['current_level'] + 1;
        $new_xp = $level_info['current_xp'] - $xp_needed;
        
        $level_titles = [
            1 => 'Novice Learner',
            2 => 'Curious Student',
            3 => 'Dedicated Scholar',
            4 => 'Knowledge Seeker',
            5 => 'Academic Explorer',
            6 => 'Learning Enthusiast',
            7 => 'Study Champion',
            8 => 'Wisdom Gatherer',
            9 => 'Master Student',
            10 => 'Academic Legend'
        ];
        
        $title = $level_titles[$new_level] ?? "Level {$new_level} Scholar";
        
        $stmt = $pdo->prepare("
            UPDATE ai_user_levels 
            SET current_level = ?, current_xp = ?, level_title = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$new_level, $new_xp, $title, $user_id]);
    }
}
?>
