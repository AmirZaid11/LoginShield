<?php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["error"] = "Username already exists. Please choose another.";
        header("Location: register.php");
        exit();
    }
    $stmt->close();

    // Check if email already exists
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["error"] = "Email already in use. Please use another email.";
        header("Location: register.php");
        exit();
    }
    $stmt->close();

    // Insert new user
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        if ($stmt->execute()) {
            $_SESSION["success"] = "Account created successfully! Please log in.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION["error"] = "Error creating account. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION["error"] = "Database error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Anonymous Security</title>
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

    <!-- Registration Form -->
    <div class="w-full max-w-md p-6 form-container relative">
        <!-- Website Name -->
        <h1 class="text-3xl font-bold text-center mb-4 text-green-500">LOGIN SHIELD</h1>
        <h2 class="text-2xl font-semibold text-center mb-4 text-green-500">Create an Account</h2>

        <!-- Display Error Messages -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-3 mb-4">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <!-- Username Field -->
            <input type="text" name="username" placeholder="Username" required 
                class="w-full p-2 border border-green-500 bg-black bg-opacity-50 text-green-500 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">

            <!-- Email Field -->
            <input type="email" name="email" placeholder="Email" required 
                class="w-full p-2 border border-green-500 bg-black bg-opacity-50 text-green-500 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">

            <!-- Password Field -->
            <input type="password" name="password" placeholder="Password" required 
                class="w-full p-2 border border-green-500 bg-black bg-opacity-50 text-green-500 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">

            <!-- Register Button -->
            <button type="submit" class="w-full bg-green-500 text-black py-2 rounded-md hover:bg-green-600 transition duration-300">
                Register
            </button>
        </form>

        <!-- Login Link -->
        <div class="text-center mt-4">
            <p class="text-gray-400">Already have an account?</p>
            <a href="login.php" class="text-green-500 hover:underline">Login Here</a>
        </div>
    </div>
</body>
</html>