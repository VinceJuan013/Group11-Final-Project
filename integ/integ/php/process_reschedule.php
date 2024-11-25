<?php
// Start session at the very beginning
session_start();
session_regenerate_id();

// Turn off error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($data)
{
    echo json_encode($data);
    exit;
}

// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['patient_id']) && isset($_SESSION['username']);
}

// Function to log errors
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

// Function to insert notification
function insertNotification($conn, $appointmentId, $patientName, $oldDateTime, $newDateTime)
{
    $message = sprintf(
        "Patient %s has rescheduled their appointment from %s to %s",
        $patientName,
        date('F j, Y g:i A', strtotime($oldDateTime)),
        date('F j, Y g:i A', strtotime($newDateTime))
    );

    $sql = "INSERT INTO reschedule_notification (
                appointment_id, 
                message, 
                type, 
                is_read,
                created_at
            ) VALUES (?, ?, 'reschedule', 0, NOW())";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $appointmentId, $message);
        if (!$stmt->execute()) {
            logError("Error creating notification: " . $stmt->error);
            return false;
        }
        $stmt->close();
        return true;
    }
    return false;
}

// Database connection
require_once '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    logError("Database connection failed: " . $conn->connect_error);
    sendJsonResponse([
        "status" => "error",
        "title" => "Connection Error",
        "message" => "Database connection failed. Please try again later."
    ]);
}

// Function to convert 24-hour time to 12-hour format
function convertTo12HourFormat($time)
{
    return date("h:i A", strtotime($time));
}

// Handle form submission for rescheduling an appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    if (!isLoggedIn()) {
        logError("User not logged in or username not set in session");
        sendJsonResponse([
            "status" => "error",
            "title" => "Authentication Error",
            "message" => "Error: User not logged in or session expired. Please log in again."
        ]);
    }

    // Retrieve and validate form data
    $selectedTimeSlot = $_POST['time_slot'] ?? '';
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentId = $_POST['appointment_id'] ?? null;
    $patient_id = $_SESSION['patient_id'];
    $username = $_SESSION['username'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch the old appointment details
        $sql = "SELECT appointment_date, appointment_time FROM appointments 
                WHERE appointment_id = ? AND patient_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $appointmentId, $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $oldDateTime = $row['appointment_date'] . ' ' . $row['appointment_time'];
                $newDateTime = $appointmentDate . ' ' . $selectedTimeSlot;

                // Check if the new slot is available
                $checkSql = "SELECT COUNT(*) as booked_count 
                            FROM appointments 
                            WHERE appointment_date = ? 
                            AND appointment_time = ? 
                            AND status != 'cancelled'";

                if ($checkStmt = $conn->prepare($checkSql)) {
                    $checkStmt->bind_param("ss", $appointmentDate, $selectedTimeSlot);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $bookedCount = $checkResult->fetch_assoc()['booked_count'];

                    if ($bookedCount >= 15) {
                        throw new Exception("This time slot is no longer available. Please choose another.");
                    }

                    // Update the appointment with the new date and time directly
                    $updateSql = "UPDATE appointments 
                                SET appointment_time = ?,
                                    appointment_date = ?,
                                    rescheduled_time = ?,
                                    rescheduled_date = ?,
                                    status = 'pending' 
                                WHERE appointment_id = ? 
                                AND patient_id = ?";

                    if ($updateStmt = $conn->prepare($updateSql)) {
                        $updateStmt->bind_param("ssssii", 
                            $selectedTimeSlot, 
                            $appointmentDate,
                            $selectedTimeSlot, 
                            $appointmentDate, 
                            $appointmentId, 
                            $patient_id
                        );

                        if ($updateStmt->execute()) {
                            // Insert reschedule notification
                            if (!insertNotification($conn, $appointmentId, $username, $oldDateTime, $newDateTime)) {
                                throw new Exception("Failed to create reschedule notification");
                            }

                            $conn->commit();

                            sendJsonResponse([
                                "status" => "success",
                                "title" => "Success",
                                "message" => "Appointment has been rescheduled and is pending confirmation."
                            ]);
                        } else {
                            throw new Exception("Failed to update appointment");
                        }
                    }
                }
            } else {
                throw new Exception("Appointment not found or unauthorized access");
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        logError("Transaction failed: " . $e->getMessage());
        sendJsonResponse([
            "status" => "error",
            "title" => "Error",
            "message" => $e->getMessage()
        ]);
    }
}

// Handle fetching available time slots
if (isset($_POST['action']) && $_POST['action'] == 'fetch_slots' && isset($_POST['date'])) {
    $selectedDate = $_POST['date'];

    // Query to fetch booked slots count for the selected date
    $sql = "SELECT appointment_time, COUNT(*) as booked_count FROM appointments 
            WHERE appointment_date = ? AND status != 'cancelled' 
            GROUP BY appointment_time";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookedSlots = [];
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[$row['appointment_time']] = $row['booked_count'];
        }

        // Define available slots (9 AM to 5 PM)
        $availableSlots = [];
        $startTime = 9;
        $endTime = 18;

        for ($i = $startTime; $i < $endTime; $i++) {
            $slot = sprintf("%02d:00", $i);
            $displayTime = convertTo12HourFormat($slot);
            $bookedCount = isset($bookedSlots[$displayTime]) ? $bookedSlots[$displayTime] : 0;
            $availableCount = 15 - $bookedCount;

            $availableSlots[] = [
                'time' => $displayTime,
                'displayTime' => $displayTime,
                'available' => max(0, $availableCount)
            ];
        }

        sendJsonResponse([
            "status" => "success",
            "slots" => $availableSlots
        ]);
    }
}

$conn->close();