<?php
// ✅ Start the session & check user authentication
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Cybersecurity & Cryptography</title>

    <!-- ✅ Tailwind CSS & AOS Animation Library -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

    <script>
        // ✅ Configure Tailwind for dark mode
        tailwind.config = {
            darkMode: 'class'
        };
    </script>

    <style>
        /* ✅ Dark Mode Styling */
        .dark {
            background-color: #1a202c;
            color: #e2e8f0;
        }

        /* ✅ Password Strength Indicator Colors */
        .weak { color: red; }
        .moderate { color: orange; }
        .strong { color: green; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">

    <!-- ✅ Navigation Bar -->
    <nav class="bg-blue-500 p-4 text-white flex justify-between items-center shadow-md">
        <h1 class="text-2xl font-bold">Cybersecurity Hub 🔐</h1>
        <div>
            <button onclick="toggleDarkMode()" class="bg-gray-700 px-4 py-2 rounded hover:bg-gray-600 transition">
                🌙 Toggle Dark Mode
            </button>
            <a href="index.php" class="ml-4 bg-gray-700 px-4 py-2 rounded hover:bg-gray-600 transition">Dashboard</a>
        </div>
    </nav>

    <!-- ✅ Main Content Section -->
    <div class="max-w-4xl mx-auto mt-8 p-6 bg-white shadow-md rounded-lg dark:bg-gray-800" data-aos="fade-up">

        <!-- ✅ Password Strength Checker -->
        <h2 class="text-xl font-semibold mb-4">🔐 Password Strength Checker</h2>
        <input type="password" id="passwordInput" placeholder="Type a password" class="w-full p-2 border border-gray-300 rounded-md dark:border-gray-700">
        <div id="passwordStrength" class="mt-2 text-sm font-semibold"></div>

        <hr class="my-6">

        <!-- ✅ Authentication Methods -->
        <h2 class="text-xl font-semibold mb-4">🔑 Levels of Authentication</h2>
        <ul class="list-disc ml-6 text-gray-700 dark:text-gray-300">
            <li>Single-Factor Authentication (SFA): Password or PIN.</li>
            <li>Two-Factor Authentication (2FA): Password + OTP or biometrics.</li>
            <li>Multi-Factor Authentication (MFA): Using multiple verification methods.</li>
        </ul>

        <hr class="my-6">

        <!-- ✅ Cybersecurity Quiz (10 Questions) -->
        <h2 class="text-xl font-semibold mb-4">🛡️ Cybersecurity Quiz</h2>
        <form id="quizForm">
            <?php
            // ✅ Define quiz questions
            $questions = [
                "MFA stands for?" => ["a" => "Multi-Factor Authentication", "b" => "Malware Firewall Authentication"],
                "What is Phishing?" => ["a" => "A hacking technique", "b" => "A secure login method"],
                "Which is a strong password?" => ["a" => "123456", "b" => "P@ssw0rd!"],
                "What is HTTPS used for?" => ["a" => "Secure web browsing", "b" => "Gaming"],
                "Which is NOT a cybersecurity threat?" => ["a" => "Firewall", "b" => "Ransomware"],
                "What does a VPN do?" => ["a" => "Encrypts internet traffic", "b" => "Speeds up WiFi"],
                "What is a firewall?" => ["a" => "A security system", "b" => "A computer virus"],
                "Which is a social engineering attack?" => ["a" => "Phishing", "b" => "Cloud backup"],
                "What does 2FA add to security?" => ["a" => "An extra verification step", "b" => "Better graphics"],
                "What is malware?" => ["a" => "Malicious software", "b" => "Anti-virus tool"]
            ];

            // ✅ Generate quiz form
            $i = 1;
            foreach ($questions as $question => $options) {
                echo "<div class='mb-4'>";
                echo "<label>$i. $question</label>";
                echo "<select name='q$i' class='mt-2 w-full border border-gray-300 p-2 rounded-md dark:border-gray-700'>";
                echo "<option value=''>-- Select --</option>";
                foreach ($options as $key => $value) {
                    echo "<option value='$key'>$value</option>";
                }
                echo "</select></div>";
                $i++;
            }
            ?>
            <button type="button" onclick="checkQuiz()" class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600">
                Submit Quiz
            </button>
        </form>
        <p id="quizResult" class="mt-4 text-lg font-semibold text-center"></p>

        <hr class="my-6">

        <!-- ✅ Phishing Awareness -->
        <h2 class="text-xl font-semibold mb-4">🚨 Phishing Awareness</h2>
        <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 rounded-lg dark:bg-yellow-800">
            <p class="font-semibold">Example of a Phishing Email:</p>
            <p class="text-sm text-gray-700 dark:text-gray-200">
                <strong>From:</strong> support@paypalsecurity.com <br>
                <strong>Subject:</strong> "Urgent: Your account is compromised!" <br><br>
                "Dear Customer, your PayPal account has been locked due to suspicious activity. Click the link below to verify your details immediately."  
                <br><br>
                <a href="#" class="text-blue-500 underline">🔗 Click Here to Verify</a>
            </p>
        </div>

    </div>

    <!-- ✅ Footer -->
    <footer class="bg-gray-900 text-white text-center p-4 mt-8">
        &copy; <?php echo date("Y"); ?> Cybersecurity Hub | Secure Your Digital Life
    </footer>

    <!-- ✅ JavaScript -->
    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark');
        }

        // Password Strength Checker
        document.getElementById("passwordInput").addEventListener("input", function() {
            let password = this.value;
            let strengthText = document.getElementById("passwordStrength");
            strengthText.className = "";
            strengthText.textContent = password.length >= 8 ? "Strong" : password.length >= 6 ? "Moderate" : "Weak";
            strengthText.classList.add(password.length >= 8 ? "strong" : password.length >= 6 ? "moderate" : "weak");
        });

        // Quiz Checker
        function checkQuiz() {
            let score = 0;
            let correctAnswers = ["a","a","b","a","a","a","a","a","a","a"];
            document.querySelectorAll("select").forEach((el, index) => {
                if (el.value === correctAnswers[index]) score++;
            });
            document.getElementById("quizResult").textContent = `✅ You scored ${score}/10`;
        }

        AOS.init();
    </script>

</body>
</html>
