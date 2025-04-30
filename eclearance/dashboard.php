<?php
session_start();
include 'db_connect.php';

// 1. E-Clearance statistics
$eclearance_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT studentNo) as total_students,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM student_clearance_status
")->fetch_assoc();

// 2. Students per department with clearance status
$current_ay = $conn->query("SELECT MAX(AcademicYear) as current_ay FROM academicyears")->fetch_assoc()['current_ay'];
$clearance_by_dept = $conn->query("
    SELECT 
        d.DepartmentName,
        COUNT(DISTINCT s.studentNo) as total_students,
        SUM(CASE WHEN scs.status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN scs.status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM students s
    JOIN programs p ON s.ProgramCode = p.ProgramCode
    JOIN departments d ON d.DepartmentID = d.DepartmentID
    LEFT JOIN student_clearance_status scs ON s.studentNo = scs.studentNo
    WHERE s.AcademicYear = '$current_ay'
    GROUP BY d.DepartmentName
");

// 3. Total staff count
$staff_count = $conn->query("SELECT COUNT(*) as total FROM staff")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Admin Dashboard</title>
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

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #343079;
            margin: 0;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 16px;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
            color: #343079;
        }

        .stat-card .stat-details {
            font-size: 14px;
            color: #777;
        }

        .stat-card.approved {
            border-left: 4px solid #28a745;
        }

        .stat-card.pending {
            border-left: 4px solid #ffc107;
        }

        .stat-card.total {
            border-left: 4px solid #17a2b8;
        }

        .stat-card.staff {
            border-left: 4px solid #6f42c1;
        }

        .chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .panel {
            width: 1500px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .panel h2 {
            margin-top: 0;
            color: #343079;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .action-btn:hover {
            background-color: #343079;
            color: white;
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            font-size: 12px;
            color: #777;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: #343079;
        }

        .progress-container {
            width: 50%;
            background-color: #e9ecef;
            border-radius: 4px;
            height: 14px;
            margin-top: 1px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
        }

        .progress-approved {
            background-color: #28a745;
        }

        .progress-pending {
            background-color: #ffc107;
        }
    </style>
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
            <li class="active"><a href="dashboard.php"><i class="fas fa-home icon"></i> <span>Dashboard</span></a></li>
            <li><a href="staff_management.php"><i class="fas fa-users icon"></i> <span>Staff Management</span></a></li>
            <li><a href="eclearance.php"><i class="fas fa-file-alt icon"></i> <span>E-Clearance</span></a></li>
            <li><a href="program_section.php"><i class="fas fa-th-large icon"></i> <span>Program & Section</span></a></li>
            <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> <span>Academic Year</span></a></li>
            <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> <span>Student Management</span></a></li>
            <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div class="date-display"><?php echo date('F j, Y'); ?></div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card total">
                <h3>Total Students with E-Clearance</h3>
                <div class="stat-value"><?php echo $eclearance_stats['total_students'] ?? 0; ?></div>
                <div class="stat-details">Current Academic Year</div>
            </div>
            
            <div class="stat-card approved">
                <h3>Approved Clearances</h3>
                <div class="stat-value"><?php echo $eclearance_stats['approved'] ?? 0; ?></div>
                <div class="stat-details">Students cleared</div>
            </div>
            
            <div class="stat-card pending">
                <h3>Pending Clearances</h3>
                <div class="stat-value"><?php echo $eclearance_stats['pending'] ?? 0; ?></div>
                <div class="stat-details">Need attention</div>
            </div>
            
            <div class="stat-card staff">
                <h3>Total Staff</h3>
                <div class="stat-value"><?php echo $staff_count; ?></div>
                <div class="stat-details">Administrators and faculty</div>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="quick-actions">
            <a href="student_management.php" class="action-btn">
                <i class="fas fa-user-plus"></i>
                Add New Student
            </a>
            <a href="staff_management.php" class="action-btn">
                <i class="fas fa-user-tie"></i>
                Manage Staff
            </a>
            <a href="eclearance.php" class="action-btn">
                <i class="fas fa-tasks"></i>
                Process Clearances
            </a>
            <a href="ay_semester.php" class="action-btn">
                <i class="fas fa-calendar-plus"></i>
                Set Academic Year
            </a>
        </div>

        <!-- Main Content Area -->
        <div class="chart-container">
            <div class="panel">
                <h2>Clearance Status by Department (<?php echo $current_ay; ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                     if ($clearance_by_dept && $clearance_by_dept->num_rows > 0) {
                        while ($row = $clearance_by_dept->fetch_assoc()) {
                            $approved = (int)$row['approved'];
                            $pending = (int)$row['pending'];
                            $total = (int)$row['total_students']; // Recalculate total to avoid incorrect % if original is wrong
                    
                            // Calculate percentages safely
                            $approved_percent = $total > 0 ? round(($approved / $total) * 14.2) : 0;
                            $pending_percent = $total > 0 ? round(($pending / $total) * 14.2) : 0;
                               
                               ?>
                                
                                <tr>
                                    <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><?php echo $approved; ?> (<?php echo $approved_percent; ?>%)</td>
                                    <td><?php echo $pending; ?> (<?php echo $pending_percent; ?>%)</td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar progress-approved" style="width: <?php echo $approved_percent; ?>%"></div>
                                            <div class="progress-bar progress-pending" style="width: <?php echo $pending_percent; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="5">No data available</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>