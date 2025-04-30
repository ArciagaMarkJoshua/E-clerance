<?php
// Start session
session_start();

// Include database connection
require_once "db_connect.php";

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
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <img src="dyci_logo.svg" alt="DYCI Logo" class="logo">
            <h1>Welcome to DYCI CampusConnect!<br>E-Clearance</h1>
        </div>
        <div class="right-panel">
            <h2>Administrator</h2>
            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST" action="">
                <div class="input-group">
                    <input type="email" name="email" placeholder="E-mail" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <p class="forgot-password"><a href="#">Forgot Password?</a></p>
                <button type="submit">LOG IN</button>
            </form>
        </div>
    </div>
</body>
</html>