<?php
session_start();
include '../includes/db_connect.php'; // Database connection

// Handle AJAX POST requests at the very top
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_student') {
        try {
            $data = [
                'studentNo' => $_POST['student_no'],
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'lastName' => $_POST['last_name'],
                'firstName' => $_POST['first_name'],
                'middleName' => $_POST['middle_name'] ?? '',
                'programCode' => $_POST['program'],
                'level' => $_POST['level'],
                'sectionCode' => $_POST['section'],
                'academicYear' => $_POST['academic_year'],
                'semester' => $_POST['semester']
            ];
            $error = null;
            $registration_no = add_student_record($conn, $data, $error, true);
            if ($registration_no === false) {
                throw new Exception($error);
            }
            echo json_encode(['success' => true, 'message' => "Student added successfully! Registration Number: " . $registration_no, 'new_registration_no' => $registration_no]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
            exit();
        }
    } else if ($_POST['action'] === 'edit_student') {
        try {
            $data = [
                'registrationNo' => $_POST['registration_no_for_action'],
                'studentNo' => $_POST['student_no'],
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $_POST['password'] ?? '',
                'lastName' => $_POST['last_name'],
                'firstName' => $_POST['first_name'],
                'middleName' => $_POST['middle_name'] ?? '',
                'programCode' => $_POST['program'],
                'level' => $_POST['level'],
                'sectionCode' => $_POST['section'],
                'academicYear' => $_POST['academic_year'],
                'semester' => $_POST['semester']
            ];
            $error = null;
            $result = update_student_record($conn, $data, $error);
            if ($result === false) {
                throw new Exception($error);
            }
            echo json_encode(['success' => true, 'message' => "Student updated successfully!"]);
            exit();
        } catch (Exception $e) {
            error_log("Edit student error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
            exit();
        }
    } else if ($_POST['action'] === 'archive_student') {
        try {
            if (!isset($_POST['registration_no']) || empty($_POST['registration_no'])) {
                throw new Exception("Registration number is required to archive a student.");
            }

            $registration_no = $conn->real_escape_string($_POST['registration_no']);

            $archive_query = "UPDATE students SET IsActive = 0 WHERE RegistrationNo = ?";
            $stmt = $conn->prepare($archive_query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param("s", $registration_no);

            if (!$stmt->execute()) {
                throw new Exception("Error archiving student: " . $stmt->error);
            }

            echo json_encode(['success' => true, 'message' => "Student archived successfully!"]);
            exit();

        } catch (Exception $e) {
            error_log("Archive student error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Error archiving student: " . $e->getMessage()]);
            exit();
        }
    } else if ($_POST['action'] === 'restore_student') {
        try {
            if (!isset($_POST['registration_no']) || empty($_POST['registration_no'])) {
                throw new Exception("Registration number is required to restore a student.");
            }

            $registration_no = $conn->real_escape_string($_POST['registration_no']);

            $restore_query = "UPDATE students SET IsActive = 1 WHERE RegistrationNo = ?";
            $stmt = $conn->prepare($restore_query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param("s", $registration_no);

            if (!$stmt->execute()) {
                throw new Exception("Error restoring student: " . $stmt->error);
            }

            echo json_encode(['success' => true, 'message' => "Student restored successfully!"]);
            exit();

        } catch (Exception $e) {
            error_log("Restore student error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Error restoring student: " . $e->getMessage()]);
            exit();
        }
    }
}
// ... existing code ...

// Initialize variables
$next_registration_no = '';
$search_query = "";

// Fetch next available registration number in YYYY-XXXX format
$current_year = date('Y');
$registration_no_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
$stmt = $conn->prepare($registration_no_query);
$year_pattern = $current_year . "-%";
$stmt->bind_param("s", $year_pattern);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $last_reg = $row['RegistrationNo'];
    // Extract the numeric part (XXXX) from the last registration number
    $last_number = intval(substr($last_reg, -4));
    $new_number = $last_number + 1;
} else {
    $new_number = 1;
}

$next_registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);

// Fetch programs for dropdown
$program_query = "SELECT * FROM programs"; 
$program_result = $conn->query($program_query);

// Fetch sections for dropdown
$section_query = "SELECT s.*, p.ProgramTitle 
                 FROM sections s 
                 JOIN programs p ON s.ProgramCode = p.ProgramCode";
$section_result = $conn->query($section_query);

// Fetch levels for dropdown
$level_query = "SELECT * FROM levels";
$level_result = $conn->query($level_query);

// Fetch available academic years
$ay_query = "SELECT DISTINCT AcademicYear FROM academicyears ORDER BY AcademicYear DESC";
$ay_result = $conn->query($ay_query);

// Fetch available semesters
$semester_query = "SELECT DISTINCT Semester FROM semesters ORDER BY Semester";
$semester_result = $conn->query($semester_query);

// Fetch students for display
$student_query = "SELECT s.*, p.ProgramTitle, l.LevelName 
                 FROM students s 
                 JOIN programs p ON s.ProgramCode = p.ProgramCode 
                 JOIN levels l ON s.Level = l.LevelID
                 WHERE s.IsActive = 1";

// Add filter conditions
if (!empty($_POST['filter_program'])) {
    $student_query .= " AND s.ProgramCode = '" . $conn->real_escape_string($_POST['filter_program']) . "'";
}
if (!empty($_POST['filter_section'])) {
    $student_query .= " AND s.SectionCode = '" . $conn->real_escape_string($_POST['filter_section']) . "'";
}
if (!empty($_POST['filter_level'])) {
    $student_query .= " AND s.Level = " . intval($_POST['filter_level']);
}
if (!empty($_POST['filter_semester'])) {
    $student_query .= " AND s.Semester = '" . $conn->real_escape_string($_POST['filter_semester']) . "'";
}

// Add search condition if search query exists
if (!empty($search_query)) {
    $student_query .= " AND (s.RegistrationNo LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                       OR s.Username LIKE '%" . $conn->real_escape_string($search_query) . "%'
                       OR s.Email LIKE '%" . $conn->real_escape_string($search_query) . "%'
                       OR s.LastName LIKE '%" . $conn->real_escape_string($search_query) . "%'
                       OR s.FirstName LIKE '%" . $conn->real_escape_string($search_query) . "%'
                       OR p.ProgramTitle LIKE '%" . $conn->real_escape_string($search_query) . "%')";
}

// Add sorting
$sort_by = isset($_POST['sort_by']) ? $_POST['sort_by'] : 'registration_no';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'desc';

// Map sort_by values to actual column names
$sort_columns = [
    'registration_no' => 's.RegistrationNo',
    'student_no' => 's.studentNo',
    'last_name' => 's.LastName',
    'program' => 'p.ProgramTitle',
    'section' => 's.SectionCode'
];

$sort_column = isset($sort_columns[$sort_by]) ? $sort_columns[$sort_by] : 's.RegistrationNo';
$student_query .= " ORDER BY " . $sort_column . " " . ($sort_order === 'asc' ? 'ASC' : 'DESC');

$student_result = $conn->query($student_query);

