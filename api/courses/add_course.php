<?php
// api/courses/add_course.php
session_start();
header('Content-Type: application/json');

// --- DEPENDENCIES ---
require_once '../../includes/db_connect.php';
require_once '../helpers/ai_parser.php';

// --- AUTHENTICATION ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// --- INPUT VALIDATION ---
$userId = $_SESSION['user_id'];
$courseName = trim($_POST['course_name'] ?? '');
$syllabus = trim($_POST['syllabus'] ?? '');

if (empty($courseName) || empty($syllabus)) {
    echo json_encode(['success' => false, 'message' => 'Course name and syllabus cannot be empty.']);
    exit;
}

// --- AI PROCESSING ---
$aiResult = parseSyllabusWithAI($syllabus);
if (!$aiResult['success']) {
    // Pass the AI's error message directly to the user
    echo json_encode(['success' => false, 'message' => $aiResult['message']]);
    exit;
}
$structuredCurriculum = $aiResult['data'];

// Check if the AI returned a valid, non-empty array
if (empty($structuredCurriculum) || !is_array($structuredCurriculum)) {
    echo json_encode(['success' => false, 'message' => 'The AI could not structure the syllabus. Please try rephrasing it.']);
    exit;
}


// --- DATABASE TRANSACTION ---
$mysqli->begin_transaction();

try {
    // --- Step 1: Insert the main course ---
    $sql_course = "INSERT INTO courses (user_id, course_name, syllabus) VALUES (?, ?, ?)";
    $stmt_course = $mysqli->prepare($sql_course);
    $stmt_course->bind_param("iss", $userId, $courseName, $syllabus);
    $stmt_course->execute();
    $courseId = $mysqli->insert_id;
    $stmt_course->close();

    // --- Step 2: Prepare statements ONCE before the loops for efficiency ---
    $sql_chapter = "INSERT INTO course_chapters (course_id, chapter_name, chapter_order, estimated_time) VALUES (?, ?, ?, ?)";
    $stmt_chapter = $mysqli->prepare($sql_chapter);

    $sql_topic = "INSERT INTO course_topics (chapter_id, topic_name, topic_order) VALUES (?, ?, ?)";
    $stmt_topic = $mysqli->prepare($sql_topic);
    
    // --- Step 3: Loop through structured data and insert chapters/topics ---
    $chapter_order = 1;
    foreach ($structuredCurriculum as $chapterData) {
        // Robustness: Skip if chapter name is missing or not a string
        if (empty($chapterData['chapter_name']) || !is_string($chapterData['chapter_name'])) {
            continue; 
        }
        $chapterName = trim($chapterData['chapter_name']);

        // Bind and execute the pre-prepared chapter statement
        $estimatedTime = $chapterData['estimated_time'] ?? 'N/A'; // Get the new field
$stmt_chapter->bind_param("isis", $courseId, $chapterName, $chapter_order, $estimatedTime);
        $stmt_chapter->execute();
        $newChapterId = $mysqli->insert_id; // Use a distinct variable name

        $topic_order = 1;
        // Check if topics exist and is an array
        if (isset($chapterData['topics']) && is_array($chapterData['topics'])) {
            foreach ($chapterData['topics'] as $topicName) {
                // Robustness: Skip if topic name is empty or not a string
                $topicName = trim($topicName);
                if (empty($topicName) || !is_string($topicName)) {
                    continue;
                }
                
                // Bind and execute the pre-prepared topic statement
                $stmt_topic->bind_param("isi", $newChapterId, $topicName, $topic_order);
                $stmt_topic->execute();
                $topic_order++;
            }
        }
        $chapter_order++;
    }

    // --- Step 4: Close the prepared statements ---
    $stmt_chapter->close();
    $stmt_topic->close();

    // If everything succeeded, commit the changes
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Course and syllabus added successfully!']);

} catch (Exception $e) {
    // If any part of the try block fails, rollback all changes
    $mysqli->rollback();
    // Log the actual error for debugging, but show a generic message to the user
    error_log("DB transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Could not save the course structure.']);
} finally {
    // This block ensures the connection is always closed
    $mysqli->close();
}
?>