<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['patient_id'])) {
    $_SESSION['cancel_result'] = ['success' => false, 'message' => 'User not logged in.'];
    header('Location: ../appointment/viewappointment.php');
    exit();
}

// Create a database connection
$conn = new mysqli("localhost", "root", "", "dental_db");

// Check for connection errors
if ($conn->connect_error) {
    $_SESSION['cancel_result'] = ['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error];
    header('Location: ../appointment/viewappointment.php');
    exit();
}

// Get appointment ID and reason from the query parameters
$appointmentID = isset($_GET['id']) ? $_GET['id'] : null; // Get appointment ID
$reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : null;  // Get the optional reason
$userId = $_SESSION['patient_id'];

// Prepare SQL query to update the appointment status
$sql = "UPDATE appointments SET status = 'Cancelled', cancellation_reason = ? WHERE appointment_id = ? AND patient_id = ? AND status != 'Cancelled'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $reason, $appointmentID, $userId);

// Execute the query
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['cancel_result'] = ['success' => true, 'message' => 'Appointment cancelled successfully.'];
    } else {
        $_SESSION['cancel_result'] = ['success' => false, 'message' => 'No appointment found or already cancelled.'];
    }
} else {
    $_SESSION['cancel_result'] = ['success' => false, 'message' => 'Error updating record: ' . $conn->error];
}

// Close the statement and the connection
$stmt->close();
$conn->close();

// Redirect back to the appointment view page
header('Location: ../patients/view_appointments.php');
exit();
