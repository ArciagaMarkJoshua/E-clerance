<?php
session_start();
include 'db_connect.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .student-selector {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-selector select {
            padding: 8px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            max-width: 400px;
        }
        .clearance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .clearance-table th, .clearance-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .clearance-table th {
            background-color: #f2f2f2;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .student-list tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .btn-pending {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-approved {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-row label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
    </style>
    <script>
        function selectStudent(studentNo) {
            window.location.href = "eclearance.php?studentNo=" + studentNo;
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
        <li><a href="dashboard.php"><i class="icon-home"></i> Home</a></li>
        <li><a href="staff_management.php"><i class="icon-users"></i> Staff Management</a></li>
        <li class="active"><a href="eclearance.php"><i class="icon-doc"></i> E-Clearance</a></li>
        <li><a href="program_section.php"><i class="icon-grid"></i> Program & Section</a></li>
        <li><a href="ay_semester.php"><i class="icon-calendar"></i> AY & Semester</a></li>
        <li><a href="student_management.php"><i class="icon-user"></i> Student Management</a></li>
        <li class="logout"><a href="logout.php"><i class="icon-logout"></i> Logout</a></li>
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

    <!-- Student Account Information -->
    <div class="student-info">
        <h3>Student Account Information</h3>
        <div class="info-row">
            <label>Student No:</label>
            <span><?= htmlspecialchars($student['studentNo']) ?></span>
        </div>
        <div class="info-row">
            <label>Name:</label>
            <span><?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['Mname']) ?></span>
        </div>
        <div class="info-row">
            <label>Program:</label>
            <span><?= htmlspecialchars($student['ProgramCode']) ?></span>
        </div>
        <div class="info-row">
            <label>Level:</label>
            <span><?= htmlspecialchars($student['Level']) ?></span>
        </div>
        <div class="info-row">
            <label>Section:</label>
            <span><?= htmlspecialchars($student['SectionCode']) ?></span>
        </div>
        <div class="info-row">
            <label>Email:</label>
            <span><?= htmlspecialchars($student['Email']) ?></span>
        </div>
    </div>

    <!-- Clearance Status Table -->
    <div class="clearance-status">
        <h3>Clearance Status</h3>
        <table class="clearance-table">
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
                ?>
                <tr>
                    <td><?= htmlspecialchars($requirement['DepartmentName']) ?></td>
                    <td><?= htmlspecialchars($requirement['requirement_name']) ?></td>
                    <td><?= htmlspecialchars($requirement['description']) ?></td>
                    <td class="status-<?= strtolower($status['status']) ?>">
                        <?= $status['status'] ?>
                    </td>
                    <td><?= htmlspecialchars($staff_name) ?></td>
                    <td><?= $status['updated_at'] ?></td>
                    <td>
                        <form method="POST" action="update_clearance.php">
                            <input type="hidden" name="studentNo" value="<?= $student['studentNo'] ?>">
                            <input type="hidden" name="requirement_id" value="<?= $req_id ?>">
                            <button type="submit" name="status" value="Pending" class="btn-pending">Mark Pending</button>
                            <button type="submit" name="status" value="Approved" class="btn-approved">Approve</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Table of Students -->
    <div class="student-list">
        <h3>Student List</h3>
        <?php 
        // Re-fetch students for the table
        $table_query = "SELECT CtrlNo, studentNo, LastName, FirstName, Mname, ProgramCode, Level, SectionCode 
                       FROM students ORDER BY LastName, FirstName";
        $table_result = $conn->query($table_query);
        ?>
        <table class="student-table">
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
</div>
</body>
</html>