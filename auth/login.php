<?php
session_start();
require_once "../config/db.php";
require_once "../email/send_email.php";

date_default_timezone_set("Africa/Nairobi");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"; // Replace with your secret key

    if (empty($recaptcha)) {
        $_SESSION["error"] = "Please complete the reCAPTCHA.";
    } else {
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha");
        $response = json_decode($response, true);

        if (!$response['success']) {
            $_SESSION["error"] = "Invalid reCAPTCHA.";
        } else {
            $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
            $password = trim($_POST["password"]);

            $sql = "SELECT id, username, password FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $username, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $otp = rand(100000, 999999);
                    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
                    $otp_expiry = time() + (10 * 60);

                    $updateSQL = "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSQL);
                    $updateStmt->bind_param("sii", $hashed_otp, $otp_expiry, $id);

                    if ($updateStmt->execute()) {
                        $subject = "Your OTP Code";
                        $message = '...' /* Your existing email template */;
                        sendEmail($email, $subject, $message);

                        $_SESSION["user_id"] = $id;
                        $_SESSION["email"] = $email;
                        header("Location: verify_otp.php");
                        exit();
                    }
                } else {
                    $_SESSION["error"] = "Invalid email or password.";
                }
            } else {
                $_SESSION["error"] = "No account found with this email.";
            }
            $stmt->close();
        }
    }
}

// Inside login.php, replace the password reset block with this:
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset"])) {
    $email = filter_var(trim($_POST["reset_email"]), FILTER_SANITIZE_EMAIL);
    $sql = "SELECT id, username FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username);
        $stmt->fetch();
        
        $temp_password = bin2hex(random_bytes(4)); // 8-character temp password
        $hashed_temp = password_hash($temp_password, PASSWORD_DEFAULT);
        
        $updateSQL = "UPDATE users SET temp_password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?";
        $updateStmt = $conn->prepare($updateSQL);
        $updateStmt->bind_param("si", $hashed_temp, $id);
        
        if ($updateStmt->execute()) {
            $subject = "Password Reset - LoginShield";
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #000; color: #fff;'>
                <div style='max-width: 600px; margin: 20px auto; padding: 20px; background-color: #111; border-radius: 12px; border: 1px solid #0f0;'>
                    <h1 style='color: #0f0; text-align: center;'>LoginShield</h1>
                    <h2 style='color: #0f0;'>Hello $username,</h2>
                    <p>Your temporary password is: <strong>$temp_password</strong></p>
                    <p>Use this to verify your identity and set a new password.</p>
                </div>
            </body>
            </html>";
            sendEmail($email, $subject, $message);
            
            $_SESSION["temp_user_id"] = $id;
            $_SESSION["email"] = $email;
            header("Location: verify_temp_password.php");
            exit();
        }
    } else {
        $_SESSION["error"] = "No account found with this email.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LoginShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .grid {
            position: fixed;
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
            0% { opacity: 0.3; }
            100% { opacity: 0.7; }
        }
        .form-container {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 255, 0, 0.6);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.5), 0 0 60px rgba(0, 255, 0, 0.3);
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            animation: glow 2s infinite alternate;
        }
        @keyframes glow {
            0% { box-shadow: 0 0 30px rgba(0, 255, 0, 0.5), 0 0 60px rgba(0, 255, 0, 0.3); }
            100% { box-shadow: 0 0 50px rgba(0, 255, 0, 0.7), 0 0 80px rgba(0, 255, 0, 0.5); }
        }
        .input-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-field {
            width: 100%;
            padding: 0.75rem;
            background: rgba(20, 20, 20, 0.9);
            border: 1px solid #0f0;
            border-radius: 6px;
            color: #0f0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: #0f0;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        .floating-label {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(0, 255, 0, 0.7);
            font-size: 1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .input-field:focus + .floating-label,
        .input-field:not(:placeholder-shown) + .floating-label {
            top: -0.5rem;
            font-size: 0.75rem;
            background: rgba(0, 0, 0, 0.8);
            padding: 0 0.25rem;
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #0f0;
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #00cc00;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }
        .reset-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #111;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #0f0;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="form-container">
        <h1 class="text-3xl font-bold text-center text-green-500 mb-2">LoginShield</h1>
        <h2 class="text-xl font-semibold text-center text-green-500 mb-6">Secure Login</h2>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-3 mb-6 rounded">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="login" value="1">
            <div class="input-wrapper">
                <input type="email" name="email" id="email" class="input-field" placeholder=" " required>
                <label for="email" class="floating-label">Email Address</label>
            </div>
            <div class="input-wrapper">
                <input type="password" name="password" id="password" class="input-field" placeholder=" " required>
                <label for="password" class="floating-label">Password</label>
            </div>
            <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" data-theme="dark" data-callback="enableButton"></div>
            </div>
            <button type="submit" id="loginBtn" class="btn" disabled>Login</button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-400 mb-2">Don't have an account? <a href="register.php" class="text-green-500 hover:underline">Register</a></p>
            <button id="resetBtn" class="text-green-500 hover:underline">Forgot Password?</button>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="reset-modal" id="resetModal">
        <div class="modal-content">
            <h2 class="text-xl font-semibold text-green-500 mb-4">Reset Password</h2>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="reset" value="1">
                <div class="input-wrapper">
                    <input type="email" name="reset_email" id="reset_email" class="input-field" placeholder=" " required>
                    <label for="reset_email" class="floating-label">Enter Your Email</label>
                </div>
                <button type="submit" class="btn">Send Temporary Password</button>
                <button type="button" id="closeModal" class="w-full bg-gray-700 text-white py-2 rounded-md hover:bg-gray-600">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function enableButton() {
            document.getElementById('loginBtn').disabled = false;
        }

        const resetBtn = document.getElementById('resetBtn');
        const resetModal = document.getElementById('resetModal');
        const closeModal = document.getElementById('closeModal');

        resetBtn.addEventListener('click', () => {
            resetModal.style.display = 'flex';
        });

        closeModal.addEventListener('click', () => {
            resetModal.style.display = 'none';
        });

        resetModal.addEventListener('click', (e) => {
            if (e.target === resetModal) {
                resetModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>