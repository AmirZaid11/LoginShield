<?php
session_start();
require_once "../config/db.php";
require_once "../vendor/autoload.php"; // Path from dashboard/ to vendor/

use PHPGangsta_GoogleAuthenticator\GoogleAuthenticator;

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION["user_id"];
$ga = new GoogleAuthenticator(); // Line 18: Using PHPGangsta_GoogleAuthenticator

// Fetch current MFA status and secret
$sql = "SELECT username, email, mfa_enabled, mfa_secret FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $mfa_enabled, $mfa_secret);
$stmt->fetch();
$stmt->close();

// Generate a new secret if not already set or MFA is disabled
if (!$mfa_secret || !$mfa_enabled) {
    $mfa_secret = $ga->createSecret();
    $_SESSION["temp_mfa_secret"] = $mfa_secret; // Store temporarily
}

// Handle MFA verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_mfa"])) {
    $code = trim($_POST["mfa_code"]);
    $secret = $_SESSION["temp_mfa_secret"] ?? $mfa_secret;

    if ($ga->verifyCode($secret, $code, 2)) { // 2 = 60-second tolerance
        $update_sql = "UPDATE users SET mfa_enabled = 1, mfa_secret = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt === false) {
            die("Prepare failed for update: " . $conn->error);
        }
        $update_stmt->bind_param("si", $secret, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION["success"] = "MFA enabled successfully!";
            unset($_SESSION["temp_mfa_secret"]);
            header("Location: index.php");
            exit();
        } else {
            $_SESSION["error"] = "Failed to enable MFA.";
        }
        $update_stmt->close();
    } else {
        $_SESSION["error"] = "Invalid MFA code. Please try again.";
    }
}

// QR Code URL for authenticator app
$qrCodeUrl = $ga->getQRCodeGoogleUrl("AnonymousShield:$username", $mfa_secret);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enable MFA - ANONYMOUSWORLDKE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); 
            font-family: 'Arial', sans-serif; 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
        }
        h1, h3 { font-family: 'Orbitron', sans-serif; }
        .card { 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(0, 255, 0, 0.3); 
            border-radius: 12px; 
            padding: 2rem; 
            width: 100%; 
            max-width: 500px; 
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5); 
        }
        .glow { 
            box-shadow: 0 0 10px #0f0, 0 0 20px #0f0; 
            transition: box-shadow 0.3s ease; 
        }
        .glow:hover { 
            box-shadow: 0 0 15px #0f0, 0 0 30px #0f0; 
        }
        .btn { 
            transition: transform 0.2s ease, background 0.3s ease; 
        }
        .btn:active { 
            transform: scale(0.95); 
        }
        input { 
            background: #1a1a1a; 
            border: 1px solid #0f0; 
            color: white; 
            border-radius: 8px; 
            padding: 0.5rem; 
            width: 100%; 
        }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="text-3xl font-semibold text-green-500 mb-2 text-center">ANONYMOUS SHIELD</h1>
        <h3 class="text-xl font-semibold text-white mb-4 text-center">Enable Multi-Factor Authentication</h3>

        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-900 text-green-300 p-3 mb-4 rounded"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 text-red-300 p-3 mb-4 rounded"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <?php if ($mfa_enabled): ?>
            <p class="text-gray-300 mb-4 text-center">MFA is already enabled for your account.</p>
            <div class="text-center">
                <a href="index.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <p class="text-gray-300 mb-4">Scan this QR code with an authenticator app (e.g., Google Authenticator) to set up MFA:</p>
            <div class="text-center mb-4">
                <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="MFA QR Code" class="inline-block">
            </div>
            <p class="text-gray-300 mb-4">Or manually enter this secret: <code class="bg-gray-800 p-1 rounded"><?php echo htmlspecialchars($mfa_secret); ?></code></p>
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="text-gray-300 block mb-1">Enter the 6-digit code from your app:</label>
                    <input type="text" name="mfa_code" maxlength="6" pattern="\d{6}" required placeholder="123456">
                </div>
                <div class="flex justify-between">
                    <button type="submit" name="verify_mfa" class="btn bg-green-500 text-black py-2 px-4 rounded-md glow">Verify & Enable</button>
                    <a href="index.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>