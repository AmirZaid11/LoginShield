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
    <title>Register - LoginShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="w-full max-w-md p-6 bg-white shadow-md rounded-lg">
        <h2 class="text-2xl font-semibold text-center mb-4">Create an Account</h2>

        <!-- Display Error Messages -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4">
                <?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <input type="text" name="username" placeholder="Username" required 
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            <input type="email" name="email" placeholder="Email" required 
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            <input type="password" name="password" placeholder="Password" required 
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">
                Register
            </button>
        </form>

        <!-- Login Button -->
        <div class="text-center mt-4">
            <p class="text-gray-600">Already have an account?</p>
            <a href="login.php" class="text-blue-500 hover:underline">Login Here</a>
        </div>
    </div>
</body>
</html>
