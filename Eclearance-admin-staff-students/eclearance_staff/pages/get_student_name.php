<?php
include '../includes/db_connect.php';

header('Content-Type: application/json');

$studentNo = $_GET['student_no'] ?? '';

$response = ['success' => false, 'student_name' => 'N/A'];

if (!empty($studentNo)) {
    $query = "SELECT FirstName, LastName FROM students WHERE studentNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $studentNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $response['success'] = true;
        $response['student_name'] = htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']);
    }
}

echo json_encode($response);
?> 