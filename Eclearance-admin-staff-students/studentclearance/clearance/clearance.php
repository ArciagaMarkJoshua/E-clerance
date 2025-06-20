<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['account_type'], ['Admin', 'Staff'])) {
    header("Location: login.php");
    exit();
}

$studentNo = $_GET['studentNo'] ?? '';

// Get student info
$studentQuery = $conn->prepare("SELECT * FROM students WHERE studentNo = ?");
$studentQuery->bind_param("s", $studentNo);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

// Get clearance status with department information
$clearanceQuery = $conn->prepare("
    SELECT cr.requirement_name, cr.description as general_description, 
           srd.description as student_description, scs.status, scs.updated_at, 
           d.DepartmentName, scs.approved_by, scs.requirement_id, scs.StaffID,
           s.FirstName as staff_firstname, s.LastName as staff_lastname
    FROM student_clearance_status scs
    JOIN clearance_requirements cr ON scs.requirement_id = cr.requirement_id
    LEFT JOIN student_requirement_descriptions srd ON scs.requirement_id = srd.requirement_id AND scs.studentNo = srd.studentNo
    JOIN departments d ON cr.requirement_id = d.DepartmentID
    LEFT JOIN staff s ON scs.StaffID = s.StaffID
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
    <title>Student Clearance - DYCI Clearance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Student Clearance</h1>
                <div class="student-info">
                    <h2><?php echo htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']); ?></h2>
                    <p>Student No: <?php echo htmlspecialchars($student['studentNo']); ?></p>
                </div>
            </header>

            <div class="clearance-table">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clearance-body">
                        <?php while ($row = $clearanceStatus->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                                <td><?php echo htmlspecialchars($row['requirement_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['general_description']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_description'] ?? 'No specific requirements'); ?></td>
                                <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status'] === 'Approved' ? 'Cleared' : $row['status']; ?></td>
                                <td>
                                    <?php 
                                    if ($row['status'] === 'Approved') {
                                        if (!empty($row['approved_by'])) {
                                            echo htmlspecialchars($row['approved_by']);
                                        } elseif (!empty($row['staff_firstname']) && !empty($row['staff_lastname'])) {
                                            echo htmlspecialchars($row['staff_firstname'] . ' ' . $row['staff_lastname']);
                                        } else {
                                            echo 'System Administrator';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['updated_at'])); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <button class="btn-approve" onclick="approveClearance(<?php echo $row['requirement_id']; ?>)">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function approveClearance(requirementId) {
            if (confirm('Are you sure you want to approve this requirement?')) {
                fetch('approve_clearance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `requirement_id=${requirementId}&studentNo=<?php echo $studentNo; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Instead of reloading, fetch updated data
                        fetchClearanceData();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the clearance.');
                });
            }
        }

        function fetchClearanceData() {
            fetch('fetch_clearance.php?studentNo=<?php echo $studentNo; ?>')
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
                            <td class="status-${row.status.toLowerCase()}">${row.status === 'Approved' ? 'Cleared' : row.status}</td>
                            <td>${row.status === 'Approved' ? (row.approved_by || (row.staff_firstname && row.staff_lastname ? 
                                `${row.staff_firstname} ${row.staff_lastname}` : 'System Administrator')) : '-'}</td>
                            <td>${new Date(row.updated_at).toLocaleString()}</td>
                            <td>${row.status === 'Pending' ? 
                                `<button class="btn-approve" onclick="approveClearance(${row.requirement_id})">
                                    <i class="fas fa-check"></i> Approve
                                </button>` : ''}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Fetch data every 5 seconds
        setInterval(fetchClearanceData, 5000);
    </script>
</body>
</html> 