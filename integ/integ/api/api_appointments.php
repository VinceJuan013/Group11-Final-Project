<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Prepare the SQL statement to fetch only confirmed appointments
$sql = "SELECT appointment_id, appointment_date, appointment_time, username, selected_services, complaint, preferred_dentist, status, remarks 
        FROM appointments 
        WHERE status = 'confirmed'";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to execute statement: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        'id' => $row['appointment_id'],
        'title' => "Appointment #" . $row['appointment_id'],
        'start' => $row['appointment_date'],
        'extendedProps' => [
            'username' => $row['username'],
            'selected_services' => $row['selected_services'],
            'complaint' => $row['complaint'],
            'appointment_time' => $row['appointment_time'],
            'preferred_dentist' => $row['preferred_dentist'],
            'status' => $row['status'],
            'remarks' => $row['remarks']
        ]
    ];
}

echo json_encode($appointments);

$stmt->close();
$conn->close();
