<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['account_type'], ['Admin', 'Staff'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$studentNo = $_GET['studentNo'] ?? '';

if (empty($studentNo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Student number is required']);
    exit();
}

// Get clearance status with department information
$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, cr.description as general_description, 
           srd.description as student_description, scs.status, scs.updated_at, 
           d.DepartmentName, scs.approved_by, scs.requirement_id, scs.StaffID,
           s.FirstName as staff_firstname, s.LastName as staff_lastname
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    LEFT JOIN student_requirement_descriptions srd ON scs.requirement_id = srd.requirement_id AND scs.studentNo = srd.studentNo
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    LEFT JOIN staff s ON scs.StaffID = s.StaffID
    WHERE scs.studentNo = ?
");
$clearanceQuery->bind_param("s", $studentNo);
$clearanceQuery->execute();
$result = $clearanceQuery->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?> 