<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error reporting for debugging
error_log("PHP errors logged.", 3, 'error.log'); // Log errors to a file

header('Content-Type: application/json');

function sendJsonResponse($data)
{
    $jsonData = json_encode($data);
    if ($jsonData === false) {
        logError("JSON encode error: " . json_last_error_msg());
        echo json_encode(["status" => "error", "message" => "Error encoding response"]);
    } else {
        echo $jsonData;
    }
    exit;
}

function isLoggedIn()
{
    return isset($_SESSION['patient_id']) && isset($_SESSION['username']);
}

function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

require_once '../db_connect.php';

$action = $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'fetch_slots':
            $slots = fetchAvailableSlots($conn);
            sendJsonResponse($slots);
            break;
        case 'book_appointment':
            bookAppointment($conn);
            break;
        case 'cancel_appointment':
            cancelAppointment($conn);
            break;
        default:
            sendJsonResponse(['status' => 'error', 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    logError("Unhandled exception: " . $e->getMessage());
    sendJsonResponse(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}

// Remaining functions: fetchAvailableSlots, bookAppointment, cancelAppointment, etc.
function fetchAvailableSlots($conn)
{
    $selectedDate = $_POST['date'] ?? null;

    $sql = "SELECT appointment_time, 
            SUM(CASE WHEN standing = 'active' AND status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN standing = 'active' THEN 1 ELSE 0 END) as total_active_count
            FROM appointments 
            WHERE appointment_date = ?
            GROUP BY appointment_time";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $selectedDate);
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['appointment_time']] = [
            'confirmed' => $row['confirmed_count'],
            'total' => $row['total_active_count']
        ];
    }

    $availableSlots = [];
    $startTime = 9;
    $endTime = 18;

    for ($i = $startTime; $i < $endTime; $i++) {
        $slot = sprintf("%02d:00", $i);
        $displayTime = convertTo12HourFormat($slot);
        $confirmedCount = $bookedSlots[$displayTime]['confirmed'] ?? 0;
        $totalActiveCount = $bookedSlots[$displayTime]['total'] ?? 0;
        $availableCount = 3 - $confirmedCount;

        $availableSlots[] = [
            'time' => $displayTime,
            'available' => max(0, $availableCount),
            'pending' => $totalActiveCount - $confirmedCount
        ];
    }

    return [
        'date' => $selectedDate,
        'slots' => $availableSlots
    ];

}


function bookAppointment($conn)
{
    logError("POST data received: " . print_r($_POST, true));
    if (!isLoggedIn()) {
        logError("User is not logged in.");
        throw new Exception("User not logged in.");
    }

    $selected_services = isset($_POST['selected_services']) ? formatServices($_POST['selected_services']) : '';
    $complaint = strtoupper(trim($_POST['complaint'] ?? ''));
    $selectedTimeSlot = strtoupper($_POST['time_slot'] ?? '');
    $appointmentDate = strtoupper($_POST['date'] ?? '');
    $other_details = strtoupper($_POST['other_details'] ?? '');
    $follow_up = strtoupper($_POST['followup'] ?? '');
    $preferred_dentist = strtoupper($_POST['preferred_dentist'] ?? '');    
    $patient_id = $_SESSION['patient_id'];
    $username = $_SESSION['username'];

    if (empty($selected_services) || empty($complaint) || empty($selectedTimeSlot) || empty($appointmentDate)) {
        throw new Exception("Please fill in all required fields.");
    }

    $today = date('Y-m-d');
    if ($appointmentDate <= $today) {
        throw new Exception("Cannot book appointments for today or past dates.");
    }

    $conn->begin_transaction();

    try {
        // Check if the slot is available
        $sql = "SELECT 
                SUM(CASE WHEN standing = 'active' AND status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                SUM(CASE WHEN standing = 'active' THEN 1 ELSE 0 END) as total_active_count
                FROM appointments 
                WHERE appointment_date = ? AND appointment_time = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $appointmentDate, $selectedTimeSlot);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $confirmedCount = $row['confirmed_count'];
        $totalActiveCount = $row['total_active_count'];

        if ($confirmedCount >= 3) {
            throw new Exception("Sorry, this slot is no longer available.");
        }

        // Insert appointment
        $status = 'active';
        $appointmentStatus = 'pending';

        $sql = "INSERT INTO appointments 
                (selected_services, username, complaint, other_details, followup, preferred_dentist, patient_id, appointment_time, appointment_date, standing, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssss",
            $selected_services,
            $username,
            $complaint,
            $other_details,
            $follow_up,
            $preferred_dentist,
            $patient_id,
            $selectedTimeSlot,
            $appointmentDate,
            $status,
            $appointmentStatus
        );

        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $conn->commit();

        $updatedSlots = fetchAvailableSlots($conn);
        sendJsonResponse([
            'status' => 'success',
            'message' => 'Appointment booked successfully',
            'updatedSlots' => $updatedSlots
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}


function cancelAppointment($conn)
{
    if (!isLoggedIn()) {
        throw new Exception("User not logged in.");
    }

    $appointment_id = $_POST['appointment_id'] ?? '';
    $patient_id = $_SESSION['patient_id'];

    if (empty($appointment_id)) {
        throw new Exception("Appointment ID is required.");
    }

    // Update both `standing` and `status` to 'cancelled'
    $sql = "UPDATE appointments SET standing = 'cancelled', status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ss", $appointment_id, $patient_id);
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    sendJsonResponse(["status" => "success", "message" => "Appointment cancelled successfully."]);
}



// At the beginning of your PHP file, add this function to format the services
function formatServices($jsonServices) {
    $services = json_decode($jsonServices, true);
    if (!is_array($services)) {
        return strtoupper($jsonServices);
    }
    
    $formattedServices = array();
    foreach ($services as $service) {
        if (is_array($service) || is_object($service)) {
            // Handle "Other" service with specification
            if (isset($service['value']) && $service['value'] === 'other' && isset($service['specification'])) {
                $formattedServices[] = "OTHER: " . strtoupper($service['specification']);
            }
        } else {
            // Handle regular services
            $formattedServices[] = strtoupper($service);
        }
    }
    
    return implode(', ', $formattedServices);
}

function convertTo12HourFormat($time)
{
    return date("h:i A", strtotime($time));
}
