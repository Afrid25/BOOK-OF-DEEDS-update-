<?php
// =================================================================
//  GLOBAL ERROR HANDLING & SETUP
// =================================================================

// Prevent PHP errors from corrupting JSON output. This is a robust setup.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
if (!is_dir(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_error.log');

// Start output buffering to catch any stray output.
ob_start();

// Guarantee a valid JSON response is sent, even on fatal errors.
register_shutdown_function(function() {
    // Don't do anything if the connection was aborted by the client.
    if (connection_aborted()) {
        return;
    }

    $last_error = error_get_last();
    // Check for fatal errors that would prevent normal execution.
    if ($last_error && in_array($last_error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // If headers haven't been sent, send a JSON error header.
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            // Log the actual error for debugging
            error_log("Fatal Error Caught by Shutdown Handler: " . print_r($last_error, true));
        }
        // Ensure the output buffer is clean before echoing our JSON.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode(['success' => false, 'message' => 'A fatal server error occurred. Please check the logs for details.']);
    } elseif (ob_get_length() === 0 && !headers_sent()) {
        // If execution finished but nothing was ever output, it's likely an issue.
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'The server returned an empty response.']);
    }
    // If there is content in the buffer, flush it to the client.
    elseif (ob_get_length() > 0) {
        ob_end_flush();
    }
});


// =================================================================
//  INCLUDES & SESSION START
// =================================================================

session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once '../../includes/db_connect.php'; 
require_once '../../includes/config.php'; // Required for the AI Parser's API Key

global $mysqli;

// =================================================================
//  HELPER FUNCTIONS
// =================================================================

/**
 * Centralized function to output JSON and terminate the script.
 *
 * @param array $data The data to encode as JSON.
 */
function exit_json(array $data) {
    global $mysqli;
    if (isset($mysqli) && $mysqli->ping()) {
        $mysqli->close();
    }
    // Clean any previously buffered content to ensure only our JSON is sent.
    ob_end_clean();
    echo json_encode($data);
    exit;
}

/**
 * Helper to format seconds into HH:MM:SS format.
 */
if (!function_exists('format_seconds')) {
    function format_seconds(int $seconds): string {
        return gmdate("H:i:s", $seconds > 0 ? $seconds : 0);
    }
}

/**
 * Parses syllabus text using an AI model.
 *
 * @param string $syllabus The raw text of the course syllabus.
 * @return array An array with success status and either structured data or a detailed error message.
 */
function parseSyllabusWithAI(string $syllabus): array {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($apiKey)) {
        return ['success' => false, 'message' => 'AI API key is not configured on the server.'];
    }

    // Determine API endpoint and model based on API key prefix
    $apiUrl = str_starts_with($apiKey, 'gsk_') ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
    $model = str_starts_with($apiKey, 'gsk_') ? 'llama3-70b-8192' : 'gpt-4-turbo-preview';

    $prompt = "You are an expert curriculum designer. Your task is to analyze the following course syllabus and transform it into a structured JSON object.

    Rules for parsing:
    1.  The top-level structure MUST be a JSON object with a single key: 'chapters'.
    2.  The 'chapters' key must contain an array of chapter objects.
    3.  Each chapter object MUST have three keys: 'chapter_name' (string), 'estimated_time' (string, e.g., '2 hours', '45 minutes'), and 'topics' (array of strings).
    4.  Consolidate all related sub-topics under their main chapter. Treat 'Week', 'Module', or 'Unit' as a 'chapter'.
    5.  Clean up any extraneous text or numbering. The output must be ONLY the raw JSON, with no markdown formatting (`json ... `) or explanations.

    Example of required output format:
    {\"chapters\":[{\"chapter_name\":\"Introduction to Quantum Physics\",\"estimated_time\":\"3 hours\",\"topics\":[\"Historical Overview\",\"Wave-Particle Duality\"]}]}
    
    Syllabus to parse:
    ---
    " . $syllabus;

    $postData = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.1,
        'response_format' => ['type' => 'json_object']
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_TIMEOUT => 90
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("cURL Error in AI parser: " . $curl_error);
        return ['success' => false, 'message' => 'A network error occurred while contacting the AI service.'];
    }

    if ($http_code !== 200) {
        $errorDetails = "HTTP Status: $http_code. Response: $response";
        error_log("AI API Error: " . $errorDetails);
        switch ($http_code) {
            case 401: return ['success' => false, 'message' => 'Authentication Error: The provided AI API key is invalid.'];
            case 429: return ['success' => false, 'message' => 'Rate Limit Exceeded. Please check your AI provider account and billing.'];
            default: return ['success' => false, 'message' => 'The AI service returned an error. Please try again later.'];
        }
    }

    $result = json_decode($response, true);
    $jsonContent = $result['choices'][0]['message']['content'] ?? null;
    if (empty($jsonContent)) {
        return ['success' => false, 'message' => 'The AI service returned an empty or invalid response.'];
    }

    $structuredData = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Log the malformed JSON for debugging purposes
        error_log("AI returned malformed JSON. Error: " . json_last_error_msg() . ". Content: " . $jsonContent);
        return ['success' => false, 'message' => 'The AI returned data in an unexpected format. Please try parsing again.'];
    }

    if (!isset($structuredData['chapters']) || !is_array($structuredData['chapters'])) {
        return ['success' => false, 'message' => "AI Response Error: The structured response is missing the required 'chapters' array."];
    }

    // Deep validation of the structure
    foreach ($structuredData['chapters'] as $i => $chapter) {
        if (!isset($chapter['chapter_name'], $chapter['estimated_time'], $chapter['topics']) || !is_array($chapter['topics'])) {
            return ['success' => false, 'message' => "AI Response Error: Chapter #".($i+1)." is missing required keys ('chapter_name', 'estimated_time', 'topics')."];
        }
    }

    return ['success' => true, 'data' => $structuredData['chapters']];
}

