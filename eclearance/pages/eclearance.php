<?php
session_start();
include '../includes/db_connect.php';

// To this (to match your database values):
$is_admin = ($_SESSION['role'] ?? '') === 'Admin' || ($_SESSION['account_type'] ?? '') === 'Admin';

// Store selected student in session when available
if (isset($_GET['studentNo'])) {
    $_SESSION['current_student'] = $_GET['studentNo'];
}

// Remove the student selector related code and modify the student fetching logic
$student_no = $_GET['studentNo'] ?? "";

if(empty($student_no)) {
    // If no student is selected, show the student list without individual student details
    $show_student_details = false;
    } else {
    $show_student_details = true;
    try {
        $student_query = "SELECT * FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($student_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing student query.");
        }
        
        $stmt->bind_param("s", $student_no);
        $stmt->execute();
        $student_result = $stmt->get_result();

if ($student_result->num_rows > 0) {
    $student = $student_result->fetch_assoc();
} else {
            $show_student_details = false;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error retrieving student data: " . $e->getMessage());
        $show_student_details = false;
    }
}

// Fetch all students for dropdown and table
$students_query = "SELECT studentNo, LastName, FirstName FROM students ORDER BY LastName, FirstName";
$students_result = $conn->query($students_query);

// Create requirement-department mapping
$requirement_departments = [
    1 => 'College Library',
    2 => 'Guidance Office',
    3 => 'Office of the Dean',
    4 => 'Office of the Finance Director',
    5 => 'Office of the Registrar',
    6 => 'Property Custodian',
    7 => 'Student Council'
];

// Get user's department from session
$user_department = $_SESSION['department'] ?? '';

// Map departments to requirement IDs
$department_requirements = array_flip($requirement_departments);
$user_requirement_id = $department_requirements[$user_department] ?? 0;

// Fetch clearance requirements
$requirements_query = "SELECT * FROM clearance_requirements ORDER BY requirement_id";
$requirements_result = $conn->query($requirements_query);
$requirements = [];
while ($req = $requirements_result->fetch_assoc()) {
    $req['DepartmentName'] = $requirement_departments[$req['requirement_id']] ?? 'Unknown';
    $requirements[$req['requirement_id']] = $req;
}

// Check if student_requirement_descriptions table exists, if not create it
$check_table_query = "SHOW TABLES LIKE 'student_requirement_descriptions'";
$table_exists = $conn->query($check_table_query)->num_rows > 0;

if (!$table_exists) {
    $create_table_query = "CREATE TABLE student_requirement_descriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        studentNo VARCHAR(20) NOT NULL,
        requirement_id INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (studentNo) REFERENCES students(studentNo),
        FOREIGN KEY (requirement_id) REFERENCES clearance_requirements(requirement_id),
        UNIQUE KEY unique_student_requirement (studentNo, requirement_id)
    )";
    
    if (!$conn->query($create_table_query)) {
        error_log("Error creating student_requirement_descriptions table: " . $conn->error);
    }
}

// Initialize individual descriptions array
$individual_descriptions = [];

// Only try to fetch descriptions if the table exists
if ($table_exists) {
    $descriptions_query = "SELECT * FROM student_requirement_descriptions WHERE studentNo = ?";
    $stmt = $conn->prepare($descriptions_query);
    if ($stmt) {
        $stmt->bind_param("s", $student_no);
        $stmt->execute();
        $descriptions_result = $stmt->get_result();
        while ($desc = $descriptions_result->fetch_assoc()) {
            $individual_descriptions[$desc['requirement_id']] = $desc['description'];
        }
        $stmt->close();
    }
}

// Fetch all staff
$staff_query = "SELECT * FROM staff";
$staff_result = $conn->query($staff_query);
$staff_members = [];
while ($staff = $staff_result->fetch_assoc()) {
    $staff_members[$staff['StaffID']] = $staff;
}

// Fetch clearance status
if ($show_student_details) {
    $clearance_query = "SELECT * FROM student_clearance_status WHERE studentNo = ?";
    $stmt = $conn->prepare($clearance_query);
    if ($stmt) {
        $stmt->bind_param("s", $student_no);
        $stmt->execute();
        $clearance_result = $stmt->get_result();
$clearance_status = [];
while ($status = $clearance_result->fetch_assoc()) {
    $clearance_status[$status['requirement_id']] = $status;
        }
        $stmt->close();
    }
}

