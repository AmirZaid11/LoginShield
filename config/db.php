<?php
$host = "localhost";
$user = "root";  // Update if needed
$pass = "";  // If your MySQL has a password, update here
$dbname = "loginshield";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
} else {
   // echo "Connected successfully";  // Temporary check, remove after testing
}
?>

