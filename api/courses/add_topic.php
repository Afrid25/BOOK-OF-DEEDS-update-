<?php
// api/courses/add_topic.php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

// A helper function to avoid repetition
function exit_json($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit_json(['success' => false, 'message' => 'Not authenticated.']);
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$chapterId = $data['chapter_id'] ?? 0;
$topicName = trim($data['topic_name'] ?? '');

if (empty($chapterId) || empty($topicName)) {
    exit_json(['success' => false, 'message' => 'Chapter ID and topic name are required.']);
}

// --- Security Check: Verify the user owns the parent chapter before adding a topic ---
$sql_check = "SELECT cc.id 
              FROM course_chapters cc 
              JOIN courses c ON cc.course_id = c.id 
              WHERE cc.id = ? AND c.user_id = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ii", $chapterId, $userId);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    exit_json(['success' => false, 'message' => 'Permission denied.']);
}
$stmt_check->close();


// --- Get the next topic order ---
$sql_order = "SELECT MAX(topic_order) AS max_order FROM course_topics WHERE chapter_id = ?";
$stmt_order = $mysqli->prepare($sql_order);
$stmt_order->bind_param("i", $chapterId);
$stmt_order->execute();
$result_order = $stmt_order->get_result()->fetch_assoc();
$newOrder = ($result_order['max_order'] ?? 0) + 1;
$stmt_order->close();


// --- Insert the new topic ---
$sql_insert = "INSERT INTO course_topics (chapter_id, topic_name, topic_order) VALUES (?, ?, ?)";
if ($stmt_insert = $mysqli->prepare($sql_insert)) {
    $stmt_insert->bind_param("isi", $chapterId, $topicName, $newOrder);
    if ($stmt_insert->execute()) {
        $newTopicId = $mysqli->insert_id;
        exit_json([
            'success' => true, 
            'message' => 'Topic added successfully!',
            'new_topic_id' => $newTopicId
        ]);
    } else {
        exit_json(['success' => false, 'message' => 'Failed to add topic.']);
    }
    $stmt_insert->close();
} else {
    exit_json(['success' => false, 'message' => 'Database error.']);
}
$mysqli->close();
?>