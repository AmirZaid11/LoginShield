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

// Fetch current user details
$sql = "SELECT username, email, phone_number FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $phone_number);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $new_email = trim($_POST["email"]);
    $new_phone = trim($_POST["phone_number"]);

    // Basic validation
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["error"] = "Invalid email format.";
    } elseif (!empty($new_phone) && !preg_match("/^\+?[1-9]\d{1,14}$/", $new_phone)) {
        $_SESSION["error"] = "Invalid phone number format (e.g., +15551234567).";
    } else {
        // Update the database
        $update_sql = "UPDATE users SET email = ?, phone_number = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt === false) {
            die("Prepare failed for update: " . $conn->error);
        }
        $update_stmt->bind_param("ssi", $new_email, $new_phone, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION["success"] = "Profile updated successfully!";
            $email = $new_email; // Update local variable for display
            $phone_number = $new_phone;
        } else {
            $_SESSION["error"] = "Failed to update profile.";
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ANONYMOUSWORLDKE</title>
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
        input, textarea { 
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
        <h3 class="text-xl font-semibold text-white mb-4 text-center">Your Profile</h3>

        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-900 text-green-300 p-3 mb-4 rounded"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 text-red-300 p-3 mb-4 rounded"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="text-gray-300 block mb-1">Username:</label>
                <input type="text" value="<?php echo htmlspecialchars($username); ?>" class="cursor-not-allowed" disabled>
                <p class="text-gray-500 text-sm mt-1">Username cannot be changed.</p>
            </div>
            <div>
                <label class="text-gray-300 block mb-1">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div>
                <label class="text-gray-300 block mb-1">Phone Number (optional):</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number ?? ''); ?>" placeholder="+15551234567">
            </div>
            <div class="flex justify-between">
                <button type="submit" name="update_profile" class="btn bg-green-500 text-black py-2 px-4 rounded-md glow">Update Profile</button>
                <a href="index.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Back to Dashboard</a>
            </div>
        </form>
    </div>
</body>
</html>