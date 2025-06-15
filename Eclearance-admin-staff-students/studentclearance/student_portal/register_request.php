<?php
session_start();
require_once 'includes/db_connect.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentNo = trim($_POST['studentNo']);
    $email = trim($_POST['email']);
    $lastName = trim($_POST['lastName']);
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $programCode = trim($_POST['programCode']);
    $level = trim($_POST['level']);
    $sectionCode = trim($_POST['sectionCode']);
    $academicYear = trim($_POST['academicYear']);
    $semester = trim($_POST['semester']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Validate input
    if (empty($studentNo) || empty($email) || empty($lastName) || empty($firstName) || 
        empty($programCode) || empty($level) || empty($sectionCode) || empty($academicYear) || empty($semester) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Check if student number or email already exists
        $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?");
        $stmt->bind_param("ss", $studentNo, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Student number or email already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert registration request
            $stmt = $conn->prepare("INSERT INTO registration_requests (studentNo, email, lastName, firstName, middleName, programCode, level, sectionCode, academicYear, semester, password_hash, status, request_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("sssssssssss", 
                $studentNo, 
                $email, 
                $lastName, 
                $firstName, 
                $middleName, 
                $programCode, 
                $level, 
                $sectionCode, 
                $academicYear, 
                $semester, 
                $hashed_password
            );
            
            if ($stmt->execute()) {
                $success = "Registration request submitted successfully. Please wait for administrator approval.";
            } else {
                $error = "Error submitting request. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Registration - DYCI Clearance System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #343079 0%, #23205a 100%);
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #343079 0%, #23205a 100%);
        }
        .login-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px 32px 32px 32px;
            max-width: 500px;
            width: 100%;
            margin: 30px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        .logo {
            width: 60px;
            height: 60px;
        }
        .login-header h1 {
            margin: 0 0 5px 0;
            color: #343079;
            font-size: 1.7rem;
            font-weight: 700;
        }
        .login-header .subtitle {
            color: #343079;
            font-size: 1rem;
            opacity: 0.8;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            box-sizing: border-box;
        }
        .alert-error {
            background: #fbe7e7;
            color: #dc3545;
        }
        .alert-success {
            background: #e7fbe9;
            color: #28a745;
        }
        .login-form {
            width: 100%;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 6px;
            color: #343079;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-group input,
        .form-group select {
            width: 80%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f8f9fa;
            transition: border 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #343079;
            outline: none;
        }
        .login-btn {
            background-color: #343079;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        .login-btn:hover {
            background-color: #23205a;
        }
        .login-footer {
            margin-top: 18px;
            text-align: center;
            width: 100%;
        }
        .login-footer a {
            color: #343079;
            text-decoration: underline;
            font-weight: 600;
        }
        @media (max-width: 700px) {
            .login-box {
                padding: 20px 5px 15px 5px;
                max-width: 98vw;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-group input,
            .form-group select {
                width: 100%;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box register-box">
            <div class="login-header">
                <div class="logo-wrapper">
                    <img src="../dyci_logo.png" alt="DYCI Logo" class="logo">
                </div>
                <h1>Request Registration</h1>
                <p class="subtitle">Create your student account</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="studentNo">
                            <i class="fas fa-id-card"></i>
                            Student Number
                        </label>
                        <input type="text" id="studentNo" name="studentNo" placeholder="Enter your student number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lastName">
                            <i class="fas fa-user"></i>
                            Last Name
                        </label>
                        <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="firstName">
                            <i class="fas fa-user"></i>
                            First Name
                        </label>
                        <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="middleName">
                        <i class="fas fa-user"></i>
                        Middle Name
                    </label>
                    <input type="text" id="middleName" name="middleName" placeholder="Enter your middle name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="programCode">
                            <i class="fas fa-graduation-cap"></i>
                            Program
                        </label>
                        <select id="programCode" name="programCode" required>
                            <option value="">Select Program</option>
                            <option value="BSCS">Bachelor of Science in Computer Science</option>
                            <option value="BSIS">Bachelor of Science in Information Systems</option>
                            <option value="BSIT">Bachelor of Science in Information Technology</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="level">
                            <i class="fas fa-layer-group"></i>
                            Year Level
                        </label>
                        <select id="level" name="level" required onchange="updateSections()">
                            <option value="">Select Year Level</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                            <option value="5">5th Year</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sectionCode">
                            <i class="fas fa-users"></i>
                            Section
                        </label>
                        <select id="sectionCode" name="sectionCode" required>
                            <option value="">Select Section</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicYear">
                            <i class="fas fa-calendar"></i>
                            Academic Year
                        </label>
                        <select id="academicYear" name="academicYear" required>
                            <option value="">Select Academic Year</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2024-2025">2024-2025</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="semester">
                        <i class="fas fa-clock"></i>
                        Semester
                    </label>
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                        <option value="Summer Semester">Summer Semester</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Enter your password (min. 8 characters)" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Request
                </button>
            </form>

            <div class="login-footer">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        function updateSections() {
            const level = document.getElementById('level').value;
            const sectionSelect = document.getElementById('sectionCode');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (level) {
                const sections = ['A', 'B', 'C', 'D'];
                sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = level + section.toLowerCase();
                    option.textContent = level + section;
                    sectionSelect.appendChild(option);
                });
            }
        }
    </script>
</body>
</html> 