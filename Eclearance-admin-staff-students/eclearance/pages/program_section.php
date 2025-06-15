<?php
session_start();
include '../includes/db_connect.php';

// Initialize variables
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
$message = '';
$message_type = '';

// Fetch programs for display
$program_query = "SELECT * FROM programs";
if (!empty($search_query)) {
    $program_query .= " WHERE ProgramCode LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                       OR ProgramTitle LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}
$program_result = $conn->query($program_query);

// Fetch sections for display
$section_query = "SELECT s.*, l.LevelName, p.ProgramTitle 
                 FROM sections s 
                 JOIN levels l ON s.YearLevel = l.LevelID
                 JOIN programs p ON s.ProgramCode = p.ProgramCode";
if (!empty($search_query)) {
    $section_query .= " WHERE s.SectionCode LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                       OR s.SectionTitle LIKE '%" . $conn->real_escape_string($search_query) . "%'
                       OR p.ProgramTitle LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}
$section_result = $conn->query($section_query);

// Fetch levels for dropdown
$level_query = "SELECT * FROM levels";
$level_result = $conn->query($level_query);

// Program CRUD Operations
if (isset($_POST['add_program'])) {
    try {
        if (empty($_POST['program_code']) || empty($_POST['program_title'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $program_code = $conn->real_escape_string($_POST['program_code']);
        $program_title = $conn->real_escape_string($_POST['program_title']);

        // Check if program code already exists
        $check_query = "SELECT ProgramCode FROM programs WHERE ProgramCode = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $program_code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Program code already exists!");
        }
        $stmt->close();

        // Insert new program
        $insert_query = "INSERT INTO programs (ProgramCode, ProgramTitle) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $program_code, $program_title);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding program: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Program added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=program");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=program");
        exit();
    }
}

if (isset($_POST['edit_program'])) {
    try {
        if (empty($_POST['program_code']) || empty($_POST['program_title'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $program_code = $conn->real_escape_string($_POST['program_code']);
        $program_title = $conn->real_escape_string($_POST['program_title']);
        $original_code = $conn->real_escape_string($_POST['original_code']);

        // Check if program exists
        $check_query = "SELECT ProgramCode FROM programs WHERE ProgramCode = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $original_code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Program not found.");
        }
        $stmt->close();

        // Update program
        $update_query = "UPDATE programs SET ProgramCode = ?, ProgramTitle = ? WHERE ProgramCode = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $program_code, $program_title, $original_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating program: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Program updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=program");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=program");
        exit();
    }
}

if (isset($_POST['delete_program'])) {
    try {
        if (empty($_POST['program_code'])) {
            throw new Exception("Please select a program to delete.");
        }

        $program_code = $conn->real_escape_string($_POST['program_code']);
        
        // Check if program is in use
        $check_usage_query = "SELECT s.studentNo, s.FirstName, s.LastName 
                            FROM students s 
                            WHERE s.ProgramCode = ?";
        $stmt = $conn->prepare($check_usage_query);
        $stmt->bind_param("s", $program_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['studentNo'] . ')';
            }
            throw new Exception("Cannot delete program: It is currently in use by the following students:\n" . implode("\n", $students));
        }
        $stmt->close();

        // Delete program
        $delete_query = "DELETE FROM programs WHERE ProgramCode = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $program_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting program: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Program deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=program");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=program");
        exit();
    }
}

// Section CRUD Operations
if (isset($_POST['add_section'])) {
    try {
        if (empty($_POST['section_code']) || empty($_POST['section_title']) || empty($_POST['year_level']) || empty($_POST['program_code'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $section_code = $conn->real_escape_string($_POST['section_code']);
        $section_title = $conn->real_escape_string($_POST['section_title']);
        $year_level = intval($_POST['year_level']);
        $program_code = $conn->real_escape_string($_POST['program_code']);

        // Check if section code already exists
        $check_query = "SELECT SectionCode FROM sections WHERE SectionCode = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $section_code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Section code already exists!");
        }
        $stmt->close();

        // Insert new section
        $insert_query = "INSERT INTO sections (SectionCode, SectionTitle, YearLevel, ProgramCode) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssis", $section_code, $section_title, $year_level, $program_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding section: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Section added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=section");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=section");
        exit();
    }
}

if (isset($_POST['edit_section'])) {
    try {
        if (empty($_POST['section_code']) || empty($_POST['section_title']) || empty($_POST['year_level']) || empty($_POST['program_code'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $section_code = $conn->real_escape_string($_POST['section_code']);
        $section_title = $conn->real_escape_string($_POST['section_title']);
        $year_level = intval($_POST['year_level']);
        $program_code = $conn->real_escape_string($_POST['program_code']);
        $original_code = $conn->real_escape_string($_POST['original_code']);

        // Check if section exists
        $check_query = "SELECT SectionCode FROM sections WHERE SectionCode = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $original_code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Section not found.");
        }
        $stmt->close();

        // Update section
        $update_query = "UPDATE sections SET SectionCode = ?, SectionTitle = ?, YearLevel = ?, ProgramCode = ? WHERE SectionCode = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssiss", $section_code, $section_title, $year_level, $program_code, $original_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating section: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Section updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=section");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=section");
        exit();
    }
}

