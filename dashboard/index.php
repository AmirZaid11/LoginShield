<?php
session_start();
require_once "../config/db.php"; // Include database connection

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// ✅ Fetch user security details
$sql = "SELECT username, security_score, mfa_enabled FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $security_score, $mfa_enabled);
$stmt->fetch();
$stmt->close();

// ✅ Fetch latest security logs (limit 5)
$logs_sql = "SELECT event, timestamp FROM security_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5";
$logs_stmt = $conn->prepare($logs_sql);
$logs_stmt->bind_param("i", $user_id);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();
$logs_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - ANONYMOUSWORLDKE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Toggle Dark Mode
        function toggleDarkMode() {
            document.body.classList.toggle("dark");
            localStorage.setItem("darkMode", document.body.classList.contains("dark") ? "enabled" : "disabled");
        }

        // Check for saved dark mode preference
        window.onload = function() {
            if (localStorage.getItem("darkMode") === "enabled") {
                document.body.classList.add("dark");
            }
        };
    </script>
    <style>
        /* Dark Mode Styles */
        .dark { background-color:rgb(19, 1, 1); color:rgb(7, 3, 3); }
        .dark .bg-white { background-color: #1a1a1a; }
        .dark .text-gray-700 { color:rgb(9, 5, 253); }
        .dark .bg-blue-600 { background-color: #0f0; color: #000; } /* Neon Green for Sidebar */
        .dark .bg-blue-500 { background-color: #0a0; } /* Darker Green for Buttons */
        .dark .bg-red-500 { background-color: #f00; } /* Red for Logout Button */
        .dark .bg-gray-500 { background-color: #333; } /* Gray for Toggle Button */
        .dark .bg-green-500 { background-color: #0f0; } /* Neon Green for Progress Bar */
        .dark .bg-yellow-500 { background-color: #ff0; color: #000; } /* Yellow for Manage Sessions Button */
    </style>
</head>
<body class="flex h-screen bg-black dark">

    <!-- Sidebar -->
    <div class="w-72 bg-blue-600 text-white p-5 space-y-6">
        <h2 class="text-2xl font-semibold">🛡️ ANONYMOUS WORLD</h2>

        <nav class="mt-6 space-y-2">
            <a href="index.php" class="block py-2 px-4 rounded-md bg-blue-500 hover:bg-blue-700">🏠 Dashboard</a>
            <a href="security.php" class="block py-2 px-4 rounded-md hover:bg-blue-700">🔐 Security</a>
            <a href="devices.php" class="block py-2 px-4 rounded-md hover:bg-blue-700">📲 Active Sessions</a>
            <a href="../auth/logout.php" class="block py-2 px-4 rounded-md bg-red-500 hover:bg-red-600">🚪 Logout</a>
        </nav>

        <button onclick="toggleDarkMode()" class="w-full mt-4 bg-gray-500 py-2 px-4 rounded-md">🌙 Toggle Dark Mode</button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-10">
        <h2 class="text-3xl font-semibold text-gray-800 dark:text-white">Welcome, <?php echo htmlspecialchars($username); ?>! 👋</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-300">Your cybersecurity control panel.</p>

        <!-- Security Score -->
        <div class="mt-6 p-5 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">🔐 Security Score</h3>
            <p class="text-gray-600 dark:text-gray-300">Your current account security level:</p>
            <div class="mt-3 w-full bg-gray-300 h-4 rounded-full">
                <div class="bg-green-500 h-4 rounded-full text-center text-white text-xs" style="width: <?php echo $security_score; ?>%;">
                    <?php echo $security_score; ?>%
                </div>
            </div>
        </div>

        <!-- Multi-Factor Authentication (MFA) -->
        <div class="mt-6 p-5 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">🔐 Multi-Factor Authentication</h3>
            <p class="text-gray-600 dark:text-gray-300">Status: 
                <?php echo $mfa_enabled ? "<span class='text-green-500'>Enabled ✅</span>" : "<span class='text-red-500'>Disabled ❌</span>"; ?>
            </p>
            <?php if (!$mfa_enabled): ?>
                <a href="enable_mfa.php" class="mt-3 inline-block bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                    Enable MFA
                </a>
            <?php endif; ?>
        </div>

        <!-- Active Sessions -->
        <div class="mt-6 p-5 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">📲 Active Sessions</h3>
            <p class="text-gray-600 dark:text-gray-300">Your currently logged-in devices:</p>
            <ul class="mt-3 space-y-2 text-gray-600 dark:text-gray-300">
                <li>✅ Windows 10 - Chrome (This Device)</li>
                <li>✅ Android - Safari (Logged in 3 hours ago)</li>
                <li>⚠️ Unknown Device - Denied (Yesterday)</li>
            </ul>
            <a href="devices.php" class="mt-3 inline-block bg-yellow-500 text-white py-2 px-4 rounded-md hover:bg-yellow-600">
                Manage Sessions
            </a>
        </div>

        <!-- Recent Security Logs -->
        <div class="mt-6 p-5 bg-white dark:bg-gray-800 shadow-md rounded-lg">
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">📢 Security Logs</h3>
            <ul class="mt-3 space-y-2 text-gray-600 dark:text-gray-300">
                <?php while ($log = $logs_result->fetch_assoc()): ?>
                    <li>📌 <?php echo htmlspecialchars($log["event"]) . " - " . date("d M Y, H:i", strtotime($log["timestamp"])); ?></li>
                <?php endwhile; ?>
                <?php if ($logs_result->num_rows == 0): ?>
                    <li>No recent security events.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

</body>
</html>