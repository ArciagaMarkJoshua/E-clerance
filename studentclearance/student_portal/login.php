<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentNo = trim($_POST['studentNo']);
    $password = trim($_POST['password']);

    if (!empty($studentNo) && !empty($password)) {
        $stmt = $conn->prepare("SELECT studentNo, LastName, FirstName, PasswordHash FROM students WHERE studentNo = ?");
        $stmt->bind_param("s", $studentNo);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($studentNo, $lastName, $firstName, $hashed_password);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['student_id'] = $studentNo;
                $_SESSION['student_name'] = $lastName . ', ' . $firstName;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid student number or password.";
            }
        } else {
            $error = "Student not found.";
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
    <title>Student Login - DYCI Clearance System</title>
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
                <p class="subtitle">Student Portal</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="studentNo">
                        <i class="fas fa-user"></i>
                        Student Number
                    </label>
                    <input type="text" id="studentNo" name="studentNo" placeholder="Enter your student number" required>
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

            <div class="login-footer">
                <p>Don't have an account? <a href="register_request.php">Request Registration</a></p>
            </div>
        </div>
    </div>
</body>
</html>