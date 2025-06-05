<?php
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['year_level'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Year level is required']);
    exit;
}

$year_level = intval($_GET['year_level']);

try {
    // Validate year level
    if ($year_level < 1 || $year_level > 5) {
        throw new Exception("Invalid year level");
    }

    $query = "SELECT SectionCode, SectionTitle FROM sections WHERE YearLevel = ? ORDER BY SectionTitle";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error while preparing query.");
    }
    
    $stmt->bind_param("i", $year_level);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    if (empty($sections)) {
        echo json_encode(['error' => 'No sections found for this year level']);
    } else {
        echo json_encode($sections);
    }
    
} catch (Exception $e) {
    error_log("Error fetching sections: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching sections']);
}
?> 