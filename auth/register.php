<?php
session_start();
require_once "../config/db.php"; // MySQLi connection: $conn
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

date_default_timezone_set("Africa/Nairobi");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe";

    if (empty($recaptcha)) {
        $_SESSION["error"] = "Please complete the reCAPTCHA.";
    } else {
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptcha");
        $response = json_decode($response, true);

        if (!$response['success']) {
            $_SESSION["error"] = "Invalid reCAPTCHA.";
        } else {
            $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
            $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
            $password = trim($_POST["password"]);
            $q1 = filter_var(trim($_POST["q1"]), FILTER_SANITIZE_STRING);
            $a1 = filter_var(trim($_POST["a1"]), FILTER_SANITIZE_STRING);
            $q2 = filter_var(trim($_POST["q2"]), FILTER_SANITIZE_STRING);
            $a2 = filter_var(trim($_POST["a2"]), FILTER_SANITIZE_STRING);
            $q3 = filter_var(trim($_POST["q3"]), FILTER_SANITIZE_STRING);
            $a3 = filter_var(trim($_POST["a3"]), FILTER_SANITIZE_STRING);

            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($q1) || empty($a1) || 
                empty($q2) || empty($a2) || empty($q3) || empty($a3)) {
                $_SESSION["error"] = "All fields must be filled.";
            } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
                     !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $_SESSION["error"] = "Password must be 8+ characters with uppercase, lowercase, number, and special character.";
            } else {
                // Check username
                $check_sql = "SELECT id FROM users WHERE username = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $_SESSION["error"] = "Username already taken.";
                    $stmt->close();
                    header("Location: register.php");
                    exit();
                }
                $stmt->close();

                // Check email
                $check_email_sql = "SELECT id FROM users WHERE email = ?";
                $stmt = $conn->prepare($check_email_sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $_SESSION["error"] = "Email already registered.";
                    $stmt->close();
                    header("Location: register.php");
                    exit();
                }
                $stmt->close();

                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert user
                    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $username, $email, $hashed_password);
                    $stmt->execute();
                    $user_id = $conn->insert_id;

                    // Insert security questions
                    $q_sql = "INSERT INTO security_questions (user_id, question1, answer1, question2, answer2, question3, answer3) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $q_stmt = $conn->prepare($q_sql);
                    $q_stmt->bind_param("issssss", $user_id, $q1, $a1, $q2, $a2, $q3, $a3);
                    $q_stmt->execute();

                    header("Location: login.php");
                    exit();
                } catch (Exception $e) {
                    $_SESSION["error"] = "Error: " . $e->getMessage();
                    header("Location: register.php");
                    exit();
                }
            }
        }
    }
    header("Location: register.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LoginShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
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
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .form-container {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 0, 0.5);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.6), 0 0 60px rgba(0, 255, 0, 0.4);
            border-radius: 12px;
            animation: glow 2s infinite alternate;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            width: 100%;
            max-width: 400px; /* Reduced from 500px for smaller screens */
            position: relative;
            z-index: 10;
        }
        @keyframes glow {
            0% { box-shadow: 0 0 30px rgba(0, 255, 0, 0.6), 0 0 60px rgba(0, 255, 0, 0.4); }
            100% { box-shadow: 0 0 50px rgba(0, 255, 0, 0.8), 0 0 80px rgba(0, 255, 0, 0.6); }
        }
        .input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        .input-field {
            padding: 10px;
            font-size: 14px;
            width: 100%;
            background: #111;
            border: 1px solid #0f0;
            color: #0f0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .floating-label {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #0f0;
            font-size: 14px;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .input-field:focus + .floating-label,
        .input-field:not(:placeholder-shown) + .floating-label {
            top: 0;
            font-size: 12px;
            background: #111;
            padding: 0 4px;
        }
        .password-wrapper {
            position: relative;
        }
        .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }
        .eye-icon:hover {
            transform: translateY(-50%) scale(1.1);
        }
        .progress-bar {
            height: 4px;
            background: #333;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #0f0;
            width: 0;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="form-container">
        <h1 class="text-2xl font-bold text-center text-green-500 mb-2">LOGIN SHIELD</h1>
        <h2 class="text-xl font-semibold text-center text-green-500 mb-6">Create an Account</h2>

        <?php if (isset($_SESSION["error"])): ?>
            <p class="text-red-500 text-center mb-4"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></p>
        <?php endif; ?>

        <form action="" method="POST" id="registerForm" class="space-y-4">
            <div class="input-wrapper">
                <input type="text" name="username" id="username" class="input-field" placeholder=" " required>
                <label for="username" class="floating-label">Username</label>
            </div>
            <div class="input-wrapper">
                <input type="email" name="email" id="email" class="input-field" placeholder=" " required>
                <label for="email" class="floating-label">Email</label>
            </div>
            <div class="input-wrapper password-wrapper">
                <input type="password" name="password" id="password" class="input-field" placeholder=" " required>
                <label for="password" class="floating-label">Password</label>
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="#0f0">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <div>
                <button type="button" id="toggleSecurityQuestions" class="text-green-500 text-sm underline mb-2">Show Security Questions</button>
                <div id="securityQuestions" class="hidden space-y-4">
                    <div class="space-y-2">
                        <div class="input-wrapper">
                            <input type="text" name="q1" class="input-field" placeholder=" " required>
                            <label class="floating-label">Security Question 1</label>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" name="a1" class="input-field" placeholder=" " required>
                            <label class="floating-label">Answer 1</label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="input-wrapper">
                            <input type="text" name="q2" class="input-field" placeholder=" " required>
                            <label class="floating-label">Security Question 2</label>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" name="a2" class="input-field" placeholder=" " required>
                            <label class="floating-label">Answer 2</label>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="input-wrapper">
                            <input type="text" name="q3" class="input-field" placeholder=" " required>
                            <label class="floating-label">Security Question 3</label>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" name="a3" class="input-field" placeholder=" " required>
                            <label class="floating-label">Answer 3</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" data-theme="dark" data-callback="enableButton"></div>
            </div>
            <button type="submit" id="registerBtn" class="w-full bg-green-500 text-black py-2 rounded-md hover:bg-green-600 transition duration-300" disabled>
                Register
            </button>
        </form>
        <div class="text-center mt-4">
            <p class="text-gray-400">Already have an account?</p>
            <a href="login.php" class="text-green-500 hover:underline">Login Here</a>
        </div>
    </div>

    <script>
        function enableButton() {
            document.getElementById('registerBtn').disabled = false;
        }

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!grecaptcha.getResponse()) {
                e.preventDefault();
                alert('Please complete the reCAPTCHA.');
            }
        });

        const passwordInput = document.getElementById('password');
        const progressFill = document.getElementById('progressFill');
        const eyeIcon = document.querySelector('.eye-icon');

        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 20;
            progressFill.style.width = `${strength}%`;
        });

        eyeIcon.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        });

        document.getElementById('toggleSecurityQuestions').addEventListener('click', function() {
            document.getElementById('securityQuestions').classList.toggle('hidden');
        });
    </script>
</body>
</html>