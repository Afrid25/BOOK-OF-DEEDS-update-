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
            case 'get_exam_list':
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_exam_prep 
                    WHERE user_id = ? 
                    ORDER BY exam_date ASC
                ");
                $stmt->execute([$user_id]);
                $exams = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'exams' => $exams
                ]);
                break;
                
            case 'get_exam_details':
                $exam_id = $_GET['exam_id'] ?? 0;
                
                if ($exam_id > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM ai_exam_prep WHERE id = ? AND user_id = ?");
                    $stmt->execute([$exam_id, $user_id]);
                    $exam = $stmt->fetch();
                    
                    if ($exam) {
                        echo json_encode([
                            'success' => true,
                            'exam' => $exam
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Exam not found'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid exam ID'
                    ]);
                }
                break;
                
            case 'get_upcoming_exams':
                $stmt = $pdo->prepare("
                    SELECT * FROM ai_exam_prep 
                    WHERE user_id = ? AND exam_date >= CURDATE()
                    ORDER BY exam_date ASC
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $upcoming_exams = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'upcoming_exams' => $upcoming_exams
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching exam prep data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching data']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'create_exam_prep':
                $exam_title = trim($data['exam_title'] ?? '');
                $subject = trim($data['subject'] ?? '');
                $exam_date = $data['exam_date'] ?? '';
                $exam_type = $data['exam_type'] ?? 'quiz';
                
                if (empty($exam_title) || empty($subject) || empty($exam_date)) {
                    echo json_encode(['success' => false, 'message' => 'Exam title, subject, and date are required']);
                    exit;
                }
                
                // Generate exam preparation materials
                $prep_id = $ai_service->generateExamPrep($user_id, $exam_title, $subject, $exam_date);
                
                if ($prep_id) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Exam preparation created successfully!',
                        'prep_id' => $prep_id
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create exam preparation'
                    ]);
                }
                break;
                
            case 'update_prep_status':
                $exam_id = $data['exam_id'] ?? 0;
                $preparation_status = $data['preparation_status'] ?? '';
                $confidence_level = $data['confidence_level'] ?? 50;
                
                if ($exam_id > 0 && in_array($preparation_status, ['not_started', 'in_progress', 'completed'])) {
                    $stmt = $pdo->prepare("
                        UPDATE ai_exam_prep 
                        SET preparation_status = ?, confidence_level = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$preparation_status, $confidence_level, $exam_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Preparation status updated successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid exam ID or status'
                    ]);
                }
                break;
                
            case 'add_study_notes':
                $exam_id = $data['exam_id'] ?? 0;
                $notes = trim($data['notes'] ?? '');
                
                if ($exam_id > 0 && !empty($notes)) {
                    $stmt = $pdo->prepare("
                        UPDATE ai_exam_prep 
                        SET study_notes = CONCAT(IFNULL(study_notes, ''), '\n\n', ?), updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$notes, $exam_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Study notes added successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid exam ID or notes'
                    ]);
                }
                break;
                
            case 'delete_exam':
                $exam_id = $data['exam_id'] ?? 0;
                
                if ($exam_id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM ai_exam_prep WHERE id = ? AND user_id = ?");
                    $stmt->execute([$exam_id, $user_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Exam deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid exam ID'
                    ]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        error_log("Error in exam prep API: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
