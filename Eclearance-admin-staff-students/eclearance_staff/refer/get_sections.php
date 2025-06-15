<?php
ini_set('display_errors', 'Off');
error_reporting(E_ALL);
include __DIR__ . '/includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['year_level']) || !isset($_GET['program_code'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$year_level = intval($_GET['year_level']);
$program_code = $conn->real_escape_string($_GET['program_code']);

try {
    // First check if the program exists
    $check_program = "SELECT ProgramCode FROM programs WHERE ProgramCode = ?";
    $stmt = $conn->prepare($check_program);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $program_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Invalid program code");
    }
    $stmt->close();

    // Get sections for the program and year level
    $query = "SELECT s.*, p.ProgramTitle 
              FROM sections s 
              JOIN programs p ON s.ProgramCode = p.ProgramCode 
              WHERE s.YearLevel = ? AND s.ProgramCode = ?
              ORDER BY s.SectionCode";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("is", $year_level, $program_code);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $sections = [];

    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            'SectionCode' => $row['SectionCode'],
            'SectionTitle' => $row['SectionTitle'],
            'ProgramTitle' => $row['ProgramTitle']
        ];
    }

    if (empty($sections)) {
        // If no sections exist, create them
        $letters = ['A', 'B', 'C', 'D'];
        foreach ($letters as $letter) {
            $section_code = $program_code . $year_level . $letter;
            $section_title = $program_code . $year_level . $letter;
            
            // Check if section already exists
            $check_section = "SELECT SectionCode FROM sections WHERE SectionCode = ?";
            $check_stmt = $conn->prepare($check_section);
            $check_stmt->bind_param("s", $section_code);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 0) {
                $insert_query = "INSERT INTO sections (SectionCode, SectionTitle, YearLevel, ProgramCode) 
                               VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                if (!$insert_stmt) {
                    throw new Exception("Error preparing insert statement: " . $conn->error);
                }
                $insert_stmt->bind_param("ssis", $section_code, $section_title, $year_level, $program_code);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Error creating section: " . $insert_stmt->error);
                }
                $sections[] = [
                    'SectionCode' => $section_code,
                    'SectionTitle' => $section_title,
                    'ProgramTitle' => $program_code
                ];
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }

    echo json_encode($sections);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 