<?php
session_start();
include '../includes/db_connect.php';

// To this (to match your database values):
$is_admin = ($_SESSION['role'] ?? '') === 'Admin' || ($_SESSION['account_type'] ?? '') === 'Admin';

// Store selected student in session when available
if (isset($_GET['studentNo'])) {
    $_SESSION['current_student'] = $_GET['studentNo'];
}

// Fetch student details - use session if no studentNo in URL
$student_no = $_GET['studentNo'] ?? $_SESSION['current_student'] ?? "";

if(empty($student_no)) {
    // Try to get first student if none selected
    $first_student = $conn->query("SELECT studentNo FROM students ORDER BY studentNo LIMIT 1");
    if ($first_student->num_rows > 0) {
        $student_no = $first_student->fetch_assoc()['studentNo'];
        $_SESSION['current_student'] = $student_no;
        header("Location: eclearance.php?studentNo=".$student_no);
        exit();
    } else {
        die("No students found in database.");
    }
}

$student_query = "SELECT * FROM students WHERE studentNo = '$student_no'";
$student_result = $conn->query($student_query);

if ($student_result->num_rows > 0) {
    $student = $student_result->fetch_assoc();
} else {
    unset($_SESSION['current_student']); // Clear invalid student from session
    die("Student not found.");
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

// Fetch all staff
$staff_query = "SELECT * FROM staff";
$staff_result = $conn->query($staff_query);
$staff_members = [];
while ($staff = $staff_result->fetch_assoc()) {
    $staff_members[$staff['StaffID']] = $staff;
}

// Fetch clearance status
$clearance_query = "SELECT * FROM student_clearance_status WHERE studentNo = '$student_no'";
$clearance_result = $conn->query($clearance_query);
$clearance_status = [];
while ($status = $clearance_result->fetch_assoc()) {
    $clearance_status[$status['requirement_id']] = $status;
}

// Handle description update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description'])) {
    if (!isset($_SESSION['staff_id'])) {
        die("Error: Unauthorized access");
    }
    
    $requirement_id = intval($_POST['requirement_id']);
    $new_description = $conn->real_escape_string($_POST['description']);
    
    // Check if user has permission (admin or staff from this department)
    $is_department_staff = ($requirement_departments[$requirement_id] == $user_department);
    
    if ($is_admin || $is_department_staff) {
        $update_query = "UPDATE clearance_requirements SET description = '$new_description' 
                         WHERE requirement_id = $requirement_id";
        if ($conn->query($update_query)) {
            // Refresh requirements data
            $requirements[$requirement_id]['description'] = $new_description;
        } else {
            die("Error updating description: " . $conn->error);
        }
    } else {
        die("Error: You don't have permission to update this description");
    }
}
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
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="logo-container">
        <img src="../assets/dyci_logo.svg" alt="College Logo" class="logo">
        <div class="logo-text">
            <h2>DR. YANGA'S COLLEGES INC.</h2>
            <p>Administrator</p>
        </div>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home icon"></i> <span>Home</span></a></li>
        <li><a href="staff_management.php"><i class="fas fa-users icon"></i> <span>Staff Management</span></a></li>
        <li class="active"><a href="eclearance.php"><i class="fas fa-file-alt icon"></i> <span>E-Clearance</span></a></li>
        <li><a href="program_section.php"><i class="fas fa-th-large icon"></i> <span>Program & Section</span></a></li>
        <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> <span>Academic Year</span></a></li>
        <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> <span>Student Management</span></a></li>
        <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> <span>Logout</span></a></li>
    </ul>
</nav>

<div class="container">
    <!-- Student Selector Dropdown -->
    <div class="student-selector">
        <form method="GET" action="eclearance.php">
            <select name="studentNo" onchange="this.form.submit()">
                <option value="">-- Select a Student --</option>
                <?php while ($row = $students_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['studentNo']) ?>"
                        <?= ($row['studentNo'] == $student_no) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['LastName'] . ', ' . $row['FirstName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

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
                        <?= htmlspecialchars($requirement['description']) ?>
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
                                <textarea name="description"><?= htmlspecialchars($requirement['description']) ?></textarea>
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


    <!-- Table of Students -->
    <h3 class="section-header">Student List</h3>
    <?php 
    // Re-fetch students for the table
    $table_query = "SELECT CtrlNo, studentNo, LastName, FirstName, Mname, ProgramCode, Level, SectionCode 
                   FROM students ORDER BY LastName, FirstName";
    $table_result = $conn->query($table_query);
    ?>
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
            <?php while ($row = $table_result->fetch_assoc()): 
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
                <td><?= $row['ProgramCode'] ?></td>
                <td><?= $row['Level'] ?></td>
                <td><?= $row['SectionCode'] ?></td>
                <td class="status-<?= strtolower($overall_status) ?>"><?= $overall_status ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<<script>
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