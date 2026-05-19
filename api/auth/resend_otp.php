<?php
header('Content-Type: application/json');

define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/db_connect.php';
require_once ROOT_PATH . '/api/auth/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address provided.']);
    exit();
}

// Check if a pending user exists for this email
$sql = "SELECT email FROM pending_users WHERE email = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No pending signup was found for this email.']);
    exit();
}
$stmt->close();

// Generate a new OTP and its expiration time
$otp_code = strval(rand(100000, 999999));
$otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update the OTP in the database for the pending user
$sql_update = "UPDATE pending_users SET otp_code = ?, otp_expires_at = ? WHERE email = ?";
$stmt2 = $mysqli->prepare($sql_update);
$stmt2->bind_param('sss', $otp_code, $otp_expires_at, $email);

if ($stmt2->execute()) {
    // Send the new OTP via email
    $contentHtml = '<p>Your new One-Time Password (OTP) is:</p><div class="otp">' . $otp_code . '</div><p>This code is valid for 10 minutes. If you did not request this, please ignore this email.</p>';
    $emailBody = getPremiumEmailTemplate('Resend OTP - Book of Deeds', $contentHtml);
    sendPremiumMail($email, 'Your new OTP for Book of Deeds Signup', $emailBody);
    
    echo json_encode(['success' => true, 'message' => 'A new OTP has been sent.']);
} else {
    error_log("Failed to update OTP for " . $email . ": " . $stmt2->error);
    echo json_encode(['success' => false, 'message' => 'A server error occurred while updating the OTP.']);
}

$stmt2->close();
$mysqli->close();
exit();