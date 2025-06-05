<?php
session_start();
require_once 'includes/db_connect.php';

// Get the year level from the request
$yearLevel = $_GET['level'] ?? '';

if (empty($yearLevel)) {
    http_response_code(400);
    echo json_encode(['error' => 'Year level is required']);
    exit();
}

// Get sections for the selected year level
$query = $conn->prepare("
    SELECT SectionCode, SectionTitle 
    FROM sections 
    WHERE YearLevel = ?
    ORDER BY SectionCode
");
$query->bind_param("i", $yearLevel);
$query->execute();
$result = $query->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = [
        'code' => $row['SectionCode'],
        'title' => $row['SectionTitle']
    ];
}

header('Content-Type: application/json');
echo json_encode($sections);
?> 