// Handle description update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description'])) {
    try {
        // Debug logging
        error_log("Description update attempt - POST data: " . print_r($_POST, true));
        error_log("Session data: " . print_r($_SESSION, true));
        
    if (!isset($_SESSION['staff_id'])) {
            throw new Exception("Unauthorized access - No staff_id in session");
    }
    
    $requirement_id = intval($_POST['requirement_id']);
        $new_description = trim($_POST['description']);
        $student_no = $_POST['studentNo'] ?? $student_no;
        
        error_log("Processing update for student: $student_no, requirement: $requirement_id");
    
    // Check if user has permission (admin or staff from this department)
    $is_department_staff = ($requirement_departments[$requirement_id] == $user_department);
    
    if ($is_admin || $is_department_staff) {
            // First check if the record exists
            $check_query = "SELECT id FROM student_requirement_descriptions 
                           WHERE studentNo = ? AND requirement_id = ?";
            $stmt = $conn->prepare($check_query);
            if (!$stmt) {
                throw new Exception("Database error while checking description: " . $conn->error);
            }
            
            $stmt->bind_param("si", $student_no, $requirement_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $update_query = "UPDATE student_requirement_descriptions 
                               SET description = ?, updated_at = CURRENT_TIMESTAMP 
                               WHERE studentNo = ? AND requirement_id = ?";
                $stmt = $conn->prepare($update_query);
                if (!$stmt) {
                    throw new Exception("Database error while preparing update: " . $conn->error);
                }
                $stmt->bind_param("ssi", $new_description, $student_no, $requirement_id);
            } else {
                // Insert new record
                $insert_query = "INSERT INTO student_requirement_descriptions 
                               (studentNo, requirement_id, description) 
                               VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    throw new Exception("Database error while preparing insert: " . $conn->error);
                }
                $stmt->bind_param("sis", $student_no, $requirement_id, $new_description);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            error_log("Description updated successfully for student: $student_no, requirement: $requirement_id");
            
            // Update the descriptions array
            $individual_descriptions[$requirement_id] = $new_description;
            $_SESSION['success'] = "Description updated successfully.";
        } else {
            throw new Exception("You don't have permission to update this description");
        }
    } catch (Exception $e) {
        error_log("Error updating description: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redirect back to the same page
    header("Location: eclearance.php?studentNo=" . $student_no);
    exit();
}

// Fetch programs for filter
$program_query = "SELECT * FROM programs";
$program_result = $conn->query($program_query);

// Fetch sections for filter
$section_query = "SELECT * FROM sections";
$section_result = $conn->query($section_query);

// Fetch levels for filter
$level_query = "SELECT * FROM levels";
$level_result = $conn->query($level_query);

// Initialize filter variables
$selected_program = isset($_GET['program']) ? $_GET['program'] : '';
$selected_section = isset($_GET['section']) ? $_GET['section'] : '';
$selected_level = isset($_GET['level']) ? $_GET['level'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with filters
$query = "SELECT s.*, p.ProgramTitle, sec.SectionTitle, l.LevelName 
          FROM students s
          LEFT JOIN programs p ON s.ProgramCode = p.ProgramCode
          LEFT JOIN sections sec ON s.SectionCode = sec.SectionCode
          LEFT JOIN levels l ON s.Level = l.LevelID
          WHERE 1=1";

$params = array();
$types = "";

if (!empty($selected_program)) {
    $query .= " AND s.ProgramCode = ?";
    $params[] = $selected_program;
    $types .= "s";
}

if (!empty($selected_section)) {
    $query .= " AND s.SectionCode = ?";
    $params[] = $selected_section;
    $types .= "s";
}

if (!empty($selected_level)) {
    $query .= " AND s.Level = ?";
    $params[] = $selected_level;
    $types .= "i";
}

if (!empty($search_query)) {
    $query .= " AND (s.studentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY s.LastName, s.FirstName";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Clearance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
    <style>
        /* ===== BASE STYLES ===== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR STYLES ===== */
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

        /* ===== MAIN CONTENT STYLES ===== */
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

        /* Student Selector */
        .student-selector {
            margin-bottom: 25px;
        }

        .student-selector select {
            width: 100%;
            max-width: 400px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .student-selector select:focus {
            outline: none;
            border-color: #343079;
            box-shadow: 0 0 0 3px rgba(52, 48, 121, 0.1);
        }

        /* Student Info Card */
        .student-info-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .student-info-card h3 {
            margin-top: 0;
            color: #343079;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            display: block;
            margin-bottom: 3px;
            font-size: 14px;
        }

        .info-value {
            color: #333;
            font-size: 15px;
        }

        /* Section Headers */
        .section-header {
            color: #343079;
            margin: 25px 0 15px 0;
            font-size: 18px;
            font-weight: 600;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .data-table th {
            background-color: #343079;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f9f9f9;
        }

        /* Status Badges */
        .status-pending {
            color: #e67e22;
            font-weight: 500;
        }

        .status-approved {
            color: #27ae60;
            font-weight: 500;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 13px;
        }

        .btn-pending {
            background-color: #f39c12;
            color: white;
        }

        .btn-pending:hover {
            background-color: #d35400;
        }

        .btn-approved {
            background-color: #2ecc71;
            color: white;
        }

        .btn-approved:hover {
            background-color: #27ae60;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .info-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }
            
            .container {
                margin-left: 250px;
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .logo-text, .sidebar li a span {
                display: none;
            }
            
            .sidebar .icon {
                margin-right: 0;
                font-size: 20px;
            }
            
            .container {
                margin-left: 70px;
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            .description-edit {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        
        .description-edit textarea {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 60px;
        }
        
        .description-edit button {
            padding: 8px 12px;
            background-color: #343079;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .description-edit button:hover {
            background-color: #2a2861;
        }
        
        .edit-description-btn {
            background: none;
            border: none;
            color: #343079;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }
        }

        /* Add these styles to your existing CSS */
        .filter-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
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
            font-weight: 500;
            color: #555;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .filter-buttons button,
        .filter-buttons a {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }

        .apply-filters {
            background-color: #343079;
            color: white;
        }

        .apply-filters:hover {
            background-color: #2a2861;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .clear-filters {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .clear-filters:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Add icons to buttons */
        .filter-buttons button i,
        .filter-buttons a i {
            margin-right: 5px;
        }

        .search-container {
            margin-bottom: 15px;
        }

        .search-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
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
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
        <li class="active"><a href="eclearance.php"><i class="fas fa-clipboard-check icon"></i> E-Clearance</a></li>
        <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
        <li><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
        <li><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
        <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
        <li><a href="registration_requests.php"><i class="fas fa-user-plus icon"></i> Registration Requests</a></li>
        <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="filter-container">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Program</label>
                    <select name="program">
                        <option value="">All Programs</option>
                        <?php 
                        $program_result->data_seek(0);
                        while ($program = $program_result->fetch_assoc()) { ?>
                            <option value="<?php echo $program['ProgramCode']; ?>" 
                                <?php echo $selected_program === $program['ProgramCode'] ? 'selected' : ''; ?>>
                                <?php echo $program['ProgramTitle']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Section</label>
                    <select name="section">
                        <option value="">All Sections</option>
                        <?php 
                        $section_result->data_seek(0);
                        while ($section = $section_result->fetch_assoc()) { ?>
                            <option value="<?php echo $section['SectionCode']; ?>"
                                <?php echo $selected_section === $section['SectionCode'] ? 'selected' : ''; ?>>
                                <?php echo $section['SectionTitle']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Year Level</label>
                    <select name="level">
                        <option value="">All Year Levels</option>
                        <?php 
                        $level_result->data_seek(0);
                        while ($level = $level_result->fetch_assoc()) { ?>
                            <option value="<?php echo $level['LevelID']; ?>"
                                <?php echo $selected_level == $level['LevelID'] ? 'selected' : ''; ?>>
                                <?php echo $level['LevelName']; ?>
                    </option>
                        <?php } ?>
            </select>
                </div>
            </div>
            <div class="search-container">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by Student No., Last Name, or First Name..."
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="filter-buttons">
                <button type="submit" class="apply-filters">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="eclearance.php" class="clear-filters">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>

    <?php if ($show_student_details): ?>
    <!-- Student Information Card -->
    <div class="student-info-card">
        <h3>Student Account Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Student No:</span>
                <span class="info-value"><?= htmlspecialchars($student['studentNo']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['Mname']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Program:</span>
                <span class="info-value"><?= htmlspecialchars($student['ProgramCode']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Level:</span>
                <span class="info-value"><?= htmlspecialchars($student['Level']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Section:</span>
                <span class="info-value"><?= htmlspecialchars($student['SectionCode']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= htmlspecialchars($student['Email']) ?></span>
            </div>
        </div>
    </div>

    <!-- Clearance Status Table -->
    <h3 class="section-header">Clearance Status</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Department</th>
                <th>Requirement</th>
                <th>Description</th>
                <th>Status</th>
                <th>Approved By</th>
                <th>Date Updated</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requirements as $req_id => $requirement): 
            $status = $clearance_status[$req_id] ?? [
                'status' => 'Pending',
                'StaffID' => null,
                'updated_at' => ''
            ];
            
            $staff_name = '';
            if ($status['StaffID'] && isset($staff_members[$status['StaffID']])) {
                $staff = $staff_members[$status['StaffID']];
                $staff_name = $staff['LastName'] . ', ' . $staff['FirstName'];
            }
            
            $can_edit_status = $is_admin || ($req_id == $user_requirement_id);
            $can_edit_description = $is_admin || ($requirement_departments[$req_id] == $user_department);
            ?>
            <tr>
                <td><?= htmlspecialchars($requirement['DepartmentName']) ?></td>
                <td><?= htmlspecialchars($requirement['requirement_name']) ?></td>
                <td>
                    <div class="description-content">
                        <?= htmlspecialchars($individual_descriptions[$req_id] ?? $requirement['description']) ?>
                        <?php if ($can_edit_description): ?>
                            <button class="edit-description-btn" onclick="toggleDescriptionEdit(<?= $req_id ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($can_edit_description): ?>
                        <div class="description-edit" id="edit-description-<?= $req_id ?>" style="display: none;">
                            <form method="POST" action="eclearance.php">
                                <input type="hidden" name="update_description" value="1">
                                <input type="hidden" name="requirement_id" value="<?= $req_id ?>">
                                <input type="hidden" name="studentNo" value="<?= $student['studentNo'] ?>">
                                <textarea name="description" required><?= htmlspecialchars($individual_descriptions[$req_id] ?? $requirement['description']) ?></textarea>
                                <button type="submit">Update</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="status-<?= strtolower($status['status']) ?>">
                    <?= $status['status'] ?>
                </td>
                <td><?= htmlspecialchars($staff_name) ?></td>
                <td><?= $status['updated_at'] ?></td>
                <td>
    <div class="action-buttons">
        <form method="POST" action="update_clearance.php">
            <input type="hidden" name="studentNo" value="<?= $student['studentNo'] ?>">
            <input type="hidden" name="requirement_id" value="<?= $req_id ?>">
            <?php if ($is_admin || $req_id == $user_requirement_id): ?>
                <button type="submit" name="status" value="Pending" class="btn-pending">Pending</button>
                <button type="submit" name="status" value="Approved" class="btn-approved">Approve</button>
            <?php else: ?>
                <button type="button" disabled class="btn-pending" style="opacity: 0.5;">Pending</button>
                <button type="button" disabled class="btn-approved" style="opacity: 0.5;">Approve</button>
            <?php endif; ?>
        </form>
    </div>
</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    <?php endif; ?>

    <!-- Table of Students -->
    <h3 class="section-header">Student List</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Ctrl No.</th>
                <th>Student No.</th>
                <th>Name</th>
                <th>Program</th>
                <th>Level</th>
                <th>Section</th>
                <th>Clearance Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $status_query = "SELECT COUNT(*) as total, 
                                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved
                                FROM student_clearance_status 
                                WHERE studentNo = '{$row['studentNo']}'";
                $status_result = $conn->query($status_query);
                $status_data = $status_result->fetch_assoc();
                
                $overall_status = 'Pending';
                if ($status_data['total'] > 0) {
                    $overall_status = ($status_data['approved'] == $status_data['total']) ? 'Approved' : 'Pending';
                }
            ?>
            <tr onclick="selectStudent('<?= $row['studentNo'] ?>')">
                <td><?= $row['CtrlNo'] ?></td>
                <td><?= $row['studentNo'] ?></td>
                <td><?= htmlspecialchars($row['LastName'] . ', ' . $row['FirstName'] . ' ' . $row['Mname']) ?></td>
                <td><?= $row['ProgramTitle'] ?? $row['ProgramCode'] ?></td>
                <td><?= $row['LevelName'] ?? $row['Level'] ?></td>
                <td><?= $row['SectionTitle'] ?? $row['SectionCode'] ?></td>
                <td class="status-<?= strtolower($overall_status) ?>"><?= $overall_status ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
        function selectStudent(studentNo) {
            window.location.href = "eclearance.php?studentNo=" + studentNo;
        }
        
        function toggleDescriptionEdit(reqId) {
            const content = document.querySelector(`#edit-description-${reqId}`);
            content.style.display = content.style.display === 'none' ? 'flex' : 'none';
        }
</script>
</body>
</html>