<?php
/**
 * Intelligent Exam Generator + Performance Evaluator API
 * Main API endpoint for exam system functionality
 */

// Error handling and session management
ini_set('display_errors', 0);
error_reporting(E_ALL);
if (!is_dir(__DIR__ . '/../../logs')) mkdir(__DIR__ . '/../../logs', 0755, true);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/exam_api_error.log');
session_start();

// Authentication check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
    exit;
}

require_once __DIR__ . '/../../includes/db_connect.php';
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once __DIR__ . '/../../config.php';
}

// OpenAI API key configuration
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', ''); // Set your OpenAI API key in config.php
}

$user_id = $_SESSION['user_id'];
$requestData = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $requestData['action'] ?? null;

// =====================================================
// AI INTEGRATION FUNCTIONS
// =====================================================

/**
 * Generate exam questions using OpenAI API
 */
function generateExamQuestions($topics, $difficulty = 'medium', $questionCount = 10) {
    $apiKey = OPENAI_API_KEY;
    if (empty($apiKey)) {
        return ['success' => false, 'message' => 'AI API key is not configured.'];
    }

    $isGroq = str_starts_with($apiKey, 'gsk_');
    $apiUrl = $isGroq ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
    $model = $isGroq ? 'llama3-70b-8192' : 'gpt-4-turbo-preview';

    $prompt = "Generate $questionCount multiple choice questions for an exam. Topics: " . implode(', ', $topics) . ". Difficulty: $difficulty. 
    
    Format as JSON array:
    [
        {
            \"question_text\": \"Question here?\",
            \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
            \"correct_answer\": \"Option A\",
            \"explanation\": \"Explanation why this is correct\",
            \"topic\": \"Topic name\",
            \"difficulty\": \"$difficulty\",
            \"estimated_time_seconds\": 300
        }
    ]
    
    Make questions challenging but fair. Ensure explanations are educational.";

    $postData = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'response_format' => ['type' => 'json_object']
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'message' => 'Network error: ' . $curl_error];
    }

    if ($http_code !== 200) {
        return ['success' => false, 'message' => 'AI service error (Status: ' . $http_code . '). Response: ' . $response];
    }

    $result = json_decode($response, true);
    $jsonContent = $result['choices'][0]['message']['content'] ?? null;
    
    if (!$jsonContent) {
        return ['success' => false, 'message' => 'AI returned empty response.'];
    }

    $questions = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
        return ['success' => false, 'message' => 'AI returned malformed data.'];
    }

    return ['success' => true, 'questions' => $questions];
}

// =====================================================
// API ROUTER
// =====================================================

if ($action && isset($mysqli)) {
    header('Content-Type: application/json');
    $mysqli->begin_transaction();

    try {
        switch ($action) {
            case 'get_user_exams':
                $status = $requestData['status'] ?? 'all';
                $course_id = $requestData['course_id'] ?? null;
                
                $sql = "SELECT e.*, c.course_name 
                        FROM ai_exams e 
                        JOIN courses c ON e.course_id = c.id 
                        WHERE e.user_id = ?";
                $params = [$user_id];
                $types = "i";
                
                if ($status !== 'all') {
                    $sql .= " AND e.status = ?";
                    $params[] = $status;
                    $types .= "s";
                }
                
                if ($course_id) {
                    $sql .= " AND e.course_id = ?";
                    $params[] = $course_id;
                    $types .= "i";
                }
                
                $sql .= " ORDER BY e.scheduled_date ASC";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                echo json_encode(['success' => true, 'exams' => $exams]);
                break;

            case 'generate_exam_questions':
                $exam_id = (int)($requestData['exam_id'] ?? 0);
                if (!$exam_id) {
                    throw new Exception("Exam ID is required.");
                }
                
                // Get exam details and course topics
                $stmt = $mysqli->prepare("
                    SELECT e.*, c.course_name 
                    FROM ai_exams e 
                    JOIN courses c ON e.course_id = c.id 
                    WHERE e.id = ? AND e.user_id = ?
                ");
                $stmt->bind_param("ii", $exam_id, $user_id);
                $stmt->execute();
                $exam = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if (!$exam) {
                    throw new Exception("Exam not found.");
                }
                
                // Get course topics
                $stmt = $mysqli->prepare("
                    SELECT ct.topic_name 
                    FROM course_topics ct 
                    JOIN course_chapters cc ON ct.chapter_id = cc.id 
                    WHERE cc.course_id = ? AND ct.is_completed = 1
                ");
                $stmt->bind_param("i", $exam['course_id']);
                $stmt->execute();
                $topics = [];
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $topics[] = $row['topic_name'];
                }
                $stmt->close();
                
                if (empty($topics)) {
                    throw new Exception("No completed topics found for this course.");
                }
                
                // Generate questions using AI
                $aiResult = generateExamQuestions($topics, $exam['difficulty_level'], $exam['total_questions']);
                if (!$aiResult['success']) {
                    throw new Exception("Failed to generate questions: " . $aiResult['message']);
                }
                
                // Save questions to database
                $stmt = $mysqli->prepare("
                    INSERT INTO ai_exam_questions (exam_id, question_text, options, correct_answer, explanation, topic, difficulty, estimated_time_seconds)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($aiResult['questions'] as $question) {
                    $options = json_encode($question['options']);
                    $stmt->bind_param("issssssi", 
                        $exam_id,
                        $question['question_text'],
                        $options,
                        $question['correct_answer'],
                        $question['explanation'],
                        $question['topic'],
                        $question['difficulty'],
                        $question['estimated_time_seconds']
                    );
                    $stmt->execute();
                }
                $stmt->close();
                
                // Update exam status
                $stmt = $mysqli->prepare("UPDATE ai_exams SET status = 'active' WHERE id = ?");
                $stmt->bind_param("i", $exam_id);
                $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => true, 'questions' => $aiResult['questions']]);
                break;

            default:
                throw new Exception("Invalid action: $action");
        }
        
        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        error_log("Exam API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $mysqli->close();
    exit;
}
?> 