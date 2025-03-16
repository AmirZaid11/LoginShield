<?php
session_start();
require_once "../config/db.php";
require_once "../email/send_email.php";

date_default_timezone_set("Africa/Nairobi");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);

    // ✅ Check if user exists
    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // ✅ Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION["raw_otp"] = $otp; // Store raw OTP for debugging (REMOVE IN PRODUCTION)
            
            // ✅ Hash OTP for security
            $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expiry = time() + (10 * 60); // 10 minutes validity

            // ✅ Update OTP in database
            $updateSQL = "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSQL);
            $updateStmt->bind_param("sii", $hashed_otp, $otp_expiry, $id);

            if ($updateStmt->execute()) {
                echo "✅ Database updated successfully!<br>";
                echo "New OTP (Raw): " . $otp . "<br>"; // Debugging (REMOVE IN PRODUCTION)
                echo "New Hashed OTP: " . $hashed_otp . "<br>";
                echo "OTP Expiry: " . date("Y-m-d H:i:s", $otp_expiry) . "<br>";

                // ✅ Send OTP via Email
                $subject = "Your OTP Code";
                $message = "Hello $username,<br>Your OTP code is: <strong>$otp</strong>.<br>It expires in 10 minutes.";
                sendEmail($email, $subject, $message);

                // ✅ Redirect to OTP verification page
                $_SESSION["user_id"] = $id;
                $_SESSION["email"] = $email;
                header("Location: verify_otp.php");
                exit();
            } else {
                echo "❌ Error updating OTP in database: " . $conn->error . "<br>";
            }
        } else {
            $_SESSION["error"] = "❌ Invalid email or password.";
        }
    } else {
        $_SESSION["error"] = "❌ No account found with this email.";
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - LoginShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-2xl font-bold text-center text-gray-700 mb-4">Login to Your Account</h2>

        <!-- Error Message -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 text-sm">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="text-sm text-gray-600">Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required 
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="text-sm text-gray-600">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required 
                    class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">Login</button>
        </form>

        <!-- Register Button -->
        <div class="text-center mt-4">
            <p class="text-gray-600">Don't have an account?</p>
            <a href="register.php" class="text-blue-500 hover:underline">Register Here</a>
        </div>
    </div>
</body>
</html>
