<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$eclearance_stats = [
    'total_students' => 0,
    'approved' => 0,
    'pending' => 0
];
$staff_count = 0;
$clearance_by_dept = [];

// Only query database if connection is successful
if ($conn) {
    // Get specific academic year 2024-2025
    $current_ay_query = "SELECT Code, AcademicYear FROM academicyears 
                        WHERE AcademicYear = '2024-2025'
                        LIMIT 1";
    $current_ay_result = $conn->query($current_ay_query);
    $current_ay = $current_ay_result->fetch_assoc();

    if ($current_ay) {
        // 1. E-Clearance statistics for 2024-2025
        $stats_query = "
            SELECT 
                COUNT(DISTINCT s.studentNo) as total_students,
                SUM(CASE WHEN c.status = 'Completed' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) as pending
            FROM students s
            LEFT JOIN clearance c ON s.studentNo = c.studentNo
            WHERE s.AcademicYear = '2024-2025'
        ";
        $stmt = $conn->prepare($stats_query);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $eclearance_stats = $result->fetch_assoc();
            // If all values are null, set them to 0
            $eclearance_stats['total_students'] = $eclearance_stats['total_students'] ?? 0;
            $eclearance_stats['approved'] = $eclearance_stats['approved'] ?? 0;
            $eclearance_stats['pending'] = $eclearance_stats['pending'] ?? 0;
        }
        $stmt->close();

        // 2. Students per department with clearance status for 2024-2025
        $dept_query = "
            SELECT 
                d.DepartmentName,
                COUNT(DISTINCT s.studentNo) as total_students,
                SUM(CASE WHEN scs.status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN scs.status = 'Pending' THEN 1 ELSE 0 END) as pending
            FROM departments d
            LEFT JOIN clearance_requirements cr ON d.DepartmentID = cr.department_id
            LEFT JOIN student_clearance_status scs ON cr.requirement_id = scs.requirement_id
            LEFT JOIN students s ON scs.studentNo = s.studentNo AND s.AcademicYear = '2024-2025'
            GROUP BY d.DepartmentName
            ORDER BY d.DepartmentName
        ";
        $stmt = $conn->prepare($dept_query);
        $stmt->execute();
        $clearance_by_dept = $stmt->get_result();
        $stmt->close();
    }

    // 3. Total active staff count
    $staff_query = "SELECT COUNT(*) as total FROM staff WHERE IsActive = 1";
    $result = $conn->query($staff_query);
    if ($result) {
        $staff_count = $result->fetch_assoc()['total'];
    }

    // Debug information
    $debug_info = [
        'current_ay' => $current_ay,
        'eclearance_stats' => $eclearance_stats,
        'staff_count' => $staff_count
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
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

        .chart-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .panel {
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
            width: 100%;
            background-color: #e9ecef;
            border-radius: 4px;
            height: 14px;
            margin-top: 5px;
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
        <?php echo "<!-- Current Page: " . basename($_SERVER['PHP_SELF']) . " -->"; ?>
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card approved">
                <h3>Approved Clearances</h3>
                <div class="stat-value"><?php echo $eclearance_stats['approved'] ?? 0; ?></div>
                <div class="stat-details">Total approved clearances for <?php echo htmlspecialchars($current_ay['AcademicYear'] ?? 'Current AY'); ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Clearances</h3>
                <div class="stat-value"><?php echo $eclearance_stats['pending'] ?? 0; ?></div>
                <div class="stat-details">Awaiting approval for <?php echo htmlspecialchars($current_ay['AcademicYear'] ?? 'Current AY'); ?></div>
            </div>
            <div class="stat-card total">
                <h3>Total Students</h3>
                <div class="stat-value"><?php echo $eclearance_stats['total_students'] ?? 0; ?></div>
                <div class="stat-details">Registered students for <?php echo htmlspecialchars($current_ay['AcademicYear'] ?? 'Current AY'); ?></div>
            </div>
            <div class="stat-card staff">
                <h3>Active Staff</h3>
                <div class="stat-value"><?php echo $staff_count; ?></div>
                <div class="stat-details">Currently active staff members</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="student_management.php" class="action-btn">
                <i class="fas fa-user-graduate"></i>
                <span>Student Management</span>
            </a>
            <a href="staff_management.php" class="action-btn">
                <i class="fas fa-users"></i>
                <span>Staff Management</span>
            </a>
            <a href="program_section.php" class="action-btn">
                <i class="fas fa-book"></i>
                <span>Program Section</span>
            </a>
            <a href="academicyear.php" class="action-btn">
                <i class="fas fa-calendar-alt"></i>
                <span>Academic Year</span>
            </a>
        </div>

        <div class="chart-container">
            <div class="panel">
                <h2>Clearance Status by Department (<?php echo htmlspecialchars($current_ay['AcademicYear'] ?? 'Current AY'); ?>)</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($clearance_by_dept && $clearance_by_dept->num_rows > 0):
                            while($row = $clearance_by_dept->fetch_assoc()): 
                                $total = $row['total_students'];
                                $approved = $row['approved'];
                                $pending = $row['pending'];
                                $approved_percentage = $total > 0 ? ($approved / $total) * 100 : 0;
                                $pending_percentage = $total > 0 ? ($pending / $total) * 100 : 0;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                                <td><?php echo $total; ?></td>
                                <td><?php echo $approved; ?></td>
                                <td><?php echo $pending; ?></td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar progress-approved" style="width: <?php echo $approved_percentage; ?>%"></div>
                                        <div class="progress-bar progress-pending" style="width: <?php echo $pending_percentage; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="text-center">No data available for current academic year</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>