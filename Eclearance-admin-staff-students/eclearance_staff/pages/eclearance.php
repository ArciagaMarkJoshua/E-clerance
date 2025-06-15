<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Get staff department from session
$staff_department_name = $_SESSION['department'];

// Initialize variables for requirement details
$current_requirement_details = null;
$requirement_id = null;
$department_id = null;

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

// Initialize variables
$students = [];
$selected_student = null;
$clearance_status = null;

// Initialize error and success messages from session, then clear them
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $student_no = $_POST['student_no'];
        $status = $_POST['status'];
        $comments = $_POST['comments'] ?? '';

        // Ensure requirement_id is valid for update
        if (!$requirement_id) {
            $error = "Error: Department requirement not found.";
        } else {
            // Update clearance status
            $update_query = "INSERT INTO student_clearance_status (studentNo, requirement_id, StaffID, status, comments, approved_by) 
                            VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            status = VALUES(status),
                            comments = VALUES(comments),
                            approved_by = VALUES(approved_by),
                            date_approved = CURRENT_TIMESTAMP";

            $stmt = $conn->prepare($update_query);
            $staff_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $stmt->bind_param("siisss", $student_no, $requirement_id, $_SESSION['staff_id'], $status, $comments, $staff_name);
            
            if ($stmt->execute()) {
                $success = "Clearance status updated successfully.";
                
                // Log the activity
                $log_query = "INSERT INTO activity_logs (user_id, user_type, action, details) VALUES (?, 'Staff', ?, ?)";
                $log_stmt = $conn->prepare($log_query);
                $action = $status === 'Approved' ? 'Approved Clearance' : 'Updated Clearance Status';
                $details = $status . " clearance for student " . $student_no . ", requirement " . ($current_requirement_details['requirement_name'] ?? $requirement_id);
                $log_stmt->bind_param("iss", $_SESSION['staff_id'], $action, $details);
                $log_stmt->execute();
            } else {
                $error = "Error updating clearance status.";
            }
        }
    }
}

// Get students based on filters
$program = $_GET['program'] ?? '';
$section = $_GET['section'] ?? '';
$level = $_GET['level'] ?? '';
$search = $_GET['search'] ?? '';
$clearance_status_filter = $_GET['clearance_status'] ?? '';

$query = "SELECT DISTINCT s.*, 
          scs.status,
          scs.comments,
          scs.date_approved,
          scs.approved_by,
          srd.description as student_requirement_description
          FROM students s
          LEFT JOIN student_clearance_status scs ON s.studentNo = scs.studentNo 
          AND scs.requirement_id = ?
          LEFT JOIN student_requirement_descriptions srd ON s.studentNo = srd.studentNo AND srd.requirement_id = ?
          WHERE s.IsActive = 1";

$params = [$requirement_id, $requirement_id];
$types = "ii";

if ($program) {
    $query .= " AND s.ProgramCode = ?";
    $params[] = $program;
    $types .= "s";
}

if ($section) {
    $query .= " AND s.SectionCode = ?";
    $params[] = $section;
    $types .= "s";
}

if ($level) {
    $query .= " AND s.Level = ?";
    $params[] = $level;
    $types .= "i";
}

if ($search) {
    $query .= " AND (s.studentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($clearance_status_filter) {
    // If filtering by 'Cleared', consider 'Approved' status
    if ($clearance_status_filter === 'Cleared') {
        $query .= " AND scs.status = ?";
        $params[] = 'Approved';
    } else {
        // For 'Pending' or 'Pending Approve', use the actual status
        $query .= " AND scs.status = ?";
        $params[] = $clearance_status_filter;
    }
    $types .= "s";
}

$query .= " ORDER BY s.LastName, s.FirstName";

$stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Get programs for filter
$programs_query = "SELECT ProgramCode, ProgramTitle FROM programs ORDER BY ProgramTitle";
$programs_result = $conn->query($programs_query);
$programs = $programs_result->fetch_all(MYSQLI_ASSOC);

