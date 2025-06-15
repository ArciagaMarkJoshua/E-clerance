<?php
session_start();
include '../includes/db_connect.php';

// Initialize message variables
$message = '';
$message_type = '';

// Initialize variables
$current_year = (int)date('Y');
$next_registration_no = sprintf('%d-%03d', $current_year, 1); // Default starting value (current year-001)

// Fetch the next available control number
$registration_no_query = "SELECT MAX(RegistrationNo) AS max_registration FROM staff";
$registration_no_result = $conn->query($registration_no_query);

error_log("DEBUG: Initial next_registration_no (default): " . $next_registration_no);

if ($registration_no_result) {
    error_log("DEBUG: Query result object is valid.");
    error_log("DEBUG: Number of rows from MAX(RegistrationNo) query: " . $registration_no_result->num_rows);

    if ($registration_no_result->num_rows > 0) {
        $row = $registration_no_result->fetch_assoc();
        error_log("DEBUG: Fetched row: " . print_r($row, true));

        if ($row['max_registration'] !== null) {
            $max_registration = $row['max_registration'];
            error_log("DEBUG: MAX(RegistrationNo) fetched from DB: " . $max_registration);
            
            // Check if max_registration matches the YYYY-NNN format
            if (preg_match('/^(\d{4})-(\d+)$/', $max_registration, $matches)) {
                error_log("DEBUG: preg_match found YYYY-NNN format. Matches: " . print_r($matches, true));

                $year_part = (int)$matches[1];
                $number_part = (int)$matches[2];
                
                error_log("DEBUG: Extracted year_part: " . $year_part . ", number_part: " . $number_part);
                error_log("DEBUG: Current year: " . $current_year);

                if ($year_part < $current_year) {
                    // If the last registration is from a previous year, start new sequence for current year
                    $next_number = 1;
                    error_log("DEBUG: Previous year detected. Starting next_number from 1.");
                } else {
                    // If it's the same year, increment the number part
                    $next_number = $number_part + 1;
                    error_log("DEBUG: Same year detected. Incrementing number_part to: " . $next_number);
                }
                $next_registration_no = sprintf('%d-%03d', $current_year, $next_number);
                error_log("DEBUG: Calculated next_registration_no: " . $next_registration_no);
            } else {
                // If format is not YYYY-NNN, it means there are existing non-standard entries.
                // Log a warning and fallback to current year-001 to ensure a valid format.
                error_log(sprintf("Warning: MAX(RegistrationNo) found but in unexpected format '%s'. Defaulting to current year-001.", (string)$max_registration));
                $current_year = (int)date('Y');
                $next_registration_no = sprintf('%d-%03d', $current_year, 1);
                error_log("DEBUG: Fallback next_registration_no: " . $next_registration_no);
            }
        } else {
            // max_registration is null (e.g., table has rows but all RegistrationNo are null)
            error_log("DEBUG: MAX(RegistrationNo) returned NULL. Using default next_registration_no.");
        }
    } else {
        // num_rows is 0 (table is empty)
        error_log("DEBUG: MAX(RegistrationNo) query returned 0 rows. Table is likely empty. Using default next_registration_no.");
    }
} else {
    // $registration_no_result is false (query failed)
    error_log("DEBUG: MAX(RegistrationNo) query failed. Check database connection/query syntax. Using default next_registration_no.");
}

// Fetch departments for dropdown
$dept_query = "SELECT DepartmentID, DepartmentName FROM departments ORDER BY DepartmentName"; 
$dept_result = $conn->query($dept_query);
$departments = [];
while ($dept = $dept_result->fetch_assoc()) {
    $departments[] = $dept;
}

// Fetch all offices for the JavaScript
$office_query = "SELECT OfficeID, OfficeName, DepartmentID, Description FROM offices ORDER BY OfficeName";
$office_result = $conn->query($office_query);
$offices = [];
while ($office = $office_result->fetch_assoc()) {
    $offices[] = $office;
}

