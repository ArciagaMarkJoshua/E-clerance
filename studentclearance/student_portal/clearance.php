<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

$studentNo = $_SESSION['student_id'];

// Get clearance status with department information
$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, cr.description as general_description, 
           srd.description as student_description, scs.status, scs.updated_at, 
           d.DepartmentName, scs.approved_by
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    LEFT JOIN student_requirement_descriptions srd ON scs.requirement_id = srd.requirement_id AND scs.studentNo = srd.studentNo
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    WHERE scs.studentNo = ?
");
$clearanceQuery->bind_param("s", $studentNo);
$clearanceQuery->execute();
$clearanceStatus = $clearanceQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Status</title>
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
        }
        @media (max-width: 700px) {
            .sidebar { width: 100px; }
            .container { margin-left: 100px; padding: 5px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
            <li class="active"><a href="clearance.php"><i class="fas fa-clipboard-check icon"></i> Clearance Status</a></li>
            <li><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
            <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="dashboard-header">
            <h1>Clearance Status</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
            </div>
        </div>
        <div class="panel">
            <h2>My Clearance Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Requirement</th>
                        <th>General Description</th>
                        <th>Student Description</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody id="clearance-body">
                    <!-- Data will be loaded here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    <script>
    function fetchClearance() {
        fetch('fetch_clearance.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('clearance-body');
                tbody.innerHTML = '';
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.DepartmentName}</td>
                        <td>${row.requirement_name}</td>
                        <td>${row.general_description}</td>
                        <td>${row.student_description || 'No specific requirements'}</td>
                        <td class="status-${row.status.toLowerCase()}">${row.status}</td>
                        <td>${row.approved_by || '-'}</td>
                        <td>${row.updated_at}</td>
                    `;
                    tbody.appendChild(tr);
                });
            });
    }
    setInterval(fetchClearance, 1000);
    window.onload = fetchClearance;
    </script>
</body>
</html>