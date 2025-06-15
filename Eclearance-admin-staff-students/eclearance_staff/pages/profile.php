<?php
session_start();
include '../includes/db_connect.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$success = '';
$error = '';

// Fetch staff information
$staff_query = "SELECT Username, Email, FirstName, LastName, Department FROM staff WHERE StaffID = ?";
$stmt = $conn->prepare($staff_query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff_info = $result->fetch_assoc();

if (!$staff_info) {
    // Staff not found, redirect to login
    header("Location: login.php");
    exit();
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $department = trim($_POST['department']);

    // Basic validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($department)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Update staff information
        $update_query = "UPDATE staff SET Username = ?, Email = ?, FirstName = ?, LastName = ?, Department = ? WHERE StaffID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $username, $email, $first_name, $last_name, $department, $staff_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Update session variables
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['department'] = $department;
            // Re-fetch updated info
            $stmt->execute(); // Re-execute to get updated info for display
            $staff_info = $result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
}

// Handle form submission for password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current hashed password
    $pass_query = "SELECT PasswordHash FROM staff WHERE StaffID = ?";
    $stmt = $conn->prepare($pass_query);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $pass_result = $stmt->get_result();
    $pass_info = $pass_result->fetch_assoc();

    if (!password_verify($current_password, $pass_info['PasswordHash'])) {
        $error = "Current password is incorrect.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = "New password and confirm password are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pass_query = "UPDATE staff SET PasswordHash = ? WHERE StaffID = ?";
        $stmt = $conn->prepare($update_pass_query);
        $stmt->bind_param("si", $hashed_password, $staff_id);

        if ($stmt->execute()) {
            $success = "Password updated successfully!";
        } else {
            $error = "Error updating password: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - E-Clearance System</title>
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #343079;
            margin: 0;
        }
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
}

.user-info i {
    font-size: 18px;
}

.department {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 5px 10px;
    border-radius: 4px;
}

.logout-btn {
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Alert styles */
.alert {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Filter styles */
.filter-section {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #343079;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Table styles */
.students-table {
    width: 100%;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.students-table table {
    width: 100%;
    border-collapse: collapse;
}

.students-table th,
.students-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.students-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #555;
}

.students-table tr:hover {
    background-color: #f8f9fa;
}

/* Status badge styles */
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-approved {
    background-color: #d4edda;
    color: #155724;
}

/* Action button styles */
.action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.action-btn:hover {
    opacity: 0.9;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group select,
.form-group textarea,
.form-group input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.submit-btn {
    background-color: #343079;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background-color: #2a2861;
}

/* Responsive styles */
@media (max-width: 768px) {
    .sidebar {
        width: 0;
        padding: 0;
    }

    .container {
        margin-left: 0;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .students-table {
        overflow-x: auto;
    }
}

/* Profile card styles */
.profile-card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 20px;
}

.sidebar-logo {
    width: 50px;
    height: 50px;
    margin-right: 15px;
}

.sidebar-title {
    font-size: 16px;
    font-weight: 600;
    color: white;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 15px 25px;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 15px;
}

.sidebar-menu li a:hover {
    background-color: rgba(255,255,255,0.1);
    padding-left: 30px;
}

.sidebar-menu li.active a {
    background-color: rgba(255,255,255,0.2);
}

.sidebar-menu li.logout {
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.sidebar-menu .icon {
    margin-right: 15px;
    font-size: 18px;
    width: 20px;
    text-align: center;
}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-container">
        <img src="../assets/dyci_logo.svg" alt="DYCI Logo" class="logo">
        <div class="logo-text">
            <h2>DYCI CampusConnect</h2>
            <p>E-Clearance System</p>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li <?php echo ($current_page === 'eclearance.php') ? 'class="active"' : ''; ?>><a href="eclearance.php"><i class="fas fa-file-alt icon"></i> Student Clearance</a></li>
        <li <?php echo ($current_page === 'profile.php') ? 'class="active"' : ''; ?>><a href="profile.php"><i class="fas fa-user-circle icon"></i> Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
    </div>

    <div class="container">
        <div class="header">
            <h1>Staff Profile</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['department']); ?>)</span>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <h2>Personal Information</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($staff_info['Username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff_info['Email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($staff_info['FirstName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($staff_info['LastName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($staff_info['Department']); ?>" required>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                </div>
            </form>
        </div>

        <div class="profile-card" style="margin-top: 20px;">
            <h2>Change Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="update_password" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 