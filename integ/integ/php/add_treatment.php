<?php
// add_treatment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    try {
        $conn->begin_transaction();
        
        // Get the patient_id from POST data
        $patient_id = $_POST['patient_id'] ?? null;
        
        if (!$patient_id) {
            throw new Exception("Patient ID is required");
        }

        // Validate that the patient exists
        $check_patient = $conn->prepare("SELECT patient_id FROM patient_profiles WHERE patient_id = ?");
        $check_patient->bind_param("i", $patient_id);
        $check_patient->execute();
        $result = $check_patient->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Invalid patient ID");
        }
        $check_patient->close();

        $toothNumber = $_POST['toothNumber'];
        $treatment = $_POST['treatment'];
        $date = $_POST['date'];
        $status = $_POST['status'];
        $progress = $_POST['progress'];
        $notes = $_POST['notes'] ?? '';

        // First, ensure the tooth exists in tooth_data or insert it
        $tooth_stmt = $conn->prepare("
            INSERT INTO tooth_data (patient_id, tooth_number, status,last_checked)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                last_checked = VALUES(last_checked)
        ");
        
        $tooth_stmt->bind_param("iiss", 
            $patient_id,
            $toothNumber,
            $status,
            $date
        );
        
        $tooth_stmt->execute();
        $tooth_stmt->close();

        // Then insert the treatment
        $treatment_stmt = $conn->prepare("
            INSERT INTO treatments (patient_id, tooth_number, treatment, date, status, progress, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $treatment_stmt->bind_param("iisssss", 
            $patient_id,
            $toothNumber, 
            $treatment, 
            $date, 
            $status, 
            $progress,
            $notes
        );
        
        $treatment_stmt->execute();
        $treatment_stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Treatment added successfully',
            'id' => $conn->insert_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in add_treatment.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } finally {
        $conn->close();
    }
}