// Get sections for filter
$sections_query = "SELECT SectionCode, SectionTitle FROM sections ORDER BY SectionTitle";
$sections_result = $conn->query($sections_query);
$sections = $sections_result->fetch_all(MYSQLI_ASSOC);

// Get levels for filter
$levels_query = "SELECT LevelID, LevelName FROM levels ORDER BY LevelID";
$levels_result = $conn->query($levels_query);
$levels = $levels_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Clearance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/styles.css">
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
            flex: 1;
            margin-left: 300px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #343079;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #343079;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .students-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .students-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th,
        .students-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .students-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .students-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }

        .modal-content h2 {
            color: #343079;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .modal-content .form-group label {
            font-weight: 600;
            color: #444;
        }

        .modal-content .filter-buttons {
            justify-content: flex-end;
            margin-top: 20px;
        }

        .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }

            .container {
                margin-left: 0;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .students-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
    <div class="logo-container">
        <img src="../assets/dyci_logo.svg" alt="DYCI Logo" class="logo">
        <div class="logo-text">
            <h2>DYCI CampusConnect</h2>
            <p>E-Clearance System</p>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="active"><a href="eclearance.php"><i class="fas fa-file-alt icon"></i> Student Clearance</a></li>
        <li><a href="profile.php"><i class="fas fa-user-circle icon"></i> Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
    </div>

<div class="container">
        <div class="header">
            <h1>E-Clearance Management</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="program">Program</label>
                    <select name="program" id="program">
                        <option value="">All Programs</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?php echo $program['ProgramCode']; ?>" <?php echo ($_GET['program'] ?? '') === $program['ProgramCode'] ? 'selected' : ''; ?>>
                                <?php echo $program['ProgramTitle']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="section">Section</label>
                    <select name="section" id="section">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['SectionCode']; ?>" <?php echo ($_GET['section'] ?? '') === $section['SectionCode'] ? 'selected' : ''; ?>>
                                <?php echo $section['SectionTitle']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="level">Year Level</label>
                    <select name="level" id="level">
                        <option value="">All Levels</option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?php echo $level['LevelID']; ?>" <?php echo ($_GET['level'] ?? '') == $level['LevelID'] ? 'selected' : ''; ?>>
                                <?php echo $level['LevelName']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="clearance_status">Clearance Status</label>
                    <select name="clearance_status" id="clearance_status">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo (($_GET['clearance_status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Cleared" <?php echo (($_GET['clearance_status'] ?? '') === 'Cleared') ? 'selected' : ''; ?>>Cleared</option>
                        <option value="Pending Approve" <?php echo (($_GET['clearance_status'] ?? '') === 'Pending Approve') ? 'selected' : ''; ?>>Pending Approve</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" placeholder="Search by name or ID" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="eclearance.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="button" class="btn btn-primary" onclick="openBulkClearanceModal()">
                        <i class="fas fa-upload"></i> Bulk Clearance
                    </button>
                </div>
            </form>
        </div>

        <div class="students-table">
            <table>
        <thead>
            <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Section</th>
                        <th>Nature of Accountability</th>
                        <th>Approved By</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
            </tr>
        </thead>
        <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['studentNo']); ?></td>
                            <td><?php echo htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($student['ProgramCode']); ?></td>
                            <td><?php echo htmlspecialchars($student['SectionCode']); ?></td>
                            <td>
                                <?php 
                                    if ($student['status'] === 'Approved') {
                                        echo 'Cleared';
                                    } else {
                                        echo htmlspecialchars($student['comments'] ?? ($student['student_requirement_description'] ?? ($current_requirement_details['description'] ?? 'No outstanding requirements')));
                                    }
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($student['approved_by'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                                    <?php echo $student['status']; ?>
                                </span>
                </td>
                            <td>
                                <?php 
                                if ($student['date_approved']) {
                                    echo date('M d, Y h:i A', strtotime($student['date_approved']));
                                } else {
                                    echo 'Not updated';
                                }
                                ?>
                </td>
                            <td>
                                <button class="action-btn btn-primary" onclick="openUpdateModal('<?php echo $student['studentNo']; ?>', '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['comments'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button class="action-btn btn-secondary" onclick="openDetailsModal('<?php echo $student['studentNo']; ?>')">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUpdateModal()">&times;</span>
            <h2>Update Clearance Status</h2>
            <form method="POST" action="">
                <input type="hidden" name="student_no" id="modal_student_no">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending Approve">Pending Approve</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea name="comments" id="comments" rows="3" placeholder="Add any comments or notes"></textarea>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Clearance Modal -->
    <div id="bulkClearanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBulkClearanceModal()">&times;</span>
            <h2>Bulk Clearance via CSV</h2>
            <form action="bulk_clearance.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Upload CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <small>Expected CSV format: studentNo,status,comments</small>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="upload_csv" class="btn btn-primary">
                        <i class="fas fa-cloud-upload-alt"></i> Upload and Clear
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeBulkClearanceModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clearance Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <h2>Student Clearance Details</h2>
            <div id="student_details_content">
                <!-- Details will be loaded here via AJAX -->
                <p><strong>Student Name:</strong> <span id="detail_student_name"></span></p>
                <p><strong>Student ID:</strong> <span id="detail_student_no"></span></p>
                <h3>Clearance Requirements:</h3>
                <ul id="clearance_requirements_list">
                    <!-- Requirements will be dynamically loaded here -->
                </ul>
            </div>
        </div>
</div>

<script>
        function openUpdateModal(studentNo, currentStatus, comments) {
            document.getElementById('modal_student_no').value = studentNo;
            document.getElementById('status').value = currentStatus;
            document.getElementById('comments').value = comments;
            document.getElementById('updateModal').style.display = 'block';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        function openBulkClearanceModal() {
            document.getElementById('bulkClearanceModal').style.display = 'block';
        }

        function closeBulkClearanceModal() {
            document.getElementById('bulkClearanceModal').style.display = 'none';
        }

        function openDetailsModal(studentNo) {
            document.getElementById('detail_student_no').textContent = studentNo;
            document.getElementById('clearance_requirements_list').innerHTML = ''; // Clear previous data

            // Fetch student's full name
            fetch('get_student_name.php?student_no=' + studentNo)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detail_student_name').textContent = data.student_name;
                    } else {
                        document.getElementById('detail_student_name').textContent = 'N/A';
                    }
                })
                .catch(error => console.error('Error fetching student name:', error));

            // Fetch clearance details for the student
            fetch('get_clearance_details.php?student_no=' + studentNo)
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('clearance_requirements_list');
                    if (data.success && data.requirements.length > 0) {
                        data.requirements.forEach(req => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <strong>${req.requirement_name}:</strong> 
                                <span class="status-badge status-${req.status.toLowerCase()}">${req.status}</span>
                                ${req.description ? `<p>${req.description}</p>` : ''}
                                ${req.comments ? `<p>Comments: ${req.comments}</p>` : ''}
                                ${req.approved_by ? `<p>Approved By: ${req.approved_by}</p>` : ''}
                                ${req.date_approved ? `<p>Date Approved: ${req.date_approved}</p>` : ''}
                            `;
                            list.appendChild(li);
                        });
                    } else {
                        list.innerHTML = '<li>No clearance requirements found for this student.</li>';
                    }
                })
                .catch(error => console.error('Error fetching clearance details:', error));

            document.getElementById('detailsModal').style.display = 'block';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('updateModal')) {
                closeUpdateModal();
            } else if (event.target == document.getElementById('detailsModal')) {
                closeDetailsModal();
            } else if (event.target == document.getElementById('bulkClearanceModal')) {
                closeBulkClearanceModal();
            }
        }
</script>
</body>
</html>