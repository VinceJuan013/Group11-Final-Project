<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$servername = "localhost";
$dbname = "appointment_db";
$username = "root";
$password = "";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default message type if it's not set yet
$_SESSION['message_type'] = $_SESSION['message_type'] ?? 'warning'; // Default to warning if not set

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = trim($_POST['currentPassword']);
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);
    $patient_id = $_SESSION['patient_id'] ?? null;

    if ($patient_id) {
        // Query to get the current password for the user
        $query = "SELECT password FROM patient_profiles WHERE patient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $stmt->bind_result($dbPassword);
        $stmt->fetch();
        
        // Check if password was retrieved successfully
        if (!$dbPassword) {
            $_SESSION['message'] = "User not found!";
            $_SESSION['message_type'] = "error"; // Error icon for this case
            header("Location: ../patients/change_password.php");
            exit;
        }
        
        $stmt->close();

        // Verify the current password
        if (password_verify($currentPassword, $dbPassword)) {
            // Password validation checks
            if ($newPassword === $currentPassword) {
                $_SESSION['message'] = "New password cannot be the same as the current password!";
                $_SESSION['message_type'] = "warning";
            } elseif (strlen($newPassword) < 8 || !preg_match('/[0-9]/', $newPassword)) {
                $_SESSION['message'] = "New password must be at least 8 characters and contain a number!";
                $_SESSION['message_type'] = "warning";
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['message'] = "New password and confirmation do not match!";
                $_SESSION['message_type'] = "warning";
            } else {
                // Hash and update password in the database
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE patient_profiles SET password = ? WHERE patient_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $hashedPassword, $patient_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Password updated successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating password!";
                    $_SESSION['message_type'] = "error";
                }

                $stmt->close();
            }
        } else {
            $_SESSION['message'] = "Current password is incorrect!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "User not logged in!";
        $_SESSION['message_type'] = "error";
    }

    header("Location: ../patients/change_password.php");
    exit;
}

$conn->close();
?>