// Search functionality
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $conn->real_escape_string($_POST['search_query']);
    $sql = "SELECT * FROM staff WHERE 
            StaffID LIKE '%$search_query%' OR 
            Username LIKE '%$search_query%' OR 
            LastName LIKE '%$search_query%' OR 
            FirstName LIKE '%$search_query%' OR 
            Department LIKE '%$search_query%'";
} else {
    $sql = "SELECT * FROM staff";
}

// Add Staff
if (isset($_POST['add_staff'])) {
    try {
        // Determine the department value to save
        $selected_department_id = $conn->real_escape_string($_POST['department']);
        $selected_office_name = isset($_POST['office']) ? $conn->real_escape_string($_POST['office']) : '';
        
        $final_department_to_save = '';
        if (!empty($selected_office_name)) {
            $final_department_to_save = $selected_office_name; // Save office name if selected
        } else if (!empty($selected_department_id)) {
            // Get department name from ID
            $stmt_dept_name = $conn->prepare("SELECT DepartmentName FROM departments WHERE DepartmentID = ?");
            $stmt_dept_name->bind_param("i", $selected_department_id);
            $stmt_dept_name->execute();
            $res_dept_name = $stmt_dept_name->get_result();
            if ($row_dept_name = $res_dept_name->fetch_assoc()) {
                $final_department_to_save = $row_dept_name['DepartmentName'];
            }
            $stmt_dept_name->close();
        } else {
            throw new Exception("Please select a Department.");
        }

        // Validate required fields
        $required_fields = ['staff_id', 'username', 'password', 'confirm_password', 'last_name', 'first_name', 'email', 'account_type'];
        if (empty($final_department_to_save)) {
             throw new Exception("Department or Office must be selected.");
        }

        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        $staff_id = $conn->real_escape_string($_POST['staff_id']);
        $username = $conn->real_escape_string($_POST['username']);
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $account_type = $conn->real_escape_string($_POST['account_type']);
        $is_active = 1;

        // Check if staff ID already exists
        $check_query = "SELECT * FROM staff WHERE StaffID = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception("Database error while checking existing staff.");
        }
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Staff ID already exists!");
        }
        $stmt->close();

        // Insert new staff
        $insert_query = "INSERT INTO staff (RegistrationNo, StaffID, Username, PasswordHash, LastName, FirstName, Mname, Email, Department, AccountType, IsActive) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing insert query.");
        }
        
        $stmt->bind_param("ssssssssssi", 
            $next_registration_no, // Ensure this is explicitly bound
            $staff_id,
            $username,
            $password_hash,
            $last_name,
            $first_name,
            $middle_name,
            $email,
            $final_department_to_save,
            $account_type,
            $is_active
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding staff: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Staff added successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Staff management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
        exit();
    }
}

