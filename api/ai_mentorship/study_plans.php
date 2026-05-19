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
        $plan_type = $_GET['type'] ?? 'daily';
        $limit = (int)($_GET['limit'] ?? 10);
        
        // Get study plans
        $stmt = $pdo->prepare("
            SELECT sp.*, 
                   COUNT(ast.id) as total_tasks,
                   COUNT(CASE WHEN ast.completed = 1 THEN 1 END) as completed_tasks
            FROM ai_study_plans sp
            LEFT JOIN ai_study_tasks ast ON sp.id = ast.plan_id
            WHERE sp.user_id = ? AND sp.plan_type = ?
            GROUP BY sp.id
            ORDER BY sp.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $plan_type, $limit]);
        $plans = $stmt->fetchAll();
        
        // Get tasks for each plan
        foreach ($plans as &$plan) {
            $stmt = $pdo->prepare("
                SELECT * FROM ai_study_tasks 
                WHERE plan_id = ? 
                ORDER BY due_date ASC, created_at ASC
            ");
            $stmt->execute([$plan['id']]);
            $plan['tasks'] = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'plans' => $plans
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching study plans: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching study plans']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'generate':
                $plan_type = $data['plan_type'] ?? 'daily';
                $plan_id = $ai_service->generateStudyPlan($user_id, $plan_type);
                
                if ($plan_id) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Study plan generated successfully!',
                        'plan_id' => $plan_id
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to generate study plan'
                    ]);
                }
                break;
                
            case 'update_task':
                $task_id = $data['task_id'] ?? 0;
                $completed = $data['completed'] ?? false;
                $score = $data['score'] ?? null;
                
                if ($task_id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE ai_study_tasks 
                        SET completed = ?, completed_at = ?, score = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$completed ? 1 : 0, $completed ? date('Y-m-d H:i:s') : null, $score, $task_id, $user_id]);
                    
                    // Track performance if score provided
                    if ($score !== null) {
                        $stmt = $pdo->prepare("
                            SELECT subject, topic, difficulty_level FROM ai_study_tasks WHERE id = ?
                        ");
                        $stmt->execute([$task_id]);
                        $task_info = $stmt->fetch();
                        
                        if ($task_info) {
                            $stmt = $pdo->prepare("
                                INSERT INTO ai_performance_tracking 
                                (user_id, subject, topic, activity_type, score, difficulty_level)
                                VALUES (?, ?, ?, 'quiz', ?, ?)
                            ");
                            $stmt->execute([
                                $user_id, $task_info['subject'], $task_info['topic'], 
                                $score, $task_info['difficulty_level']
                            ]);
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Task updated successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid task ID'
                    ]);
                }
                break;
                
            case 'delete_plan':
                $plan_id = $data['plan_id'] ?? 0;
                
                if ($plan_id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM ai_study_plans WHERE id = ? AND user_id = ?");
                    $stmt->execute([$plan_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Study plan deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid plan ID'
                    ]);
                }
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
        
    } catch (Exception $e) {
        error_log("Error in study plans API: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
