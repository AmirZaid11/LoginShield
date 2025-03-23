<?php
session_start();
require_once "../config/db.php";
require_once "../email/send_email.php";

if (!isset($_SESSION["temp_user_id"]) || !isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["temp_user_id"];
$email = $_SESSION["email"];

// Handle verification of temporary password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify"])) {
    $temp_password = trim($_POST["temp_password"]);

    $sql = "SELECT temp_password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_temp = $row["temp_password"];

        if (password_verify($temp_password, $hashed_temp)) {
            $_SESSION["temp_verified"] = true;
            $stmt->close();
            header("Location: reset_password.php");
            exit();
        } else {
            $_SESSION["error"] = "Invalid temporary password.";
        }
    } else {
        $_SESSION["error"] = "User not found.";
    }
    $stmt->close();
    header("Location: verify_temp_password.php");
    exit();
}

// Handle request for new temporary password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["request_new"])) {
    $temp_password = bin2hex(random_bytes(4)); // Generate new 8-character temp password
    $hashed_temp = password_hash($temp_password, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET temp_password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_temp, $user_id);

    if ($stmt->execute()) {
        $sql = "SELECT username FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $username = $row["username"];

        $subject = "New Temporary Password - LoginShield";
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #000; color: #fff;'>
            <div style='max-width: 600px; margin: 20px auto; padding: 20px; background-color: #111; border-radius: 12px; border: 1px solid #0f0;'>
                <h1 style='color: #0f0; text-align: center;'>LoginShield</h1>
                <h2 style='color: #0f0;'>Hello $username,</h2>
                <p>Your new temporary password is: <strong>$temp_password</strong></p>
                <p>Use this to verify your identity and set a new password.</p>
            </div>
        </body>
        </html>";
        sendEmail($email, $subject, $message);

        $_SESSION["success"] = "A new temporary password has been sent to your email.";
    } else {
        $_SESSION["error"] = "Failed to generate a new temporary password.";
    }
    $stmt->close();
    header("Location: verify_temp_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Temporary Password - LoginShield</title>
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
        .btn-secondary {
            background: #333;
            color: #0f0;
            margin-top: 1rem;
        }
        .btn-secondary:hover {
            background: #444;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="form-container">
        <h1 class="text-3xl font-bold text-center text-green-500 mb-2">LoginShield</h1>
        <h2 class="text-xl font-semibold text-center text-green-500 mb-6">Verify Temporary Password</h2>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-3 mb-6 rounded">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-3 mb-6 rounded">
                <?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="verify" value="1">
            <div class="input-wrapper">
                <input type="text" name="temp_password" id="temp_password" class="input-field" placeholder=" " required>
                <label for="temp_password" class="floating-label">Temporary Password</label>
            </div>
            <button type="submit" class="btn">Verify</button>
        </form>

        <form action="" method="POST">
            <input type="hidden" name="request_new" value="1">
            <button type="submit" class="btn btn-secondary">Request New Temporary Password</button>
        </form>
    </div>
</body>
</html>