<?php
date_default_timezone_set("Africa/Nairobi"); // Set to your region
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST["otp"]); // User input
    $user_id = $_SESSION["user_id"] ?? null;

    // Debug: Log user ID and entered OTP
    error_log("User ID: $user_id, Entered OTP: $entered_otp");

    if (!$user_id) {
        $_SESSION["error"] = "❌ Session expired. Please log in again.";
        header("Location: login.php");
        exit();
    }

    // ✅ Get stored hashed OTP from database
    $sql = "SELECT otp, otp_expiry FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare SQL statement: " . $conn->error);
        $_SESSION["error"] = "❌ Database error. Please try again.";
        header("Location: verify_otp.php");
        exit();
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_otp, $otp_expiry);
        $stmt->fetch();

        // Debug: Log hashed OTP and expiry time
        error_log("Hashed OTP: $hashed_otp, OTP Expiry: $otp_expiry");

        // ✅ Check if OTP is expired
        if (time() > $otp_expiry) {
            $_SESSION["error"] = "❌ OTP has expired. A new OTP has been sent to your email.";

            // Auto-generate new OTP and send it
            $otp = rand(100000, 999999);
            $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
            $new_expiry = time() + (10 * 60); // 10 minutes validity

            // Debug: Log new OTP and expiry
            error_log("New OTP: $otp, New Expiry: $new_expiry");

            // ✅ Update OTP in database
            $updateSQL = "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSQL);
            if (!$updateStmt) {
                error_log("Failed to prepare update SQL statement: " . $conn->error);
                $_SESSION["error"] = "❌ Database error. Please try again.";
                header("Location: verify_otp.php");
                exit();
            }

            $updateStmt->bind_param("sii", $hashed_otp, $new_expiry, $user_id);

            if ($updateStmt->execute()) {
                $_SESSION["success"] = "✅ A new OTP has been sent to your email.";

                // ✅ Send OTP via Email (ensure `sendEmail` function is correct)
                require_once "../email/send_email.php";
                $email = $_SESSION["email"];
                $subject = "Your New OTP Code";
                $message = "Hello,<br>Your new OTP code is: <strong>$otp</strong>.<br>It expires in 10 minutes.";

                if (sendEmail($email, $subject, $message)) {
                    error_log("New OTP sent to email: $email");
                } else {
                    error_log("Failed to send email to: $email");
                }
            } else {
                error_log("Failed to update OTP in database for user ID: $user_id");
                $_SESSION["error"] = "❌ Failed to generate new OTP. Try again.";
            }

            header("Location: verify_otp.php");
            exit();
        }

        // ✅ Verify OTP
        if (password_verify($entered_otp, $hashed_otp)) {
            error_log("OTP verification successful for user ID: $user_id");
            $_SESSION["success"] = "✅ OTP Verified Successfully!";
            header("Location: ../dashboard/index.php"); // Redirect to secure page
            exit();
        } else {
            error_log("OTP verification failed for user ID: $user_id. Entered OTP: $entered_otp, Hashed OTP: $hashed_otp");
            $_SESSION["error"] = "❌ Invalid OTP. Try again.";
            header("Location: verify_otp.php");
            exit();
        }
    } else {
        error_log("User not found in database for user ID: $user_id");
        $_SESSION["error"] = "❌ User not found.";
        header("Location: verify_otp.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP - ANONYMOUSWORLDKE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Glass Morphism Effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Dark Mode Styles */
        .dark { background-color: #0a0a0a; color: #e0e0e0; }
        .dark .glass { background: rgba(26, 26, 26, 0.8); border: 1px solid rgba(0, 255, 0, 0.3); }
        .dark .text-gray-700 { color: #e0e0e0; }
        .dark .bg-blue-500 { background-color: #0f0; color: #000; } /* Neon Green for Buttons */
        .dark .bg-blue-500:hover { background-color: #0a0; } /* Darker Green on Hover */
        .dark .bg-gray-500 { background-color: #333; } /* Gray for Resend Button */
        .dark .bg-gray-500:hover { background-color: #444; } /* Lighter Gray on Hover */
        .dark .border-gray-300 { border-color: #444; } /* Border Color for Input */
        .dark .focus\:ring-blue-500:focus { --tw-ring-color: rgba(0, 255, 0, 0.5); } /* Neon Green Focus Ring */
    </style>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100 dark">
    <!-- Form Container -->
    <div class="w-full max-w-md p-6 glass rounded-lg">
        <h2 class="text-2xl font-bold text-center text-gray-700 dark:text-white mb-4">Verify OTP</h2>

        <!-- Error Message -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 text-sm dark:bg-red-900 dark:border-red-700 dark:text-red-300">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mb-4 text-sm dark:bg-green-900 dark:border-green-700 dark:text-green-300">
                <?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?>
            </div>
        <?php endif; ?>

        <!-- OTP Verification Form -->
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">Enter OTP</label>
                <input type="text" name="otp" placeholder="Enter your OTP" required 
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
            </div>

            <!-- Verify OTP Button -->
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">
                Verify OTP
            </button>
        </form>

        <!-- Resend OTP Button -->
        <form action="resend_otp.php" method="POST" class="mt-4">
            <button type="submit" class="w-full bg-gray-500 text-white py-2 rounded-md hover:bg-gray-600">
                Resend OTP
            </button>
        </form>
    </div>
</body>
</html>