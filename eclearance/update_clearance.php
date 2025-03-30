<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and has permission

// Get form data
$studentNo = $_POST['studentNo'] ?? '';
$requirement_id = $_POST['requirement_id'] ?? '';
$status = $_POST['status'] ?? '';
$staffID = $_SESSION['staff_id'] ?? 0; // Assuming staff ID is stored in session

// Validate inputs
if (empty($studentNo) || empty($requirement_id) || empty($status)) {
    die("Missing required parameters");
}

if (!in_array($status, ['Pending', 'Approved'])) {
    die("Invalid status value");
}

// Check if record exists
$check_query = "SELECT * FROM student_clearance_status 
                WHERE studentNo = '$studentNo' AND requirement_id = $requirement_id";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    // Update existing record
    $query = "UPDATE student_clearance_status 
              SET status = '$status', 
                  StaffID = $staffID,
                  updated_at = NOW()
              WHERE studentNo = '$studentNo' AND requirement_id = $requirement_id";
} else {
    // Insert new record
    $query = "INSERT INTO student_clearance_status 
              (studentNo, requirement_id, StaffID, status, updated_at)
              VALUES ('$studentNo', $requirement_id, $staffID, '$status', NOW())";
}

// Execute query
if ($conn->query($query)) {
    // Update overall clearance status if all requirements are approved
    update_overall_clearance($studentNo, $conn);
    
    // Redirect back to the clearance page
    header("Location: eclearance.php?studentNo=$studentNo");
    exit();
} else {
    die("Error updating clearance: " . $conn->error);
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
?>