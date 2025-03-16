<?php
session_start();
require_once "../config/db.php";
require_once "../email/send_email.php";

date_default_timezone_set("Africa/Nairobi"); // Set timezone

if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "Unauthorized access.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$email = $_SESSION["email"];

// ✅ Generate new OTP
$otp = rand(100000, 999999);
$_SESSION["otp"] = $otp;

// ✅ Hash the new OTP
$hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
$otp_expiry = time() + (10 * 60);

// ✅ Update OTP in the database
$updateSQL = "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?";
$updateStmt = $conn->prepare($updateSQL);
$updateStmt->bind_param("sii", $hashed_otp, $otp_expiry, $user_id);
$updateStmt->execute();

// ✅ Send the new OTP via Email
$subject = "Your New OTP Code - LoginShield";
$message = '
<html>
<body style="font-family: Arial, sans-serif; background-color: #000; color: #fff; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #111; border-radius: 12px; border: 1px solid #0f0; box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);">
        <!-- Header -->
        <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #0f0;">
            <h1 style="color: #0f0; font-size: 24px; margin: 0;">LoginShield</h1>
            <p style="font-size: 16px; color: #0f0; margin: 5px 0 0;">Secure Authentication Made Simple</p>
        </div>

        <!-- Content -->
        <div style="padding: 20px 0;">
            <h2 style="color: #0f0; font-size: 20px; margin: 0 0 10px;">Hello ' . $username . ',</h2>
            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 20px; color: #fff;">Your new One-Time Password (OTP) for LoginShield is ready. Use the code below to securely log in to your account:</p>
            
            <!-- OTP Code -->
            <div style="font-size: 32px; font-weight: bold; color: #0f0; text-align: center; margin: 20px 0; padding: 20px; background-color: #000; border: 2px solid #0f0; border-radius: 12px; box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);">
                ' . $otp . '
            </div>

            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 10px; color: #fff;">This OTP is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
            <p style="font-size: 16px; line-height: 1.6; margin: 0 0 10px; color: #fff;">If you didn\'t request this OTP, please ignore this email or contact our support team immediately.</p>
        </div>

        <!-- Footer -->
        <div style="text-align: center; padding-top: 20px; border-top: 1px solid #0f0; font-size: 14px; color: #0f0;">
            <p>Best regards,</p>
            <p><strong>Ernest</strong></p>
            <p>Developer of LoginShield</p>
            <p>Website designed to improve authentication and keep your accounts secure.</p>
        </div>
    </div>
</body>
</html>
';

sendEmail($email, $subject, $message);

// ✅ Redirect back to OTP verification
$_SESSION["message"] = "A new OTP has been sent to your email.";
header("Location: verify_otp.php");
exit();
?>
