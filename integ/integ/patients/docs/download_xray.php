<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$xray_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$patient_id = $_SESSION['patient_id'];

// Verify that this xray belongs to the logged-in patient
$sql = "SELECT image_path, xray_name FROM xray_images 
        WHERE xray_id = ? AND patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $xray_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Correctly build file path
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($row['image_path'], '/');
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $file_name = $row['xray_name'] . '.' . $file_extension;

    if (file_exists($file_path)) {
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // Output file
        readfile($file_path);

        // Close statements and connection here since script exits after this
        $stmt->close();
        $conn->close();
        
        exit();
    } else {
        $stmt->close();
        $conn->close();
        die("File not found.");
    }
} else {
    $stmt->close();
    $conn->close();
    die("X-ray not found or access denied.");
}