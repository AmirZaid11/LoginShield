<?php
date_default_timezone_set("Africa/Nairobi"); // Set to your region

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

function sendEmail($recipientEmail, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Use Gmail SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'eddysimba9@gmail.com';  // Your Gmail address
        $mail->Password = 'oywiqscznrenpmzi';  // Use App Password (Not Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        
        // Email Headers
        $mail->setFrom('your_email@gmail.com', 'LoginShield'); // Sender Email
        $mail->addAddress($recipientEmail); // Recipient Email
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(true);

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Email Error: " . $mail->ErrorInfo;
    }
}
?>

