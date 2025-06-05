<?php
session_start();
include '../includes/db_connect.php'; // Database connection

// Initialize variables
$next_ctrl_no = 1;
$search_query = "";

// Fetch next available control number
$ctrl_no_query = "SELECT MAX(CtrlNo) AS max_ctrl FROM students";
$ctrl_no_result = $conn->query($ctrl_no_query);
if ($ctrl_no_result && $row = $ctrl_no_result->fetch_assoc()) {
    $next_ctrl_no = $row['max_ctrl'] + 1;
}

// Fetch programs for dropdown
$program_query = "SELECT * FROM programs"; 
$program_result = $conn->query($program_query);

// Fetch sections for dropdown
$section_query = "SELECT * FROM sections";
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
        $required_fields = ['student_no', 'username', 'last_name', 'first_name', 'email', 'program_code', 'section_code', 'academic_year', 'semester', 'level'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        $student_no = $conn->real_escape_string($_POST['student_no']);
        $username = $conn->real_escape_string($_POST['username']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $program_code = $conn->real_escape_string($_POST['program_code']);
        $section_code = $conn->real_escape_string($_POST['section_code']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $semester = $conn->real_escape_string($_POST['semester']);
        $level = $conn->real_escape_string($_POST['level']);
        $account_type = "Student";
        $is_active = 1;

        // Check if student number already exists
        $check_query = "SELECT * FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception("Database error while checking existing student.");
        }
        $stmt->bind_param("s", $student_no);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Student number already exists!");
        }
        $stmt->close();

        // Insert new student
        $insert_query = "INSERT INTO students (studentNo, Username, LastName, FirstName, Mname, Email, ProgramCode, SectionCode, AcademicYear, Semester, Level, AccountType, IsActive) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing insert query.");
        }
        
        $stmt->bind_param("ssssssssssssi", 
            $student_no,
            $username,
            $last_name,
            $first_name,
            $middle_name,
            $email,
            $program_code,
            $section_code,
            $academic_year,
            $semester,
            $level,
            $account_type,
            $is_active
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding student: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Student added successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Student management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
        }
        exit();
    }
}

