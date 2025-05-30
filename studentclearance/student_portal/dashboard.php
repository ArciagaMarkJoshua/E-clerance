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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
                <li class="active"><a href="dashboard.php"><i class="fas fa-home icon"></i> Dashboard</a></li>
                <li><a href="clearance.php"><i class="fas fa-file-alt icon"></i> Clearance Status</a></li>
                <li><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-content">
            <header>
                <h1>Welcome, <?php echo htmlspecialchars($studentName); ?></h1>
                <div class="date-display"><?php echo date('F j, Y'); ?></div>
            </header>

            <div class="dashboard-cards">
                <div class="card">
                    <h3>Student Information</h3>
                    <div class="info-item">
                        <span>Student No:</span>
                        <span><?php echo htmlspecialchars($student['studentNo']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Program:</span>
                        <span><?php echo htmlspecialchars($student['ProgramCode']); ?></span>
                    </div>
                    <div class="info-item">
                        <span>Level:</span>
                        <span><?php echo htmlspecialchars($student['Level']); ?></span>
                    </div>
                </div>

                <div class="card">
                    <h3>Clearance Overview</h3>
                    <?php
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
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo ($totalRequirements > 0) ? round(($approved/$totalRequirements)*100) : 0; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <?php echo $approved; ?> of <?php echo $totalRequirements; ?> requirements approved
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Clearance Updates</h2>
                <?php
                $count = 0;
                foreach ($clearanceRows as $row) {
                    if ($count >= 3) break;
                    echo '<div class="activity-item">';
                    echo '<div class="activity-detail">' . htmlspecialchars($row['DepartmentName'] . ' - ' . $row['requirement_name']) . '</div>';
                    echo '<div class="activity-status status-' . strtolower($row['status']) . '">' . $row['status'] . '</div>';
                    echo '<div class="activity-time">' . date('M j, g:i a', strtotime($row['updated_at'])) . '</div>';
                    echo '</div>';
                    $count++;
                }
                ?>
                <a href="clearance.php" class="view-all">View All Clearance Status</a>
            </div>
        </div>
    </div>
</body>
</html>