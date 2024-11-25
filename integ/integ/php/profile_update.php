<?php
session_start();

// Check if patient_id is set in the session
if (!isset($_SESSION['patient_id']) || empty($_SESSION['patient_id'])) {
    die("Error: Patient ID is not set. Please log in.");
}

$patient_id = (int) $_SESSION['patient_id'];
$update_status = '';
$success_message = '';
$error_message = '';

// Database connection credentials
$host = 'localhost';
$dbname = 'appointment_db';
$username = 'root';
$password = '';

// Create a connection to MySQL database
$conn = new mysqli($host, $username, $password, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get parameter types for bind_param
function getTypes($params) {
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

// Function to handle database queries with prepared statements
function executeQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = getTypes($params);
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Function to fetch single row
function fetchRow($conn, $sql, $params = []) {
    $result = executeQuery($conn, $sql, $params);
    return $result ? $result->fetch_assoc() : false;
}

// Function to check if record exists
function recordExists($conn, $table, $patient_id) {
    $sql = "SELECT 1 FROM " . $table . " WHERE patient_id = ? LIMIT 1";
    $result = executeQuery($conn, $sql, [$patient_id]);
    return $result->num_rows > 0;
}

// Initialize variables
$first_name = $last_name = $middle_initial = $present_address = $date_of_birth = $age = $gender = $occupation = $phone_number = $email = "";
$q1 = $q2 = $q3 = $q4 = $q5 = $medications = $q6 = $q7 = $q9 = $q10 = $q11 = $q12 = $q13a = $q13b = $q13c = "";
$q8 = [];

// Fetch existing profile data
$profile_sql = "SELECT first_name, last_name, middle_initial, present_address, date_of_birth, 
                age, gender, occupation, phone_number, email 
                FROM patient_profiles 
                WHERE patient_id = ?";
$profile_data = fetchRow($conn, $profile_sql, [$patient_id]);

if ($profile_data) {
    foreach ($profile_data as $key => $value) {
        $$key = htmlspecialchars($value);
    }
}

// Fetch existing medical history data
$history_sql = "SELECT * FROM medical_history WHERE patient_id = ?";
$history_data = fetchRow($conn, $history_sql, [$patient_id]);

if ($history_data) {
    foreach ($history_data as $key => $value) {
        if ($key === 'q8') {
            $q8 = !empty($value) ? unserialize($value) : [];
        } elseif ($key !== 'patient_id') {
            $$key = htmlspecialchars($value);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $errors = [];
        $required_fields = [
            'last_name' => 'Last name',
            'first_name' => 'First name',
            'date_of_birth' => 'Date of birth',
            'gender' => 'Gender',
            'age' => 'Age',
            'phone_number' => 'Phone number',
            'email' => 'Email'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = "$label is required";
            }
        }

        if (empty($errors)) {
            $conn->begin_transaction();

            // Collect profile data
            $profile_params = [
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['middle_initial'],
                $_POST['date_of_birth'],
                $_POST['gender'],
                $_POST['age'],
                $_POST['occupation'],
                $_POST['present_address'],
                $_POST['phone_number'],
                $_POST['email'],
                $patient_id
            ];

            if (recordExists($conn, 'patient_profiles', $patient_id)) {
                $profile_sql = "UPDATE patient_profiles SET 
                    first_name = ?, 
                    last_name = ?, 
                    middle_initial = ?, 
                    date_of_birth = ?,
                    gender = ?, 
                    age = ?, 
                    occupation = ?, 
                    present_address = ?,
                    phone_number = ?, 
                    email = ? 
                    WHERE patient_id = ?";
            } else {
                $profile_sql = "INSERT INTO patient_profiles 
                    (first_name, last_name, middle_initial, date_of_birth,
                    gender, age, occupation, present_address, phone_number,
                    email, patient_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }

            executeQuery($conn, $profile_sql, $profile_params);

            // Collect medical history data
            $q8_serialized = serialize(isset($_POST['q8']) && is_array($_POST['q8']) ? $_POST['q8'] : []);
            
            $history_params = [
                $_POST['q1'] ?? 'No',
                $_POST['q2'] ?? 'No',
                $_POST['q3'] ?? 'No',
                $_POST['q4'] ?? 'No',
                $_POST['q5'] ?? 'No',
                $_POST['medications'] ?? '',
                $_POST['q6'] ?? 'No',
                $_POST['q7'] ?? 'No',
                $q8_serialized,
                $_POST['q9'] ?? '',
                $_POST['q10'] ?? 'No',
                $_POST['q11'] ?? 'No',
                $_POST['q12'] ?? 'No',
                $_POST['q13a'] ?? 'No',
                $_POST['q13b'] ?? 'No',
                $_POST['q13c'] ?? 'No',
                $patient_id
            ];

            if (recordExists($conn, 'medical_history', $patient_id)) {
                $history_sql = "UPDATE medical_history SET 
                    q1 = ?, q2 = ?, q3 = ?, q4 = ?, q5 = ?,
                    medications = ?, q6 = ?, q7 = ?, q8 = ?,
                    q9 = ?, q10 = ?, q11 = ?, q12 = ?,
                    q13a = ?, q13b = ?, q13c = ? 
                    WHERE patient_id = ?";
            } else {
                $history_sql = "INSERT INTO medical_history 
                    (q1, q2, q3, q4, q5, medications, q6, q7, q8,
                    q9, q10, q11, q12, q13a, q13b, q13c, patient_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }

            executeQuery($conn, $history_sql, $history_params);

            $conn->commit();
            $update_status = 'success';
            $success_message = 'Profile updated successfully!';
        } else {
            throw new Exception(implode(", ", $errors));
        }
    } catch (Exception $e) {
        if ($conn->connect_error === null) {
            $conn->rollback();
        }
        $update_status = 'error';
        $error_message = $e->getMessage();
    }

    // Handle AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'status' => $update_status,
            'message' => $update_status === 'success' ? $success_message : $error_message
        ]);
        exit;
    }
}

$conn->close();