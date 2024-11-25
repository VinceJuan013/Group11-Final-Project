<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$response = [];

try {
    // Create a new MySQLi connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get data from POST request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $middle_initial = $_POST['middle_initial'];
        $gender = $_POST['gender'];
        $date_of_birth = $_POST['birthdate'];
        $age = $_POST['age'];
        $phone_number = $_POST['phone_number'];
        $email = $_POST['email'];
        $occupation = $_POST['occupation'];
        $present_address = $_POST['present_address'];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO patient_profiles (last_name, first_name, middle_initial, gender, date_of_birth, age, phone_number, email, occupation, present_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiisss", $last_name, $first_name, $middle_initial, $gender, $date_of_birth, $age, $phone_number, $email, $occupation, $present_address);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Patient added successfully";
        } else {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Close the connection
$conn->close();

// Output response as JSON
echo json_encode($response);
