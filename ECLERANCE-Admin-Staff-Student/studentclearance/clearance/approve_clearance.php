<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['account_type'], ['Admin', 'Staff'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requirement_id = $_POST['requirement_id'] ?? '';
    $studentNo = $_POST['studentNo'] ?? '';
    $staff_id = $_SESSION['staff_id'];

    if (empty($requirement_id) || empty($studentNo)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    // Get staff name
    $staffQuery = $conn->prepare("SELECT FirstName, LastName FROM staff WHERE StaffID = ?");
    $staffQuery->bind_param("i", $staff_id);
    $staffQuery->execute();
    $staffResult = $staffQuery->get_result();
    $staff = $staffResult->fetch_assoc();
    
    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'Staff information not found']);
        exit();
    }
    
    $approved_by = $staff['FirstName'] . ' ' . $staff['LastName'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if record exists
        $checkQuery = $conn->prepare("SELECT * FROM student_clearance_status WHERE studentNo = ? AND requirement_id = ?");
        $checkQuery->bind_param("si", $studentNo, $requirement_id);
        $checkQuery->execute();
        $checkResult = $checkQuery->get_result();

        if ($checkResult->num_rows > 0) {
            // Update existing record
            $updateQuery = $conn->prepare("
                UPDATE student_clearance_status 
                SET status = 'Approved', 
                    updated_at = CURRENT_TIMESTAMP,
                    approved_by = ?,
                    StaffID = ?,
                    date_approved = CURRENT_TIMESTAMP
                WHERE studentNo = ? AND requirement_id = ?
            ");
            $updateQuery->bind_param("siss", $approved_by, $staff_id, $studentNo, $requirement_id);
        } else {
            // Insert new record
            $updateQuery = $conn->prepare("
                INSERT INTO student_clearance_status 
                (studentNo, requirement_id, status, updated_at, StaffID, approved_by, date_approved) 
                VALUES (?, ?, 'Approved', CURRENT_TIMESTAMP, ?, ?, CURRENT_TIMESTAMP)
            ");
            $updateQuery->bind_param("siss", $studentNo, $requirement_id, $staff_id, $approved_by);
        }

        if (!$updateQuery->execute()) {
            throw new Exception("Failed to update clearance status: " . $conn->error);
        }

        // Update overall clearance status
        $totalReq = $conn->query("SELECT COUNT(*) as total FROM clearance_requirements")->fetch_assoc()['total'];
        $approvedReq = $conn->query("SELECT COUNT(*) as approved FROM student_clearance_status WHERE studentNo = '$studentNo' AND status = 'Approved'")->fetch_assoc()['approved'];
        $overallStatus = ($approvedReq == $totalReq) ? 'Completed' : 'Pending';

        // Update or insert overall clearance record
        $checkClearance = $conn->query("SELECT * FROM clearance WHERE studentNo = '$studentNo'");
        if ($checkClearance->num_rows > 0) {
            $conn->query("UPDATE clearance SET status = '$overallStatus' WHERE studentNo = '$studentNo'");
        } else {
            $conn->query("INSERT INTO clearance (studentNo, status) VALUES ('$studentNo', '$overallStatus')");
        }

        // Log the activity
        $logQuery = $conn->prepare("
            INSERT INTO activity_logs (user_id, user_type, action, details)
            VALUES (?, 'Staff', 'Approved Clearance', ?)
        ");
        $details = "Approved clearance for student $studentNo, requirement $requirement_id";
        $logQuery->bind_param("is", $staff_id, $details);
        
        if (!$logQuery->execute()) {
            throw new Exception("Failed to log activity: " . $conn->error);
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 