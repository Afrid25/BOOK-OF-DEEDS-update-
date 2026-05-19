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
            case 'get_today_challenge':
                $today = date('Y-m-d');
                
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_daily_challenges 
                    WHERE user_id = ? AND challenge_date = ?
                ");
                $stmt->execute([$user_id, $today]);
                $challenge = $stmt->fetch();
                
                if (!$challenge) {
                    // Generate new challenge
                    $challenge_id = $ai_service->generateDailyChallenge($user_id);
                    if ($challenge_id) {
                        $stmt = $pdo->prepare("SELECT * FROM ai_daily_challenges WHERE id = ?");
                        $stmt->execute([$challenge_id]);
                        $challenge = $stmt->fetch();
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'challenge' => $challenge
                ]);
                break;
                
            case 'get_challenge_history':
                $limit = (int)($_GET['limit'] ?? 10);
                
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_daily_challenges 
                    WHERE user_id = ? 
                    ORDER BY challenge_date DESC 
                    LIMIT ?
                ");
                $stmt->execute([$user_id, $limit]);
                $history = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'history' => $history
                ]);
                break;
                
            case 'get_challenge_stats':
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_challenges,
                        COUNT(CASE WHEN completed = 1 THEN 1 END) as completed_challenges,
                        AVG(score) as average_score,
                        AVG(time_taken) as average_time
                    FROM ai_daily_challenges 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                $stats = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching challenge data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching data']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'complete_challenge':
                $challenge_id = $data['challenge_id'] ?? 0;
                $score = $data['score'] ?? null;
                $time_taken = $data['time_taken'] ?? null;
                
                if ($challenge_id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE ai_daily_challenges 
                        SET completed = 1, score = ?, time_taken = ?, completed_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$score, $time_taken, $challenge_id, $user_id]);
                    
                    // Record performance
                    if ($score !== null) {
                        $stmt = $pdo->prepare("
                            SELECT subject, difficulty_level FROM ai_daily_challenges WHERE id = ?
                        ");
                        $stmt->execute([$challenge_id]);
                        $challenge_info = $stmt->fetch();
                        
                        if ($challenge_info) {
                            $stmt = $pdo->prepare("
                                INSERT INTO ai_performance_tracking 
                                (user_id, subject, activity_type, score, time_spent, difficulty_level)
                                VALUES (?, ?, 'quiz', ?, ?, ?)
                            ");
                            $stmt->execute([
                                $user_id, $challenge_info['subject'], $score, 
                                $time_taken, $challenge_info['difficulty_level']
                            ]);
                        }
                    }
                    
                    // Check for achievements
                    $new_achievements = [];
                    if ($score >= 90) {
                        $new_achievements[] = "Challenge Master";
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Challenge completed successfully!',
                        'new_achievements' => $new_achievements
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid challenge ID'
                    ]);
                }
                break;
                
            case 'generate_new_challenge':
                $challenge_id = $ai_service->generateDailyChallenge($user_id);
                
                if ($challenge_id) {
                    $stmt = $pdo->prepare("SELECT * FROM ai_daily_challenges WHERE id = ?");
                    $stmt->execute([$challenge_id]);
                    $challenge = $stmt->fetch();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'New challenge generated!',
                        'challenge' => $challenge
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to generate challenge'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error in challenges API: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
