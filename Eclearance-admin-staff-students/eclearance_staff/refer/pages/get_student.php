<?php
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['registration_no'])) {
    echo json_encode(['error' => 'Registration number is required']);
    exit;
}

$registration_no = $conn->real_escape_string($_GET['registration_no']);

try {
    $query = "SELECT s.*, p.ProgramTitle, l.LevelName 
              FROM students s 
              JOIN programs p ON s.ProgramCode = p.ProgramCode 
              JOIN levels l ON s.Level = l.LevelID 
              WHERE s.RegistrationNo = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $registration_no);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Student not found");
    }
    
    $student = $result->fetch_assoc();
    echo json_encode($student);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 