<?php
include '../includes/db_connect.php';

header('Content-Type: application/json');

$studentNo = $_GET['student_no'] ?? '';

$response = ['success' => false, 'requirements' => []];

if (!empty($studentNo)) {
    // Fetch all clearance requirements and their status for the student
    $query = "SELECT cr.requirement_name, 
                     COALESCE(scs.status, 'Pending') as status, 
                     srd.description, 
                     scs.comments,
                     scs.approved_by,
                     scs.date_approved
              FROM clearance_requirements cr
              LEFT JOIN student_clearance_status scs ON cr.requirement_id = scs.requirement_id AND scs.studentNo = ?
              LEFT JOIN student_requirement_descriptions srd ON cr.requirement_id = srd.requirement_id AND srd.studentNo = ?
              ORDER BY cr.requirement_id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $studentNo, $studentNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['requirements'][] = $row;
        }
    }
}

echo json_encode($response);
?> 