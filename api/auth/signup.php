<?php
header('Content-Type: application/json');

// --- CONFIGURATION AND BOOTSTRAP ---
define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/includes/db_connect.php';
require_once ROOT_PATH . '/api/auth/mail_helper.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Redirect non-POST requests back to the form
    header("location: ../../signup.php");
    exit();
}

// --- DEFINE CONSTANTS ---
define('UPLOAD_DIR', ROOT_PATH . '/uploads/profile_pictures/');
define('UPLOAD_URL', 'uploads/profile_pictures/'); // Web-accessible path
define('DEFAULT_MALE_AVATAR', 'assets/images/avatars/male.png');
define('DEFAULT_FEMALE_AVATAR', 'assets/images/avatars/female.png');
define('DEFAULT_AVATAR', 'assets/images/avatars/default.png');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// --- FORM DATA PROCESSING ---
$user_name = trim($_POST['user_name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$gender = trim($_POST['gender'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$year = trim($_POST['year'] ?? '');
$policy = isset($_POST['policy']);

// FIXED: Capture all new fields from the form
$bio = trim($_POST['bio'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$academic_session = trim($_POST['academic_session'] ?? '');

// FIXED: Handle manual institution/department entry
$institution = trim($_POST['institution'] ?? '');
$manual_institution = trim($_POST['manual_institution'] ?? '');
if (!empty($manual_institution)) {
    $institution = $manual_institution; // Override with manual entry
}

$department = trim($_POST['department'] ?? '');
$manual_department = trim($_POST['manual_department'] ?? '');
if (!empty($manual_department)) {
    $department = $manual_department; // Override with manual entry
}

// --- VALIDATION ---
if (empty($user_name) || empty($email) || empty($gender) || empty($blood_group) || empty($year) || !$policy) {
    header("location: ../../signup.php?error=empty_fields");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("location: ../../signup.php?error=invalid_email");
    exit();
}

// Check if email already exists in users table
$stmt_check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    $stmt_check->close();
    header("location: ../../signup.php?error=email_exists");
    exit();
}
$stmt_check->close();

// --- PROFILE PICTURE LOGIC ---
$profile_picture_path = '';
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    if ($file['size'] > MAX_FILE_SIZE) {
        header("location: ../../signup.php?error=file_too_large");
        exit();
    }
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExt, $allowed)) {
        header("location: ../../signup.php?error=invalid_file_type");
        exit();
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        error_log("Upload directory does not exist and could not be created: " . UPLOAD_DIR);
        header("location: ../../signup.php?error=file_error_config");
        exit();
    }
    $newFileName = uniqid('', true) . "." . $fileExt;
    $fileDestination = UPLOAD_DIR . $newFileName;
    if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
        $profile_picture_path = UPLOAD_URL . $newFileName;
    } else {
        header("location: ../../signup.php?error=file_error_move");
        exit();
    }
} else {
    // Set default avatar based on gender
    $profile_picture_path = ($gender === 'male') ? DEFAULT_MALE_AVATAR : (($gender === 'female') ? DEFAULT_FEMALE_AVATAR : DEFAULT_AVATAR);
}

// --- OTP GENERATION ---
$otp_code = strval(rand(100000, 999999));
$otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Remove any previous pending signup for this email to avoid duplicates
$stmt_delete = $mysqli->prepare("DELETE FROM pending_users WHERE email = ?");
$stmt_delete->bind_param("s", $email);
$stmt_delete->execute();
$stmt_delete->close();

// --- STORE PENDING USER DATA IN DATABASE ---
// FIXED: Added all new columns to the SQL query and bind_param
$sql = "INSERT INTO pending_users (
            user_name, email, gender, blood_group, year, institution, department, 
            profile_picture_path, otp_code, otp_expires_at, bio, student_id, academic_session
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    header("location: ../../signup.php?error=db_error");
    exit();
}

// FIXED: Updated bind_param to match all 13 columns
$stmt->bind_param("sssssssssssss",
    $user_name, $email, $gender, $blood_group, $year, $institution, $department,
    $profile_picture_path, $otp_code, $otp_expires_at, $bio, $student_id, $academic_session
);

if ($stmt->execute()) {
    $stmt->close();
    $mysqli->close();
    
    // --- SEND OTP EMAIL ---
    $contentHtml = '<p>Thank you for signing up!<br>Your One-Time Password (OTP) is:</p><div class="otp">' . $otp_code . '</div><p>This code is valid for 10 minutes. If you did not request this, please ignore this email.</p>';
    $emailBody = getPremiumEmailTemplate('Verify Your Email - Book of Deeds', $contentHtml);
    sendPremiumMail($email, 'Your OTP for Book of Deeds Signup', $emailBody);
    
    // Redirect to the OTP verification step
    header("location: ../../signup.php?action=verify_otp&email=" . urlencode($email));
    exit();
} else {
    error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    $stmt->close();
    $mysqli->close();
    header("location: ../../signup.php?error=db_error");
    exit();
}