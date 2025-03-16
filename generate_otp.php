<?php
date_default_timezone_set("Africa/Nairobi"); // Set to your region

session_start();
require_once "config/db.php";  // Remove "../"


if (!isset($_SESSION["user_id"])) {
    echo "Unauthorized access!";
    exit();
}

$user_id = $_SESSION["user_id"];
$otp = rand(100000, 999999); // Generate a random 6-digit OTP
$_SESSION["test_otp"] = $otp; // Store it in session for debugging

// Hash the OTP before storing it
$hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
$otp_expiry = time() + (10 * 60); // OTP expires in 10 minutes

// Update OTP in database
$sql = "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $hashed_otp, $otp_expiry, $user_id);

if ($stmt->execute()) {
    echo "✅ OTP stored successfully!<br>";
    echo "Generated OTP: " . $otp . "<br>";
    echo "Hashed OTP: " . $hashed_otp . "<br>";
    echo "Expiry Time: " . date("Y-m-d H:i:s", $otp_expiry) . "<br>";
} else {
    echo "❌ Error storing OTP!";
}

$stmt->close();
exit();
?>
