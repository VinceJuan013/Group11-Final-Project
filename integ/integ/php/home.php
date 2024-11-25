<?php
// Session is already started in patient_home.php

// Ensure the user is logged in and is a patient
if (!isset($_SESSION['patient_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login_form.php");
    exit();
}

// Check if the login success flag is set in the session
$login_success = false;
if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
    $login_success = true;
    // Unset the login success flag to prevent showing the toast on page refresh
    unset($_SESSION['login_success']);
}

$loggedInUserId = $_SESSION['patient_id'];

// Database connection
require_once '../db_connect.php';

// Fetch the next upcoming confirmed appointment for the logged-in user
$sql = "SELECT appointment_id, status, appointment_date, appointment_time, selected_services, complaint, remarks 
        FROM appointments
        WHERE patient_id = ? AND appointment_date >= CURDATE() 
        AND status = 'Confirmed'
        ORDER BY appointment_date ASC LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $appointment_id = $status = $appointment_date = $appointment_time = $complaint = $selected_services = $remarks = 'N/A';
} else {
    $stmt->bind_param("i", $loggedInUserId);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $appointment_id = $status = $appointment_date = $appointment_time = $complaint= $selected_services  = $remarks = 'N/A';
    } else {
        $result = $stmt->get_result();

        if ($result === false) {
            error_log("Getting result set failed: " . $stmt->error);
            $appointment_id = $status = $appointment_date = $appointment_time = $selected_services  = $complaint = $remarks = 'N/A';
        } else {
            // Check if a confirmed appointment was found
            if ($appointment = $result->fetch_assoc()) {
                $appointment_id = htmlspecialchars($appointment['appointment_id']);
                $status = htmlspecialchars($appointment['status']);
                $appointment_date = htmlspecialchars($appointment['appointment_date']);
                $selected_services= htmlspecialchars($appointment['selected_services']);
                $appointment_time = htmlspecialchars($appointment['appointment_time']);
                $complaint = htmlspecialchars($appointment['complaint']);
                $remarks = htmlspecialchars($appointment['remarks']);
            } else {
                $appointment_id = $status = $appointment_date = $appointment_time = $selected_services  = $complaint = $remarks = 'N/A';
            }
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the connection
$conn->close();