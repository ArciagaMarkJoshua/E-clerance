<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

$studentNo = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'];

// Get student info
$studentQuery = $conn->prepare("SELECT * FROM students WHERE studentNo = ?");
$studentQuery->bind_param("s", $studentNo);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

// Get clearance status with department information
$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, scs.status, scs.updated_at, d.DepartmentName 
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    WHERE scs.studentNo = ?
    ORDER BY scs.updated_at DESC
");
$clearanceQuery->bind_param("s", $studentNo);
$clearanceQuery->execute();
$clearanceStatus = $clearanceQuery->get_result();

$totalRequirements = $clearanceStatus->num_rows;
$approved = 0;
$pending = 0;
$clearanceRows = [];
while ($row = $clearanceStatus->fetch_assoc()) {
    $clearanceRows[] = $row;
    if ($row['status'] == 'Approved') {
        $approved++;
    } else {
        $pending++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
            gap: 0.5rem;
            color: #343079;
            font-weight: 600;
        }
        .user-info i {
            font-size: 1.5rem;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
        .panel {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        @media (max-width: 1100px) {
            .container { padding: 10px; }
            .stats-container { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .sidebar { width: 100px; }
            .container { margin-left: 100px; padding: 5px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .stats-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="logo-container">
            <img src="dyci_logo.png" alt="DYCI Logo" class="logo">
            <div class="logo-text">
                <h2>DYCI CampusConnect</h2>
                <p>Student Portal</p>
            </div>
        </div>
        <ul>
            <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
            <li><a href="clearance.php"><i class="fas fa-clipboard-check icon"></i> Clearance Status</a></li>
            <li><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
            <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($studentName); ?></span>
            </div>
        </div>
        <div class="stats-container">
            <div class="stat-card approved">
                <h3>Approved Clearances</h3>
                <div class="stat-value"><?php echo $approved; ?></div>
                <div class="stat-details">Total approved clearances</div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Clearances</h3>
                <div class="stat-value"><?php echo $pending; ?></div>
                <div class="stat-details">Awaiting approval</div>
            </div>
            <div class="stat-card total">
                <h3>Total Requirements</h3>
                <div class="stat-value"><?php echo $totalRequirements; ?></div>
                <div class="stat-details">Requirements to complete</div>
            </div>
        </div>
        <div class="panel">
            <h2>Recent Clearance Updates</h2>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Requirement</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 0; foreach ($clearanceRows as $row) { if ($count >= 5) break; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                        <td><?php echo htmlspecialchars($row['requirement_name']); ?></td>
                        <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></td>
                        <td><?php echo date('M j, g:i a', strtotime($row['updated_at'])); ?></td>
                    </tr>
                    <?php $count++; } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>