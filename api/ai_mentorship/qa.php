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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'ask_question':
                $question = trim($data['question'] ?? '');
                $subject = trim($data['subject'] ?? '');
                $topic = trim($data['topic'] ?? '');
                
                if (empty($question)) {
                    echo json_encode(['success' => false, 'message' => 'Question is required']);
                    exit;
                }
                
                $answer = $ai_service->answerQuestion($user_id, $question, $subject, $topic);
                
                echo json_encode([
                    'success' => true,
                    'answer' => $answer,
                    'question' => $question,
                    'subject' => $subject,
                    'topic' => $topic
                ]);
                break;
                
            case 'rate_answer':
                $interaction_id = $data['interaction_id'] ?? 0;
                $rating = (int)($data['rating'] ?? 0);
                
                if ($interaction_id > 0 && $rating >= 1 && $rating <= 5) {
                    $stmt = $pdo->prepare("
                        UPDATE ai_qa_interactions 
                        SET helpful_rating = ? 
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$rating, $interaction_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rating saved successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid rating or interaction ID'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error in Q&A API: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your question']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_history':
                $limit = (int)($_GET['limit'] ?? 20);
                $subject = $_GET['subject'] ?? '';
                
                $sql = "
                    SELECT * FROM ai_qa_interactions 
                    WHERE user_id = ?
                ";
                $params = [$user_id];
                
                if (!empty($subject)) {
                    $sql .= " AND subject = ?";
                    $params[] = $subject;
                }
                
                $sql .= " ORDER BY created_at DESC LIMIT ?";
                $params[] = $limit;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $history = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'history' => $history
                ]);
                break;
                
            case 'get_subjects':
                $stmt = $pdo->prepare("
                    SELECT DISTINCT subject 
                    FROM ai_qa_interactions 
                    WHERE user_id = ? AND subject IS NOT NULL AND subject != ''
                    ORDER BY subject
                ");
                $stmt->execute([$user_id]);
                $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo json_encode([
                    'success' => true,
                    'subjects' => $subjects
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching Q&A data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
