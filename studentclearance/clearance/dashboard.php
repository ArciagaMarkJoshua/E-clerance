<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['account_type'], ['Admin', 'Staff'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$account_type = $_SESSION['account_type'];

// Get staff info
$staffQuery = $conn->prepare("SELECT * FROM staff WHERE StaffID = ?");
$staffQuery->bind_param("i", $staff_id);
$staffQuery->execute();
$staff = $staffQuery->get_result()->fetch_assoc();

// Get pending clearance requests
$clearanceQuery = $conn->prepare("
    SELECT scs.*, s.FirstName, s.LastName, cr.requirement_name, d.DepartmentName
    FROM student_clearance_status scs
    JOIN students s ON scs.studentNo = s.studentNo
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    WHERE scs.status = 'Pending'
    ORDER BY scs.updated_at DESC
");
$clearanceQuery->execute();
$pendingClearances = $clearanceQuery->get_result();

// Get recent approvals
$recentApprovalsQuery = $conn->prepare("
    SELECT scs.*, s.FirstName, s.LastName, cr.requirement_name, d.DepartmentName
    FROM student_clearance_status scs
    JOIN students s ON scs.studentNo = s.studentNo
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    WHERE scs.status = 'Approved' AND scs.StaffID = ?
    ORDER BY scs.updated_at DESC
    LIMIT 5
");
$recentApprovalsQuery->bind_param("i", $staff_id);
$recentApprovalsQuery->execute();
$recentApprovals = $recentApprovalsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - DYCI Clearance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($staff_name); ?></span>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <h3>Pending Clearances</h3>
                        <p><?php echo $pendingClearances->num_rows; ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-sections">
                <section class="pending-clearances">
                    <h2>Pending Clearances</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Requirement</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($clearance = $pendingClearances->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($clearance['LastName'] . ', ' . $clearance['FirstName']); ?></td>
                                        <td><?php echo htmlspecialchars($clearance['requirement_name']); ?></td>
                                        <td><?php echo htmlspecialchars($clearance['DepartmentName']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($clearance['updated_at'])); ?></td>
                                        <td>
                                            <a href="clearance.php?studentNo=<?php echo $clearance['studentNo']; ?>" class="btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="recent-approvals">
                    <h2>Recent Approvals</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Requirement</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($approval = $recentApprovals->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($approval['LastName'] . ', ' . $approval['FirstName']); ?></td>
                                        <td><?php echo htmlspecialchars($approval['requirement_name']); ?></td>
                                        <td><?php echo htmlspecialchars($approval['DepartmentName']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($approval['updated_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>
</html> 