<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT StaffID, Username, LastName, FirstName, AccountType, PasswordHash FROM staff WHERE Username = ? AND IsActive = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($staff_id, $username, $lastName, $firstName, $accountType, $hashed_password);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['staff_id'] = $staff_id;
                $_SESSION['staff_name'] = $lastName . ', ' . $firstName;
                $_SESSION['account_type'] = $accountType;
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE staff SET LastLogin = CURRENT_TIMESTAMP WHERE StaffID = ?");
                $updateStmt->bind_param("i", $staff_id);
                $updateStmt->execute();
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Account not found or inactive.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - DYCI Clearance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-wrapper">
                    <img src="../dyci_logo.png" alt="DYCI Logo" class="logo">
                </div>
                <h1>DYCI Clearance System</h1>
                <p class="subtitle">Staff Portal</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html> 