// Search functionality
if (isset($_GET['search_query'])) {
    $search_query = $conn->real_escape_string($_GET['search_query']);
    $sql = "SELECT s.*, p.ProgramTitle, sec.SectionTitle, l.LevelName 
            FROM students s
            LEFT JOIN programs p ON s.ProgramCode = p.ProgramCode
            LEFT JOIN sections sec ON s.SectionCode = sec.SectionCode
            LEFT JOIN levels l ON s.Level = l.LevelID
            WHERE 
            s.studentNo LIKE '%$search_query%' OR 
            s.Username LIKE '%$search_query%' OR 
            s.LastName LIKE '%$search_query%' OR 
            s.SectionCode LIKE '%$search_query%' OR 
            s.FirstName LIKE '%$search_query%' OR
            s.AcademicYear LIKE '%$search_query%' OR
            s.Semester LIKE '%$search_query%'";
} else if (isset($_POST['search'])) {
    $search_query = $conn->real_escape_string($_POST['search_query']);
    $sql = "SELECT s.*, p.ProgramTitle, sec.SectionTitle, l.LevelName 
            FROM students s
            LEFT JOIN programs p ON s.ProgramCode = p.ProgramCode
            LEFT JOIN sections sec ON s.SectionCode = sec.SectionCode
            LEFT JOIN levels l ON s.Level = l.LevelID
            WHERE 
            s.studentNo LIKE '%$search_query%' OR 
            s.Username LIKE '%$search_query%' OR 
            s.LastName LIKE '%$search_query%' OR 
            s.SectionCode LIKE '%$search_query%' OR 
            s.FirstName LIKE '%$search_query%' OR
            s.AcademicYear LIKE '%$search_query%' OR
            s.Semester LIKE '%$search_query%'";
} else {
    $sql = "SELECT s.*, p.ProgramTitle, sec.SectionTitle, l.LevelName 
            FROM students s
            LEFT JOIN programs p ON s.ProgramCode = p.ProgramCode
            LEFT JOIN sections sec ON s.SectionCode = sec.SectionCode
            LEFT JOIN levels l ON s.Level = l.LevelID";
}
$result = $conn->query($sql);

// Add Student
if (isset($_POST['add_student'])) {
    try {
        // Validate required fields
        $required_fields = ['student_no', 'username', 'email', 'password', 'confirm_password', 'last_name', 'first_name', 'program', 'section', 'academic_year', 'semester', 'level'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        // Generate registration number (automatic)
        $current_year = date('Y');
        $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
        $stmt = $conn->prepare($last_reg_query);
        $year_pattern = $current_year . "-%";
        $stmt->bind_param("s", $year_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $last_reg = $result->fetch_assoc()['RegistrationNo'];
            $last_number = intval(substr($last_reg, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);

        // Student number (manual input)
        $student_no = $conn->real_escape_string($_POST['student_no']);

        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name'] ?? '');
        $program = $conn->real_escape_string($_POST['program']);
        $section = $conn->real_escape_string($_POST['section']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $semester = $conn->real_escape_string($_POST['semester']);
        $level = intval($_POST['level']);

        // Check if email or student number already exists
        $check_query = "SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $student_no, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['studentNo'] === $student_no) {
                throw new Exception("Student number already exists.");
            }
            if ($row['Email'] === $email) {
                throw new Exception("Email already exists.");
            }
        }
        $stmt->close();

        // Insert new student
        $insert_query = "INSERT INTO students (RegistrationNo, studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, SectionCode, AcademicYear, Semester, Level, AccountType) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssssssssssssi", $registration_no, $student_no, $username, $email, $password, $last_name, $first_name, $middle_name, $program, $section, $academic_year, $semester, $level);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding student: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Student added successfully! Registration Number: " . $registration_no;
        $_SESSION['message_type'] = "success";
        echo json_encode(['success' => true, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type'], 'new_registration_no' => $registration_no]);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        echo json_encode(['success' => false, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type']]);
        exit();
    }
}

// Edit Student
if (isset($_POST['edit_student'])) {
    try {
        // Validate required fields (excluding password for edit)
        $required_fields = ['registration_no_for_action', 'student_no', 'username', 'email', 'last_name', 'first_name', 'program', 'section', 'academic_year', 'semester', 'level'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        $registration_no_for_action = $conn->real_escape_string($_POST['registration_no_for_action']); // Use RegistrationNo
        $student_no = $conn->real_escape_string($_POST['student_no']);
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        
        // Check if email or student number already exists for a different student
        $check_query = "SELECT studentNo, Email FROM students WHERE (studentNo = ? OR Email = ?) AND RegistrationNo != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("sss", $student_no, $email, $registration_no_for_action);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['studentNo'] === $student_no) {
                throw new Exception("Student number already exists for another student.");
            }
            if ($row['Email'] === $email) {
                throw new Exception("Email already exists for another student.");
            }
        }
        $stmt->close();

        // Only update password if new password is provided
        $password_update = '';
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                throw new Exception("Passwords do not match.");
            }
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ', PasswordHash = ?';
        }

        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name'] ?? '');
        $program = $conn->real_escape_string($_POST['program']);
        $section = $conn->real_escape_string($_POST['section']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        
        error_log("DEBUG: POST semester: " . $_POST['semester']);
        $semester = $conn->real_escape_string($_POST['semester']);
        error_log("DEBUG: Escaped semester: " . $semester);

        $level = intval($_POST['level']);

        $update_query = "UPDATE students SET studentNo = ?, Username = ?, Email = ?, LastName = ?, FirstName = ?, Mname = ?, ProgramCode = ?, SectionCode = ?, AcademicYear = ?, Semester = ?, Level = ?" . $password_update . " WHERE RegistrationNo = ?";
        $stmt = $conn->prepare($update_query);

        if (!empty($_POST['password'])) {
            $stmt->bind_param("ssssssssssiss", $student_no, $username, $email, $last_name, $first_name, $middle_name, $program, $section, $academic_year, $semester, $level, $password_hash, $registration_no_for_action);
        } else {
            $stmt->bind_param("ssssssssssis", $student_no, $username, $email, $last_name, $first_name, $middle_name, $program, $section, $academic_year, $semester, $level, $registration_no_for_action);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating student: " . $stmt->error);
        }

        $_SESSION['message'] = "Student updated successfully!";
        $_SESSION['message_type'] = "success";
        echo json_encode(['success' => true, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type']]);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        echo json_encode(['success' => false, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type']]);
        exit();
    }
}

