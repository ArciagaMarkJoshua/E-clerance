<?php
session_start();
include '../includes/db_connect.php';

// Initialize variables
$message = '';
$message_type = '';

// CRUD Operations for Academic Years
if (isset($_POST['add_ay'])) {
    try {
        if (empty($_POST['code']) || empty($_POST['academic_year']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $code = $conn->real_escape_string($_POST['code']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);

        // Check if academic year code already exists
        $check_query = "SELECT Code FROM academicyears WHERE Code = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Academic year code already exists!");
        }
        $stmt->close();

        // Validate dates
        if (strtotime($start_date) >= strtotime($end_date)) {
            throw new Exception("End date must be after start date.");
        }

        // Insert new academic year
        $insert_query = "INSERT INTO academicyears (Code, AcademicYear, StartDate, EndDate) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $code, $academic_year, $start_date, $end_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding academic year: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Academic year added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: academicyear.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: academicyear.php");
        exit();
    }
}

if (isset($_POST['edit_ay'])) {
    try {
        if (empty($_POST['code']) || empty($_POST['academic_year']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $code = $conn->real_escape_string($_POST['code']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        $original_code = $conn->real_escape_string($_POST['original_code']);

        // Check if academic year exists
        $check_query = "SELECT Code FROM academicyears WHERE Code = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $original_code);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Academic year not found.");
        }
        $stmt->close();

        // Validate dates
        if (strtotime($start_date) >= strtotime($end_date)) {
            throw new Exception("End date must be after start date.");
        }

        // Update academic year
        $update_query = "UPDATE academicyears SET Code = ?, AcademicYear = ?, StartDate = ?, EndDate = ? WHERE Code = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssss", $code, $academic_year, $start_date, $end_date, $original_code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating academic year: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Academic year updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: academicyear.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: academicyear.php");
        exit();
    }
}

if (isset($_POST['delete_ay'])) {
    try {
        if (empty($_POST['code'])) {
            throw new Exception("Please select an academic year to delete.");
        }

        $code = $conn->real_escape_string($_POST['code']);
        
        // Check if academic year is in use
        $check_usage_query = "SELECT s.studentNo, s.FirstName, s.LastName 
                            FROM students s 
                            WHERE s.AcademicYear = ?";
        $stmt = $conn->prepare($check_usage_query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['studentNo'] . ')';
            }
            throw new Exception("Cannot delete academic year: It is currently in use by the following students:\n" . implode("\n", $students));
        }
        $stmt->close();

        // Delete academic year
        $delete_query = "DELETE FROM academicyears WHERE Code = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $code);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting academic year: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Academic year deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: academicyear.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: academicyear.php");
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

// Search functionality
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
$ay_sql = "SELECT * FROM academicyears";
if (!empty($search_query)) {
    $ay_sql .= " WHERE Code LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                 OR AcademicYear LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}
$ay_result = $conn->query($ay_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<title>Academic Year Management</title>
<style>
    /* Reuse the same styles from program_section.php */
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

    .form-buttons button[name="add_ay"] {
        background-color: #28a745;
    }

    .form-buttons button[name="edit_ay"] {
        background-color: #17a2b8;
    }

    .form-buttons button[name="delete_ay"] {
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

    .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        text-align: left;
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
    }
</style>
<script>
    function selectAY(code, academicYear, startDate, endDate) {
        document.getElementById('code').value = code;
        document.getElementById('original_code').value = code;
        document.getElementById('academic_year').value = academicYear;
        document.getElementById('start_date').value = startDate;
        document.getElementById('end_date').value = endDate;
    }

    function clearForm() {
        document.getElementById('code').value = '';
        document.getElementById('original_code').value = '';
        document.getElementById('academic_year').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
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
<div class="container">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" placeholder="Search academic years..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="ay_semester.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Academic Year Form -->
    <div class="panel">
        <h3>Academic Year Information</h3>
        <form method="POST">
            <input type="hidden" id="original_code" name="original_code">
            <div class="form-row">
                <div class="form-group">
                    <label>Code</label>
                    <input type="text" id="code" name="code" required>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" id="academic_year" name="academic_year" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_ay">Add</button>
                <button type="submit" name="edit_ay">Edit</button>
                <button type="submit" name="delete_ay" onclick="return confirm('Are you sure you want to delete this academic year?');">Delete</button>
                <button type="button" onclick="clearForm()">Clear</button>
            </div>
        </form>
    </div>

    <!-- Academic Year List -->
    <div class="panel">
        <h3>Academic Year List</h3>
        <div class="scrollable-content">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Academic Year</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($ay = $ay_result->fetch_assoc()) { ?>
                        <tr onclick="selectAY('<?php echo $ay['Code']; ?>', '<?php echo $ay['AcademicYear']; ?>', '<?php echo $ay['StartDate']; ?>', '<?php echo $ay['EndDate']; ?>')">
                            <td><?php echo $ay['Code']; ?></td>
                            <td><?php echo $ay['AcademicYear']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($ay['StartDate'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($ay['EndDate'])); ?></td>
                            <td><button type="button">Select</button></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>