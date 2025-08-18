<?php
// 1. Start session and check for authentication
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // If not logged in, send an unauthorized response
    http_response_code(401);
    exit("Unauthorized");
}

// 2. Include DB connection and define constants
require_once '../../includes/db_connect.php';
define('UPLOAD_DIR', '../../uploads/profile_pictures/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// 3. Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];

    // *** FIX: Read 'user_name' from $_POST as sent by the form ***
    $user_name = trim($_POST['user_name']);
    
    // Sanitize and retrieve other text data
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : null;
    $blood_group = isset($_POST['blood_group']) ? trim($_POST['blood_group']) : null;
    $institution = isset($_POST['institution']) ? trim($_POST['institution']) : null;
    $department = isset($_POST['department']) ? trim($_POST['department']) : null;
    
    // --- Profile Picture Update Logic ---
    $new_picture_path = null;

    // Check if a new file has been uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            header("location: ../../edit-profile.php?error=file_too_large");
            exit();
        }

        // Validate file type
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowed)) {
            header("location: ../../edit-profile.php?error=invalid_file_type");
            exit();
        }

        // Create a unique filename and move the file
        $newFileName = uniqid('', true) . "." . $fileExt;
        $fileDestination = UPLOAD_DIR . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
            $new_picture_path = 'uploads/profile_pictures/' . $newFileName;
            // Optional: Delete the old profile picture from the server to save space
            // You would need to fetch the old path from DB first before updating
        }
    }

    // --- Database Update ---
    // Prepare the SQL query. We only update the profile picture if a new one was uploaded.
    if ($new_picture_path !== null) {
        $sql = "UPDATE users SET user_name = ?, profile_picture_path = ?, gender = ?, blood_group = ?, institution = ?, department = ? WHERE id = ?";
    } else {
        $sql = "UPDATE users SET user_name = ?, gender = ?, blood_group = ?, institution = ?, department = ? WHERE id = ?";
    }

    if ($stmt = $mysqli->prepare($sql)) {
        if ($new_picture_path !== null) {
            $stmt->bind_param("ssssssi", $user_name, $new_picture_path, $gender, $blood_group, $institution, $department, $userId);
        } else {
            $stmt->bind_param("sssssi", $user_name, $gender, $blood_group, $institution, $department, $userId);
        }

        if ($stmt->execute()) {
            // Update the name in the session if it was changed
            $_SESSION['user_name'] = $user_name;
            if ($new_picture_path !== null) {
                $_SESSION['profile_picture_path'] = $new_picture_path;
            }
            header("location: ../../edit-profile.php?success=1");
        } else {
            header("location: ../../edit-profile.php?error=db_error");
        }
        $stmt->close();
    } else {
        header("location: ../../edit-profile.php?error=db_error");
    }
    $mysqli->close();
    exit();
}
?>