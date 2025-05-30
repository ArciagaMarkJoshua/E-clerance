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
    <title>Student Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="../dyci_logo.png" alt="DYCI Logo" class="logo">
            <h2>Student Login</h2>
            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="studentNo" placeholder="Student Number" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>