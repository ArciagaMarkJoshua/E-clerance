<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$staff_department_name = $_SESSION['department'];
$requirement_id = null;

// Attempt to find DepartmentID from 'departments' table first
$dept_query = "SELECT DepartmentID FROM departments WHERE DepartmentName = ?";
$dept_stmt = $conn->prepare($dept_query);
$dept_stmt->bind_param("s", $staff_department_name);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();

if ($dept_result->num_rows > 0) {
    $department_row = $dept_result->fetch_assoc();
    $department_id = $department_row['DepartmentID'];
} else {
    // If not found in 'departments', try to find DepartmentID from 'offices' table
    $office_query = "SELECT DepartmentID FROM offices WHERE OfficeName = ?";
    $office_stmt = $conn->prepare($office_query);
    $office_stmt->bind_param("s", $staff_department_name);
    $office_stmt->execute();
    $office_result = $office_stmt->get_result();

    if ($office_result->num_rows > 0) {
        $office_row = $office_result->fetch_assoc();
        $department_id = $office_row['DepartmentID'];
    }
}

// If DepartmentID is found, fetch the clearance requirement details
if ($department_id) {
    $req_query = "SELECT requirement_id, requirement_name, description FROM clearance_requirements WHERE department_id = ?";
    $req_stmt = $conn->prepare($req_query);
    $req_stmt->bind_param("i", $department_id);
    $req_stmt->execute();
    $req_result = $req_stmt->get_result();
    if ($req_result->num_rows > 0) {
        $current_requirement_details = $req_result->fetch_assoc();
        $requirement_id = $current_requirement_details['requirement_id'];
    }
}

if (!$requirement_id) {
    $_SESSION['error'] = "Error: Department requirement not found. Cannot perform bulk clearance.";
    header("Location: eclearance.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    error_log("Bulk clearance POST request received.");
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        error_log("CSV file uploaded successfully: " . $_FILES['csv_file']['tmp_name']);
        $file_name = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file_name, "r");

        if ($handle !== FALSE) {
            $row_count = 0;
            $success_count = 0;
            $error_count = 0;
            $errors = [];

            // Skip header row if exists
            fgetcsv($handle);
            error_log("CSV file handle opened. Starting row processing.");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_count++;
                error_log("Processing row $row_count: " . implode(",", $data));
                if (count($data) >= 2) { // Ensure at least studentNo and status are present
                    $student_no = trim($data[0]);
                    $status = trim($data[1]);
                    $comments = isset($data[2]) ? trim($data[2]) : '';

                    // Check if student exists
                    $student_check_query = "SELECT studentNo FROM students WHERE studentNo = ? AND IsActive = 1";
                    $student_check_stmt = $conn->prepare($student_check_query);
                    $student_check_stmt->bind_param("s", $student_no);
                    $student_check_stmt->execute();
                    $student_check_result = $student_check_stmt->get_result();
                    error_log("Student check for $student_no: " . $student_check_result->num_rows . " rows found.");

                    if ($student_check_result->num_rows === 0) {
                        $errors[] = "Row $row_count: Student with ID {$student_no} not found or is inactive.";
                        $error_count++;
                        error_log("Error: Student $student_no not found/inactive.");
                        continue; // Skip to the next row
                    }

                    // Validate status
                    if (!in_array($status, ['Pending', 'Approved', 'Pending Approve'])) {
                        $errors[] = "Row $row_count: Invalid status '{$status}' for student {$student_no}.";
                        $error_count++;
                        error_log("Error: Invalid status '{$status}' for student $student_no.");
                        continue;
                    }

                    // Update clearance status
                    $update_query = "INSERT INTO student_clearance_status (studentNo, requirement_id, StaffID, status, comments, approved_by)
                                    VALUES (?, ?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE
                                    status = VALUES(status),
                                    comments = VALUES(comments),
                                    approved_by = VALUES(approved_by),
                                    date_approved = CURRENT_TIMESTAMP";

                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("siisss", $student_no, $requirement_id, $staff_id, $status, $comments, $staff_name);
                    error_log("Attempting to execute update for student $student_no with status $status.");

                    if ($stmt->execute()) {
                        $success_count++;
                        error_log("Successfully updated student $student_no. Success count: $success_count");
                        // Log the activity
                        $log_query = "INSERT INTO activity_logs (user_id, user_type, action, details) VALUES (?, 'Staff', ?, ?)";
                        $log_stmt = $conn->prepare($log_query);
                        $action = "Bulk Clearance: " . $status;
                        $details = "Bulk updated clearance for student " . $student_no . ", requirement " . ($current_requirement_details['requirement_name'] ?? $requirement_id) . " to " . $status;
                        $log_stmt->bind_param("iss", $staff_id, $action, $details);
                        $log_stmt->execute();
                    } else {
                        $errors[] = "Row $row_count: Error updating clearance for student {$student_no}: " . $conn->error;
                        $error_count++;
                        error_log("Database error for student $student_no: " . $conn->error);
                    }
                } else {
                    $errors[] = "Row $row_count: Invalid CSV format. Expected at least 'studentNo,status'.";
                    $error_count++;
                    error_log("Error: Invalid CSV format for row $row_count.");
                }
            }
            fclose($handle);
            error_log("CSV processing finished. Successes: $success_count, Errors: $error_count");

            if ($success_count > 0) {
                $_SESSION['success'] = "Bulk clearance completed: {$success_count} records updated successfully.";
                error_log("Session success set: " . $_SESSION['success']);
            }
            if ($error_count > 0) {
                $_SESSION['error'] = "Bulk clearance errors: " . implode("<br>", $errors);
                error_log("Session error set: " . $_SESSION['error']);
            }
            if ($success_count == 0 && $error_count == 0) {
                $_SESSION['error'] = "No valid records found in the CSV file or file is empty.";
                error_log("Session error set: No valid records.");
            }

        } else {
            $_SESSION['error'] = "Error opening the uploaded CSV file.";
            error_log("Error: Could not open uploaded CSV file.");
        }
    } else {
        $_SESSION['error'] = "Error uploading CSV file: " . $_FILES['csv_file']['error'];
        error_log("Error: CSV file upload error: " . $_FILES['csv_file']['error']);
    }
} else {
    $_SESSION['error'] = "Invalid request for bulk clearance.";
    error_log("Error: Invalid POST request for bulk clearance.");
}

$conn->close();
error_log("Closing database connection. Redirecting to eclearance.php");
header("Location: eclearance.php");
exit();
?> 