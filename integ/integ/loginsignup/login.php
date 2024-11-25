<?php

// Check if a session is already active before starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../db_connect.php';

// Handle form submission (login attempt)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF token validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token validation failed");
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: login_form.php");
        exit();
    }

    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Log attempt for debugging purposes
    error_log("Login attempt for user: " . $username);

    // Attempt to find user in patient_profiles table
    $stmt = $conn->prepare("SELECT patient_id, username, password FROM patient_profiles WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("User found: " . json_encode($user));

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['patient_id'] = $user['patient_id'];
            $_SESSION['role'] = 'patient';
            $_SESSION['login_success'] = true;

            // Log successful login for debugging
            error_log("Login successful for user: " . $_SESSION['username']);

            // Redirect the user to the patient dashboard
            header("Location: ../patients/patient_home.php");
            exit();
        } else {
            error_log("Password verification failed for user: " . $username);
            $_SESSION['error_message'] = "Invalid username or password.";
            header("Location: login_form.php");
            exit();
        }
    } else {
        // If no user found
        error_log("User not found: " . $username);
        $_SESSION['error_message'] = "Invalid username or password.";
        header("Location: login_form.php");
        exit();
    }
}

$conn->close();

// Set security headers for protection
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
