<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

$studentNo = $_SESSION['student_id'];
$error = '';
$success = '';

// Get student info
$studentQuery = $conn->prepare("SELECT * FROM students WHERE studentNo = ?");
$studentQuery->bind_param("s", $studentNo);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

// Get options for dropdowns
$programOptions = ['BSIT', 'BSCS', 'BSIS'];
$levelOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
$sectionOptions = ['1A', '1B', '1C', '1D','2A', '2B', '2C', '2D','3A', '3B', '3C', '3D','4A', '4B', '4C', '4D'];
$academicYearOptions = ['2023-2024', '2024-2025', '2025-2026'];
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $lastName = trim($_POST['last_name']);
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $programCode = trim($_POST['program_code']);
    $level = trim($_POST['level']);
    $sectionCode = trim($_POST['section_code']);
    $academicYear = trim($_POST['academic_year']);
    $semester = trim($_POST['semester']);
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } 
    // Check if password is being changed
    elseif (!empty($currentPassword)) {
        if (!password_verify($currentPassword, $student['PasswordHash'])) {
            $error = "Current password is incorrect";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords don't match";
        } elseif (strlen($newPassword) < 8) {
            $error = "Password must be at least 8 characters";
        } else {
            // Update with new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = $conn->prepare("UPDATE students SET 
                Username=?, Email=?, LastName=?, FirstName=?, Mname=?, 
                ProgramCode=?, Level=?, SectionCode=?, AcademicYear=?, Semester=?, 
                PasswordHash=? 
                WHERE studentNo=?");
            $updateQuery->bind_param("ssssssssssss", 
                $username, $email, $lastName, $firstName, $middleName,
                $programCode, $level, $sectionCode, $academicYear, $semester,
                $hashedPassword, $studentNo);
        }
    } else {
        // Update without changing password
        $updateQuery = $conn->prepare("UPDATE students SET 
            Username=?, Email=?, LastName=?, FirstName=?, Mname=?, 
            ProgramCode=?, Level=?, SectionCode=?, AcademicYear=?, Semester=? 
            WHERE studentNo=?");
        $updateQuery->bind_param("sssssssssss", 
            $username, $email, $lastName, $firstName, $middleName,
            $programCode, $level, $sectionCode, $academicYear, $semester,
            $studentNo);
    }

    if (empty($error)) {
        if ($updateQuery->execute()) {
            $success = "Profile updated successfully";
            // Refresh student data
            $studentQuery->execute();
            $student = $studentQuery->get_result()->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
                <li><a href="clearance.php"><i class="fas fa-file-alt icon"></i> Clearance Status</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-content">
            <header>
                <h1>My Profile</h1>
                <div class="date-display"><?php echo date('F j, Y'); ?></div>
            </header>

            <div class="profile-form">
                <?php if (!empty($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" name="student_no" value="<?php echo htmlspecialchars($student['studentNo']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($student['Username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['Email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['LastName']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['FirstName']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($student['Mname']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Program Code</label>
                        <select name="program_code" required>
                            <?php foreach ($programOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($student['ProgramCode'] == $option) ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Level</label>
                        <select name="level" required>
                            <?php foreach ($levelOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($student['Level'] == $option) ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Section Code</label>
                        <select name="section_code">
                            <?php foreach ($sectionOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($student['SectionCode'] == $option) ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Academic Year</label>
                        <select name="academic_year" required>
                            <?php foreach ($academicYearOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($student['AcademicYear'] == $option) ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <?php foreach ($semesterOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($student['Semester'] == $option) ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Password (required to change password)</label>
                        <input type="password" name="current_password">
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password">
                    </div>
                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>