<?php
// --- CONFIGURATION AND BOOTSTRAP ---
define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/db_connect.php';
require_once ROOT_PATH . '/api/auth/mail_helper.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../forgot_password.php");
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
if (empty($email)) {
    header("location: ../../forgot_password.php?error=empty_email");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("location: ../../forgot_password.php?error=invalid_email");
    exit();
}
// Check if email exists in users
$sql_check = "SELECT id, user_name FROM users WHERE email = ?";
if (!($stmt_check = $mysqli->prepare($sql_check))) {
    error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    header("location: ../../forgot_password.php?error=db_error");
    exit();
}
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows === 0) {
    $stmt_check->close();
    header("location: ../../forgot_password.php?error=email_not_found");
    exit();
}
$stmt_check->bind_result($user_id, $user_name);
$stmt_check->fetch();
$stmt_check->close();

// Generate OTP
$otp_code = strval(rand(100000, 999999));
$otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
// Remove any previous OTP for this email
$mysqli->query("DELETE FROM password_reset_otps WHERE email = '" . $mysqli->real_escape_string($email) . "'");
$sql = "INSERT INTO password_reset_otps (email, otp_code, otp_expires_at) VALUES (?, ?, ?)";
if (!($stmt = $mysqli->prepare($sql))) {
    error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    header("location: ../../forgot_password.php?error=db_error");
    exit();
}
$stmt->bind_param("sss", $email, $otp_code, $otp_expires_at);
if ($stmt->execute()) {
    $stmt->close();
    $mysqli->close();
    // Send OTP email
    $contentHtml = '<p>Hello ' . htmlspecialchars($user_name) . ',<br>Your password reset OTP is:</p><div class="otp">' . $otp_code . '</div><p>This code is valid for 10 minutes. If you did not request this, please ignore this email.</p>';
    $emailBody = getPremiumEmailTemplate('Password Reset OTP - Book of Deeds', $contentHtml);
    sendPremiumMail($email, 'Your OTP for Book of Deeds Password Reset', $emailBody);
    header("location: ../../reset_password.php?email=" . urlencode($email));
    exit();
} else {
    $stmt->close();
    $mysqli->close();
    error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    header("location: ../../forgot_password.php?error=db_error");
    exit();
}