// Archive Student
if (isset($_POST['archive_student'])) {
    try {
        error_log("DEBUG: Archiving student request received.");
        if (!isset($_POST['registration_no'])) {
            throw new Exception("Registration number is required for archiving.");
        }
        
        $registration_no = $conn->real_escape_string($_POST['registration_no']);
        error_log("DEBUG: Archiving student with RegistrationNo: " . $registration_no);
        
        // Update the student's IsActive status to 0 (archived)
        $archive_query = "UPDATE students SET IsActive = 0 WHERE RegistrationNo = ?";
        $stmt = $conn->prepare($archive_query);
        $stmt->bind_param("s", $registration_no);
        
        if (!$stmt->execute()) {
            error_log("ERROR archiving student: " . $stmt->error);
            throw new Exception("Error archiving student: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Student archived successfully!";
        $_SESSION['message_type'] = "success";
        echo json_encode(['success' => true, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type']]);
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        echo json_encode(['success' => false, 'message' => $_SESSION['message'], 'message_type' => $_SESSION['message_type']]);
        exit();
    }
}

// Display message from session if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Bulk upload logic
if (isset($_POST['bulk_upload_submit']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    $bulk_upload_message = '';
    $success = 0;
    $errors = [];
    if (!$handle) {
        $bulk_upload_message = 'Failed to open uploaded file.';
    } else {
        $header = fgetcsv($handle);
        $expected = ['studentNo','username','email','password','lastName','firstName','middleName','programCode','level','sectionCode','academicYear','semester'];
        if ($header !== $expected) {
            $bulk_upload_message = 'CSV header does not match expected columns.';
        } else {
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $data = array_combine($expected, $row);
                // Validate required fields
                foreach (['studentNo','username','email','password','lastName','firstName','programCode','level','sectionCode','academicYear','semester'] as $field) {
                    if (empty($data[$field])) {
                        $errors[] = "Row $rowNum: Missing $field.";
                        continue 2;
                    }
                }
                // Check for duplicate studentNo or email
                $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?");
                $stmt->bind_param("ss", $data['studentNo'], $data['email']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $rowx = $result->fetch_assoc();
                    if ($rowx['studentNo'] === $data['studentNo']) {
                        $errors[] = "Row $rowNum: Student number already exists.";
                    } else {
                        $errors[] = "Row $rowNum: Email already exists.";
                    }
                    $stmt->close();
                    continue;
                }
                $stmt->close();
                // Generate RegistrationNo
                $current_year = date('Y');
                $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
                $stmt = $conn->prepare($last_reg_query);
                $year_pattern = $current_year . "-%";
                $stmt->bind_param("s", $year_pattern);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $last_reg = $result->fetch_assoc()['RegistrationNo'];
                    $last_number = intval(substr($last_reg, -4));
                    $new_number = $last_number + 1;
                } else {
                    $new_number = 1;
                }
                $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
                $stmt->close();
                // Hash password
                $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
                // Insert student
                $insert_query = "INSERT INTO students (RegistrationNo, studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("sssssssssssss", $registration_no, $data['studentNo'], $data['username'], $data['email'], $password_hash, $data['lastName'], $data['firstName'], $data['middleName'], $data['programCode'], $data['level'], $data['sectionCode'], $data['academicYear'], $data['semester']);
                if ($stmt->execute()) {
                    $success++;
                } else {
                    $errors[] = "Row $rowNum: DB error: " . $stmt->error;
                }
                $stmt->close();
            }
            fclose($handle);
        }
    }
    $bulk_upload_message = "$success students added successfully.";
    if ($errors) $bulk_upload_message .= '<br>Errors:<br>' . implode('<br>', $errors);
    $_SESSION['message'] = $bulk_upload_message;
    $_SESSION['message_type'] = ($success > 0 && count($errors) == 0) ? 'success' : 'error';
    header('Location: student_management.php');
    exit();
}

// Helper function for adding a student (single or bulk)
function add_student_record($conn, $data, &$error = null, $generate_registration = true) {
    $required_fields = ['studentNo','username','email','password','lastName','firstName','programCode','level','sectionCode','academicYear','semester'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $error = "Missing $field.";
            return false;
        }
    }
    // Check for duplicate studentNo or email
    $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?");
    $stmt->bind_param("ss", $data['studentNo'], $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $rowx = $result->fetch_assoc();
        if ($rowx['studentNo'] === $data['studentNo']) {
            $error = "Student number already exists.";
        } else {
            $error = "Email already exists.";
        }
        $stmt->close();
        return false;
    }
    $stmt->close();
    // Generate RegistrationNo
    if ($generate_registration) {
        $current_year = date('Y');
        $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
        $stmt = $conn->prepare($last_reg_query);
        $year_pattern = $current_year . "-%";
        $stmt->bind_param("s", $year_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $last_reg = $result->fetch_assoc()['RegistrationNo'];
            $last_number = intval(substr($last_reg, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
        $stmt->close();
    } else {
        $registration_no = $data['registrationNo'];
    }
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    // Insert student
    $insert_query = "INSERT INTO students (RegistrationNo, studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssssssssssss", $registration_no, $data['studentNo'], $data['username'], $data['email'], $password_hash, $data['lastName'], $data['firstName'], $data['middleName'], $data['programCode'], $data['level'], $data['sectionCode'], $data['academicYear'], $data['semester']);
    $result = $stmt->execute();
    if (!$result) {
        $error = "DB error: " . $stmt->error;
        $stmt->close();
        return false;
    }
    $stmt->close();
    return $registration_no;
}

// Helper function for updating a student
function update_student_record($conn, $data, &$error = null) {
    $required_fields = ['registrationNo','studentNo','username','email','lastName','firstName','programCode','level','sectionCode','academicYear','semester'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $error = "Missing $field.";
            return false;
        }
    }
    // Check for duplicate studentNo or email for other students
    $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE (studentNo = ? OR Email = ?) AND RegistrationNo != ?");
    $stmt->bind_param("sss", $data['studentNo'], $data['email'], $data['registrationNo']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $rowx = $result->fetch_assoc();
        if ($rowx['studentNo'] === $data['studentNo']) {
            $error = "Student number already exists for another student.";
        } else {
            $error = "Email already exists for another student.";
        }
        $stmt->close();
        return false;
    }
    $stmt->close();
    // Update student record
    $update_query = "UPDATE students SET studentNo = ?, Username = ?, Email = ?, LastName = ?, FirstName = ?, Mname = ?, ProgramCode = ?, Level = ?, SectionCode = ?, AcademicYear = ?, Semester = ?";
    $params = [
        $data['studentNo'], $data['username'], $data['email'], $data['lastName'], $data['firstName'], $data['middleName'],
        $data['programCode'], $data['level'], $data['sectionCode'], $data['academicYear'], $data['semester']
    ];
    $types = "sssssssssss";
    if (!empty($data['password'])) {
        $update_query .= ", PasswordHash = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        $types .= "s";
    }
    $update_query .= " WHERE RegistrationNo = ?";
    $params[] = $data['registrationNo'];
    $types .= "s";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    if (!$result) {
        $error = "DB error: " . $stmt->error;
        $stmt->close();
        return false;
    }
    $stmt->close();
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Student Management</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
        color: #333;
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 300px;
        background-color: #343079;
        color: white;
        height: 100vh;
        position: fixed;
        padding: 20px 0;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        overflow-y: auto;
        transition: all 0.3s;
    }
   
    .logo-container {
        display: flex;
        align-items: center;
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }

    .logo {
        width: 50px;
        height: 50px;
        margin-right: 15px;
    }

    .logo-text h2 {
        font-size: 16px;
        margin: 0 0 5px 0;
        font-weight: 600;
    }

    .logo-text p {
        font-size: 12px;
        margin: 0;
        opacity: 0.8;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar li a {
        display: flex;
        align-items: center;
        padding: 15px 25px;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 15px;
    }

    .sidebar li a:hover {
        background-color: rgba(255,255,255,0.1);
        padding-left: 30px;
    }

    .sidebar li.active a {
        background-color: rgba(255,255,255,0.2);
    }

    .sidebar li.logout {
        margin-top: auto;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar .icon {
        margin-right: 15px;
        font-size: 18px;
        width: 20px;
        text-align: center;
    }

    .container {
        min-width: calc(100% - 300px); /* Ensure it always accounts for sidebar */
        flex: 1;
        margin-left: 300px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        box-sizing: border-box;
        overflow-y: auto;
    }

    .user-panel {
        display: none; /* Hide by default */
        background-color: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .user-panel h3 {
        margin-top: 0;
        color: #343079;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }

    .form-group input,
    .form-group select {
        padding: 10px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        transition: 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #343079;
        box-shadow: 0 0 0 2px rgba(52, 48, 121, 0.15);
    }

    .form-buttons button {
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: 0.3s;
        color: white;
        margin-right: 10px;
    }

    .form-buttons button[name="add_student"] {
        background-color: #28a745; /* green */
    }

    .form-buttons button[name="add_student"]:hover {
        background-color: #218838;
    }

    .form-buttons button[name="edit_student"] {
        background-color: #17a2b8; /* blue-teal */
    }

    .form-buttons button[name="edit_student"]:hover {
        background-color: #138496;
    }

    .form-buttons button[name="archive_student"] {
        background-color: #dc3545 !important; /* red */
        color: white !important;
    }

    .form-buttons button[name="archive_student"]:hover {
        background-color: #c82333 !important;
    }

    .form-buttons button[type="button"] {
        background-color: #6c757d; /* gray */
    }

    .form-buttons button[type="button"]:hover {
        background-color: #5a6268;
    }

    .search-container {
        margin-bottom: 20px;
    }

    .search-form {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-input-group {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .search-input-group input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px 0 0 6px;
        font-size: 14px;
    }

    .search-button,
    .clear-search-button {
        padding: 10px 15px;
        background-color: #343079;
        color: white;
        border: none;
        font-size: 14px;
        border-radius: 0 6px 6px 0;
        cursor: pointer;
        transition: 0.3s;
    }

    .search-button:hover,
    .clear-search-button:hover {
        background-color: #2c2765;
    }

    .scrollable-content {
        overflow-x: auto;
    }

    .user-list-container h3 {
        margin-bottom: 10px;
        color: #343079;
    }

    table {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
    }

    thead th {
        background-color: #343079;
        color: white;
    }

    tbody tr {
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    tbody tr:hover {
        background-color: #f0f0f0;
    }

    tbody td button {
        background-color: #343079;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
    }

    tbody td button:hover {
        background-color: #2c2765;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
        animation: fadeOut 5s forwards;
        animation-delay: 2s;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        min-width: 300px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    .action-buttons {
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        justify-content: center; /* Center the buttons */
    }

    .action-buttons button {
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 8px;
        border: none;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .action-buttons button.add-btn {
        background-color: #28a745; /* Green */
    }

    .action-buttons button.edit-btn {
        background-color: #17a2b8; /* Blue-teal */
    }

    .action-buttons button.delete-btn {
        background-color: #dc3545; /* Red */
    }

    .action-buttons button:hover {
        opacity: 0.9;
    }

    .back-btn {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
    }

    .back-btn:hover {
        background-color: #5a6268;
    }

    .filter-container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        color: #333;
        font-weight: 500;
    }

    .filter-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .filter-buttons button {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    .filter-buttons .apply-btn {
        background-color: #28a745;
        color: white;
    }

    .filter-buttons .reset-btn {
        background-color: #6c757d;
        color: white;
    }

    .filter-buttons button:hover {
        opacity: 0.9;
    }

    .sort-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .sort-container label {
        font-weight: 500;
        color: #333;
    }

    .sort-container select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
    }

    .navigation-buttons {
        display: inline-block;
        margin-left: 10px;
    }
    .nav-btn {
        padding: 8px 15px;
        margin: 0 5px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .nav-btn:hover {
        background-color: #45a049;
    }
    .nav-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    #bulk_upload_panel .form-buttons button[name="bulk_upload_submit"] {
        background-color: #28a745 !important; /* Green */
        color: white;
    }
    #bulk_upload_panel .form-buttons button[name="bulk_upload_submit"]:hover {
        background-color: #218838 !important;
    }
    #bulk_upload_panel .form-buttons button[name="bulk_upload_submit"]:disabled {
        background-color: #a5d6a7 !important; /* Lighter green for disabled */
        color: #fff !important;
        opacity: 1 !important;
    }
    </style>


    <script>
        // Declare global variables for DOM elements and data that need to be accessed by multiple functions
        let addStudentActionBtn;
        let editStudentActionBtn;
        let deleteStudentActionBtn;
        let studentFormContainer;
        let formAddBtn;
        let formEditBtn;
        let formDeleteBtn;
        let registrationNoInput;
        let studentNoInput;
        let nextRegistrationNo; // This will be populated from PHP on DOMContentLoaded
        // let bulkAddStudentActionBtn;
        // let bulkUploadContainer;

        // Global variable to store selected student data
        let selectedStudentData = null;

        // Add these variables at the top with other global variables
        let currentStudentIndex = -1;
        let studentList = [];

        function showAddStudentForm() {
            document.getElementById('bulk_upload_panel').style.display = 'none';
            document.getElementById('student_form_container').style.display = 'block';
            clearForm();
            addStudentActionBtn.style.display = 'none';
            editStudentActionBtn.style.display = 'none';
            deleteStudentActionBtn.style.display = 'none';
            formAddBtn.style.display = 'inline-block';
            formEditBtn.style.display = 'none';
            formDeleteBtn.style.display = 'none';
            document.getElementById('student_form').action = 'student_management.php'; // Set form action for add
            // Make password fields required for new student
            document.getElementById('password').setAttribute('required', true);
            document.getElementById('confirm_password').setAttribute('required', true);
        }

        function showEditForm() {
            document.getElementById('bulk_upload_panel').style.display = 'none';
            document.getElementById('student_form_container').style.display = 'block';
            if (!selectedStudentData) {
                alert('Please select a student to update.');
                return;
            }
            // ...existing code...
        }

        function showArchiveForm() {
            if (!selectedStudentData) {
                alert('Please select a student to archive.');
                return;
            }
            populateForm(selectedStudentData); // Populate form for confirmation

            studentFormContainer.style.display = 'block';
            addStudentActionBtn.style.display = 'none';
            editStudentActionBtn.style.display = 'none';
            deleteStudentActionBtn.style.display = 'none';
            formAddBtn.style.display = 'none';
            formEditBtn.style.display = 'none';
            formDeleteBtn.style.display = 'inline-block';
            document.querySelector('.navigation-buttons').style.display = 'none'; // Hide nav buttons in archive mode

            // Make all fields read-only in archive mode except password
            document.getElementById('student_no').setAttribute('readonly', true);
            document.getElementById('username').setAttribute('readonly', true);
            document.getElementById('email').setAttribute('readonly', true);
            document.getElementById('last_name').setAttribute('readonly', true);
            document.getElementById('first_name').setAttribute('readonly', true);
            document.getElementById('middle_name').setAttribute('readonly', true);
            document.getElementById('program').setAttribute('readonly', true);
            document.getElementById('level').setAttribute('readonly', true);
            document.getElementById('section').setAttribute('readonly', true);
            document.getElementById('academic_year').setAttribute('readonly', true);
            document.getElementById('semester').setAttribute('readonly', true);

            // Password fields should still be editable if needed, but not required
            document.getElementById('password').removeAttribute('required');
            document.getElementById('confirm_password').removeAttribute('required');
            document.getElementById('password').setAttribute('readonly', true);
            document.getElementById('confirm_password').setAttribute('readonly', true);

            // Disable dropdowns explicitly since readonly might not work
            document.getElementById('program').style.pointerEvents = 'none';
            document.getElementById('level').style.pointerEvents = 'none';
            document.getElementById('section').style.pointerEvents = 'none';
            document.getElementById('academic_year').style.pointerEvents = 'none';
            document.getElementById('semester').style.pointerEvents = 'none';

            document.getElementById('password').value = '';
            document.getElementById('confirm_password').value = '';
        }

        function populateForm(data) {
            console.log('Populating form with data:', data);
            document.getElementById('registration_no').value = data.registrationNo;
            document.getElementById('student_no').value = data.studentNo;
            document.getElementById('username').value = data.username;
            document.getElementById('email').value = data.email;
            document.getElementById('last_name').value = data.lastName;
            document.getElementById('first_name').value = data.firstName;
            document.getElementById('middle_name').value = data.middleName || '';
            document.getElementById('program').value = data.programCode;
            document.getElementById('section').value = data.sectionCode;
            document.getElementById('academic_year').value = data.academicYear;
            console.log('Semester dropdown value after set:', document.getElementById('semester').value);
            document.getElementById('semester').value = data.semester;
            document.getElementById('level').value = data.level;
            // Clear password fields for security
            document.getElementById('password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('registration_no_for_action').value = data.registrationNo;
        }

        // Add this function to store student data when clicking on a row
        function storeStudentData(registrationNo, studentNo, username, email, lastName, firstName, middleName, programCode, level, sectionCode, academicYear, semester) {
            console.log('storeStudentData received:', { registrationNo, studentNo, username, email, lastName, firstName, middleName, programCode, level, sectionCode, academicYear, semester });
            selectedStudentData = {
                registrationNo: registrationNo,
                studentNo: studentNo,
                username: username,
                email: email,
                lastName: lastName,
                firstName: firstName,
                middleName: middleName,
                programCode: programCode,
                level: level,
                sectionCode: sectionCode,
                academicYear: academicYear,
                semester: semester
            };
            
            // Store the current index
            currentStudentIndex = studentList.findIndex(student => student.registrationNo === registrationNo);
            
            // Show navigation buttons if we're in edit mode
            if (formEditBtn.style.display === 'inline-block') {
                document.querySelector('.navigation-buttons').style.display = 'inline-block';
            }
        }
        
        function clearForm() {
            document.getElementById('student_form').reset();
            document.getElementById('registration_no_for_action').value = '';
            selectedStudentData = null;
            document.getElementById('form_add_student_btn').style.display = 'inline-block';
            document.getElementById('form_edit_student_btn').style.display = 'none';
            document.getElementById('form_delete_student_btn').style.display = 'none';

            // Re-enable password fields and make them required for new entries
            document.getElementById('password').setAttribute('required', true);
            document.getElementById('confirm_password').setAttribute('required', true);

            // Ensure all fields are editable again
            document.getElementById('student_no').removeAttribute('readonly');
            document.getElementById('username').removeAttribute('readonly');
            document.getElementById('email').removeAttribute('readonly');
            document.getElementById('last_name').removeAttribute('readonly');
            document.getElementById('first_name').removeAttribute('readonly');
            document.getElementById('middle_name').removeAttribute('readonly');
            document.getElementById('program').removeAttribute('readonly');
            document.getElementById('level').removeAttribute('readonly');
            document.getElementById('section').removeAttribute('readonly');
            document.getElementById('academic_year').removeAttribute('readonly');
            document.getElementById('semester').removeAttribute('readonly');
            document.getElementById('password').removeAttribute('readonly');
            document.getElementById('confirm_password').removeAttribute('readonly');

            document.getElementById('program').style.pointerEvents = 'auto';
            document.getElementById('level').style.pointerEvents = 'auto';
            document.getElementById('section').style.pointerEvents = 'auto';
            document.getElementById('academic_year').style.pointerEvents = 'auto';
            document.getElementById('semester').style.pointerEvents = 'auto';

            document.querySelector('.navigation-buttons').style.display = 'none';
            currentStudentIndex = -1;
            studentList = [];
        }

        function goBack() {
            studentFormContainer.style.display = 'none';
            addStudentActionBtn.style.display = 'inline-block';
            editStudentActionBtn.style.display = 'inline-block';
            deleteStudentActionBtn.style.display = 'inline-block';
            clearForm(); // Clear the form and reset buttons
            document.querySelector('.navigation-buttons').style.display = 'none';
            currentStudentIndex = -1;
            studentList = [];
        }

        // Remove goBackFromBulkUpload and showBulkAddStudentForm functions

        function filterSections() {
            const programCode = document.getElementById('program').value;
            const yearLevel = document.getElementById('level').value;
            const sectionDropdown = document.getElementById('section');

            console.log('filterSections called. ProgramCode:', programCode, 'YearLevel:', yearLevel);

            sectionDropdown.innerHTML = '<option value="">Select Section</option>'; // Clear current options

            if (programCode && yearLevel) {
                fetch(`../get_sections.php?program_code=${programCode}&year_level=${yearLevel}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Data received from get_sections.php:', data);
                        if (data.error) {
                            console.error('Error from get_sections.php:', data.error);
                        return;
                    }
                        data.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.SectionCode;
                        option.textContent = section.SectionTitle;
                            sectionDropdown.appendChild(option);
                        });
                        // If a student is selected and has a section, try to set it
                        if (selectedStudentData && selectedStudentData.sectionCode) {
                            sectionDropdown.value = selectedStudentData.sectionCode;
                        }
                    })
                    .catch(error => console.error('Error fetching sections:', error));
            }
        }

        // Add this function to handle navigation
        function navigateStudent(direction) {
            if (currentStudentIndex === -1 || studentList.length === 0) return;

            let newIndex;
            if (direction === 'next') {
                newIndex = currentStudentIndex + 1;
                if (newIndex >= studentList.length) {
                    // Optionally loop back to the beginning or disable button
                    // newIndex = 0; 
                    return;
                }
            } else {
                newIndex = currentStudentIndex - 1;
                if (newIndex < 0) {
                    // Optionally loop to the end or disable button
                    // newIndex = studentList.length - 1;
                    return;
                }
            }

            const student = studentList[newIndex];
            currentStudentIndex = newIndex;

            // Populate form with student data
            populateForm(student);

            // Clear password fields (already done in populateForm, but good to ensure)
            document.getElementById('password').value = '';
            document.getElementById('confirm_password').value = '';
        }

        // Function to display messages
        function displayMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;

            const container = document.querySelector('.container');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            }

            setTimeout(() => {
                alertDiv.remove();
            }, 5000); // Remove message after 5 seconds
        }

        // Function to refresh the student list
        function refreshStudentList() {
            console.log("Refreshing student list...");
            const filterForm = document.getElementById('filter-form');
            const formData = new FormData(filterForm); // Get current filter/sort data

            fetch('student_management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text()) // Fetch the entire HTML of the student list section
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newStudentList = doc.querySelector('.student-list');
                    const currentStudentList = document.querySelector('.student-list');

                    if (newStudentList && currentStudentList) {
                        currentStudentList.innerHTML = newStudentList.innerHTML;
                    } else {
                        console.error("Could not find .student-list element to update.");
                    }
                    // Re-attach event listeners to new rows if needed, or handle events via delegation
                    attachRowClickListeners();
                })
                .catch(error => {
                    console.error('Error refreshing student list:', error);
                    displayMessage('Failed to refresh student list.', 'error');
                });
        }

        // Function to re-attach event listeners to table rows after refresh
        function attachRowClickListeners() {
            const studentRows = document.querySelectorAll('.student-list tbody tr');
            studentRows.forEach(row => {
                // Remove existing listener to prevent duplicates
                row.removeEventListener('click', handleRowClick);
                row.addEventListener('click', handleRowClick);
            });
        }

        // Ensure handleRowClick is defined globally
        function handleRowClick(event) {
            const rowElement = event.currentTarget;
            try {
                const studentData = JSON.parse(rowElement.getAttribute('data-student'));
                console.log('Row clicked, studentData:', studentData);
                storeStudentData(
                    studentData.registrationNo,
                    studentData.studentNo,
                    studentData.username,
                    studentData.email,
                    studentData.lastName,
                    studentData.firstName,
                    studentData.middleName,
                    studentData.programCode,
                    studentData.level,
                    studentData.sectionCode,
                    studentData.academicYear,
                    studentData.semester
                );
                // Visually highlight the selected row
                document.querySelectorAll('.student-list tbody tr').forEach(tr => tr.classList.remove('selected-row'));
                rowElement.classList.add('selected-row');
            } catch (e) {
                console.error('Error in handleRowClick:', e);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize global variables once the DOM is loaded
            addStudentActionBtn = document.getElementById('add_student_action_btn');
            editStudentActionBtn = document.getElementById('edit_student_action_btn');
            deleteStudentActionBtn = document.getElementById('delete_student_action_btn');
            studentFormContainer = document.getElementById('student_form_container');
            // bulkAddStudentActionBtn = document.getElementById('bulk_add_student_action_btn');
            // bulkUploadContainer = document.getElementById('bulk_upload_container');

            formAddBtn = document.getElementById('form_add_student_btn');
            formEditBtn = document.getElementById('form_edit_student_btn');
            formDeleteBtn = document.getElementById('form_delete_student_btn');

            registrationNoInput = document.getElementById('registration_no');
            studentNoInput = document.getElementById('student_no');
            nextRegistrationNo = "<?php echo htmlspecialchars($next_registration_no); ?>"; // Populate from PHP

            // Fetch initial next registration number
            fetch('get_next_registration_no.php')
                .then(response => response.text())
                .then(data => {
                    nextRegistrationNo = data.trim();
                    if (registrationNoInput) {
                        registrationNoInput.value = nextRegistrationNo;
                    }
                })
                .catch(error => console.error('Error fetching next registration number:', error));

            // Initial attachment of event listeners to rows
            attachRowClickListeners();

            // Event Listeners for main action buttons
            if (addStudentActionBtn) {
                addStudentActionBtn.addEventListener('click', showAddStudentForm);
            }
            if (editStudentActionBtn) {
                editStudentActionBtn.addEventListener('click', showEditForm);
            }
            if (deleteStudentActionBtn) {
                deleteStudentActionBtn.addEventListener('click', showArchiveForm);
            }

            // Remove bulk upload event listeners and logic
        });

        function resetFilters() {
            document.getElementById('filter_program').value = '';
            document.getElementById('filter_section').value = '';
            document.getElementById('filter_level').value = '';
            document.getElementById('filter_semester').value = '';
            document.getElementById('sort_by').value = 'registration_no';
            document.getElementById('sort_order').value = 'desc';
            document.getElementById('filter-form').submit();
        }

        function archiveStudent(registrationNo) {
            console.log('Attempting to archive student with RegistrationNo:', registrationNo);
            if (confirm('Are you sure you want to archive this student?')) {
                fetch('student_management.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=archive_student&registration_no=' + registrationNo
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayMessage(data.message, 'success');
                            window.location.href = 'archived_students.php'; // Redirect to archived students page
                        } else {
                            displayMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        displayMessage('An error occurred while archiving the student.', 'error');
                    });
            }
        }

        // Bulk upload logic
        if (isset($_POST['bulk_upload_submit']) && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            $bulk_upload_message = '';
            $success = 0;
            $errors = [];
            if (!$handle) {
                $bulk_upload_message = 'Failed to open uploaded file.';
            } else {
                $header = fgetcsv($handle);
                $expected = ['studentNo','username','email','password','lastName','firstName','middleName','programCode','level','sectionCode','academicYear','semester'];
                if ($header !== $expected) {
                    $bulk_upload_message = 'CSV header does not match expected columns.';
                } else {
                    $rowNum = 1;
                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNum++;
                        $data = array_combine($expected, $row);
                        // Validate required fields
                        foreach (['studentNo','username','email','password','lastName','firstName','programCode','level','sectionCode','academicYear','semester'] as $field) {
                            if (empty($data[$field])) {
                                $errors[] = "Row $rowNum: Missing $field.";
                                continue 2;
                            }
                        }
                        // Check for duplicate studentNo or email
                        $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?");
                        $stmt->bind_param("ss", $data['studentNo'], $data['email']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $rowx = $result->fetch_assoc();
                            if ($rowx['studentNo'] === $data['studentNo']) {
                                $errors[] = "Row $rowNum: Student number already exists.";
                            } else {
                                $errors[] = "Row $rowNum: Email already exists.";
                            }
                            $stmt->close();
                            continue;
                        }
                        $stmt->close();
                        // Generate RegistrationNo
                        $current_year = date('Y');
                        $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
                        $stmt = $conn->prepare($last_reg_query);
                        $year_pattern = $current_year . "-%";
                        $stmt->bind_param("s", $year_pattern);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $last_reg = $result->fetch_assoc()['RegistrationNo'];
                            $last_number = intval(substr($last_reg, -4));
                            $new_number = $last_number + 1;
                        } else {
                            $new_number = 1;
                        }
                        $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
                        $stmt->close();
                        // Hash password
                        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
                        // Insert student
                        $insert_query = "INSERT INTO students (RegistrationNo, studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student')";
                        $stmt = $conn->prepare($insert_query);
                        $stmt->bind_param("sssssssssssss", $registration_no, $data['studentNo'], $data['username'], $data['email'], $password_hash, $data['lastName'], $data['firstName'], $data['middleName'], $data['programCode'], $data['level'], $data['sectionCode'], $data['academicYear'], $data['semester']);
                        if ($stmt->execute()) {
                            $success++;
                        } else {
                            $errors[] = "Row $rowNum: DB error: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                    fclose($handle);
                }
            }
            $bulk_upload_message = "$success students added successfully.";
            if ($errors) $bulk_upload_message .= '<br>Errors:<br>' . implode('<br>', $errors);
            $_SESSION['message'] = $bulk_upload_message;
            $_SESSION['message_type'] = ($success > 0 && count($errors) == 0) ? 'success' : 'error';
            header('Location: student_management.php');
            exit();
        }
    </script>
</head>
<body>
    

    <nav class="sidebar">
    <div class="logo-container">
        <img src="../assets/dyci_logo.svg" alt="DYCI Logo" class="logo">
        <div class="logo-text">
            <h2>DYCI CampusConnect</h2>
            <p>E-Clearance System</p>
        </div>
    </div>
    <ul>
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
        <li <?php echo ($current_page == 'student_management.php') ? 'class="active"' : ''; ?>><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
        <li <?php echo ($current_page == 'staff_management.php') ? 'class="active"' : ''; ?>><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
        <li <?php echo ($current_page == 'program_section.php') ? 'class="active"' : ''; ?>><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
        <li <?php echo ($current_page == 'office_management.php') ? 'class="active"' : ''; ?>><a href="office_management.php"><i class="fas fa-building icon"></i> Office Management</a></li>
        <li <?php echo ($current_page == 'academicyear.php') ? 'class="active"' : ''; ?>><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
        <li <?php echo ($current_page == 'reports.php') ? 'class="active"' : ''; ?>><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
        <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
</nav>

    <?php echo "<!-- Current Page: " . basename($_SERVER['PHP_SELF']) . " -->"; ?>
<div class="container">
    <?php if (isset($message)): ?>
        <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <button type="button" class="add-btn" id="add_student_action_btn" onclick="showAddStudentPanel()">Add New Student</button>
        <button type="button" class="delete-btn" id="delete_student_action_btn" onclick="window.location.href='archived_students.php'">View Archived Students</button>
        <button type="button" class="add-btn" onclick="showBulkUploadPanel();" style="background-color:#343079;"><i class="fas fa-file-csv"></i> Bulk Upload</button>
    </div>
    
    <div class="user-panel" id="student_form_container">
        <h3>Student Information</h3>
        <form method="POST" id="student_form">
            <div class="form-row">
                <div class="form-group">
                    <label for="registration_no">Registration No.:</label>
                    <input type="text" id="registration_no" name="registration_no" value="<?php echo htmlspecialchars($next_registration_no); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="student_no">Student No.:</label>
                    <input type="text" id="student_no" name="student_no" value="" required>
                    <input type="hidden" id="registration_no_for_action" name="registration_no_for_action" value="">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Program</label>
                    <select id="program" name="program" required>
                        <option value="">Select Program</option>
                        <?php 
                        $program_result->data_seek(0); // Reset pointer
                        while ($program = $program_result->fetch_assoc()) { ?>
                            <option value="<?php echo $program['ProgramCode']; ?>"><?php echo $program['ProgramTitle']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <select id="section" name="section" required>
                        <option value="">Select Section</option>
                        <?php 
                        $section_result->data_seek(0);
                        while ($section = $section_result->fetch_assoc()) { ?>
                            <option value="<?php echo $section['SectionCode']; ?>"><?php echo $section['SectionTitle']; ?> (<?php echo $section['ProgramTitle']; ?>)</option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Academic Year</label>
                    <select id="academic_year" name="academic_year" required>
                        <option value="">Select Academic Year</option>
                        <?php 
                        $ay_result->data_seek(0); // Reset pointer
                        while ($ay = $ay_result->fetch_assoc()) { ?>
                            <option value="<?php echo $ay['AcademicYear']; ?>"><?php echo $ay['AcademicYear']; ?></option>
                        <?php } ?>
                        
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <?php 
                        $semester_result->data_seek(0);
                        while ($sem = $semester_result->fetch_assoc()) { ?>
                            <option value="<?php echo $sem['Semester']; ?>"><?php echo $sem['Semester']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Level</label>
                    <select id="level" name="level" required>
                        <option value="">Select Level</option>
                        <?php 
                        $level_result->data_seek(0); // Reset pointer
                        while ($level = $level_result->fetch_assoc()) { ?>
                            <option value="<?php echo $level['LevelID']; ?>"><?php echo $level['LevelName']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_student" id="form_add_student_btn">Add</button>
                <button type="submit" name="edit_student" id="form_edit_student_btn" style="display:none;">Update</button>
                <button type="submit" name="archive_student" id="form_delete_student_btn">Archive</button>
                <button type="button" onclick="clearForm();">Clear</button>
                <button type="button" onclick="hideStudentPanel();" class="back-btn">Back</button>
                <div class="navigation-buttons" style="display:none;">
                    <button type="button" onclick="navigateStudent('prev')" class="nav-btn">Previous</button>
                    <button type="button" onclick="navigateStudent('next')" class="nav-btn">Next</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" placeholder="Search Student NO., Username, Section, Level, Program, Academic Year or Semester..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="student_management.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="filter-container">
        <form method="POST" id="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_program">Program</label>
                    <select id="filter_program" name="filter_program">
                        <option value="">All Programs</option>
                        <?php 
                        $program_result->data_seek(0);
                        while ($program = $program_result->fetch_assoc()) { ?>
                            <option value="<?php echo $program['ProgramCode']; ?>" <?php echo (isset($_POST['filter_program']) && $_POST['filter_program'] == $program['ProgramCode']) ? 'selected' : ''; ?>>
                                <?php echo $program['ProgramTitle']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter_section">Section</label>
                    <select id="filter_section" name="filter_section">
                        <option value="">All Sections</option>
                        <?php 
                        $section_result->data_seek(0);
                        while ($section = $section_result->fetch_assoc()) { ?>
                            <option value="<?php echo $section['SectionCode']; ?>" <?php echo (isset($_POST['filter_section']) && $_POST['filter_section'] == $section['SectionCode']) ? 'selected' : ''; ?>>
                                <?php echo $section['SectionTitle']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter_level">Level</label>
                    <select id="filter_level" name="filter_level">
                        <option value="">All Levels</option>
                        <?php 
                        $level_result->data_seek(0);
                        while ($level = $level_result->fetch_assoc()) { ?>
                            <option value="<?php echo $level['LevelID']; ?>" <?php echo (isset($_POST['filter_level']) && $_POST['filter_level'] == $level['LevelID']) ? 'selected' : ''; ?>>
                                <?php echo $level['LevelName']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter_semester">Semester</label>
                    <select id="filter_semester" name="filter_semester">
                        <option value="">All Semesters</option>
                        <?php 
                        $semester_result->data_seek(0);
                        while ($sem = $semester_result->fetch_assoc()) { ?>
                            <option value="<?php echo $sem['Semester']; ?>" <?php echo (isset($_POST['filter_semester']) && $_POST['filter_semester'] == $sem['Semester']) ? 'selected' : ''; ?>>
                                <?php echo $sem['Semester']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="sort-container">
                <label for="sort_by">Sort by:</label>
                <select id="sort_by" name="sort_by">
                    <option value="registration_no" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] == 'registration_no') ? 'selected' : ''; ?>>Registration No.</option>
                    <option value="student_no" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] == 'student_no') ? 'selected' : ''; ?>>Student No.</option>
                    <option value="last_name" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] == 'last_name') ? 'selected' : ''; ?>>Last Name</option>
                    <option value="program" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] == 'program') ? 'selected' : ''; ?>>Program</option>
                    <option value="section" <?php echo (isset($_POST['sort_by']) && $_POST['sort_by'] == 'section') ? 'selected' : ''; ?>>Section</option>
                </select>
                <select id="sort_order" name="sort_order">
                    <option value="asc" <?php echo (isset($_POST['sort_order']) && $_POST['sort_order'] == 'asc') ? 'selected' : ''; ?>>Ascending</option>
                    <option value="desc" <?php echo (isset($_POST['sort_order']) && $_POST['sort_order'] == 'desc') ? 'selected' : ''; ?>>Descending</option>
                </select>
            </div>
            <div class="filter-buttons">
                <button type="submit" name="apply_filters" class="apply-btn">Apply Filters</button>
                <button type="button" onclick="window.location.href='student_management.php'" class="reset-btn">Reset</button>
            </div>
        </form>
    </div>

    <div class="scrollable-content">
        <div class="student-list">
        <table>
            <thead>
                <tr>
                        <th>Reg. No.</th>
                    <th>Student No.</th>
                    <th>Username</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Program</th>
                        <th>Level</th>
                    <th>Section</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                    <?php
                    if ($student_result->num_rows > 0) {
                        while ($row = $student_result->fetch_assoc()) {
                            $student_data = [
                                'registrationNo' => $row['RegistrationNo'],
                                'studentNo' => $row['studentNo'],
                                'username' => $row['Username'],
                                'email' => $row['Email'],
                                'lastName' => $row['LastName'],
                                'firstName' => $row['FirstName'],
                                'middleName' => $row['Mname'],
                                'programCode' => $row['ProgramCode'],
                                'level' => $row['Level'],
                                'sectionCode' => $row['SectionCode'],
                                'academicYear' => $row['AcademicYear'],
                                'semester' => $row['Semester'],
                            ];
                            $json_student_data = htmlspecialchars(json_encode($student_data), ENT_QUOTES, 'UTF-8');
                    ?>
                            <tr data-student='<?php echo $json_student_data; ?>' 
                                data-registration-no='<?php echo htmlspecialchars($row['RegistrationNo']); ?>'
                                data-program-code='<?php echo htmlspecialchars($row['ProgramCode']); ?>'
                                data-level='<?php echo htmlspecialchars($row['Level']); ?>'
                                data-section-code='<?php echo htmlspecialchars($row['SectionCode']); ?>'
                                data-academic-year='<?php echo htmlspecialchars($row['AcademicYear']); ?>'
                                data-semester='<?php echo htmlspecialchars($row['Semester']); ?>'
                                onclick="handleRowClick(event);">
                                <td><?php echo htmlspecialchars($row['RegistrationNo']); ?></td>
                                <td><?php echo htmlspecialchars($row['studentNo']); ?></td>
                                <td><?php echo htmlspecialchars($row['Username']); ?></td>
                                <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                                <td><?php echo htmlspecialchars($row['Mname']); ?></td>
                                <td><?php echo htmlspecialchars($row['ProgramTitle']); ?></td>
                                <td><?php echo htmlspecialchars($row['LevelName']); ?></td>
                                <td><?php echo htmlspecialchars($row['SectionCode']); ?></td>
                                <td><?php echo htmlspecialchars($row['AcademicYear']); ?></td>
                                <td><?php echo htmlspecialchars($row['Semester']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="event.stopPropagation(); archiveStudent('<?php echo $row['RegistrationNo']; ?>')" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                            Archive
                                        </button>
                                        <button type="button" onclick="event.stopPropagation(); viewStudentFromTable(this)" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                            View
                                        </button>
                                    </div>
                                </td>
                    </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="11">No students found.</td>
                        </tr>
                    <?php
                    }
                    ?>
            </tbody>
        </table>
    </div>
</div>

    <div class="user-panel" id="bulk_upload_panel" style="display:none;">
        <h3>Bulk Upload Students (CSV)</h3>
        <?php if (isset($bulk_upload_message)): ?>
            <div class="alert"><?php echo $bulk_upload_message; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="csv_file">Select CSV File:</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="bulk_upload_submit"><i class="fas fa-upload"></i> Upload</button>
                <button type="button" class="back-btn" onclick="hideBulkUploadPanel();"><i class="fas fa-arrow-left"></i> Back</button>
            </div>
        </form>
        <p style="margin-top:20px;">CSV columns: <code>studentNo,username,email,password,lastName,firstName,middleName,programCode,level,sectionCode,academicYear,semester</code></p>
        <p>Example:</p>
        <pre style="background:#f8f8f8;padding:10px;border-radius:6px;">studentNo,username,email,password,lastName,firstName,middleName,programCode,level,sectionCode,academicYear,semester
2025-0001,jdoe,jdoe@email.com,Password123!,Doe,John,Michael,BSCS,1,BSCS1A,2024-2025,First Semester
2025-0002,asmith,asmith@email.com,Password456!,Smith,Anna,Marie,BSIT,2,BSIT2B,2024-2025,Second Semester</pre>
</div>
</body>
</html>

<script>
// --- GLOBAL JS FUNCTIONS FOR STUDENT MANAGEMENT ---

// Store selected student data globally
let selectedStudentData = null;

function handleRowClick(event) {
    const rowElement = event.currentTarget;
    try {
        const studentData = JSON.parse(rowElement.getAttribute('data-student'));
        console.log('Row clicked, studentData:', studentData);
        storeStudentData(
            studentData.registrationNo,
            studentData.studentNo,
            studentData.username,
            studentData.email,
            studentData.lastName,
            studentData.firstName,
            studentData.middleName,
            studentData.programCode,
            studentData.level,
            studentData.sectionCode,
            studentData.academicYear,
            studentData.semester
        );
        document.querySelectorAll('.student-list tbody tr').forEach(tr => tr.classList.remove('selected-row'));
        rowElement.classList.add('selected-row');
    } catch (e) {
        console.error('Error in handleRowClick:', e);
    }
}

function viewStudentFromTable(btn) {
    const row = btn.closest('tr');
    if (!row) return;
    handleRowClick({ currentTarget: row });
    showEditStudentPanel();
}

function archiveStudent(registrationNo) {
    if (confirm('Are you sure you want to archive this student?')) {
        fetch('student_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=archive_student&registration_no=' + registrationNo
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = 'archived_students.php';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('An error occurred while archiving the student.');
        });
    }
}

function storeStudentData(registrationNo, studentNo, username, email, lastName, firstName, middleName, programCode, level, sectionCode, academicYear, semester) {
    selectedStudentData = {
        registrationNo: registrationNo,
        studentNo: studentNo,
        username: username,
        email: email,
        lastName: lastName,
        firstName: firstName,
        middleName: middleName,
        programCode: programCode,
        level: level,
        sectionCode: sectionCode,
        academicYear: academicYear,
        semester: semester
    };
    console.log('storeStudentData set:', selectedStudentData);
}

function populateForm(data) {
    document.getElementById('registration_no').value = data.registrationNo;
    document.getElementById('student_no').value = data.studentNo;
    document.getElementById('username').value = data.username;
    document.getElementById('email').value = data.email;
    document.getElementById('last_name').value = data.lastName;
    document.getElementById('first_name').value = data.firstName;
    document.getElementById('middle_name').value = data.middleName || '';
    document.getElementById('program').value = data.programCode;
    document.getElementById('section').value = data.sectionCode;
    document.getElementById('academic_year').value = data.academicYear;
    document.getElementById('semester').value = data.semester;
    document.getElementById('level').value = data.level;
    document.getElementById('password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('registration_no_for_action').value = data.registrationNo;
    console.log('populateForm called with:', data);
}

function showEditStudentPanel() {
    if (!selectedStudentData) {
        alert('Please select a student to update.');
        return;
    }
    populateForm(selectedStudentData);
    document.getElementById('student_form_container').style.display = 'block';
    document.getElementById('bulk_upload_panel').style.display = 'none';
    document.getElementById('form_add_student_btn').style.display = 'none';
    document.getElementById('form_edit_student_btn').style.display = 'inline-block';
    document.getElementById('form_delete_student_btn').style.display = 'none';
    document.getElementById('password').removeAttribute('required');
    document.getElementById('confirm_password').removeAttribute('required');
    console.log('showEditStudentPanel: form shown for update');
}

function showBulkUploadPanel() {
    document.getElementById('bulk_upload_panel').style.display = 'block';
    document.getElementById('student_form_container').style.display = 'none';
}
function hideBulkUploadPanel() {
    document.getElementById('bulk_upload_panel').style.display = 'none';
    document.getElementById('student_form_container').style.display = 'block';
}
function showAddStudentPanel() {
    document.getElementById('student_form_container').style.display = 'block';
    document.getElementById('bulk_upload_panel').style.display = 'none';
}
function hideStudentPanel() {
    document.getElementById('student_form_container').style.display = 'none';
}
// --- END GLOBAL JS FUNCTIONS ---

// --- GLOBAL JS FUNCTIONS FOR STUDENT MANAGEMENT ---
// ... existing functions ...

document.addEventListener('DOMContentLoaded', function() {
    // Intercept student form submit for AJAX
    const studentForm = document.getElementById('student_form');
    if (studentForm) {
        studentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(studentForm);
            // Determine which button was clicked
            let action = '';
            if (document.activeElement && document.activeElement.name) {
                action = document.activeElement.name;
            } else {
                // fallback: check for visible button
                if (document.getElementById('form_add_student_btn').style.display !== 'none') action = 'add_student';
                else if (document.getElementById('form_edit_student_btn').style.display !== 'none') action = 'edit_student';
                else if (document.getElementById('form_delete_student_btn').style.display !== 'none') action = 'archive_student';
            }
            if (action) formData.append('action', action);
            fetch('student_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showStudentFormMessage(data.message, data.success ? 'success' : 'error');
                if (data.success && action === 'add_student') {
                    studentForm.reset();
                }
                // Optionally, refresh the student list here
            })
            .catch(error => {
                showStudentFormMessage('An error occurred. Please try again.', 'error');
            });
        });
    }
});

function showStudentFormMessage(message, type) {
    // Remove any existing alert
    const oldAlert = document.querySelector('#student_form_container .alert');
    if (oldAlert) oldAlert.remove();
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
    alertDiv.textContent = message;
    // Insert at the top of the form container
    const container = document.getElementById('student_form_container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
// --- END GLOBAL JS FUNCTIONS ---
</script>