/**
 * Handles adding or updating a course and its curriculum from a syllabus.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $userId The ID of the logged-in user.
 * @param array $postData The data from the $_POST superglobal.
 * @return array Result array with 'success' and 'message' keys.
 */
function saveCourse(mysqli $mysqli, int $userId, array $postData): array {
    $courseId = isset($postData['course_id']) ? (int)$postData['course_id'] : null;
    $courseName = trim($postData['course_name'] ?? '');
    $syllabus = trim($postData['syllabus'] ?? '');
    $isUpdate = $courseId !== null;

    if (empty($courseName)) {
        return ['success' => false, 'message' => 'Course name cannot be empty.'];
    }

    $mysqli->begin_transaction();
    try {
        // If updating, first verify the user owns this course.
        if ($isUpdate) {
            $stmt_verify = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND user_id = ? AND is_deleted = 0");
            $stmt_verify->bind_param("ii", $courseId, $userId);
            $stmt_verify->execute();
            if ($stmt_verify->get_result()->num_rows === 0) {
                throw new Exception("Permission denied or course not found.");
            }
            $stmt_verify->close();
            
            $stmt_course = $mysqli->prepare("UPDATE courses SET course_name = ?, syllabus = ? WHERE id = ?");
            $stmt_course->bind_param("ssi", $courseName, $syllabus, $courseId);
        } else { // Adding a new course
            $stmt_course = $mysqli->prepare("INSERT INTO courses (user_id, course_name, syllabus, created_at) VALUES (?, ?, ?, NOW())");
            $stmt_course->bind_param("iss", $userId, $courseName, $syllabus);
        }

        if (!$stmt_course->execute()) {
            throw new Exception("Database error while saving course: " . $stmt_course->error);
        }

        if (!$isUpdate) {
            $courseId = $mysqli->insert_id;
        }
        $stmt_course->close();

        // If a syllabus was provided, parse it and update the curriculum.
        if (!empty($syllabus)) {
            $aiResult = parseSyllabusWithAI($syllabus);
            if (!$aiResult['success']) {
                throw new Exception($aiResult['message']);
            }
            $structuredCurriculum = $aiResult['data'];
            
            if (!empty($structuredCurriculum)) {
                $stmt_delete = $mysqli->prepare("DELETE FROM course_chapters WHERE course_id = ?");
                $stmt_delete->bind_param("i", $courseId);
                $stmt_delete->execute();
                $stmt_delete->close(); 

                // Prepare statements for insertion.
                $stmt_chapter = $mysqli->prepare("INSERT INTO course_chapters (course_id, chapter_name, chapter_order, estimated_time) VALUES (?, ?, ?, ?)");
                $stmt_topic = $mysqli->prepare("INSERT INTO course_topics (chapter_id, topic_name, topic_order) VALUES (?, ?, ?)");

                foreach ($structuredCurriculum as $chapter_order => $chapterData) {
                    $chapterName = trim($chapterData['chapter_name'] ?? '');
                    if (empty($chapterName)) continue; // Skip chapters with no name

                    $estimatedTime = trim($chapterData['estimated_time'] ?? '');
                    
                    $stmt_chapter->bind_param("isis", $courseId, $chapterName, $chapter_order, $estimatedTime);
                    if (!$stmt_chapter->execute()) throw new Exception("DB error inserting chapter: " . $stmt_chapter->error);
                    $newChapterId = $mysqli->insert_id;

                    if (isset($chapterData['topics']) && is_array($chapterData['topics'])) {
                        foreach ($chapterData['topics'] as $topic_order => $topicName) {
                            $trimmedTopic = trim($topicName);
                            if (empty($trimmedTopic)) continue; // Skip empty topic names
                            
                            $stmt_topic->bind_param("isi", $newChapterId, $trimmedTopic, $topic_order);
                            if (!$stmt_topic->execute()) throw new Exception("DB error inserting topic: " . $stmt_topic->error);
                        }
                    }
                }
                $stmt_chapter->close();
                $stmt_topic->close();
            }
        }
        
        $mysqli->commit();
        $actionVerb = $isUpdate ? "updated" : "added";
        return ['success' => true, 'message' => "Course $actionVerb successfully!"];

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log(($isUpdate ? "Update" : "Add") . " course failed for user $userId: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}


// =================================================================
//  AUTHENTICATION & ROUTER SETUP
// =================================================================

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit_json(['success' => false, 'message' => 'Authentication required. Please log in.']);
}

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Define action constants to avoid typos
const ACTION_GET_PROGRESS = 'get_progress_data';
const ACTION_GET_DETAILS = 'get_course_details';
const ACTION_ADD_COURSE = 'add_course';
const ACTION_UPDATE_COURSE = 'update_course';
const ACTION_ADD_CHAPTER = 'add_chapter';
const ACTION_ADD_TOPIC = 'add_topic';
const ACTION_DELETE_ITEM = 'delete_item';
const ACTION_TOGGLE_TOPIC = 'toggle_topic_status';
const ACTION_UPDATE_TIMER = 'update_timer';
const ACTION_UPLOAD_PROOF = 'upload_proof';


// =================================================================
//  MAIN ACTION ROUTER
// =================================================================

switch ($action) {

    case ACTION_GET_PROGRESS:
        $response = ['labels' => [], 'data' => []];
        // MODIFIED: Added `c.is_deleted = 0` to respect the soft-delete flag
        $sql = "SELECT c.course_name, COUNT(ct.id) AS completed_topics 
                FROM courses c 
                LEFT JOIN course_chapters cc ON c.id = cc.course_id 
                LEFT JOIN course_topics ct ON cc.id = ct.chapter_id AND ct.is_completed = 1
                WHERE c.user_id = ? AND c.is_deleted = 0
                GROUP BY c.id, c.course_name 
                ORDER BY c.created_at DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response['labels'][] = $row['course_name'];
            $response['data'][] = (int)$row['completed_topics'];
        }
        $stmt->close();
        exit_json(['success' => true, 'data' => $response]);
        break;

    case ACTION_GET_DETAILS:
        $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($courseId <= 0) exit_json(['success' => false, 'message' => 'Invalid course ID provided.']);
        
        // MODIFIED: Added `is_deleted = 0` to the query
        $stmt = $mysqli->prepare("SELECT course_name, syllabus FROM courses WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->bind_param("ii", $courseId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($course = $result->fetch_assoc()) {
            exit_json(['success' => true, 'data' => $course]);
        } else {
            exit_json(['success' => false, 'message' => 'Course not found or you do not have permission to view it.']);
        }
        $stmt->close();
        break;

    case ACTION_ADD_COURSE:
    case ACTION_UPDATE_COURSE:
        $result = saveCourse($mysqli, $userId, $_POST);
        exit_json($result);
        break;

     case ACTION_DELETE_ITEM:
        // Expecting a JSON payload from JS fetch API
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['type'] ?? '';
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        
        $allowed_types = ['course', 'chapter', 'topic'];
        if ($id <= 0 || !in_array($type, $allowed_types, true)) {
            exit_json(['success' => false, 'message' => 'Invalid item type or ID specified for deletion.']);
        }

        // --- OWNERSHIP VERIFICATION (Crucial for security) ---
        if ($type === 'course') {
            $sql_check_ownership = "SELECT id FROM courses WHERE id = ? AND user_id = ?";
            $stmt_check = $mysqli->prepare($sql_check_ownership);
            $stmt_check->bind_param("ii", $id, $userId);
        } elseif ($type === 'chapter') {
            $sql_check_ownership = "SELECT ch.id FROM course_chapters ch JOIN courses c ON ch.course_id = c.id WHERE ch.id = ? AND c.user_id = ?";
            $stmt_check = $mysqli->prepare($sql_check_ownership);
            $stmt_check->bind_param("ii", $id, $userId);
        } else { // topic
            $sql_check_ownership = "SELECT t.id FROM course_topics t JOIN course_chapters cc ON t.chapter_id = cc.id JOIN courses c ON cc.course_id = c.id WHERE t.id = ? AND c.user_id = ?";
            $stmt_check = $mysqli->prepare($sql_check_ownership);
            $stmt_check->bind_param("ii", $id, $userId);
        }
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
             exit_json(['success' => false, 'message' => 'Permission denied or item not found.']);
        }
        $stmt_check->close();


        // --- DELETION LOGIC (CORRECTED) ---
        $is_soft_delete = false;

        if ($type === 'course') {
            // **FIXED**: Course is soft-deleted by updating the is_deleted flag.
            $stmt_delete = $mysqli->prepare("UPDATE courses SET is_deleted = 1 WHERE id = ?");
            $stmt_delete->bind_param("i", $id);
            $is_soft_delete = true;

        } else {
            // Chapters and Topics are hard-deleted as they don't have the flag.
            $table = 'course_' . $type . 's';

            // If it's a topic with a proof file, get the path for later deletion.
            $proof_path_to_delete = null;
            if ($type === 'topic') {
                $stmt_get_path = $mysqli->prepare("SELECT proof_file_path, proof_path FROM course_topics WHERE id = ?");
                $stmt_get_path->bind_param("i", $id);
                $stmt_get_path->execute();
                $path_row = $stmt_get_path->get_result()->fetch_assoc();
                if ($path_row) {
                    $path = !empty($path_row['proof_file_path']) ? $path_row['proof_file_path'] : $path_row['proof_path'];
                    if (!empty($path)) {
                        $proof_path_to_delete = '../../' . ltrim($path, '/\\');
                    }
                }
                $stmt_get_path->close();
            }

            $stmt_delete = $mysqli->prepare("DELETE FROM $table WHERE id = ?");
            $stmt_delete->bind_param("i", $id);

            // Execute deletion and handle file unlinking if necessary.
            if ($stmt_delete->execute()) {
                 if ($proof_path_to_delete && file_exists($proof_path_to_delete)) {
                    if (!unlink($proof_path_to_delete)) {
                        error_log("Failed to unlink proof file: $proof_path_to_delete for topic ID: $id");
                    }
                 }
                exit_json(['success' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
            } else {
                exit_json(['success' => false, 'message' => 'Database error during deletion: ' . $stmt_delete->error]);
            }
            $stmt_delete->close();
            return; // Exit here since we've already sent a response
        }

        // This part only runs for soft-delete (courses)
        if ($is_soft_delete) {
            if ($stmt_delete->execute()) {
                exit_json(['success' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
            } else {
                exit_json(['success' => false, 'message' => 'Database error during deletion: ' . $stmt_delete->error]);
            }
            $stmt_delete->close();
        }
        break;
    case ACTION_TOGGLE_TOPIC:
        $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        $isCompleted = isset($_POST['is_completed']) && $_POST['is_completed'] == '1' ? 1 : 0;

        if ($topicId <= 0) exit_json(['success' => false, 'message' => 'Invalid topic ID.']);
        
        $sql = "UPDATE course_topics ct 
                JOIN course_chapters cc ON ct.chapter_id = cc.id 
                JOIN courses c ON cc.course_id = c.id 
                SET ct.is_completed = ?, ct.completed_at = ? 
                WHERE ct.id = ? AND c.user_id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $completedAt = $isCompleted ? date("Y-m-d H:i:s") : null;
        $stmt->bind_param("isii", $isCompleted, $completedAt, $topicId, $userId);

        if ($stmt->execute()) {
            exit_json(['success' => $stmt->affected_rows > 0, 'message' => $stmt->affected_rows > 0 ? 'Topic status updated.' : 'No change detected or topic not found.']);
        } else {
            exit_json(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case ACTION_UPDATE_TIMER:
        $data = json_decode(file_get_contents('php://input'), true);
        $topicId = isset($data['topic_id']) ? (int)$data['topic_id'] : 0;
        $seconds = isset($data['seconds']) ? (int)$data['seconds'] : -1;

        if ($topicId <= 0 || $seconds < 0) exit_json(['success' => false, 'message' => 'Invalid topic ID or time value.']);
        
        // REWRITTEN: Update both `time_spent_seconds` and the redundant `time_spent` column for consistency.
        $sql = "UPDATE course_topics ct 
                JOIN course_chapters cc ON ct.chapter_id = cc.id 
                JOIN courses c ON cc.course_id = c.id 
                SET ct.time_spent_seconds = ?, ct.time_spent = ?
                WHERE ct.id = ? AND c.user_id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiii", $seconds, $seconds, $topicId, $userId); // Bind seconds twice

        if ($stmt->execute()) {
            exit_json(['success' => true, 'message' => $stmt->affected_rows > 0 ? 'Time saved.' : 'No change detected.']);
        } else {
            exit_json(['success' => false, 'message' => 'Database error updating timer: ' . $stmt->error]);
        }
        break;

    case ACTION_UPLOAD_PROOF:
        $topicId = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
        if ($topicId <= 0) exit_json(['success' => false, 'message' => 'Topic ID is missing.']);
        if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
             exit_json(['success' => false, 'message' => 'File upload error or no file provided. Error code: ' . ($_FILES['proof_file']['error'] ?? 'N/A')]);
        }
        
        $stmt_check = $mysqli->prepare("SELECT ct.id FROM course_topics ct JOIN course_chapters cc ON ct.chapter_id = cc.id JOIN courses c ON cc.course_id = c.id WHERE ct.id = ? AND c.user_id = ?");
        $stmt_check->bind_param("ii", $topicId, $userId);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            exit_json(['success' => false, 'message' => 'Permission denied. You do not own this topic.']);
        }
        $stmt_check->close();

        // File validation
        $uploadDir = '../../uploads/proofs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileExt = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg','jpeg','png','pdf'];
        if (!in_array($fileExt, $allowedExts)) exit_json(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
        if ($_FILES['proof_file']['size'] > 5 * 1024 * 1024) exit_json(['success' => false, 'message' => 'File is too large. The maximum size is 5MB.']);

        $newFileName = 'proof_' . $userId . '_' . $topicId . '_' . time() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        $webPath = 'uploads/proofs/' . $newFileName;

        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $destination)) {
            // REWRITTEN: Update both `proof_file_path` and `proof_path` columns.
            $sql_update = "UPDATE course_topics SET is_completed = 1, proof_file_path = ?, proof_path = ?, completed_at = NOW() WHERE id = ?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("ssi", $webPath, $webPath, $topicId); // Bind web path twice
            if ($stmt_update->execute()) {
                exit_json(['success' => true, 'message' => 'Proof uploaded and topic marked as complete!', 'file_path' => $webPath]);
            } else {
                unlink($destination); // Clean up orphaned file on DB error
                exit_json(['success' => false, 'message' => 'Database update failed: ' . $stmt_update->error]);
            }
        } else {
            exit_json(['success' => false, 'message' => 'Failed to save the uploaded file. Check server permissions.']);
        }
        break;

    // Cases for manually adding chapters/topics without AI
    case ACTION_ADD_CHAPTER:
    case ACTION_ADD_TOPIC:
        $mysqli->begin_transaction();
        try {
            if ($action === ACTION_ADD_CHAPTER) {
                $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
                $chapterName = trim($_POST['chapter_name'] ?? '');
                $estimatedTime = trim($_POST['estimated_time'] ?? '');
                if ($courseId <= 0 || empty($chapterName)) throw new Exception("Course ID and chapter name are required.");

                $stmt_verify = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND user_id = ?");
                $stmt_verify->bind_param("ii", $courseId, $userId);
                $stmt_verify->execute();
                if($stmt_verify->get_result()->num_rows === 0) throw new Exception("Permission denied.");
                $stmt_verify->close();

                $stmt_order = $mysqli->prepare("SELECT COALESCE(MAX(chapter_order), -1) + 1 as new_order FROM course_chapters WHERE course_id = ?");
                $stmt_order->bind_param("i", $courseId);
                $stmt_order->execute();
                $newOrder = (int) $stmt_order->get_result()->fetch_assoc()['new_order'];
                $stmt_order->close();
                
                $stmt_insert = $mysqli->prepare("INSERT INTO course_chapters (course_id, chapter_name, estimated_time, chapter_order) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("issi", $courseId, $chapterName, $estimatedTime, $newOrder);
                if (!$stmt_insert->execute()) throw new Exception('DB Error: ' . $stmt_insert->error);
                $newId = $mysqli->insert_id;
                $mysqli->commit();

                $timeMeta = !empty($estimatedTime) ? '<span class="chapter-meta"><i class="fas fa-clock"></i> ' . htmlspecialchars($estimatedTime) . '</span>' : '';
                $html = '<div class="chapter-item" data-chapter-id="' . $newId . '"><div class="chapter-header"><h4 class="chapter-title">' . htmlspecialchars($chapterName) . '</h4><div class="chapter-actions-group">' . $timeMeta . '<button class="icon-btn add-topic-btn" title="Add Topic"><i class="fas fa-plus"></i></button><button class="icon-btn delete-item-btn" data-type="chapter" data-id="' . $newId . '" title="Delete Chapter"><i class="fas fa-trash-alt"></i></button></div></div><ul class="topic-list"></ul></div>';
                exit_json(['success' => true, 'message' => 'Chapter added!', 'new_html' => $html]);

            } else { // add_topic
                $chapterId = isset($_POST['chapter_id']) ? (int)$_POST['chapter_id'] : 0;
                $topicName = trim($_POST['topic_name'] ?? '');
                if ($chapterId <= 0 || empty($topicName)) throw new Exception("Chapter ID and topic name are required.");

                $stmt_verify = $mysqli->prepare("SELECT cc.id FROM course_chapters cc JOIN courses c ON cc.course_id = c.id WHERE cc.id = ? AND c.user_id = ?");
                $stmt_verify->bind_param("ii", $chapterId, $userId);
                $stmt_verify->execute();
                if($stmt_verify->get_result()->num_rows === 0) throw new Exception("Permission denied.");
                $stmt_verify->close();
                
                $stmt_order = $mysqli->prepare("SELECT COALESCE(MAX(topic_order), -1) + 1 as new_order FROM course_topics WHERE chapter_id = ?");
                $stmt_order->bind_param("i", $chapterId);
                $stmt_order->execute();
                $newOrder = (int) $stmt_order->get_result()->fetch_assoc()['new_order'];
                $stmt_order->close();
                
                $stmt_insert = $mysqli->prepare("INSERT INTO course_topics (chapter_id, topic_name, topic_order) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("isi", $chapterId, $topicName, $newOrder);
                if (!$stmt_insert->execute()) throw new Exception('DB Error: ' . $stmt_insert->error);
                $newId = $mysqli->insert_id;
                $mysqli->commit();
              
                $html = '<li class="topic-item" data-topic-id="' . $newId . '"><input type="checkbox" class="topic-checkbox" title="Mark as complete"><span class="topic-title">' . htmlspecialchars($topicName) . '</span><div class="topic-timer"><button class="icon-btn timer-toggle-btn" title="Start/Stop Timer"><i class="fas fa-play"></i></button><span class="timer-display">' . format_seconds(0) . '</span></div><div class="proof-section"><button class="icon-btn upload-btn" title="Upload Proof"><i class="fas fa-upload"></i></button></div><button class="icon-btn delete-item-btn" data-type="topic" data-id="' . $newId . '" title="Delete Topic"><i class="fas fa-times"></i></button></li>';
                exit_json(['success' => true, 'message' => 'Topic added!', 'new_html' => $html]);
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            exit_json(['success' => false, 'message' => $e->getMessage()]);
        }
        break;


    default:
        exit_json(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}