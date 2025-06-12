<?php
// Start session
session_start();

// Include database connection
require_once "../includes/db_connect.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    $staff_id = $_SESSION['staff_id'];

    try {
        if ($action === 'approve') {
            // Get request details
            $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();

            // Start transaction
            $conn->autocommit(FALSE);

            // Update request status
            $update_stmt = $conn->prepare("UPDATE registration_requests SET status = 'Approved', processed_date = NOW(), processed_by = ?, notes = ? WHERE request_id = ?");
            $update_stmt->bind_param("isi", $staff_id, $notes, $request_id);
            $update_stmt->execute();

            // Create student record
            $insert_stmt = $conn->prepare("INSERT INTO students (studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Set default values
            $username = $request['studentNo']; // Using student number as username
            $account_type = 'STU';
            $is_active = 1;

            $insert_stmt->bind_param("sssssssssssssi", 
                $request['studentNo'],
                $username,
                $request['email'],
                $request['password_hash'], // Use the provided password hash
                $request['lastName'],
                $request['firstName'],
                $request['middleName'],
                $request['programCode'],
                $request['level'],
                $request['sectionCode'],
                $request['academicYear'],
                $request['semester'],
                $account_type,
                $is_active
            );
            $insert_stmt->execute();

            $conn->commit();
            $conn->autocommit(TRUE);
            $success_message = "Request approved successfully. Student record created.";
        } else if ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE registration_requests SET status = 'Rejected', processed_date = NOW(), processed_by = ?, notes = ? WHERE request_id = ?");
            $stmt->bind_param("isi", $staff_id, $notes, $request_id);
            $stmt->execute();
            $success_message = "Request rejected successfully.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Fetch pending requests
$query = "SELECT r.*, CONCAT(s.LastName, ', ', s.FirstName) as processed_by_name 
          FROM registration_requests r 
          LEFT JOIN staff s ON r.processed_by = s.StaffID 
          WHERE r.status = 'Pending' 
          ORDER BY r.request_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - DYCI E-Clearance</title>
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

        .request-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .request-header h3 {
            margin: 0;
            color: #343079;
            font-size: 18px;
        }

        .request-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-group {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-size: 15px;
        }

        .request-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .modal-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .modal-header h3 {
            margin: 0;
            color: #343079;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #343079;
            box-shadow: 0 0 0 2px rgba(52, 48, 121, 0.15);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #343079;
            margin: 0;
            font-size: 24px;
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
            <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
            <li><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
            <li><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
            <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
            <li class="active"><a href="registration_requests.php"><i class="fas fa-user-plus icon"></i> Registration Requests</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
            <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Registration Requests</h1>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while($request = $result->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-header">
                        <h3>Request from <?php echo htmlspecialchars($request['firstName'] . ' ' . $request['lastName']); ?></h3>
                        <span class="request-date"><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></span>
                    </div>
                    
                    <div class="request-info">
                        <div class="info-group">
                            <div class="info-label">Student Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['studentNo']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['email']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Program</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['programCode']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Level</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['level']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Section</div>
                            <div class="info-value"><?php echo htmlspecialchars($request['sectionCode']); ?></div>
                        </div>
                    </div>

                    <div class="request-actions">
                        <button class="btn btn-approve" onclick="showModal('approve', <?php echo $request['request_id']; ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-reject" onclick="showModal('reject', <?php echo $request['request_id']; ?>)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="request-card">
                <p>No pending registration requests.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Approval Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Registration Request</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="approveRequestId">
                <input type="hidden" name="action" value="approve">
                <div class="form-group">
                    <label for="approveNotes">Notes (Optional)</label>
                    <textarea name="notes" id="approveNotes" placeholder="Add any notes about this approval..."></textarea>
                </div>
                <div class="request-actions">
                    <button type="submit" class="btn btn-approve">
                        <i class="fas fa-check"></i> Confirm Approval
                    </button>
                    <button type="button" class="btn btn-reject" onclick="hideModal('approve')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Registration Request</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="rejectRequestId">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="rejectNotes">Reason for Rejection</label>
                    <textarea name="notes" id="rejectNotes" placeholder="Please provide a reason for rejecting this request..." required></textarea>
                </div>
                <div class="request-actions">
                    <button type="submit" class="btn btn-reject">
                        <i class="fas fa-times"></i> Confirm Rejection
                    </button>
                    <button type="button" class="btn btn-approve" onclick="hideModal('reject')">
                        <i class="fas fa-check"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(action, requestId) {
            const modal = document.getElementById(action + 'Modal');
            const requestIdInput = document.getElementById(action + 'RequestId');
            requestIdInput.value = requestId;
            modal.style.display = 'flex';
        }

        function hideModal(action) {
            const modal = document.getElementById(action + 'Modal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html> 