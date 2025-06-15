<?php

session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

$studentNo = $_SESSION['student_id'];

$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, cr.description as general_description, 
           srd.description as student_description, scs.status, scs.updated_at, 
           d.DepartmentName, scs.approved_by
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    LEFT JOIN student_requirement_descriptions srd ON scs.requirement_id = srd.requirement_id AND scs.studentNo = srd.studentNo
    JOIN departments d ON cr.requirement_id = d.DepartmentID
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