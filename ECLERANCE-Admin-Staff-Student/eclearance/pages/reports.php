<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$academic_years = [];
$selected_year = isset($_GET['year']) ? $_GET['year'] : '2024-2025';
$department_stats = [];
$monthly_stats = [];
$clearance_trends = [];

// Get all academic years
$years_query = "SELECT AcademicYear FROM academicyears ORDER BY AcademicYear DESC";
$years_result = $conn->query($years_query);
while ($row = $years_result->fetch_assoc()) {
    $academic_years[] = $row['AcademicYear'];
}

// Get department-wise statistics
$dept_stats_query = "
    SELECT 
        d.DepartmentName,
        COUNT(DISTINCT s.studentNo) as total_students,
        SUM(CASE WHEN scs.status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN scs.status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN scs.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM departments d
    LEFT JOIN clearance_requirements cr ON d.DepartmentID = cr.department_id
    LEFT JOIN student_clearance_status scs ON cr.requirement_id = scs.requirement_id
    LEFT JOIN students s ON scs.studentNo = s.studentNo AND s.AcademicYear = ?
    GROUP BY d.DepartmentName
    ORDER BY d.DepartmentName
";
$stmt = $conn->prepare($dept_stats_query);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$department_stats = $stmt->get_result();
$stmt->close();

// Get monthly clearance statistics
$monthly_stats_query = "
    SELECT 
        DATE_FORMAT(scs.updated_at, '%Y-%m') as month,
        COUNT(DISTINCT scs.studentNo) as total_students,
        SUM(CASE WHEN scs.status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN scs.status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN scs.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM student_clearance_status scs
    JOIN students s ON scs.studentNo = s.studentNo
    WHERE s.AcademicYear = ?
    GROUP BY DATE_FORMAT(scs.updated_at, '%Y-%m')
    ORDER BY month
";
$stmt = $conn->prepare($monthly_stats_query);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$monthly_stats = $stmt->get_result();
$stmt->close();

// Get overall clearance statistics
$overall_stats_query = "
    SELECT 
        COUNT(DISTINCT s.studentNo) as total_students,
        COUNT(DISTINCT CASE WHEN c.status = 'Completed' THEN s.studentNo END) as completed_clearances,
        COUNT(DISTINCT CASE WHEN c.status = 'Pending' THEN s.studentNo END) as pending_clearances
    FROM students s
    LEFT JOIN clearance c ON s.studentNo = c.studentNo
    WHERE s.AcademicYear = ?
";

$stmt = $conn->prepare($overall_stats_query);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - E-Clearance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .reports-header h1 {
            color: #343079;
            margin: 0;
        }

        .year-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .year-selector select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-panel {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .report-panel h2 {
            margin-top: 0;
            color: #343079;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .export-btn {
            background-color: #343079;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .export-btn:hover {
            background-color: #2a2660;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #343079;
            margin: 5px 0;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .export-btn {
            background-color: #343079;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background-color: #2a2660;
            transform: translateY(-2px);
        }

        .export-btn i {
            font-size: 16px;
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
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
            <li><a href="eclearance.php"><i class="fas fa-clipboard-check icon"></i> E-Clearance</a></li>
            <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
            <li><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
            <li><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
            <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
            <li><a href="registration_requests.php"><i class="fas fa-user-plus icon"></i> Registration Requests</a></li>
            <li class="active"><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
            <li><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="reports-header">
            <h1>Reports & Analytics</h1>
            <div class="year-selector">
                <label for="academic-year">Academic Year:</label>
                <select id="academic-year" onchange="window.location.href='reports.php?year=' + this.value">
                    <?php foreach ($academic_years as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $year === $selected_year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="stats-summary">
            <div class="stat-item">
                <div class="stat-value"><?php echo $overall_stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $overall_stats['completed_clearances']; ?></div>
                <div class="stat-label">Completed Clearances</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $overall_stats['pending_clearances']; ?></div>
                <div class="stat-label">Pending Clearances</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php 
                    $completion_rate = $overall_stats['total_students'] > 0 
                        ? round(($overall_stats['completed_clearances'] / $overall_stats['total_students']) * 100, 1) 
                        : 0;
                    echo $completion_rate . '%';
                    ?>
                </div>
                <div class="stat-label">Completion Rate</div>
            </div>
        </div>

        <div class="reports-grid">
            <div class="report-panel">
                <h2>Department-wise Clearance Status</h2>
                <div class="chart-container">
                    <canvas id="departmentChart"></canvas>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $department_stats->fetch_assoc()): 
                            $dept_completion_rate = $row['total_students'] > 0 
                                ? round(($row['approved'] / $row['total_students']) * 100, 1) 
                                : 0;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                                <td><?php echo $row['total_students']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['rejected']; ?></td>
                                <td><?php echo $dept_completion_rate . '%'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="export-buttons">
                    <button class="export-btn" onclick="exportTableToCSV('department_stats.csv')">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                    <button class="export-btn" onclick="exportTableToExcel('department_stats.xlsx')">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button class="export-btn" onclick="exportTableToPDF('department_stats.pdf')">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </div>
            </div>

            <div class="report-panel">
                <h2>Monthly Clearance Trends</h2>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $monthly_stats->fetch_assoc()): 
                            $monthly_completion_rate = $row['total_students'] > 0 
                                ? round(($row['approved'] / $row['total_students']) * 100, 1) 
                                : 0;
                        ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                                <td><?php echo $row['total_students']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['rejected']; ?></td>
                                <td><?php echo $monthly_completion_rate . '%'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="export-buttons">
                    <button class="export-btn" onclick="exportTableToCSV('monthly_stats.csv')">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                    <button class="export-btn" onclick="exportTableToExcel('monthly_stats.xlsx')">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button class="export-btn" onclick="exportTableToPDF('monthly_stats.pdf')">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <script>
        // Department Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $department_stats->data_seek(0);
                    $labels = [];
                    $approved = [];
                    $pending = [];
                    $rejected = [];
                    while ($row = $department_stats->fetch_assoc()) {
                        $labels[] = $row['DepartmentName'];
                        $approved[] = $row['approved'];
                        $pending[] = $row['pending'];
                        $rejected[] = $row['rejected'];
                    }
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'Approved',
                    data: <?php echo json_encode($approved); ?>,
                    backgroundColor: '#28a745'
                }, {
                    label: 'Pending',
                    data: <?php echo json_encode($pending); ?>,
                    backgroundColor: '#ffc107'
                }, {
                    label: 'Rejected',
                    data: <?php echo json_encode($rejected); ?>,
                    backgroundColor: '#dc3545'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php 
                    $monthly_stats->data_seek(0);
                    $months = [];
                    $monthly_approved = [];
                    $monthly_pending = [];
                    while ($row = $monthly_stats->fetch_assoc()) {
                        $months[] = date('M Y', strtotime($row['month'] . '-01'));
                        $monthly_approved[] = $row['approved'];
                        $monthly_pending[] = $row['pending'];
                    }
                    echo json_encode($months);
                ?>,
                datasets: [{
                    label: 'Approved',
                    data: <?php echo json_encode($monthly_approved); ?>,
                    borderColor: '#28a745',
                    fill: false
                }, {
                    label: 'Pending',
                    data: <?php echo json_encode($monthly_pending); ?>,
                    borderColor: '#ffc107',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Export to CSV function
        function exportTableToCSV(filename) {
            const table = event.target.closest('.report-panel').querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (const row of rows) {
                const cols = row.querySelectorAll('td,th');
                const rowArray = Array.from(cols).map(col => {
                    let text = col.innerText;
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    return text;
                });
                csv.push(rowArray.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }

        // Export to Excel function
        function exportTableToExcel(filename) {
            const table = event.target.closest('.report-panel').querySelector('table');
            const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
            XLSX.writeFile(wb, filename);
        }

        // Export to PDF function
        function exportTableToPDF(filename) {
            const table = event.target.closest('.report-panel').querySelector('table');
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            const title = event.target.closest('.report-panel').querySelector('h2').innerText;
            doc.setFontSize(16);
            doc.text(title, 14, 15);
            
            // Add table
            doc.autoTable({
                html: table,
                startY: 25,
                theme: 'grid',
                headStyles: { fillColor: [52, 48, 121] },
                styles: { fontSize: 8 },
                margin: { top: 25 }
            });
            
            doc.save(filename);
        }
    </script>
</body>
</html> 