<?php
$last_name = $first_name = $full_name = $middle_initial = $birthdate = $gender = $occupation = $age = $phone_number = $email = $present_address = $username = $password = "";
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appointment_db";

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}