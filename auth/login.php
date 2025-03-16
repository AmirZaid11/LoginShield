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
            // Send email
                            $subject = "Your OTP Code";
                            $message = '
                            <html>
                            <body style="font-family: Arial, sans-serif; background-color: #000; color: #fff; margin: 0; padding: 0;">
                                <div style="max-width: 600px; margin: 20px auto; padding: 20px; background-color: #111; border-radius: 12px; border: 1px solid #0f0; box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);">
                                    <!-- Header -->
                                    <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #0f0;">
                                        <h1 style="color: #0f0; font-size: 28px; margin: 0; font-weight: bold;">LoginShield</h1>
                                        <p style="font-size: 16px; color: #0f0; margin: 5px 0 0;">Secure Authentication Made Simple</p>
                                    </div>
                            
                                    <!-- Content -->
                                    <div style="padding: 20px 0;">
                                        <h2 style="color: #0f0; font-size: 22px; margin: 0 0 10px;">Hello ' . $username . ',</h2>
                                        <p style="font-size: 16px; line-height: 1.6; margin: 0 0 20px; color: #fff;">Your One-Time Password (OTP) for LoginShield is ready. Use the code below to securely log in to your account:</p>
                                        
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login -loginshieldbyErnest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Glowing Grid Background */
        body {
            margin: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
        }

        .grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0, 255, 0, 0.1) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(0, 255, 0, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: pulse 3s infinite alternate;
        }

        @keyframes pulse {
            0% {
                opacity: 0.5;
            }
            100% {
                opacity: 1;
            }
        }

        /* Glass Morphism Form */
        .form-container {
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
            backdrop-filter: blur(10px); /* Frosted glass effect */
            border: 1px solid rgba(0, 255, 0, 0.5); /* Glowing green border */
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5), 0 0 40px rgba(0, 255, 0, 0.3); /* Glow effect */
            border-radius: 10px;
            animation: glow 2s infinite alternate;
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 20px rgba(0, 255, 0, 0.5), 0 0 40px rgba(0, 255, 0, 0.3);
            }
            100% {
                box-shadow: 0 0 40px rgba(0, 255, 0, 0.8), 0 0 60px rgba(0, 255, 0, 0.5);
            }
        }
    </style>
</head>
<body class="flex items-center justify-center h-screen">
    <!-- Glowing Grid Background -->
    <div class="grid"></div>

    <!-- Login Form -->
    <div class="w-full max-w-md p-6 form-container relative">
    <h1 class="text-3xl font-bold text-center mb-4 text-green-500">LOGIN SHIELD</h1>
        <h2 class="text-2xl font-bold text-center mb-4 text-green-500">Login to Your Account</h2>

        <!-- Error Message -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-3 mb-4">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <!-- Email Field -->
            <div>
                <label class="text-sm text-green-500">Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required 
                    class="w-full p-2 border border-green-500 bg-black bg-opacity-50 text-green-500 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Password Field -->
            <div>
                <label class="text-sm text-green-500">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required 
                    class="w-full p-2 border border-green-500 bg-black bg-opacity-50 text-green-500 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Login Button -->
            <button type="submit" class="w-full bg-green-500 text-black py-2 rounded-md hover:bg-green-600 transition duration-300">
                Login
            </button>
        </form>

        <!-- Register Link -->
        <div class="text-center mt-4">
            <p class="text-gray-400">Don't have an account?</p>
            <a href="register.php" class="text-green-500 hover:underline">Register Here</a>
        </div>
    </div>
</body>
</html>