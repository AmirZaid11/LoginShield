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
$subject = "New OTP Code";
$message = "Your new OTP is: <strong>$otp</strong>. It expires in 10 minutes.";
sendEmail($email, $subject, $message);

// ✅ Redirect back to OTP verification
$_SESSION["message"] = "A new OTP has been sent to your email.";
header("Location: verify_otp.php");
exit();
?>
