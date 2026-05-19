<?php
// --- CONFIGURATION AND BOOTSTRAP ---
define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../signup.php");
    exit();
}

// --- FORM DATA ---
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$otp_code = trim($_POST['otp_code'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if (empty($email) || empty($otp_code)) {
    header("location: ../../signup.php?action=verify_otp&email=" . urlencode($email) . "&error=empty_fields");
    exit();
}

// --- VERIFY OTP ---
// FIXED: Select all necessary columns to transfer them to the 'users' table later
$sql = "SELECT * FROM pending_users WHERE email = ? AND otp_code = ? AND otp_expires_at >= NOW()";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ss", $email, $otp_code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $stmt->close();
    
    // CASE 1: Password fields are present, so finalize the registration
    if (!empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            header("location: ../../signup.php?action=set_password&email=" . urlencode($email) . "&otp=" . $otp_code . "&error=password_mismatch");
            exit();
        }
        if (strlen($password) < 8) {
            header("location: ../../signup.php?action=set_password&email=" . urlencode($email) . "&otp=" . $otp_code . "&error=weak_password");
            exit();
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // FIXED: Added all new columns to the insert statement for the 'users' table
        $sql_insert = "INSERT INTO users (
                            user_name, email, password_hash, profile_picture_path, 
                            gender, blood_group, institution, department, bio, 
                            student_id, academic_session, year
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt2 = $mysqli->prepare($sql_insert);
        if (!$stmt2) {
             error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
             header("location: ../../signup.php?action=set_password&email=" . urlencode($email) . "&error=db_error");
             exit();
        }
        
        // FIXED: Bind all 12 parameters for the new user record
        $stmt2->bind_param("ssssssssssss",
            $row['user_name'], $row['email'], $hashed_password, $row['profile_picture_path'],
            $row['gender'], $row['blood_group'], $row['institution'], $row['department'],
            $row['bio'], $row['student_id'], $row['academic_session'], $row['year']
        );

        if ($stmt2->execute()) {
            $new_user_id = $stmt2->insert_id;
            $stmt2->close();
            
            // Cleanup: Remove from pending_users
            $mysqli->query("DELETE FROM pending_users WHERE email = '" . $mysqli->real_escape_string($email) . "'");
            
            // Auto-login the user
            session_regenerate_id(true);
            $_SESSION["loggedin"] = true;
            $_SESSION["user_id"] = $new_user_id;
            $_SESSION["user_name"] = $row['user_name'];
            
            $mysqli->close();
            header("location: ../../feed.php?signup=success");
            exit();
        } else {
            error_log("Execute failed: (" . $stmt2->errno . ") " . $stmt2->error);
            $stmt2->close();
            $mysqli->close();
            header("location: ../../signup.php?action=set_password&email=" . urlencode($email) . "&error=db_error");
            exit();
        }
    } else {
        // CASE 2: Only OTP was submitted. It's valid, so redirect to the password creation form.
        // We pass the OTP along to re-validate it in the next step.
        header("location: ../../signup.php?action=set_password&email=" . urlencode($email) . "&otp=" . urlencode($otp_code));
        exit();
    }
} else {
    // OTP is invalid or expired
    $stmt->close();
    $mysqli->close();
    header("location: ../../signup.php?action=verify_otp&email=" . urlencode($email) . "&error=invalid_otp");
    exit();
}