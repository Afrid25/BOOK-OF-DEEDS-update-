<?php
// api/courses/upload_proof.php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connect.php';

function exit_json($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit_json(['success' => false, 'message' => 'Not authenticated.']);
}

$userId = $_SESSION['user_id'];
$topicId = $_POST['topic_id'] ?? 0;

// --- Basic Validation ---
if (empty($topicId) || !isset($_FILES['proof_file'])) {
    exit_json(['success' => false, 'message' => 'Missing data or file.']);
}

if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
    exit_json(['success' => false, 'message' => 'File upload error: ' . $_FILES['proof_file']['error']]);
}

// --- Security Check: Verify user owns the topic before uploading proof ---
$sql_check = "SELECT ct.id 
              FROM course_topics ct 
              JOIN course_chapters cc ON ct.chapter_id = cc.id 
              JOIN courses c ON cc.course_id = c.id 
              WHERE ct.id = ? AND c.user_id = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ii", $topicId, $userId);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    exit_json(['success' => false, 'message' => 'Permission denied.']);
}
$stmt_check->close();


// --- File Processing ---
$uploadDir = '../../uploads/proofs/'; // Relative to this script's location
$fileName = $_FILES['proof_file']['name'];
$fileTmpName = $_FILES['proof_file']['tmp_name'];
$fileSize = $_FILES['proof_file']['size'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Define allowed extensions and max size (e.g., 5MB)
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
$maxSize = 5 * 1024 * 1024;

if (!in_array($fileExt, $allowedExtensions)) {
    exit_json(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
}

if ($fileSize > $maxSize) {
    exit_json(['success' => false, 'message' => 'File size exceeds the 5MB limit.']);
}

// Create a unique, secure filename to prevent overwrites and directory traversal attacks
$newFileName = uniqid('proof_', true) . '.' . $fileExt;
$destination = $uploadDir . $newFileName;
$webPath = 'uploads/proofs/' . $newFileName; // The path to store in DB

if (move_uploaded_file($fileTmpName, $destination)) {
    // --- Update Database ---
    // The topic is marked as completed AND the file path is saved
    $sql_update = "UPDATE course_topics SET is_completed = 1, proof_file_path = ?, completed_at = NOW() WHERE id = ?";
    if ($stmt_update = $mysqli->prepare($sql_update)) {
        $stmt_update->bind_param("si", $webPath, $topicId);
        if ($stmt_update->execute()) {
            exit_json([
                'success' => true, 
                'message' => 'Proof uploaded and topic marked as complete!',
                'file_path' => $webPath
            ]);
        } else {
            // If DB update fails, delete the orphaned file to keep things clean
            unlink($destination);
            exit_json(['success' => false, 'message' => 'Database update failed after file upload.']);
        }
        $stmt_update->close();
    }
} else {
    exit_json(['success' => false, 'message' => 'Failed to move uploaded file. Check directory permissions.']);
}
$mysqli->close();
?>