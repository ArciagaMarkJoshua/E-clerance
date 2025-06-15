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

    if (empty($email) || empty($password)) {
        $error = "⚠ Please fill in all fields.";
    } else {
        try {
        // Prepare SQL statement to prevent SQL Injection
        $stmt = $conn->prepare("SELECT RegistrationNo, StaffID, Username, PasswordHash, LastName, FirstName, AccountType, Department FROM staff WHERE Email = ?");
        if (!$stmt) {
                throw new Exception("Database error. Please try again later.");
        }
            
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($registrationNo, $staffID, $username, $hashed_password, $lastName, $firstName, $accountType, $department);
            $stmt->fetch();
            
                // Verify password
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
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "⚠ An error occurred. Please try again later.";
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
    <title>DYCI E-Clearance Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/login.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: #f5f5f5;
        }

        .login-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: url('/eclearance/assets/dyci_bg.jpg') center center no-repeat;
            background-size: 120% auto;
            background-color: #f5f5f5;
            overflow: hidden;
        }
        
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 35, 126, 0.85) 0%, rgba(13, 71, 161, 0.85) 100%);
        }

        .login-card {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media (min-width: 1200px) {
            .login-container {
                background-size: 100% auto;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                background-size: 150% auto;
            }
        }

        .login-input {
            width: 80%;
            padding: 1rem 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            padding-left: 3rem;
            font-family: 'Inter', sans-serif;
        }

        .login-input:focus {
            border-color: var(--primary-color);
            outline: none;
            background-color: var(--white);
            box-shadow: 0 0 0 4px rgba(26, 35, 126, 0.1);
        }

        .form-group i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="overlay"></div>
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
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="login-input" placeholder="E-mail" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="login-input" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="login-button">
                            <i class="fas fa-sign-in-alt"></i>
                            LOG IN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>