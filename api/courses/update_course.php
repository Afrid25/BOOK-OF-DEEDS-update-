<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';
require_once '../helpers/ai_parser.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$userId = $_SESSION['user_id'];
$courseId = $_POST['course_id'] ?? 0;
$courseName = trim($_POST['course_name'] ?? '');
$syllabus = trim($_POST['syllabus'] ?? '');

if (empty($courseId) || empty($courseName) || empty($syllabus)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// 1. Call AI to parse the new syllabus
$aiResult = parseSyllabusWithAI($syllabus);
if (!$aiResult['success']) {
    echo json_encode(['success' => false, 'message' => "AI Error: " . $aiResult['message']]);
    exit;
}
$structuredCurriculum = $aiResult['data'];
if (empty($structuredCurriculum) || !is_array($structuredCurriculum)) {
    echo json_encode(['success' => false, 'message' => 'AI could not structure the syllabus. Please try rephrasing it.']);
    exit;
}

// 2. Database transaction
$mysqli->begin_transaction();
try {
    // Security: Verify user owns the course before doing anything
    $stmt_verify = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $courseId, $userId);
    $stmt_verify->execute();
    if ($stmt_verify->get_result()->num_rows === 0) {
        throw new Exception("Permission denied. You do not own this course.");
    }
    $stmt_verify->close();

    // Update course name and syllabus
    $stmt_update = $mysqli->prepare("UPDATE courses SET course_name = ?, syllabus = ? WHERE id = ?");
    $stmt_update->bind_param("ssi", $courseName, $syllabus, $courseId);
    $stmt_update->execute();
    $stmt_update->close();

    // Delete old chapters and topics. The ON DELETE CASCADE in your SQL will handle topics automatically.
    $stmt_delete = $mysqli->prepare("DELETE FROM course_chapters WHERE course_id = ?");
    $stmt_delete->bind_param("i", $courseId);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // --- THIS IS THE CRITICAL LOGIC THAT WAS MISSING ---
    // Prepare statements ONCE before the loops for efficiency
    $sql_chapter = "INSERT INTO course_chapters (course_id, chapter_name, chapter_order, estimated_time) VALUES (?, ?, ?, ?)";
    $stmt_chapter = $mysqli->prepare($sql_chapter);
    $sql_topic = "INSERT INTO course_topics (chapter_id, topic_name, topic_order) VALUES (?, ?, ?)";
    $stmt_topic = $mysqli->prepare($sql_topic);
    
    $chapter_order = 1;
    foreach ($structuredCurriculum as $chapterData) {
        if (empty($chapterData['chapter_name'])) continue;
        $chapterName = trim($chapterData['chapter_name']);
        
        $estimatedTime = $chapterData['estimated_time'] ?? 'N/A'; // Get the new field
$stmt_chapter->bind_param("isis", $courseId, $chapterName, $chapter_order, $estimatedTime);
        $stmt_chapter->execute();
        $newChapterId = $mysqli->insert_id;

       $topic_order = 1;
if (isset($chapterData['topics']) && is_array($chapterData['topics'])) {
    foreach ($chapterData['topics'] as $topicName) {
        $trimmedTopicName = trim($topicName);
        if (empty($trimmedTopicName)) continue;
        $stmt_topic->bind_param("isi", $newChapterId, $trimmedTopicName, $topic_order);
        $stmt_topic->execute();
        $topic_order++;
    }

        }
        $chapter_order++;
    }
    $stmt_chapter->close();
    $stmt_topic->close();
    // --- END OF CRITICAL LOGIC ---

    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Course updated successfully! The page will now reload.']);

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("Update course failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during update.']);
}
$mysqli->close();
?>