// Edit Staff
if (isset($_POST['edit_staff'])) {
    try {
        // Determine the department value to save
        $selected_department_id = $conn->real_escape_string($_POST['department']);
        $selected_office_name = isset($_POST['office']) ? $conn->real_escape_string($_POST['office']) : '';
        
        $final_department_to_save = '';
        if (!empty($selected_office_name)) {
            $final_department_to_save = $selected_office_name; // Save office name if selected
        } else if (!empty($selected_department_id)) {
            // Get department name from ID
            $stmt_dept_name = $conn->prepare("SELECT DepartmentName FROM departments WHERE DepartmentID = ?");
            $stmt_dept_name->bind_param("i", $selected_department_id);
            $stmt_dept_name->execute();
            $res_dept_name = $stmt_dept_name->get_result();
            if ($row_dept_name = $res_dept_name->fetch_assoc()) {
                $final_department_to_save = $row_dept_name['DepartmentName'];
            }
            $stmt_dept_name->close();
        } else {
            throw new Exception("Please select a Department.");
        }

        // Validate required fields
        $required_fields = ['staff_id', 'username', 'password', 'confirm_password', 'last_name', 'first_name', 'email', 'account_type'];
         if (empty($final_department_to_save)) {
             throw new Exception("Department or Office must be selected.");
        }

        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            // Password fields are optional during edit, only require if they are not empty
            if (($field === 'password' || $field === 'confirm_password') && empty($_POST[$field])) {
                continue;
            }
            if (empty($_POST[$field])) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        $staff_id_for_action = $conn->real_escape_string($_POST['staff_id']);
        $username = $conn->real_escape_string($_POST['username']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $account_type = $conn->real_escape_string($_POST['account_type']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Only update password if new password is provided
        $password_update = '';
        $password_hash = null;
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                throw new Exception("Passwords do not match.");
            }
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ', PasswordHash = ?';
        }

        // Update staff
        $update_query = "UPDATE staff SET Username = ?, LastName = ?, FirstName = ?, Mname = ?, Email = ?, Department = ?, AccountType = ?, IsActive = ?" . $password_update . " WHERE StaffID = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing update query.");
        }

        $param_types = "sssssssi";
        $param_values = [
            $username,
            $last_name,
            $first_name,
            $middle_name,
            $email,
            $final_department_to_save,
            $account_type,
            $is_active
        ];

        if ($password_hash !== null) {
            $param_types .= "s";
            $param_values[] = $password_hash;
        }
        $param_types .= "s"; // For StaffID
        $param_values[] = $staff_id_for_action;

        // Use call_user_func_array to bind parameters dynamically
        $stmt->bind_param($param_types, ...$param_values);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating staff: " . $stmt->error);
        }

        $_SESSION['message'] = "Staff updated successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Staff management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
        exit();
    }
}

// Delete Staff
if (isset($_POST['delete_staff'])) {
    try {
        if (empty($_POST['staff_id'])) {
            throw new Exception("Please select a staff to delete.");
        }

        $staff_id = $conn->real_escape_string($_POST['staff_id']);
        
        // Check if staff exists before deletion
        $check_query = "SELECT * FROM staff WHERE StaffID = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception("Database error while checking staff existence.");
        }
        $stmt->bind_param("s", $staff_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Staff not found.");
        }
        $stmt->close();

        // Delete staff
        $delete_query = "DELETE FROM staff WHERE StaffID = ?";
        $stmt = $conn->prepare($delete_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing delete query.");
        }
        $stmt->bind_param("s", $staff_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting staff: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Staff deleted successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Staff management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: staff_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: staff_management.php");
        }
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

// Initialize filter and sort variables
$filter_department = isset($_POST['filter_department']) ? $_POST['filter_department'] : '';
$filter_account_type = isset($_POST['filter_account_type']) ? $_POST['filter_account_type'] : '';
$sort_by = isset($_POST['sort_by']) ? $_POST['sort_by'] : 'staff_id'; // Default sort
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'asc'; // Default order

// Base SQL query for fetching staff users
$sql = "SELECT * FROM staff WHERE 1=1"; // 1=1 is a trick to easily append AND conditions

// Add filter conditions
if (!empty($filter_department)) {
    $sql .= " AND Department = '" . $conn->real_escape_string($filter_department) . "'";
}
if (!empty($filter_account_type)) {
    $sql .= " AND AccountType = '" . $conn->real_escape_string($filter_account_type) . "'";
}

// Add search condition if search query exists (re-using existing search_query variable)
if (!empty($search_query)) {
    $sql .= " AND (
                StaffID LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                Username LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                LastName LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                FirstName LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                Mname LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                Email LIKE '%" . $conn->real_escape_string($search_query) . "%' OR 
                Department LIKE '%" . $conn->real_escape_string($search_query) . "%'
            )";
}

// Add sorting
$sort_columns = [
    'registration_no' => 'RegistrationNo',
    'staff_id' => 'StaffID',
    'username' => 'Username',
    'last_name' => 'LastName',
    'first_name' => 'FirstName',
    'department' => 'Department',
    'account_type' => 'AccountType'
];

$sort_column_to_use = isset($sort_columns[$sort_by]) ? $sort_columns[$sort_by] : 'StaffID';
$sql .= " ORDER BY " . $sort_column_to_use . " " . ($sort_order === 'desc' ? 'DESC' : 'ASC');

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
}

