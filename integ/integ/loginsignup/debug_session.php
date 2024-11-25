<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Current User Status:</h3>";
if (isset($_SESSION['patient_id']) && isset($_SESSION['username']) && isset($_SESSION['role'])) {
    echo "User is logged in.<br>";
    echo "Patient ID: " . $_SESSION['patient_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "User is not logged in.<br>";
}

echo "<h3>PHP Session Settings:</h3>";
echo "Session save path: " . session_save_path() . "<br>";
echo "Session name: " . session_name() . "<br>";
echo "Session ID: " . session_id() . "<br>";

echo "<h3>Server Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
