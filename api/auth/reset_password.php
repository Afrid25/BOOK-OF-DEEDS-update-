<?php
// --- CONFIGURATION AND BOOTSTRAP ---
define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../reset_password.php");
    exit();
}

// --- FORM DATA ---
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$otp_code = trim($_POST['otp_code'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// This script handles two stages: OTP verification and password setting.
// Error redirection helper
function redirect_with_error($email, $error_code) {
    header("location: ../../reset_password.php?email=" . urlencode($email) . "&error=" . $error_code);
    exit();
}

if (empty($email) || empty($otp_code)) {
    redirect_with_error($email, 'empty_fields');
}

// --- VERIFY OTP ---
// Check against the 'password_reset_otps' table
$sql = "SELECT id FROM password_reset_otps WHERE email = ? AND otp_code = ? AND otp_expires_at >= NOW() AND used = 0";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    redirect_with_error($email, 'db_error');
}
$stmt->bind_param("ss", $email, $otp_code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $otp_id = $row['id'];
    $stmt->close();
    
    // CASE 1: Password fields are present, so reset the password
    if (!empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            redirect_with_error($email, 'password_mismatch');
        }
        if (strlen($password) < 8) {
            redirect_with_error($email, 'weak_password');
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the password in the main 'users' table
        $sql_update = "UPDATE users SET password_hash = ? WHERE email = ?";
        $stmt2 = $mysqli->prepare($sql_update);
        if (!$stmt2) {
             error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
             redirect_with_error($email, 'db_error');
        }
        
        $stmt2->bind_param("ss", $hashed_password, $email);
        if ($stmt2->execute()) {
            $stmt2->close();
            // Mark OTP as used to prevent reuse
            $mysqli->query("UPDATE password_reset_otps SET used = 1 WHERE id = " . intval($otp_id));
            $mysqli->close();
            // Redirect to login page with a success message
            header("location: ../../index.php?reset=success");
            exit();
        } else {
            error_log("Execute failed: (" . $stmt2->errno . ") " . $stmt2->error);
            $stmt2->close();
            $mysqli->close();
            redirect_with_error($email, 'db_error');
        }
    } else {
        // CASE 2: Only OTP was submitted. It's valid, so show the password reset form.
        // This redirect tells the frontend to advance to the next step.
        header("location: ../../reset_password.php?email=" . urlencode($email) . "&step=password");
        exit();
    }
} else {
    // OTP is invalid, expired, or already used
    $stmt->close();
    $mysqli->close();
    redirect_with_error($email, 'invalid_otp');
}