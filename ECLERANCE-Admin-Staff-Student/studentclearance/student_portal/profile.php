<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn();

// Error handling function
function handleError($message, $errorType = 'error') {
    $_SESSION[$errorType] = $message;
    return false;
}

// Database error handling
if (!$conn) {
    die(handleError("Database connection failed: " . mysqli_connect_error()));
}

$studentNo = $_SESSION['student_id'];
$error = '';
$success = '';

// Get student info with error handling
try {
    $studentQuery = $conn->prepare("SELECT * FROM students WHERE studentNo = ?");
    if (!$studentQuery) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    
    $studentQuery->bind_param("s", $studentNo);
    if (!$studentQuery->execute()) {
        throw new Exception("Query execution failed: " . $studentQuery->error);
    }
    
    $result = $studentQuery->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $studentQuery->error);
    }
    
    $student = $result->fetch_assoc();
    if (!$student) {
        throw new Exception("Student not found");
    }
} catch (Exception $e) {
    handleError("Error retrieving student information: " . $e->getMessage());
    // Redirect to dashboard or show error page
    header("Location: dashboard.php");
    exit();
}

// Get options for dropdowns
$programOptions = ['BSIT', 'BSCS', 'BSIS'];
$levelOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
$sectionOptions = ['1a', '1b', '1c', '1d', '2a', '2b', '2c', '2d', '3a', '3b', '3c', '3d', '4a', '4b', '4c', '4d', '5a', '5b', '5c', '5d'];
$academicYearOptions = ['2023-2024', '2024-2025', '2025-2026'];
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Input validation
        $requiredFields = ['username', 'email', 'last_name', 'first_name', 'program_code', 'level', 'academic_year', 'semester'];
        foreach ($requiredFields as $field) {
            if (empty(trim($_POST[$field]))) {
                throw new Exception("$field is required");
            }
        }

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
            throw new Exception("Invalid email format");
        }

        // Check if username already exists (excluding current user)
        $checkUsername = $conn->prepare("SELECT studentNo FROM students WHERE Username = ? AND studentNo != ?");
        if (!$checkUsername) {
            throw new Exception("Error checking username: " . $conn->error);
        }
        $checkUsername->bind_param("ss", $username, $studentNo);
        $checkUsername->execute();
        if ($checkUsername->get_result()->num_rows > 0) {
            throw new Exception("Username already exists");
        }

        // Check if email already exists (excluding current user)
        $checkEmail = $conn->prepare("SELECT studentNo FROM students WHERE Email = ? AND studentNo != ?");
        if (!$checkEmail) {
            throw new Exception("Error checking email: " . $conn->error);
        }
        $checkEmail->bind_param("ss", $email, $studentNo);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        // Password validation
        if (!empty($currentPassword)) {
            if (!password_verify($currentPassword, $student['PasswordHash'])) {
                throw new Exception("Current password is incorrect");
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords don't match");
            }
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/", $newPassword)) {
                throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number");
            }
        }

        // Update profile
        if (!empty($currentPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = $conn->prepare("UPDATE students SET 
                Username=?, Email=?, LastName=?, FirstName=?, Mname=?, 
                ProgramCode=?, Level=?, SectionCode=?, AcademicYear=?, Semester=?, 
                PasswordHash=? 
                WHERE studentNo=?");
            if (!$updateQuery) {
                throw new Exception("Error preparing update query: " . $conn->error);
            }
            $updateQuery->bind_param("ssssssssssss", 
                $username, $email, $lastName, $firstName, $middleName,
                $programCode, $level, $sectionCode, $academicYear, $semester,
                $hashedPassword, $studentNo);
        } else {
            $updateQuery = $conn->prepare("UPDATE students SET 
                Username=?, Email=?, LastName=?, FirstName=?, Mname=?, 
                ProgramCode=?, Level=?, SectionCode=?, AcademicYear=?, Semester=? 
                WHERE studentNo=?");
            if (!$updateQuery) {
                throw new Exception("Error preparing update query: " . $conn->error);
            }
            $updateQuery->bind_param("sssssssssss", 
                $username, $email, $lastName, $firstName, $middleName,
                $programCode, $level, $sectionCode, $academicYear, $semester,
                $studentNo);
        }

        if (!$updateQuery->execute()) {
            throw new Exception("Error updating profile: " . $updateQuery->error);
        }

        $success = "Profile updated successfully";
        // Refresh student data
        $studentQuery->execute();
        $student = $studentQuery->get_result()->fetch_assoc();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            padding: 20px;
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
            margin-bottom: 20px;
            padding: 0 20px;
        }
        .dashboard-header h1 {
            color: #343079;
            margin: 0;
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #343079;
            font-weight: 600;
        }
        .user-info i {
            font-size: 1.2rem;
        }
        .panel {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 0 20px;
            max-width: 1200px;
            width: 100%;
        }
        .panel h2 {
            margin-top: 0;
            color: #343079;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 18px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #343079;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input,
        .form-group select {
            width: 80%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            background: #f8f9fa;
            transition: border 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #343079;
            outline: none;
        }
        .btn-save {
            background-color: #343079;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 20px;
        }
        .btn-save:hover {
            background-color: #23205a;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
        }
        .alert.error {
            background: #fbe7e7;
            color: #dc3545;
        }
        .alert.success {
            background: #e7fbe9;
            color: #28a745;
        }
        @media (max-width: 1200px) {
            .panel {
                margin: 0 10px;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 300px;
            }
            .container {
                margin-left: 300px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
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
            <li><a href="clearance.php"><i class="fas fa-clipboard-check icon"></i> Clearance Status</a></li>
            <li class="active"><a href="profile.php"><i class="fas fa-user icon"></i> My Profile</a></li>
            <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="dashboard-header">
            <h1>My Profile</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
            </div>
        </div>
        <div class="panel">
            <?php if (!empty($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Student Number</label>
                        <input type="text" name="student_no" value="<?php echo htmlspecialchars($student['studentNo']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($student['Username']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['Email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['LastName']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['FirstName']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($student['Mname']); ?>">
                    </div>
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-row">
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Current Password (required to change password)</label>
                        <input type="password" name="current_password">
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password">
                    </div>
                </div>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>