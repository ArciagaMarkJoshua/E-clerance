<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['staff_id']) || $_SESSION['account_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Handle request approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['notes'] ?? '');
    
    if ($action === 'approve') {
        // Get request details
        $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if ($request) {
            // Check if student number already exists
            $stmt = $conn->prepare("SELECT studentNo FROM students WHERE studentNo = ?");
            $stmt->bind_param("s", $request['studentNo']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Error: Student number already exists in the system.";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into students table
                    $stmt = $conn->prepare("INSERT INTO students (studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'STU', 1)");
                    $stmt->bind_param("ssssssssssss", 
                        $request['studentNo'],
                        $request['studentNo'], // Username is same as student number
                        $request['email'],
                        $request['password_hash'], // Use the provided password hash
                        $request['lastName'],
                        $request['firstName'],
                        $request['middleName'],
                        $request['programCode'],
                        $request['level'],
                        $request['sectionCode'],
                        $request['academicYear'],
                        $request['semester']
                    );
                    $stmt->execute();
                    
                    // Update request status
                    $stmt = $conn->prepare("UPDATE registration_requests SET status = 'Approved', processed_date = NOW(), processed_by = ?, notes = ? WHERE request_id = ?");
                    $stmt->bind_param("isi", $_SESSION['staff_id'], $notes, $request_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    $success = "Request approved successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error processing request: " . $e->getMessage();
                }
            }
        } else {
            $error = "Error: Request not found.";
        }
    } elseif ($action === 'reject') {
        if (empty($notes)) {
            $error = "Error: Please provide a reason for rejection.";
        } else {
            $stmt = $conn->prepare("UPDATE registration_requests SET status = 'Rejected', processed_date = NOW(), processed_by = ?, notes = ? WHERE request_id = ?");
            $stmt->bind_param("isi", $_SESSION['staff_id'], $notes, $request_id);
            
            if ($stmt->execute()) {
                $success = "Request rejected successfully.";
            } else {
                $error = "Error rejecting request: " . $conn->error;
            }
        }
    }
}

// Fetch all pending requests
$stmt = $conn->prepare("
    SELECT r.*, s.FirstName as processed_by_name, s.LastName as processed_by_lastname 
    FROM registration_requests r 
    LEFT JOIN staff s ON r.processed_by = s.StaffID 
    ORDER BY r.request_date DESC
");
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - DYCI Clearance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Registration Requests</h1>
            </header>

            <div class="alert-container">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Success!</h4>
                            <p><?php echo $success; ?></p>
                        </div>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Error!</h4>
                            <p><?php echo $error; ?></p>
                        </div>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="requests-table">
                <table>
                    <thead>
                        <tr>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Year & Section</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['studentNo']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($request['lastName'] . ', ' . $request['firstName']);
                                    if (!empty($request['middleName'])) {
                                        echo ' ' . htmlspecialchars($request['middleName']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['programCode']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($request['level'] . ' - ' . $request['sectionCode']);
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo $request['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($request['processed_by_name']) {
                                        echo htmlspecialchars($request['processed_by_name'] . ' ' . $request['processed_by_lastname']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <div class="action-buttons">
                                            <button class="btn-approve" onclick="showActionModal(<?php echo $request['request_id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn-reject" onclick="showActionModal(<?php echo $request['request_id']; ?>, 'reject')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-view" onclick="showNotes(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['notes']); ?>')">
                                            <i class="fas fa-eye"></i> View Notes
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

    <!-- Action Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Process Request</h2>
            <form method="POST" action="">
                <input type="hidden" name="request_id" id="requestId">
                <input type="hidden" name="action" id="actionType">
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Enter any notes or comments..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-confirm" id="confirmButton">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showActionModal(requestId, action) {
            const modal = document.getElementById('actionModal');
            const modalTitle = document.getElementById('modalTitle');
            const confirmButton = document.getElementById('confirmButton');
            
            document.getElementById('requestId').value = requestId;
            document.getElementById('actionType').value = action;
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Request';
                confirmButton.textContent = 'Approve';
                confirmButton.className = 'btn-confirm btn-approve';
            } else {
                modalTitle.textContent = 'Reject Request';
                confirmButton.textContent = 'Reject';
                confirmButton.className = 'btn-confirm btn-reject';
            }
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        function showNotes(requestId, notes) {
            alert('Notes: ' + (notes || 'No notes available'));
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('actionModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 