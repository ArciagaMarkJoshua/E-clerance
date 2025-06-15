<?php
session_start();
include 'includes/db_connect.php'; // Adjust path as necessary

ini_set('display_errors', 'Off');
error_reporting(E_ALL);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'csv_import.log');

if (isset($_POST['upload_csv'])) {
    if (empty($_FILES['csv_file']['name'])) {
        $_SESSION['message'] = "Error: No CSV file uploaded.";
        $_SESSION['message_type'] = "error";
        header("Location: pages/student_management.php");
        exit();
    }

    $file_mimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );

    if (!in_array($_FILES['csv_file']['type'], $file_mimes)) {
        $_SESSION['message'] = "Error: Invalid file type. Please upload a CSV file.";
        $_SESSION['message_type'] = "error";
        header("Location: pages/student_management.php");
        exit();
    }

    $file_path = $_FILES['csv_file']['tmp_name'];
    if (!file_exists($file_path)) {
        $_SESSION['message'] = "Error: Uploaded file not found.";
        $_SESSION['message_type'] = "error";
        header("Location: pages/student_management.php");
        exit();
    }

    // Read the entire file content
    $file_content = file_get_contents($file_path);
    error_log("Raw file content:\n" . $file_content);
    
    // Convert line endings to Unix style
    $file_content = str_replace(["\r\n", "\r"], "\n", $file_content);
    
    // Split into lines
    $lines = explode("\n", $file_content);
    error_log("Number of lines after split: " . count($lines));
    
    // Remove empty lines
    $lines = array_filter($lines, 'trim');
    error_log("Number of lines after removing empty lines: " . count($lines));
    
    if (empty($lines)) {
        $_SESSION['message'] = "Error: CSV file is empty.";
        $_SESSION['message_type'] = "error";
        header("Location: pages/student_management.php");
        exit();
    }

    // Get headers from first line
    $header = str_getcsv(array_shift($lines));
    error_log("Headers found: " . print_r($header, true));
    
    // Clean and validate headers
    $expected_headers = [
        'studentNo', 'username', 'email', 'lastName', 'firstName', 'middleName',
        'programCode', 'level', 'sectionCode', 'academicYear', 'semester'
    ];

    // Clean headers: remove BOM, whitespace, and convert to lowercase
    $cleaned_header = array_map(function($h) {
        // Remove BOM and other invisible characters
        $h = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $h);
        // Trim whitespace
        $h = trim($h);
        return $h;
    }, $header);
    error_log("Cleaned headers: " . print_r($cleaned_header, true));

    // Check for missing or extra headers
    $missing_headers = array_diff($expected_headers, $cleaned_header);
    $extra_headers = array_diff($cleaned_header, $expected_headers);

    if (!empty($missing_headers)) {
        $_SESSION['message'] = "Error: Missing required CSV headers: " . implode(', ', $missing_headers);
        $_SESSION['message_type'] = "error";
        header("Location: pages/student_management.php");
        exit();
    }

    if (!empty($extra_headers)) {
        $_SESSION['message'] = "Warning: Extra headers found in CSV: " . implode(', ', $extra_headers);
        $_SESSION['message_type'] = "warning";
    }

    // Create header map for case-insensitive access
    $header_map = array_combine($cleaned_header, $header);
    error_log("Header map: " . print_r($header_map, true));

    $num_imported = 0;
    $errors = [];
    $default_password_hash = password_hash("Reimagine2025", PASSWORD_DEFAULT);
    $row_number = 1; // Start from 1 to account for header row

    foreach ($lines as $line) {
        $row_number++;
        error_log("\nProcessing row {$row_number}: " . $line);
        
        // Skip empty lines
        if (empty(trim($line))) {
            error_log("Skipping empty line");
            continue;
        }

        // Parse CSV line
        $row = str_getcsv($line);
        error_log("Parsed row data: " . print_r($row, true));
        
        if (count($row) !== count($header)) {
            $errors[] = "Row {$row_number}: Column count mismatch. Expected " . count($header) . " columns, got " . count($row);
            error_log("Column count mismatch. Expected: " . count($header) . ", Got: " . count($row));
            continue;
        }

        // Map row data to header names
        $student_data = array_combine($header, $row);
        error_log("Mapped student data: " . print_r($student_data, true));

        // Extract and clean data
        $studentNo = trim($student_data['studentNo']);
        $username = trim($student_data['username']);
        $email = trim($student_data['email']);
        $lastName = trim($student_data['lastName']);
        $firstName = trim($student_data['firstName']);
        $middleName = trim($student_data['middleName'] ?? '');
        $programCode = trim($student_data['programCode']);
        $level = intval(trim($student_data['level']));
        $sectionCode = trim($student_data['sectionCode']);
        $academicYear = trim($student_data['academicYear']);
        $semester = trim($student_data['semester']);

        error_log("Extracted and cleaned data:");
        error_log("studentNo: " . $studentNo);
        error_log("username: " . $username);
        error_log("email: " . $email);
        error_log("lastName: " . $lastName);
        error_log("firstName: " . $firstName);
        error_log("middleName: " . $middleName);
        error_log("programCode: " . $programCode);
        error_log("level: " . $level);
        error_log("sectionCode: " . $sectionCode);
        error_log("academicYear: " . $academicYear);
        error_log("semester: " . $semester);

        // Validate required fields
        $required_fields = [
            'studentNo' => $studentNo,
            'username' => $username,
            'email' => $email,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'programCode' => $programCode,
            'level' => $level,
            'sectionCode' => $sectionCode,
            'academicYear' => $academicYear,
            'semester' => $semester
        ];

        $missing_fields = array_filter($required_fields, function($value) {
            return empty($value);
        });

        if (!empty($missing_fields)) {
            $errors[] = "Row {$row_number}: Missing required fields: " . implode(', ', array_keys($missing_fields));
            error_log("Missing fields: " . print_r($missing_fields, true));
            continue;
        }

        // Validate foreign keys
        $validations = [
            'programCode' => "SELECT 1 FROM programs WHERE ProgramCode = ?",
            'level' => "SELECT 1 FROM levels WHERE LevelID = ?",
            'sectionCode' => "SELECT 1 FROM sections WHERE SectionCode = ?",
            'academicYear' => "SELECT 1 FROM academicyears WHERE AcademicYear = ?",
            'semester' => "SELECT 1 FROM semesters WHERE Semester = ?"
        ];

        foreach ($validations as $field => $query) {
            $stmt = $conn->prepare($query);
            $value = $field === 'level' ? $level : ${$field};
            $stmt->bind_param($field === 'level' ? 'i' : 's', $value);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $errors[] = "Row {$row_number}: Invalid {$field}: {$value}";
                $stmt->close();
                continue 2; // Skip to next row
            }
            $stmt->close();
        }

        // Check for duplicate studentNo or Email
        $check_duplicate_stmt = $conn->prepare("SELECT RegistrationNo FROM students WHERE studentNo = ? OR Email = ?");
        $check_duplicate_stmt->bind_param("ss", $studentNo, $email);
        $check_duplicate_stmt->execute();
        if ($check_duplicate_stmt->get_result()->num_rows > 0) {
            $errors[] = "Row {$row_number}: Duplicate studentNo or Email: {$studentNo} / {$email}";
            $check_duplicate_stmt->close();
            continue;
        }
        $check_duplicate_stmt->close();

        // Generate registration number
        $current_year = date('Y');
        $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
        $stmt_reg = $conn->prepare($last_reg_query);
        $year_pattern = $current_year . "-%";
        $stmt_reg->bind_param("s", $year_pattern);
        $stmt_reg->execute();
        $result_reg = $stmt_reg->get_result();
        
        if ($result_reg->num_rows > 0) {
            $last_reg = $result_reg->fetch_assoc()['RegistrationNo'];
            $last_number = intval(substr($last_reg, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
        $stmt_reg->close();

        // Insert student data
        $insert_query = "INSERT INTO students (
            RegistrationNo, studentNo, Username, Email, PasswordHash, 
            LastName, FirstName, Mname, ProgramCode, Level, SectionCode, 
            AcademicYear, Semester, AccountType, IsActive
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student', 1)";

        $stmt_insert = $conn->prepare($insert_query);
        if (!$stmt_insert) {
            $errors[] = "Row {$row_number}: Database error preparing insert: " . $conn->error;
            continue;
        }

        $stmt_insert->bind_param(
            "sssssssssisss",
            $registration_no, $studentNo, $username, $email, $default_password_hash,
            $lastName, $firstName, $middleName, $programCode, $level, $sectionCode,
            $academicYear, $semester
        );

        if ($stmt_insert->execute()) {
            $num_imported++;
        } else {
            $errors[] = "Row {$row_number}: Error inserting student: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }

    if (empty($errors)) {
        $_SESSION['message'] = "Successfully imported {$num_imported} students.";
        $_SESSION['message_type'] = "success";
    } else {
        $error_message = "Import completed with errors. Successfully imported {$num_imported} students.";
        if (count($errors) > 0) {
            $error_message .= "\nErrors:\n" . implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $error_message .= "\n... and " . (count($errors) - 10) . " more errors.";
            }
        }
        $_SESSION['message'] = $error_message;
        $_SESSION['message_type'] = "error";
    }

    header("Location: pages/student_management.php");
    exit();
}

$_SESSION['message'] = "Error: Invalid request.";
$_SESSION['message_type'] = "error";
header("Location: pages/student_management.php");
exit();
?> 