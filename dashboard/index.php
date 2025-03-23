<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION["user_id"];

// Fetch current user details
$sql = "SELECT username, email, security_score, mfa_enabled, created_at, last_login FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id); // Line 15 in your original code
$stmt->execute();
$stmt->bind_result($username, $email, $security_score, $mfa_enabled, $created_at, $last_login);
$stmt->fetch();
$stmt->close();

// Fetch all users except the current user
$users_sql = "SELECT id, username, email FROM users WHERE id != ? ORDER BY username";
$users_stmt = $conn->prepare($users_sql);
if ($users_stmt === false) {
    die("Prepare failed for users list: " . $conn->error);
}
$users_stmt->bind_param("i", $user_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Fetch security logs (limit 5)
$logs_sql = "SELECT event, timestamp FROM security_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5";
$logs_stmt = $conn->prepare($logs_sql);
if ($logs_stmt === false) {
    die("Prepare failed for logs: " . $conn->error);
}
$logs_stmt->bind_param("i", $user_id);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();

// Fetch activity logs (mocked or real)
$activity_sql = "SELECT event AS action, timestamp FROM security_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5";
$activity_stmt = $conn->prepare($activity_sql);
if ($activity_stmt === false) {
    die("Prepare failed for activity: " . $conn->error);
}
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();

// Mock data for other sections
$login_count = $conn->query("SELECT COUNT(*) FROM security_logs WHERE user_id = $user_id AND event = 'Login'")->fetch_row()[0] ?? 0;
$tips = ["Use a unique password for every site.", "Enable MFA for extra security.", "Review active sessions regularly."];
$random_tip = $tips[array_rand($tips)];

// Handle email sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_email"])) {
    $to_user_id = $_POST["to_user_id"];
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    
    $recipient_sql = "SELECT email FROM users WHERE id = ?";
    $recipient_stmt = $conn->prepare($recipient_sql);
    if ($recipient_stmt === false) {
        die("Prepare failed for recipient: " . $conn->error);
    }
    $recipient_stmt->bind_param("i", $to_user_id);
    $recipient_stmt->execute();
    $recipient_stmt->bind_result($to_email);
    $recipient_stmt->fetch();
    $recipient_stmt->close();

    $headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if (mail($to_email, $subject, $message, $headers)) {
        $_SESSION["success"] = "Email sent successfully to $to_email!";
    } else {
        $_SESSION["error"] = "Failed to send email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ANONYMOUSWORLDKE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a1a1a 0%, #000 100%); font-family: 'Arial', sans-serif; min-height: 100vh; margin: 0; overflow-x: hidden; }
        .dark { background: linear-gradient(135deg, #0d0d0d 0%, #000 100%); }
        h1, h2, h3 { font-family: 'Orbitron', sans-serif; }
        .sidebar { background: #0f0; color: #000; transition: width 0.3s ease; }
        .sidebar.collapsed { width: 0; padding: 0; overflow: hidden; }
        .card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(0, 255, 0, 0.3); border-radius: 12px; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 0 20px rgba(0, 255, 0, 0.5); }
        .glow { box-shadow: 0 0 10px #0f0, 0 0 20px #0f0; transition: box-shadow 0.3s ease; }
        .glow:hover { box-shadow: 0 0 15px #0f0, 0 0 30px #0f0; }
        .btn, .nav-link { transition: transform 0.2s ease, background 0.3s ease; }
        .btn:active, .nav-link:active { transform: scale(0.95); }
        .progress-fill { height: 100%; background: #0f0; border-radius: 9999px; animation: fillProgress 1.5s ease-in-out forwards; }
        @keyframes fillProgress { from { width: 0; } to { width: <?php echo $security_score; ?>%; } }
        .timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: #0f0; }
        .timeline li { position: relative; margin-bottom: 1rem; }
        .timeline li::before { content: ''; position: absolute; left: -1.5rem; top: 0.5rem; width: 8px; height: 8px; background: #0f0; border-radius: 50%; }
        .welcome-text { animation: typing 2s steps(20, end); white-space: nowrap; overflow: hidden; }
        @keyframes typing { from { width: 0; } to { width: 100%; } }
        #particles-js { position: absolute; width: 100%; height: 100%; z-index: -1; }
    </style>
</head>
<body class="flex dark">
    <div id="particles-js"></div>
    <div id="sidebar" class="sidebar w-72 p-5 space-y-6">
        <h2 class="text-2xl font-semibold"><i class="fas fa-shield-alt"></i> ANONYMOUS SHIELD</h2>
        <nav class="mt-6 space-y-2">
            <a href="index.php" class="nav-link block py-2 px-4 rounded-md bg-black text-white glow">🏠 Dashboard</a>
            <a href="security.php" class="nav-link block py-2 px-4 rounded-md hover:bg-black hover:text-white glow">🔐 Security</a>
            <a href="devices.php" class="nav-link block py-2 px-4 rounded-md hover:bg-black hover:text-white glow">📲 Active Sessions</a>
            <a href="../auth/logout.php" class="nav-link block py-2 px-4 rounded-md bg-red-500 hover:bg-red-600 glow">🚪 Logout</a>
        </nav>
        <button id="darkToggle" class="w-full mt-4 bg-gray-500 py-2 px-4 rounded-md glow"><i class="fas fa-moon"></i> Toggle Dark Mode</button>
    </div>
    <div class="flex-1 p-10">
        <button id="toggleSidebar" class="lg:hidden p-2 text-white mb-4 glow">☰</button>
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white text-xl glow">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <h2 class="ml-4 text-3xl font-semibold text-white welcome-text">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        </div>
        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-900 text-green-300 p-3 mb-6 rounded"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-900 text-red-300 p-3 mb-6 rounded"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">🔐 Security Score</h3>
                <p class="text-gray-300">Your current account security level:</p>
                <div class="mt-3 w-full bg-gray-300 h-4 rounded-full">
                    <div class="progress-fill h-4 text-center text-black text-xs" style="width: <?php echo $security_score; ?>%;">
                        <?php echo $security_score; ?>%
                    </div>
                </div>
                <ul class="mt-2 text-gray-300">
                    <li>MFA: <?php echo $mfa_enabled ? "+50%" : "0%"; ?></li>
                    <li>Password Strength: +30%</li>
                    <li>Device Security: +20%</li>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Account Overview</h3>
                <p class="text-gray-300">Email: <?php echo htmlspecialchars($email); ?></p>
                <p class="text-gray-300">Joined: <?php echo date("d M Y", strtotime($created_at)); ?></p>
                <p class="text-gray-300">Last Login: <?php echo $last_login ? date("d M Y, H:i", strtotime($last_login)) : "N/A"; ?></p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">🔐 Multi-Factor Authentication</h3>
                <p class="text-gray-300">Status: <?php echo $mfa_enabled ? "<span class='text-green-500'>Enabled ✅</span>" : "<span class='text-red-500'>Disabled ❌</span>"; ?></p>
                <?php if (!$mfa_enabled): ?>
                    <ul class="mt-2 text-gray-300">
                        <li>✅ Step 1: Verify Email</li>
                        <li>⬜ Step 2: Enable MFA (<a href="enable_mfa.php" class="text-cyan-500">Start</a>)</li>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Threat Alerts</h3>
                <ul class="text-gray-300">
                    <li>⚠️ Suspicious login attempt - 5 mins ago</li>
                    <li>✅ MFA blocked access - Yesterday</li>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="enable_mfa.php" class="btn block bg-cyan-500 text-black py-2 px-4 rounded-md glow">Enable MFA</a>
                    <a href="logout_all.php" class="btn block bg-red-500 text-white py-2 px-4 rounded-md glow">Logout All</a>
                    <a href="profile.php" class="btn block bg-green-500 text-black py-2 px-4 rounded-md glow">Update Profile</a>
                </div>
            </div>
        </div>
        <div class="card p-5 mt-6">
            <h3 class="text-xl font-semibold text-white">👥 Other Users</h3>
            <p class="text-gray-300">Connect with others in the system:</p>
            <ul class="mt-3 space-y-2 text-gray-300">
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <li class="flex justify-between items-center">
                        <span><?php echo htmlspecialchars($user["username"]); ?> (<?php echo htmlspecialchars($user["email"]); ?>)</span>
                        <button onclick="showEmailForm(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" class="btn bg-cyan-500 text-black py-1 px-2 rounded-md glow">Send Email</button>
                    </li>
                <?php endwhile; ?>
                <?php if ($users_result->num_rows == 0): ?>
                    <li>No other users found.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div id="emailForm" class="card p-5 mt-6 hidden">
            <h3 class="text-xl font-semibold text-white">📧 Send Email</h3>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="to_user_id" id="toUserId">
                <p class="text-gray-300">To: <span id="toEmail"></span></p>
                <div>
                    <label class="text-gray-300">Subject:</label>
                    <input type="text" name="subject" class="w-full p-2 bg-gray-800 text-white rounded-md" required>
                </div>
                <div>
                    <label class="text-gray-300">Message:</label>
                    <textarea name="message" class="w-full p-2 bg-gray-800 text-white rounded-md" rows="4" required></textarea>
                </div>
                <button type="submit" name="send_email" class="btn bg-green-500 text-black py-2 px-4 rounded-md glow">Send</button>
                <button type="button" onclick="hideEmailForm()" class="btn bg-red-500 text-white py-2 px-4 rounded-md glow">Cancel</button>
            </form>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="card p-5 timeline">
                <h3 class="text-xl font-semibold text-white">Recent Activity</h3>
                <ul class="text-gray-300">
                    <?php while ($activity = $activity_result->fetch_assoc()): ?>
                        <li><?php echo htmlspecialchars($activity["action"]) . " - " . date("d M Y, H:i", strtotime($activity["timestamp"])); ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Security Tip</h3>
                <p class="text-gray-300"><?php echo htmlspecialchars($random_tip); ?></p>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Usage Stats</h3>
                <p class="text-gray-300">Logins this month: <?php echo $login_count; ?></p>
                <p class="text-gray-300">Average session: 30 mins</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Password Strength</h3>
                <p class="text-gray-300">Current Strength: <span class="text-green-500">Strong</span></p>
                <a href="reset_password.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Change Password</a>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Device Health</h3>
                <p class="text-gray-300">Browser: <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></p>
                <p class="text-gray-300">Status: <span class="text-green-500">Secure</span></p>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Cybersecurity News</h3>
                <ul class="text-gray-300">
                    <li>New phishing scam targets email - Today</li>
                    <li>MFA adoption rises by 20% - Yesterday</li>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Profile Completeness</h3>
                <div class="w-full bg-gray-300 h-4 rounded-full">
                    <div class="bg-green-500 h-4 rounded-full" style="width: 75%;">75%</div>
                </div>
                <p class="text-gray-300"><a href="profile.php" class="text-cyan-500">Add phone number</a> to complete.</p>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">MFA Backup Codes</h3>
                <p class="text-gray-300">Use these if you lose access:</p>
                <ul class="text-gray-300">
                    <li>XXXX-XXXX</li>
                    <li>YYYY-YYYY</li>
                </ul>
                <a href="generate_codes.php" class="btn bg-cyan-500 text-black py-2 px-4 rounded-md glow">Generate New</a>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Trusted Devices</h3>
                <ul class="text-gray-300">
                    <li>Windows 10 - Chrome <a href="#" class="text-red-500">Revoke</a></li>
                    <li>Android - Firefox <a href="#" class="text-red-500">Revoke</a></li>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-xl font-semibold text-white">Quick Quiz</h3>
                <p class="text-gray-300">What’s the strongest MFA method?</p>
                <button onclick="alert('Correct! Authenticator apps are most secure.')" class="btn bg-green-500 text-black py-1 px-2 rounded-md glow">Authenticator App</button>
                <button onclick="alert('Not quite. SMS can be intercepted.')" class="btn bg-red-500 text-white py-1 px-2 rounded-md glow">SMS</button>
            </div>
        </div>
    </div>
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle("dark");
            localStorage.setItem("darkMode", document.body.classList.contains("dark") ? "enabled" : "disabled");
            const icon = document.querySelector('#darkToggle i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        }
        if (localStorage.getItem("darkMode") === "enabled") document.body.classList.add("dark");
        document.getElementById('toggleSidebar').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
        particlesJS('particles-js', {
            particles: {
                number: { value: 50 },
                color: { value: '#0f0' },
                shape: { type: 'circle' },
                opacity: { value: 0.5 },
                size: { value: 3 },
                move: { speed: 2 }
            }
        });
        function showEmailForm(userId, email) {
            document.getElementById('emailForm').classList.remove('hidden');
            document.getElementById('toUserId').value = userId;
            document.getElementById('toEmail').textContent = email;
        }
        function hideEmailForm() {
            document.getElementById('emailForm').classList.add('hidden');
        }
    </script>
</body>
</html>