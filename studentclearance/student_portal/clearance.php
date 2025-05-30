<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

$studentNo = $_SESSION['student_id'];

// Get clearance status with department information
$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, scs.status, scs.updated_at, d.DepartmentName 
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="student-container">
        <nav class="sidebar">
            <div class="logo-container">
                <img src="../dyci_logo.png" alt="College Logo" class="logo">
                <div class="logo-text">
                    <h2>DR. YANGA'S COLLEGES INC.</h2>
                    <p>Student Portal</p>
                </div>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home icon"></i> Dashboard</a></li>
                <li class="active"><a href="clearance.php"><i class="fas fa-file-alt icon"></i> Clearance Status</a></li>
                <li><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-content">
            <header>
                <h1>Clearance Status</h1>
                <div class="date-display"><?php echo date('F j, Y'); ?></div>
            </header>

            <div class="clearance-table">
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
                        <?php while ($row = $clearanceStatus->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                            <td><?php echo htmlspecialchars($row['requirement_name']); ?></td>
                            <td class="status-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </td>
                            <td><?php echo $row['updated_at']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>