.form-buttons button[name="add_staff"] {
    background-color: #28a745; /* green */
}

.form-buttons button[name="add_staff"]:hover {
    background-color: #218838;
}

.form-buttons button[name="edit_staff"] {
    background-color: #17a2b8; /* blue-teal */
}

.form-buttons button[name="edit_staff"]:hover {
    background-color: #138496;
}

.form-buttons button[name="delete_staff"] {
    background-color: #dc3545; /* red */
}

.form-buttons button[name="delete_staff"]:hover {
    background-color: #c82333;
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
</style>
    <script>
        function selectUser(registrationNo, staffId, username, lastName, firstName, middleName, email, departmentValue, accountType) {
            document.getElementById("registration_no").value = registrationNo;
            document.getElementById("staff_id").value = staffId;
            document.getElementById("username").value = username;
            document.getElementById("last_name").value = lastName;
            document.getElementById("first_name").value = firstName;
            document.getElementById("middle_name").value = middleName;
            document.getElementById("email").value = email;
            document.querySelector("select[name='account_type']").value = accountType;
            
            const departmentSelect = document.getElementById("department");
            const officeSelect = document.getElementById("office");
            const descriptionElement = document.getElementById('office-description');

            // Reset dropdowns and description
            departmentSelect.value = '';
            officeSelect.innerHTML = '<option value="">Select Office</option>';
            officeSelect.disabled = true;
            descriptionElement.textContent = '';
            descriptionElement.style.display = 'none';

            let departmentIdToSelect = null;
            let officeNameToSelect = null;

            // 1. Try to find if departmentValue is an OfficeName
            for (const office of officesData) {
                if (office.OfficeName === departmentValue) {
                    departmentIdToSelect = office.DepartmentID;
                    officeNameToSelect = office.OfficeName;
                    break;
                }
            }

            // 2. If not an office, try to match departmentValue as a DepartmentName
            if (departmentIdToSelect === null) {
                for (const dept of departmentsData) {
                    if (dept.DepartmentName === departmentValue) {
                        departmentIdToSelect = dept.DepartmentID;
                        break;
                    }
                }
            }
            
            // 3. If still not found, try to match departmentValue as a DepartmentID (as a string, e.g., '1')
            if (departmentIdToSelect === null && !isNaN(parseInt(departmentValue))) {
                const potentialDeptId = parseInt(departmentValue);
                for (const dept of departmentsData) {
                    if (dept.DepartmentID === potentialDeptId) {
                        departmentIdToSelect = dept.DepartmentID;
                        break;
                    }
                }
            }

            // Set the department dropdown
            if (departmentIdToSelect !== null) {
                departmentSelect.value = departmentIdToSelect;
                // Update offices dropdown, and pre-select office if found
                updateOffices(officeNameToSelect);
            } else {
                // If departmentValue is neither a known office nor a department (or ID),
                // it means it's an unrecognized value. Just leave department empty and offices cleared.
                departmentSelect.value = '';
                updateOffices();
            }
            
            // Change button focus to Edit/Delete
            document.querySelector("button[name='add_staff']").classList.remove('active');
            document.querySelector("button[name='edit_staff']").classList.add('active');
            document.querySelector("button[name='delete_staff']").classList.add('active');
        }
        
        function clearForm() {
            document.getElementById("staff_id").value = '';
            document.getElementById("username").value = '';
            document.getElementById("last_name").value = '';
            document.getElementById("first_name").value = '';
            document.getElementById("middle_name").value = '';
            document.getElementById("email").value = '';
            document.getElementById("department").value = '';
            document.getElementById("office").innerHTML = '<option value="">Select Office</option>'; // Clear offices
            document.getElementById("office").disabled = true; // Disable office select
            document.getElementById('office-description').textContent = ''; // Clear description
            document.querySelector("select[name='account_type']").value = '';
            
            // Reset to add mode
            document.querySelector("button[name='add_staff']").classList.add('active');
            document.querySelector("button[name='edit_staff']").classList.remove('active');
            document.querySelector("button[name='delete_staff']").classList.remove('active');
            
            // Update control number to next available
            document.getElementById("registration_no").value = <?php echo $next_registration_no; ?>;
        }
        
        function confirmDelete() {
            return confirm("Are you sure you want to delete this user?");
        }

        // Add focus effect when search input is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input-group input');
            
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.parentElement.querySelector('.search-button').style.color = '#4a90e2';
                });
                
                searchInput.addEventListener('blur', function() {
                    this.parentElement.querySelector('.search-button').style.color = '#666';
                });
            }

            // Call updateOffices on page load to initialize based on any pre-selected department (e.g., if form retains values on error)
            // This will also disable the office dropdown initially.
            updateOffices(); 
        });

        // Store departments data in JS for lookup
        const departmentsData = <?php echo json_encode($departments); ?>;

        // Store offices data in JS for lookup
        const officesData = <?php echo json_encode($offices); ?>;

        function updateOffices(preSelectedOfficeName = null) {
            const departmentSelect = document.getElementById('department');
            const officeSelect = document.getElementById('office');
            const descriptionElement = document.getElementById('office-description');
            
            // Clear current options and description
            officeSelect.innerHTML = '<option value="">Select Office</option>';
            officeSelect.disabled = true; // Disable until a department is selected
            descriptionElement.textContent = '';
            descriptionElement.style.display = 'none';

            const selectedDeptId = departmentSelect.value;
            if (!selectedDeptId) return;
            
            // Filter and add offices for selected department
            const departmentOffices = officesData.filter(office => office.DepartmentID == selectedDeptId);
            
            if (departmentOffices.length > 0) {
                officeSelect.disabled = false; // Enable office select if there are offices
                departmentOffices.forEach(office => {
                    const option = document.createElement('option');
                    option.value = office.OfficeName; // Store OfficeName as value
                    option.textContent = office.OfficeName;
                    option.setAttribute('data-description', office.Description);
                    officeSelect.appendChild(option);
                });

                // Pre-select office if name is provided (during edit)
                if (preSelectedOfficeName) {
                    officeSelect.value = preSelectedOfficeName;
                    const selectedOption = officeSelect.options[officeSelect.selectedIndex];
                    if (selectedOption) {
                        descriptionElement.textContent = selectedOption.getAttribute('data-description');
                        descriptionElement.style.display = 'block';
                    }
                }
            }
        }

        // Add event listener for office selection (this was already good, just re-ensuring it's there)
        document.getElementById('office').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description');
            const descriptionElement = document.getElementById('office-description');
            
            if (description) {
                descriptionElement.textContent = description;
                descriptionElement.style.display = 'block';
            } else {
                descriptionElement.textContent = '';
                descriptionElement.style.display = 'none';
            }
        });

        function resetStaffFilters() {
            document.getElementById('filter_department').value = '';
            document.getElementById('filter_account_type').value = '';
            document.getElementById('sort_by').value = 'staff_id'; // Reset to default sort
            document.getElementById('sort_order').value = 'asc'; // Reset to default order
            document.getElementById('filterSortForm').submit(); // Submit the form to apply reset
        }

        // Add password validation (client-side)
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        function validatePasswords() {
            if (confirmPasswordInput.value === '') {
                confirmPasswordInput.setCustomValidity('');
                return;
            }
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        }

        passwordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
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
    
    <div class="user-panel">
        <h3>Account Information</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Registration No.</label>
                    <input type="text" id="registration_no" name="registration_no" value="<?php echo $next_registration_no; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" id="staff_id" name="staff_id" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="New Password (Leave blank to keep current)">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department</label>
                    <select id="department" name="department" required onchange="updateOffices()">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo htmlspecialchars($dept['DepartmentName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Office</label>
                    <select id="office" name="office" disabled>
                        <option value="">Select Office</option>
                    </select>
                    <small class="form-text text-muted" id="office-description"></small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type" required>
                        <option value="">Select Account Type</option>
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_staff" class="active">Add</button>
                <button type="submit" name="edit_staff">Update</button>
                <button type="submit" name="delete_staff" onclick="return confirmDelete();">Delete</button>
                <button type="button" onclick="clearForm();">Clear</button>
            </div>
        </form>
    </div>

    <!-- Filter and Sort Section -->
    <div class="filter-sort-container" style="margin-bottom: 20px; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3>Filter and Sort Staff</h3>
        <form method="POST" id="filterSortForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="filter_department">Filter by Department/Office:</label>
                    <select id="filter_department" name="filter_department" class="form-control">
                        <option value="">All Departments/Offices</option>
                        <?php 
                        // Re-fetch all department and office names for the filter dropdown
                        $all_dept_office_query = "SELECT DepartmentName FROM departments UNION SELECT OfficeName FROM offices ORDER BY DepartmentName";
                        $all_dept_office_result = $conn->query($all_dept_office_query);
                        while($item = $all_dept_office_result->fetch_assoc()): 
                            $selected = ($filter_department == $item['DepartmentName']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($item['DepartmentName']); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($item['DepartmentName']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_account_type">Filter by Account Type:</label>
                    <select id="filter_account_type" name="filter_account_type" class="form-control">
                        <option value="">All Account Types</option>
                        <option value="Staff" <?php echo ($filter_account_type == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="Admin" <?php echo ($filter_account_type == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="sort_by">Sort By:</label>
                    <select id="sort_by" name="sort_by" class="form-control">
                        <option value="registration_no" <?php echo ($sort_by == 'registration_no') ? 'selected' : ''; ?>>Registration No.</option>
                        <option value="staff_id" <?php echo ($sort_by == 'staff_id') ? 'selected' : ''; ?>>Staff ID</option>
                        <option value="last_name" <?php echo ($sort_by == 'last_name') ? 'selected' : ''; ?>>Last Name</option>
                        <option value="first_name" <?php echo ($sort_by == 'first_name') ? 'selected' : ''; ?>>First Name</option>
                        <option value="department" <?php echo ($sort_by == 'department') ? 'selected' : ''; ?>>Department/Office</option>
                        <option value="account_type" <?php echo ($sort_by == 'account_type') ? 'selected' : ''; ?>>Account Type</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order:</label>
                    <select id="sort_order" name="sort_order" class="form-control">
                        <option value="asc" <?php echo ($sort_order == 'asc') ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo ($sort_order == 'desc') ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>
            <!-- Hidden input to preserve search query across filter/sort submits -->
            <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">

            <div class="form-buttons">
                <button type="submit" name="apply_filters" class="btn btn-success" style="background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">Apply Filters</button>
                <button type="button" name="reset_filters" class="btn btn-secondary" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;" onclick="resetStaffFilters();">Reset</button>
            </div>
        </form>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" placeholder="Search staff by ID, name, username or department..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="staff_management.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Scrollable Content Area -->
    <div class="scrollable-content">
        <!-- Staff List Table -->
        <div class="user-list-container">
            <h3>Staff List</h3>
            <?php if ($result->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Registration No.</th>
                                <th>Staff ID</th>
                                <th>Username</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr onclick="selectUser('<?php echo $row['RegistrationNo']; ?>', '<?php echo htmlspecialchars($row['StaffID']); ?>', '<?php echo htmlspecialchars($row['Username']); ?>', '<?php echo htmlspecialchars($row['LastName']); ?>', '<?php echo htmlspecialchars($row['FirstName']); ?>', '<?php echo htmlspecialchars($row['Mname']); ?>', '<?php echo htmlspecialchars($row['Email']); ?>', '<?php echo htmlspecialchars($row['Department']); ?>', '<?php echo htmlspecialchars($row['AccountType']); ?>')">
                                    <td><?php echo htmlspecialchars($row['RegistrationNo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['StaffID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Mname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Department']); ?></td>
                                    <td><button type="button" class="select-btn">Select</button></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-results">No staff members found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
<?php
$conn->close();
?>