<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["temp_verified"]) || !isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

$show_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_SESSION["email"];
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $_SESSION["error"] = "Password must be 8+ characters with uppercase, lowercase, number, and special character.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION["error"] = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, temp_password = NULL WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            $show_success = true;
            unset($_SESSION["temp_verified"]);
            unset($_SESSION["temp_user_id"]);
            unset($_SESSION["email"]);
        } else {
            $_SESSION["error"] = "Error updating password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LoginShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .success-message {
            display: none;
            text-align: center;
            color: #0f0;
            font-size: 1.5rem;
            font-weight: bold;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .loader {
            display: none;
            width: 40px;
            height: 40px;
            border: 4px solid #0f0;
            border-top: 4px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        <h1 class="text-3xl font-bold text-center text-green-500 mb-2">LoginShield</h1>
        <h2 class="text-xl font-semibold text-center text-green-500 mb-6">Set New Password</h2>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-3 mb-6 rounded">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_success): ?>
            <div id="successContainer">
                <p class="success-message" id="successText">Password changed successfully</p>
                <div class="loader" id="loader"></div>
            </div>
        <?php else: ?>
            <form action="" method="POST" class="space-y-6" id="resetForm">
                <div class="input-wrapper">
                    <input type="password" name="new_password" id="new_password" class="input-field" placeholder=" " required>
                    <label for="new_password" class="floating-label">New Password</label>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="input-field" placeholder=" " required>
                    <label for="confirm_password" class="floating-label">Confirm Password</label>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($show_success): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const successText = document.getElementById('successText');
                const loader = document.getElementById('loader');
                const form = document.getElementById('resetForm');

                if (form) form.style.display = 'none';
                successText.style.display = 'block';
                loader.style.display = 'block';

                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            });
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('new_password');
            const progressFill = document.getElementById('progressFill');

            if (passwordInput && progressFill) {
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
            }
        });
    </script>
</body>
</html>