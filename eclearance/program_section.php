<?php
session_start();
include 'db_connect.php';

// Fetch programs for display
$program_query = "SELECT * FROM programs";
$program_result = $conn->query($program_query);

// Fetch sections for display
$section_query = "SELECT * FROM sections";
$section_result = $conn->query($section_query);

// Search functionality
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
    $program_sql = "SELECT * FROM programs WHERE 
                   ProgramCode LIKE '%$search_query%' OR 
                   ProgramTitle LIKE '%$search_query%'";
    $section_sql = "SELECT * FROM sections WHERE 
                   SectionCode LIKE '%$search_query%' OR 
                   SectionTitle LIKE '%$search_query%'";
} else {
    $program_sql = "SELECT * FROM programs";
    $section_sql = "SELECT * FROM sections";
}

$program_result = $conn->query($program_sql);
$section_result = $conn->query($section_sql);

// Program CRUD Operations
if (isset($_POST['add_program'])) {
    $program_code = $_POST['program_code'];
    $program_title = $_POST['program_title'];
    
    $sql = "INSERT INTO programs (ProgramCode, ProgramTitle) 
            VALUES ('$program_code', '$program_title')";
    
    if (!$conn->query($sql)) {
        die("Insert Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

if (isset($_POST['edit_program'])) {
    $original_code = $_POST['original_code'];
    $program_code = $_POST['program_code'];
    $program_title = $_POST['program_title'];
    
    $sql = "UPDATE programs SET 
            ProgramCode='$program_code',
            ProgramTitle='$program_title'
            WHERE ProgramCode='$original_code'";
    
    if (!$conn->query($sql)) {
        die("Update Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

if (isset($_POST['delete_program'])) {
    $program_code = $_POST['program_code'];
    $sql = "DELETE FROM programs WHERE ProgramCode='$program_code'";
    
    if (!$conn->query($sql)) {
        die("Delete Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

// Section CRUD Operations
if (isset($_POST['add_section'])) {
    $section_code = $_POST['section_code'];
    $section_title = $_POST['section_title'];
  
    
    $sql = "INSERT INTO sections (SectionCode, SectionTitle) 
            VALUES ('$section_code', '$section_title')";
    
    if (!$conn->query($sql)) {
        die("Insert Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

if (isset($_POST['edit_section'])) {
    $original_code = $_POST['original_code'];
    $section_code = $_POST['section_code'];
    $section_title = $_POST['section_title'];

    
    $sql = "UPDATE sections SET 
            SectionCode='$section_code',
            SectionTitle='$section_title',
            WHERE SectionCode='$original_code'";
    
    if (!$conn->query($sql)) {
        die("Update Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

if (isset($_POST['delete_section'])) {
    $section_code = $_POST['section_code'];
    $sql = "DELETE FROM sections WHERE SectionCode='$section_code'";
    
    if (!$conn->query($sql)) {
        die("Delete Error: " . $conn->error);
    }
    header("Location: program_section.php");
}

// Fetch programs for section dropdown
$programs_for_section = $conn->query("SELECT * FROM programs");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<title>Program & Section Management</title>
<style>
    /* Reuse the same styles from student_management.php */
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
</style>
<script>
    function selectProgram(programCode, programTitle) {
        document.getElementById('program_code').value = programCode;
        document.getElementById('original_code').value = programCode;
        document.getElementById('program_title').value = programTitle;
    }

    function selectSection(sectionCode, sectionTitle) {
        document.getElementById('section_code').value = sectionCode;
        document.getElementById('original_section_code').value = sectionCode;
        document.getElementById('section_title').value = sectionTitle;
    }

    function clearForm(formType) {
        if (formType === 'program') {
            document.getElementById('program_code').value = '';
            document.getElementById('original_code').value = '';
            document.getElementById('program_title').value = '';
        } else if (formType === 'section') {
            document.getElementById('section_code').value = '';
            document.getElementById('original_section_code').value = '';
            document.getElementById('section_title').value = '';
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
</script>
</head>
<body>
<nav class="sidebar">
    <div class="logo-container">
        <img src="dyci_logo.svg" alt="College Logo" class="logo">
        <div class="logo-text">
            <h2>DR. YANGA'S COLLEGES INC.</h2>
            <p>Administrator</p>
        </div>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home icon"></i> <span>Home</span></a></li>
        <li><a href="staff_management.php"><i class="fas fa-users icon"></i> <span>Staff Management</span></a></li>
        <li><a href="eclearance.php"><i class="fas fa-file-alt icon"></i> <span>E-Clearance</span></a></li>
        <li class="active"><a href="program_section.php"><i class="fas fa-th-large icon"></i> <span>Program & Section</span></a></li>
        <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> <span>Academic Year</span></a></li>
        <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> <span>Student Management</span></a></li>
        <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> <span>Logout</span></a></li>
    </ul>
</nav>
<div class="container">
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
            <form method="POST">
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
                    <button type="submit" name="add_program">Add</button>
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
                            <tr onclick="selectProgram('<?php echo $program['ProgramCode']; ?>', '<?php echo $program['ProgramTitle']; ?>')">
                                <td><?php echo $program['ProgramCode']; ?></td>
                                <td><?php echo $program['ProgramTitle']; ?></td>
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
            <form method="POST">
                <input type="hidden" id="original_section_code" name="original_code">
                <div class="form-row">
                    <div class="form-group">
                        <label>Section Code</label>
                        <input type="text" id="section_code" name="section_code" required>
                    </div>
                    <div class="form-group">
                        <label>Section Title</label>
                        <input type="text" id="section_title" name="section_title" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="add_section">Add</button>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer for section result
                        $section_result->data_seek(0);
                        while ($section = $section_result->fetch_assoc()) { 
                            
                        ?>
                            <tr onclick="selectSection('<?php echo $section['SectionCode']; ?>', '<?php echo $section['SectionTitle']; ?>')">
                                <td><?php echo $section['SectionCode']; ?></td>
                                <td><?php echo $section['SectionTitle']; ?></td>
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