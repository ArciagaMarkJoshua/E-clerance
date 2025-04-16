<?php
session_start();
include 'db_connect.php';

// Get form data
$studentNo = $conn->real_escape_string($_POST['studentNo'] ?? '');
$requirement_id = intval($_POST['requirement_id'] ?? 0);
$status = $conn->real_escape_string($_POST['status'] ?? '');

// Default staff ID
$staffID = 1; // make sure StaffID 1 exists in your 'staff' table

// Validate inputs
if (empty($studentNo) || $requirement_id <= 0 || empty($status)) {
    die("Error: Missing required parameters");
}

if (!in_array($status, ['Pending', 'Approved'])) {
    die("Error: Invalid status value");
}

// Check if student exists
$student_check = $conn->query("SELECT studentNo FROM students WHERE studentNo = '$studentNo'");
if ($student_check->num_rows == 0) {
    die("Error: Student not found");
}

// Check if requirement exists
$req_check = $conn->query("SELECT requirement_id FROM clearance_requirements WHERE requirement_id = $requirement_id");
if ($req_check->num_rows == 0) {
    die("Error: Invalid clearance requirement");
}

// Check if record exists
$check_query = "SELECT * FROM student_clearance_status 
                WHERE studentNo = '$studentNo' AND requirement_id = $requirement_id";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    // Update existing record
    $query = "UPDATE student_clearance_status 
              SET status = '$status', 
                  updated_at = NOW(),
                  StaffID = $staffID
              WHERE studentNo = '$studentNo' AND requirement_id = $requirement_id";
} else {
    // Insert new record
    $query = "INSERT INTO student_clearance_status 
              (studentNo, requirement_id, status, updated_at, StaffID)
              VALUES ('$studentNo', $requirement_id, '$status', NOW(), $staffID)";
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