// Edit Student
if (isset($_POST['edit_student'])) {
    try {
        // Validate required fields
        $required_fields = ['student_no', 'last_name', 'first_name', 'program_code', 'section_code', 'academic_year', 'semester', 'level'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        $student_no = $conn->real_escape_string($_POST['student_no']);
        $username = $conn->real_escape_string($_POST['username']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $middle_name = $conn->real_escape_string($_POST['middle_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $program_code = $conn->real_escape_string($_POST['program_code']);
        $section_code = $conn->real_escape_string($_POST['section_code']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $semester = $conn->real_escape_string($_POST['semester']);
        $level = $conn->real_escape_string($_POST['level']);
        
        // Check if student exists
        $check_query = "SELECT * FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception("Database error while checking student existence.");
        }
        $stmt->bind_param("s", $student_no);
        $stmt->execute();

        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Student not found.");
        }
        $stmt->close();

        // Update student
        $update_query = "UPDATE students SET 
                        Username = ?,
                        LastName = ?, 
                        FirstName = ?, 
                        Mname = ?, 
                        Email = ?,
                        ProgramCode = ?, 
                        SectionCode = ?,
                        AcademicYear = ?,
                        Semester = ?,
                        Level = ?
                        WHERE studentNo = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing update query.");
        }
        $stmt->bind_param("sssssssssss", 
            $username,
            $last_name, 
            $first_name, 
            $middle_name, 
            $email,
            $program_code, 
            $section_code,
            $academic_year,
            $semester,
            $level,
            $student_no
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating student: " . $stmt->error);
        }

        $_SESSION['message'] = "Student updated successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Student management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
        }
        exit();
    }
}

// Delete Student
if (isset($_POST['delete_student'])) {
    try {
        if (empty($_POST['student_no'])) {
            throw new Exception("Please select a student to delete.");
        }

        $student_no = $conn->real_escape_string($_POST['student_no']);
        
        // Check if student exists before deletion
        $check_query = "SELECT * FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception("Database error while checking student existence.");
        }
        $stmt->bind_param("s", $student_no);
        $stmt->execute();

        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Student not found.");
        }
        $stmt->close();

        // Delete student
        $delete_query = "DELETE FROM students WHERE studentNo = ?";
        $stmt = $conn->prepare($delete_query);
        if (!$stmt) {
            throw new Exception("Database error while preparing delete query.");
        }
        $stmt->bind_param("s", $student_no);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting student: " . $stmt->error);
        }
        
        // Store message in session
        $_SESSION['message'] = "Student successfully deleted!";
        $_SESSION['message_type'] = "success";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Student management error: " . $e->getMessage());
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect with search query if it exists
        if (!empty($search_query)) {
            header("Location: student_management.php?search_query=" . urlencode($search_query));
        } else {
            header("Location: student_management.php");
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

    .form-buttons button[name="delete_student"] {
        background-color: #dc3545; /* red */
    }

    .form-buttons button[name="delete_student"]:hover {
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
    </style>


    <script>
        function selectUser(ctrlNo, studentNo, username, lastName, firstName, middleName, email, programCode, sectionCode, academicYear, semester, level) {
            document.getElementById('ctrl_no').value = ctrlNo;
            document.getElementById('student_no').value = studentNo;
            document.getElementById('username').value = username;
            document.getElementById('last_name').value = lastName;
            document.getElementById('first_name').value = firstName;
            document.getElementById('middle_name').value = middleName;
            document.getElementById('email').value = email;
            document.getElementById('program_code').value = programCode;
            document.getElementById('section_code').value = sectionCode;
            document.getElementById('academic_year').value = academicYear;
            document.getElementById('semester').value = semester;
            document.getElementById('level').value = level;

            // Make all fields optional when deleting
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.removeAttribute('required');
            });
        }
        
        function clearForm() {
            document.getElementById('ctrl_no').value = '<?php echo $next_ctrl_no; ?>';
            document.getElementById('student_no').value = '';
            document.getElementById('username').value = '';
            document.getElementById('last_name').value = '';
            document.getElementById('first_name').value = '';
            document.getElementById('middle_name').value = '';
            document.getElementById('email').value = '';
            document.getElementsByName('password')[0].value = '';
            document.getElementById('program_code').selectedIndex = 0;
            document.getElementById('section_code').selectedIndex = 0;
            document.getElementById('level').selectedIndex = 0;
            document.getElementById('academic_year').selectedIndex = 0;
            document.getElementById('semester').selectedIndex = 0;

            // Clear sections dropdown
            const sectionSelect = document.getElementById('section_code');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
        }

        function filterSections() {
            const yearLevel = document.getElementById('level').value;
            const sectionSelect = document.getElementById('section_code');
            
            // Clear current options
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (!yearLevel) return;
            
            // Fetch sections for the selected year level
            fetch(`get_sections.php?year_level=${yearLevel}`)
                .then(response => response.json())
                .then(sections => {
                    if (sections.error) {
                        console.error('Error:', sections.error);
                        return;
                    }
                    sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.SectionCode;
                        option.textContent = section.SectionTitle;
                        sectionSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        }

        // Add event listener to year level select
        document.addEventListener('DOMContentLoaded', function() {
            const levelSelect = document.getElementById('level');
            if (levelSelect) {
                levelSelect.addEventListener('change', filterSections);
        }
        });
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
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
        <li><a href="eclearance.php"><i class="fas fa-clipboard-check icon"></i> E-Clearance</a></li>
        <li class="active"><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
        <li><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
        <li><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
        <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
        <li><a href="registration_requests.php"><i class="fas fa-user-plus icon"></i> Registration Requests</a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
        <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
</nav>

    
<div class="container">
    <?php if (isset($message)): ?>
        <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="user-panel">
        <h3>Student Information</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Ctrl No.</label>
                    <input type="text" id="ctrl_no" name="ctrl_no" value="<?php echo $next_ctrl_no; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Student No.</label>
                    <input type="text" id="student_no" name="student_no" required>
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
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="New Password (Leave blank to keep current)">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Program</label>
                    <select id="program_code" name="program_code" required>
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
                    <select id="section_code" name="section_code" required>
                        <option value="">Select Section</option>
                        <?php 
                        $section_result->data_seek(0); // Reset pointer
                        while ($section = $section_result->fetch_assoc()) { ?>
                            <option value="<?php echo $section['SectionCode']; ?>"><?php echo $section['SectionTitle']; ?></option>
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
                        $semester_result->data_seek(0); // Reset pointer
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
                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" name="account_type" value="Student" readonly>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_student">Add</button>
                <button type="submit" name="edit_student">Edit</button>
                <button type="submit" name="delete_student" onclick="return confirm('Are you sure you want to delete this student?');">Delete</button>
                <button type="button" onclick="clearForm();">Clear</button>
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

    <div class="scrollable-content">
        <div class="user-list-container">
        <h3>Student List</h3>
        <table>
            <thead>
                <tr>
                    <th>Ctrl No.</th>
                    <th>Student No.</th>
                    <th>Username</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Program</th>
                    <th>Section</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr onclick="selectUser('<?php echo $row['CtrlNo']; ?>', '<?php echo $row['studentNo']; ?>', '<?php echo $row['Username']; ?>', '<?php echo $row['LastName']; ?>', '<?php echo $row['FirstName']; ?>', '<?php echo $row['Mname']; ?>', '<?php echo $row['Email']; ?>', '<?php echo $row['ProgramCode']; ?>', '<?php echo $row['SectionCode']; ?>', '<?php echo $row['AcademicYear']; ?>', '<?php echo $row['Semester']; ?>', '<?php echo $row['Level']; ?>')">
                        <td><?php echo $row['CtrlNo']; ?></td>
                        <td><?php echo $row['studentNo']; ?></td>
                        <td><?php echo $row['Username']; ?></td>
                        <td><?php echo $row['LastName']; ?></td>
                        <td><?php echo $row['FirstName']; ?></td>
                        <td><?php echo $row['Mname']; ?></td>
                       
                        <td><?php echo $row['ProgramTitle'] ?? $row['ProgramCode']; ?></td>
                        <td><?php echo $row['SectionTitle'] ?? $row['SectionCode']; ?></td>
                        <td><?php echo $row['AcademicYear']; ?></td>
                        <td><?php echo $row['Semester']; ?></td>
                        <td><?php echo $row['LevelName'] ?? $row['Level']; ?></td>
                        <td><button type="button">Select</button></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>