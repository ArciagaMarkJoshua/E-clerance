<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in as staff
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Get staff department
$user_department = $_SESSION['department'] ?? '';

// Create requirement-department mapping
$requirement_departments = [
    1 => 'College Library',
    2 => 'Guidance Office',
    3 => 'Office of the Dean',
    4 => 'Office of the Finance Director',
    5 => 'Office of the Registrar',
    6 => 'Property Custodian',
    7 => 'Student Council',
    8 => 'Clinic',
    9 => 'MOS'
];

// Map departments to requirement IDs
$department_requirements = array_flip($requirement_departments);
$user_requirement_id = $department_requirements[$user_department] ?? 0;

// Handle POST request for updating clearance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (!isset($_POST['studentNo']) || !isset($_POST['status'])) {
            throw new Exception("Missing required fields");
        }

        $student_no = $_POST['studentNo'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'] ?? '';

        // Verify student exists
        $student_query = "SELECT studentNo FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("s", $student_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student not found");
        }

        // Update or insert clearance status
        $update_query = "INSERT INTO student_clearance_status 
                        (studentNo, requirement_id, status, remarks, updated_by, updated_at) 
                        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE 
                        status = VALUES(status),
                        remarks = VALUES(remarks),
                        updated_by = VALUES(updated_by),
                        updated_at = CURRENT_TIMESTAMP";

        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing update query");
        }

        $stmt->bind_param("sisss", 
            $student_no, 
            $user_requirement_id, 
            $status, 
            $remarks, 
            $_SESSION['staff_id']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating clearance status");
        }

        // Log the update
        $log_query = "INSERT INTO clearance_logs 
                     (studentNo, requirement_id, status, remarks, updated_by, updated_at) 
                     VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("sisss", 
            $student_no, 
            $user_requirement_id, 
            $status, 
            $remarks, 
            $_SESSION['staff_id']
        );
        $stmt->execute();

        $_SESSION['success'] = "Clearance status updated successfully";
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect back to eclearance page
    header("Location: eclearance.php");
    exit();
}

// If not POST request, redirect to eclearance page
header("Location: eclearance.php");
exit();
?> 