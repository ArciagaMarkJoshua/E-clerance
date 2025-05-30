<?php
// Start session
session_start();

// Include database connection
require_once "../includes/db_connect.php";

// Initialize error variable
$error = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Prepare SQL statement to prevent SQL Injection
        $stmt = $conn->prepare("SELECT CtrlNo, StaffID, Username, PasswordHash, LastName, FirstName, AccountType, Department FROM staff WHERE Email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($ctrlNo, $staffID, $username, $hashed_password, $lastName, $firstName, $accountType, $department);
            $stmt->fetch();
            
            // Verify password (assuming passwords are hashed)
            if (password_verify($password, $hashed_password)) {
                // Set session variables
                $_SESSION['staff_id'] = $staffID;
                $_SESSION['username'] = $username;
                $_SESSION['account_type'] = $accountType;
                $_SESSION['department'] = $department;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;

                // Redirect based on account type
                if ($accountType == 'Admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: eclearance.php");
                }
                exit();
            } else {
                $error = "⚠ Invalid email or password.";
            }
        } else {
            $error = "⚠ Account not found. Please contact support.";
        }
        $stmt->close();
    } else {
        $error = "⚠ Please fill in all fields.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DYCI E-Clearance Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/dyci_logo.svg" alt="DYCI Logo" class="login-logo">
                <h1 class="login-title">Welcome to DYCI CampusConnect!</h1>
                <p class="login-subtitle">E-Clearance System</p>
            </div>
            <div class="login-form">
                <h2>Administrator Login</h2>
                <?php if (!empty($error)) echo "<p class='login-error'>$error</p>"; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="email" name="email" class="login-input" placeholder="E-mail" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" class="login-input" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="login-button">LOG IN</button>
                    </div>
                    <p class="login-footer"><a href="#">Forgot Password?</a></p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>