if (isset($_POST['delete_section'])) {
    try {
        if (empty($_POST['section_code'])) {
            throw new Exception("Please select a section to delete.");
        }

        $section_code = $conn->real_escape_string($_POST['section_code']);
        
        // Check if section is in use
        $check_usage_query = "SELECT s.studentNo, s.FirstName, s.LastName 
                            FROM students s 
                            WHERE s.SectionCode = ?";
        $stmt = $conn->prepare($check_usage_query);
        $stmt->bind_param("s", $section_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['studentNo'] . ')';
            }
            throw new Exception("Cannot delete section: It is currently in use by the following students:\n" . implode("\n", $students));
        }
        $stmt->close();

        // Delete section
        $delete_query = "DELETE FROM sections WHERE SectionCode = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $section_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting section: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Section deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: program_section.php?tab=section");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: program_section.php?tab=section");
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
<title>Program & Section Management</title>
<style>
    /* Keep existing styles */
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
        flex: 1;
        margin-left: 300px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        box-sizing: border-box;
        overflow-y: auto;
    }

    .panel {
        background-color: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .panel h3 {
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

    .form-buttons button[name="add_program"],
    .form-buttons button[name="add_section"] {
        background-color: #28a745;
    }

    .form-buttons button[name="edit_program"],
    .form-buttons button[name="edit_section"] {
        background-color: #17a2b8;
    }

    .form-buttons button[name="delete_program"],
    .form-buttons button[name="delete_section"] {
        background-color: #dc3545;
    }

    .form-buttons button[type="button"] {
        background-color: #6c757d;
    }

    .form-buttons button:hover {
        opacity: 0.9;
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

    .list-container h3 {
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

    .tab-container {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
    }

    .tab {
        padding: 10px 20px;
        cursor: pointer;
        background-color: #f1f1f1;
        border: none;
        outline: none;
        transition: 0.3s;
        font-size: 14px;
        border-radius: 6px 6px 0 0;
        margin-right: 5px;
    }

    .tab.active {
        background-color: #343079;
        color: white;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
        text-align: center;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        white-space: pre-line;
        text-align: left;
        padding: 15px;
    }
</style>
<script>
    function selectProgram(programCode, programTitle) {
        document.getElementById('program_code').value = programCode;
        document.getElementById('original_code').value = programCode;
        document.getElementById('program_title').value = programTitle;
        
        // Change button focus to Edit/Delete
        document.querySelector("button[name='add_program']").classList.remove('active');
        document.querySelector("button[name='edit_program']").classList.add('active');
        document.querySelector("button[name='delete_program']").classList.add('active');
    }

    function selectSection(sectionCode, sectionTitle, yearLevel, programCode) {
        document.getElementById('section_code').value = sectionCode;
        document.getElementById('original_section_code').value = sectionCode;
        document.getElementById('section_title').value = sectionTitle;
        document.getElementById('year_level').value = yearLevel;
        document.getElementById('program_code').value = programCode;
        
        // Extract section letter from section code
        const sectionLetter = sectionCode.slice(-1);
        document.getElementById('section_letter').value = sectionLetter;
        
        // Change button focus to Edit/Delete
        document.querySelector("button[name='add_section']").classList.remove('active');
        document.querySelector("button[name='edit_section']").classList.add('active');
        document.querySelector("button[name='delete_section']").classList.add('active');
    }

    function clearForm(formType) {
        if (formType === 'program') {
            document.getElementById('program_code').value = '';
            document.getElementById('original_code').value = '';
            document.getElementById('program_title').value = '';
            
            // Reset to add mode
            document.querySelector("button[name='add_program']").classList.add('active');
            document.querySelector("button[name='edit_program']").classList.remove('active');
            document.querySelector("button[name='delete_program']").classList.remove('active');
        } else if (formType === 'section') {
            document.getElementById('section_code').value = '';
            document.getElementById('original_section_code').value = '';
            document.getElementById('section_title').value = '';
            document.getElementById('year_level').value = '';
            document.getElementById('program_code').value = '';
            document.getElementById('section_letter').value = '';
            
            // Reset to add mode
            document.querySelector("button[name='add_section']").classList.add('active');
            document.querySelector("button[name='edit_section']").classList.remove('active');
            document.querySelector("button[name='delete_section']").classList.remove('active');
        }
    }

    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }
        
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Get the tab parameter from URL
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        // If tab parameter exists, switch to that tab
        if (tab) {
            const tabButton = document.querySelector(`.tab[onclick*="${tab}-tab"]`);
            if (tabButton) {
                tabButton.click();
            }
        }
    });

    // Add event listeners for program and year level changes
    document.addEventListener('DOMContentLoaded', function() {
        const programSelect = document.getElementById('program_code');
        const yearLevelSelect = document.getElementById('year_level');
        const sectionLetterInput = document.getElementById('section_letter');
        const sectionCodeInput = document.getElementById('section_code');
        const sectionTitleInput = document.getElementById('section_title');

        function updateSectionCode() {
            const program = programSelect.value;
            const yearLevel = yearLevelSelect.value;
            const sectionLetter = sectionLetterInput.value.toUpperCase();
            
            if (program && yearLevel && sectionLetter) {
                const sectionCode = program + yearLevel + sectionLetter;
                sectionCodeInput.value = sectionCode;
                sectionTitleInput.value = sectionCode;
            }
        }

        programSelect.addEventListener('change', updateSectionCode);
        yearLevelSelect.addEventListener('change', updateSectionCode);
        sectionLetterInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            updateSectionCode();
        });
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
<div class="container">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-container">
        <button class="tab active" onclick="openTab(event, 'program-tab')">Programs</button>
        <button class="tab" onclick="openTab(event, 'section-tab')">Sections</button>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" placeholder="Search programs or sections..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="program_section.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Program Tab Content -->
    <div id="program-tab" class="tab-content active">
        <!-- Program Form -->
        <div class="panel">
            <h3>Program Information</h3>
            <form method="POST" action="">
                <input type="hidden" id="original_code" name="original_code">
                <div class="form-row">
                    <div class="form-group">
                        <label>Program Code</label>
                        <input type="text" id="program_code" name="program_code" required>
                    </div>
                    <div class="form-group">
                        <label>Program Title</label>
                        <input type="text" id="program_title" name="program_title" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="add_program" class="active">Add</button>
                    <button type="submit" name="edit_program">Edit</button>
                    <button type="submit" name="delete_program" onclick="return confirm('Are you sure you want to delete this program?');">Delete</button>
                    <button type="button" onclick="clearForm('program')">Clear</button>
                </div>
            </form>
        </div>

        <!-- Program List -->
        <div class="panel">
            <h3>Program List</h3>
            <div class="scrollable-content">
                <table>
                    <thead>
                        <tr>
                            <th>Program Code</th>
                            <th>Program Title</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($program = $program_result->fetch_assoc()) { ?>
                            <tr onclick="selectProgram('<?php echo htmlspecialchars($program['ProgramCode']); ?>', '<?php echo htmlspecialchars($program['ProgramTitle']); ?>')">
                                <td><?php echo htmlspecialchars($program['ProgramCode']); ?></td>
                                <td><?php echo htmlspecialchars($program['ProgramTitle']); ?></td>
                                <td><button type="button">Select</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section Tab Content -->
    <div id="section-tab" class="tab-content">
        <!-- Section Form -->
        <div class="panel">
            <h3>Section Information</h3>
            <form method="POST" action="">
                <input type="hidden" id="original_section_code" name="original_code">
                <div class="form-row">
                    <div class="form-group">
                        <label>Program</label>
                        <select id="program_code" name="program_code" required>
                            <option value="">Select Program</option>
                            <?php 
                            $program_result->data_seek(0);
                            while ($program = $program_result->fetch_assoc()) { ?>
                                <option value="<?php echo $program['ProgramCode']; ?>"><?php echo $program['ProgramTitle']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select id="year_level" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <?php 
                            $level_result->data_seek(0);
                            while ($level = $level_result->fetch_assoc()) { ?>
                                <option value="<?php echo $level['LevelID']; ?>"><?php echo htmlspecialchars($level['LevelName']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section Letter</label>
                        <input type="text" id="section_letter" name="section_letter" maxlength="1" required>
                    </div>
                </div>
                <input type="hidden" id="section_code" name="section_code">
                <input type="hidden" id="section_title" name="section_title">
                <div class="form-buttons">
                    <button type="submit" name="add_section" class="active">Add</button>
                    <button type="submit" name="edit_section">Edit</button>
                    <button type="submit" name="delete_section" onclick="return confirm('Are you sure you want to delete this section?');">Delete</button>
                    <button type="button" onclick="clearForm('section')">Clear</button>
                </div>
            </form>
        </div>

        <!-- Section List -->
        <div class="panel">
            <h3>Section List</h3>
            <div class="scrollable-content">
                <table>
                    <thead>
                        <tr>
                            <th>Section Code</th>
                            <th>Section Title</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $section_result->data_seek(0);
                        while ($section = $section_result->fetch_assoc()) { ?>
                            <tr onclick="selectSection('<?php echo htmlspecialchars($section['SectionCode']); ?>', '<?php echo htmlspecialchars($section['SectionTitle']); ?>', <?php echo $section['YearLevel']; ?>, '<?php echo htmlspecialchars($section['ProgramCode']); ?>')">
                                <td><?php echo htmlspecialchars($section['SectionCode']); ?></td>
                                <td><?php echo htmlspecialchars($section['SectionTitle']); ?></td>
                                <td><?php echo htmlspecialchars($section['ProgramTitle']); ?></td>
                                <td><?php echo htmlspecialchars($section['LevelName']); ?></td>
                                <td><button type="button">Select</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>