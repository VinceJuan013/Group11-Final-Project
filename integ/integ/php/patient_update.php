<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$response = ['success' => false, 'message' => ''];

try {
    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get and validate patient_id
    $patient_id = $_POST['patient_id'] ?? null;
    if (!$patient_id) {
        throw new Exception("Patient ID is required");
    }

    // Collect and clean input data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_initial = trim($_POST['middle_initial'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $age = is_numeric($_POST['age']) ? (int) $_POST['age'] : null;
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');

    // Basic validation
    if (empty($first_name) || empty($last_name)) {
        throw new Exception("Name fields cannot be empty");
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Start transaction
    $conn->begin_transaction();

    // Prepare update statement
    $sql = "UPDATE patient_profiles SET 
            first_name = ?, 
            last_name = ?, 
            middle_initial = ?,
            date_of_birth = ?,
            gender = ?,
            occupation = ?,
            age = ?,
            phone_number = ?,
            email = ?,
            present_address = ?
            WHERE patient_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssisssi",
        $first_name,
        $last_name,
        $middle_initial,
        $date_of_birth,
        $gender,
        $occupation,
        $age,
        $phone_number,
        $email,
        $present_address,
        $patient_id
    );

    if ($stmt->execute()) {
        // Update the full_name after the main update
        $sql_full_name = "UPDATE patient_profiles 
                          SET full_name = CONCAT(first_name, 
                                                 CASE 
                                                     WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                                                     THEN CONCAT(' ', middle_initial, '.') 
                                                     ELSE '' 
                                                 END, 
                                                 ' ', last_name) 
                          WHERE patient_id = ?";
        $stmt_full_name = $conn->prepare($sql_full_name);
        $stmt_full_name->bind_param("i", $patient_id);

        if ($stmt_full_name->execute()) {
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Profile updated successfully";
        } else {
            throw new Exception("Error updating full name: " . $stmt_full_name->error);
        }

        $stmt_full_name->close();
    } else {
        throw new Exception("Error updating profile: " . $stmt->error);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === null) {
        $conn->rollback();
    }
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
