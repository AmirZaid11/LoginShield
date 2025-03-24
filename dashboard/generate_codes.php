<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION["user_id"];

// Function to generate a random backup code
function generateBackupCode() {
    $characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $code = "";
    for ($i = 0; $i < 12; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
        if ($i == 3 || $i == 7) $code .= "-"; // Add dashes for readability (e.g., ABCD-EFGH-JKLM)
    }
    return $code;
}

// Check if codes should be generated
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate"])) {
    // Delete existing unused codes for the user
    $delete_sql = "DELETE FROM mfa_backup_codes WHERE user_id = ? AND is_used = 0";
    $delete_stmt = $conn->prepare($delete_sql);
    if ($delete_stmt === false) {
        die("Prepare failed for delete: " . $conn->error);
    }
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Generate 10 new backup codes
    $codes = [];
    for ($i = 0; $i < 10; $i++) {
        $codes[] = generateBackupCode();
    }

    // Insert new codes into the database
    $insert_sql = "INSERT INTO mfa_backup_codes (user_id, code) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if ($insert_stmt === false) {
        die("Prepare failed for insert: " . $conn->error);
    }
    foreach ($codes as $code) {
        $insert_stmt->bind_param("is", $user_id, $code);
        $insert_stmt->execute();
    }
    $insert_stmt->close();

    $_SESSION["new_codes"] = $codes; // Store codes in session to display
    header("Location: generate_codes.php"); // Reload to show codes
    exit();
}

// Fetch existing or newly generated codes
$fetch_sql = "SELECT code FROM mfa_backup_codes WHERE user_id = ? AND is_used = 0 ORDER BY created_at DESC LIMIT 10";
$fetch_stmt = $conn->prepare($fetch_sql);
if ($fetch_stmt === false) {
    die("Prepare failed for fetch: " . $conn->error);
}
$fetch_stmt->bind_param("i", $user_id);
$fetch_stmt->execute();
$fetch_result = $fetch_stmt->get_result();
$existing_codes = [];
while ($row = $fetch_result->fetch_assoc()) {
    $existing_codes[] = $row["code"];
}
$fetch_stmt->close();

// Use newly generated codes from session if available
if (isset($_SESSION["new_codes"])) {
    $codes = $_SESSION["new_codes"];
    unset($_SESSION["new_codes"]);
} else {
    $codes = $existing_codes;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate MFA Backup Codes - ANONYMOUSWORLDKE</title>
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
    </style>
</head>
<body>
    <div class="card">
        <h1 class="text-3xl font-semibold text-green-500 mb-2 text-center">ANONYMOUS SHIELD</h1>
        <h3 class="text-xl font-semibold text-white mb-4 text-center">MFA Backup Codes</h3>

        <?php if (empty($codes)): ?>
            <p class="text-gray-300 mb-4">No backup codes exist. Generate new ones below:</p>
            <form action="" method="POST" class="text-center">
                <button type="submit" name="generate" class="btn bg-green-500 text-black py-2 px-4 rounded-md glow">Generate Codes</button>
            </form>
        <?php else: ?>
            <p class="text-gray-300 mb-4">Save these codes securely. They can be used once to access your account if you lose your MFA device:</p>
            <ul class="text-gray-300 space-y-2 mb-6">
                <?php foreach ($codes as $code): ?>
                    <li class="bg-gray-800 p-2 rounded-md"><?php echo htmlspecialchars($code); ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="flex justify-between">
                <a href="index.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Back to Dashboard</a>
                <form action="" method="POST">
                    <button type="submit" name="generate" class="btn bg-green-500 text-black py-2 px-4 rounded-md glow">Regenerate Codes</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Optional: Add a copy-to-clipboard feature for codes
        document.querySelectorAll('li').forEach(item => {
            item.addEventListener('click', () => {
                navigator.clipboard.writeText(item.textContent);
                alert('Code copied to clipboard: ' + item.textContent);
            });
        });
    </script>
</body>
</html>