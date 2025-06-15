<?php
session_start();
include '../includes/db_connect.php'; // Database connection

// Reactivate Student
if (isset($_POST['reactivate_student'])) {
    $registration_no_reactivate = $conn->real_escape_string($_POST['registration_no_for_action']); // Use RegistrationNo
    
    try {
        $stmt = $conn->prepare("UPDATE students SET IsActive = 1 WHERE RegistrationNo = ?");
        $stmt->bind_param("s", $registration_no_reactivate);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Student account reactivated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error: Student not found or already active.";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        header("Location: archived_students.php");
        exit();
    } catch (Exception $e) {
        error_log("Reactivate student error: " . $e->getMessage());
        $_SESSION['message'] = "Error reactivating student: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: archived_students.php");
        exit();
    }
}

// Fetch archived student data
$archived_student_query = "SELECT s.*, p.ProgramTitle, l.LevelName 
                          FROM students s 
                          JOIN programs p ON s.ProgramCode = p.ProgramCode 
                          JOIN levels l ON s.Level = l.LevelID
                          WHERE s.IsActive = 0
                          ORDER BY s.RegistrationNo DESC";
$archived_student_result = $conn->query($archived_student_query);

// Display message from session if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Archived Students</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #343079;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #343079;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .reactivate-btn {
            background-color: #28a745;
            color: white;
        }
        .reactivate-btn:hover {
            background-color: #218838;
        }
        .back-btn {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="student_management.php" class="back-btn">← Back to Student Management</a>
        <h1>Archived Students</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Registration No.</th>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Section</th>
                    <th>Level</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $archived_student_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['RegistrationNo']); ?></td>
                        <td><?php echo htmlspecialchars($row['studentNo']); ?></td>
                        <td><?php echo htmlspecialchars($row['LastName'] . ', ' . $row['FirstName'] . ' ' . $row['Mname']); ?></td>
                        <td><?php echo htmlspecialchars($row['ProgramTitle']); ?></td>
                        <td><?php echo htmlspecialchars($row['SectionCode']); ?></td>
                        <td><?php echo htmlspecialchars($row['LevelName']); ?></td>
                        <td><?php echo htmlspecialchars($row['AcademicYear']); ?></td>
                        <td><?php echo htmlspecialchars($row['Semester']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="registration_no_for_action" value="<?php echo $row['RegistrationNo']; ?>">
                                <button type="submit" name="reactivate_student" class="action-btn reactivate-btn" onclick="return confirm('Are you sure you want to reactivate this student?');">
                                    Reactivate
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
$conn->close();
?> 