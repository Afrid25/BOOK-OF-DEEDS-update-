<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__, 2) . '/PHPMailer/src/Exception.php';
require_once dirname(__DIR__, 2) . '/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__, 2) . '/PHPMailer/src/SMTP.php';

function sendPremiumMail($to, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bookofdeeds25@gmail.com';   // SMTP username
        $mail->Password   = 'pqwkzecemgjasutb';     // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('bookofdeeds25@gmail.com', 'Book of Deeds');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}

function getPremiumEmailTemplate($title, $contentHtml) {
    $logoUrl = 'https://yourdomain.com/assets/images/logo.png'; // Update with your logo URL
    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title) . '</title>
  <style>
    body { background: #f4f6fb; font-family: Arial, sans-serif; margin: 0; padding: 0; }
    .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; }
    .logo { text-align: center; margin-bottom: 24px; }
    .logo img { max-width: 120px; }
    h1 { color: #2d3748; font-size: 1.5rem; margin-bottom: 16px; }
    .content { color: #444; font-size: 1rem; line-height: 1.6; }
    .otp { font-size: 2rem; color: #2563eb; letter-spacing: 6px; font-weight: bold; margin: 24px 0; text-align: center; }
    .footer { margin-top: 32px; color: #888; font-size: 0.9rem; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo"><img src="' . $logoUrl . '" alt="Book of Deeds Logo"></div>
    <h1>' . htmlspecialchars($title) . '</h1>
    <div class="content">' . $contentHtml . '</div>
    <div class="footer">&copy; ' . date('Y') . ' Book of Deeds. All rights reserved.</div>
  </div>
</body>
</html>';
}
