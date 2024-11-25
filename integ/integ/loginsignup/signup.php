<?php
// File: signup.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = "";
$registration_success = false;

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "appointment_db";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("An error occurred. Please try again later.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    // Sanitize inputs
    $last_name = strtoupper(trim(htmlspecialchars($_POST['last_name'])));
    $first_name = strtoupper(trim(htmlspecialchars($_POST['first_name'])));
    $middle_initial = strtoupper(trim(htmlspecialchars($_POST['middle_initial'])));
    $gender = strtoupper(trim(htmlspecialchars($_POST['gender'])));
    $date_of_birth = htmlspecialchars($_POST['date_of_birth']);
    $age = filter_var($_POST['age'], FILTER_SANITIZE_NUMBER_INT);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $occupation = strtoupper(trim(htmlspecialchars($_POST['occupation'])));
    $present_address = strtoupper(trim(htmlspecialchars($_POST['present_address'])));
    $username = trim(htmlspecialchars($_POST['username']));
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    // Validate inputs
    if (
        empty($last_name) || empty($first_name) || empty($gender) || empty($date_of_birth) ||
        empty($age) || empty($phone_number) || empty($email) || empty($present_address) ||
        empty($username) || empty($password) || empty($confirmpassword)
    ) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirmpassword) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
        // Remove +63 and check if the phone number starts with 09
        if (isset($phone_number)) {
            // Remove the '+63' prefix if it exists
            $phone_number = ltrim($phone_number, '+63');

            // Ensure the number starts with '09' after removing the prefix
            if (substr($phone_number, 0, 2) !== '09') {
                $error_message = "Phone number must start with 09.";
            } elseif (!preg_match('/^09\d{9}$/', $phone_number)) {
                $error_message = "Invalid phone number format.";
            }
        }
        // Validate password strength
        $password = $_POST['password'];
        $confirmpassword = $_POST['confirmpassword'];

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            $error_message = "Password must contain at least 8 characters, including uppercase, lowercase, and a number.";
        } elseif ($password !== $confirmpassword) {
            $error_message = "Passwords do not match.";
        }
        
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    }

    // Check email uniqueness
    if (empty($error_message)) {
        $email_check = $conn->prepare("SELECT patient_id FROM patient_profiles WHERE email = ?");
        $email_check->bind_param("s", $email);
        $email_check->execute();
        $email_check->store_result();
        if ($email_check->num_rows > 0) {
            $error_message = "Email is already registered.";
        }
        $email_check->close();
    }

    // Check username uniqueness
    if (empty($error_message)) {
        $username_check = $conn->prepare("SELECT patient_id FROM patient_profiles WHERE username = ?");
        $username_check->bind_param("s", $username);
        $username_check->execute();
        $username_check->store_result();
        if ($username_check->num_rows > 0) {
            $error_message = "Username is already taken.";
        }
        $username_check->close();
    }

    // Create account if no errors
    if (empty($error_message)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'patient'; // Set the role explicitly
        $stmt = $conn->prepare("INSERT INTO patient_profiles (last_name, first_name, middle_initial, gender, date_of_birth, age, phone_number, email, occupation, present_address, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssisssssss", $last_name, $first_name, $middle_initial, $gender, $date_of_birth, $age, $phone_number, $email, $occupation, $present_address, $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $registration_success = true;
            $_SESSION['registration_success'] = true; // Set a session variable for success message
            header("Location: login.php"); // Redirect to login page after successful registration
            exit();
        } else {
            error_log("Database error: " . $stmt->error);
            $error_message = "An error occurred during registration. Please try again later.";
        }
        $stmt->close();
    }

    // Redirect with error message if any errors occurred
    if (!empty($error_message)) {
        error_log("Registration Error: " . $error_message);
        $_SESSION['error_message'] = $error_message; // Store error in session

        // Store form data in session, excluding password fields
        $_SESSION['form_data'] = [
            'last_name' => $last_name,
            'first_name' => $first_name,
            'middle_initial' => $middle_initial,
            'gender' => $gender,
            'date_of_birth' => $date_of_birth,
            'age' => $age,
            'phone_number' => $phone_number,
            'email' => $email,
            'occupation' => $occupation,
            'present_address' => $present_address,
            'username' => $username
        ];

        header("Location: signup_form.php");
        exit();
    }
}

$conn->close();

// Set secure headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
