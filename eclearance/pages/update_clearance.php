<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    $_SESSION['error'] = "Unauthorized access. Please log in.";
    header("Location: login.php");
    exit();
}

// Get user's role from session
$is_admin = ($_SESSION['role'] ?? '') === 'Admin' || ($_SESSION['account_type'] ?? '') === 'Admin';
$staffID = $_SESSION['staff_id'];

// Create requirement-department mapping
$requirement_departments = [
    1 => 'College Library',
    2 => 'Guidance Office',
    3 => 'Office of the Dean',
    4 => 'Office of the Finance Director',
    5 => 'Office of the Registrar',
    6 => 'Property Custodian',
    7 => 'Student Council'
];

try {
    // Validate inputs
    if (empty($_POST['studentNo']) || empty($_POST['requirement_id']) || empty($_POST['status'])) {
        throw new Exception("Missing required parameters");
    }

    $studentNo = $conn->real_escape_string($_POST['studentNo']);
    $requirement_id = intval($_POST['requirement_id']);
    $status = $conn->real_escape_string($_POST['status']);

    if (!in_array($status, ['Pending', 'Approved'])) {
        throw new Exception("Invalid status value");
    }

    // ADMIN CAN BYPASS DEPARTMENT CHECK
    if (!$is_admin) {
        // For non-admin users, check department permission
        $user_department = $_SESSION['department'] ?? '';
        if ($requirement_departments[$requirement_id] != $user_department) {
            throw new Exception("You don't have permission to update this requirement");
        }
    }

    // Get staff name for approved_by field
    $staffQuery = $conn->prepare("SELECT FirstName, LastName FROM staff WHERE StaffID = ?");
    $staffQuery->bind_param("i", $staffID);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();
    $staff = $staffResult->fetch_assoc();
    
    if (!$staff) {
        throw new Exception("Staff information not found");
    }
    
    $approved_by = $staff['FirstName'] . ' ' . $staff['LastName'];

    // Start transaction
    $conn->begin_transaction();

    // Check if student exists
    $stmt = $conn->prepare("SELECT studentNo FROM students WHERE studentNo = ?");
    if (!$stmt) {
        throw new Exception("Database error while checking student");
    }
    $stmt->bind_param("s", $studentNo);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        throw new Exception("Student not found");
    }
    $stmt->close();

    // Check if requirement exists
    $stmt = $conn->prepare("SELECT requirement_id FROM clearance_requirements WHERE requirement_id = ?");
    if (!$stmt) {
        throw new Exception("Database error while checking requirement");
    }
    $stmt->bind_param("i", $requirement_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        throw new Exception("Invalid clearance requirement");
    }
    $stmt->close();

    // Check if record exists
    $check_query = "SELECT * FROM student_clearance_status WHERE studentNo = ? AND requirement_id = ?";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception("Database error while checking clearance status");
    }
    $stmt->bind_param("si", $studentNo, $requirement_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    $stmt->close();

    if ($check_result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE student_clearance_status SET 
                 status = ?, 
                 updated_at = NOW(), 
                 StaffID = ?,
                 approved_by = ?,
                 date_approved = CASE WHEN ? = 'Approved' THEN NOW() ELSE date_approved END 
                 WHERE studentNo = ? AND requirement_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error while updating clearance status");
        }
        $stmt->bind_param("sissis", $status, $staffID, $approved_by, $status, $studentNo, $requirement_id);
    } else {
        // Insert new record
        $query = "INSERT INTO student_clearance_status 
                 (studentNo, requirement_id, status, updated_at, StaffID, approved_by, date_approved) 
                 VALUES (?, ?, ?, NOW(), ?, ?, CASE WHEN ? = 'Approved' THEN NOW() ELSE NULL END)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error while inserting clearance status");
        }
        $stmt->bind_param("sissis", $studentNo, $requirement_id, $status, $staffID, $approved_by, $status);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error updating clearance: " . $stmt->error);
    }
    $stmt->close();

    // Update overall clearance status
    update_overall_clearance($studentNo, $conn);
    
    // Log the activity
    $logQuery = $conn->prepare("
        INSERT INTO activity_logs (user_id, user_type, action, details)
        VALUES (?, 'Staff', ?, ?)
    ");
    $action = $status === 'Approved' ? 'Approved Clearance' : 'Updated Clearance Status';
    $details = "$status clearance for student $studentNo, requirement $requirement_id";
    $logQuery->bind_param("iss", $staffID, $action, $details);
    
    if (!$logQuery->execute()) {
        throw new Exception("Failed to log activity: " . $conn->error);
    }

    $conn->commit();
    $_SESSION['success'] = "Clearance status updated successfully";
    header("Location: eclearance.php?studentNo=" . $studentNo);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Clearance update error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: eclearance.php?studentNo=" . ($studentNo ?? ''));
    exit();
}

function update_overall_clearance($studentNo, $conn) {
    // Count total requirements
    $total_req = $conn->query("SELECT COUNT(*) as total FROM clearance_requirements")->fetch_assoc()['total'];
    
    // Count approved requirements for this student
    $approved_req = $conn->query("SELECT COUNT(*) as approved 
                                 FROM student_clearance_status 
                                 WHERE studentNo = '$studentNo' AND status = 'Approved'")
                         ->fetch_assoc()['approved'];
    
    // Determine overall status
    $overall_status = ($approved_req == $total_req) ? 'Completed' : 'Pending';
    
    // Update or insert overall clearance record
    $check_query = "SELECT * FROM clearance WHERE studentNo = '$studentNo'";
    if ($conn->query($check_query)->num_rows > 0) {
        $update_query = "UPDATE clearance SET status = '$overall_status' WHERE studentNo = '$studentNo'";
    } else {
        $update_query = "INSERT INTO clearance (studentNo, status) VALUES ('$studentNo', '$overall_status')";
    }
    
    $conn->query($update_query);
}