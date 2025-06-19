<?php
session_start();
include '../includes/db_connect.php';

function render_form($message = '', $success = false) {
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/login.css">
        <title>Bulk Upload Students</title>
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
                min-width: calc(100% - 250px);
                flex: 1;
                margin-left: 250px;
                padding: 30px;
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                box-sizing: border-box;
                overflow-y: auto;
            }
            .user-panel {
                background-color: #fff;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 30px;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }
            .user-panel h3 {
                margin-top: 0;
                color: #343079;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .form-row {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            .form-group {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .form-group label {
                margin-bottom: 5px;
                font-weight: 500;
                color: #555;
            }
            .form-group input {
                padding: 10px;
                font-size: 14px;
                border: 1px solid #ccc;
                border-radius: 6px;
                transition: 0.3s;
            }
            .form-group input:focus {
                outline: none;
                border-color: #343079;
                box-shadow: 0 0 0 2px rgba(52, 48, 121, 0.15);
            }
            .form-buttons {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
            .form-buttons button {
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                font-size: 14px;
                cursor: pointer;
                transition: 0.3s;
                color: white;
                background-color: #343079;
            }
            .form-buttons button:hover {
                background-color: #2c2765;
            }
            .alert {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                font-weight: 500;
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                animation: fadeOut 5s forwards;
                animation-delay: 2s;
                position: relative;
                min-width: 300px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            .back-link {
                display: inline-block;
                margin-top: 10px;
                color: #343079;
                text-decoration: none;
                font-weight: 500;
            }
            .back-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
    <nav class="sidebar">
        <div class="logo-container">
            <img src="../assets/dyci_logo.svg" alt="DYCI Logo" class="logo">
            <div class="logo-text">
                <h2>DYCI CampusConnect</h2>
                <p>E-Clearance System</p>
            </div>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
            <li><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
            <li class="active"><a href="bulk_upload_students.php"><i class="fas fa-file-csv icon"></i> Bulk Upload Students</a></li>
            <li><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
            <li><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
            <li><a href="office_management.php"><i class="fas fa-building icon"></i> Office Management</a></li>
            <li><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
            <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="user-panel">
            <h3>Bulk Upload Students (CSV)</h3>
            <?php if ($message): ?>
                <div class="alert"><?php echo $message; ?></div>
                <?php if ($success): ?>
                    <script>setTimeout(function(){ window.location.href = 'student_management.php'; }, 2000);</script>
                <?php endif; ?>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="csv_file">Select CSV File:</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="upload"><i class="fas fa-upload"></i> Upload</button>
                    <a href="student_management.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Student Management</a>
                </div>
            </form>
            <p style="margin-top:20px;">CSV columns: <code>studentNo,username,email,password,lastName,firstName,middleName,programCode,level,sectionCode,academicYear,semester</code></p>
            <p>Example:</p>
            <pre style="background:#f8f8f8;padding:10px;border-radius:6px;">studentNo,username,email,password,lastName,firstName,middleName,programCode,level,sectionCode,academicYear,semester
2025-0001,jdoe,jdoe@email.com,Password123!,Doe,John,Michael,BSCS,1,BSCS1A,2024-2025,First Semester
2025-0002,asmith,asmith@email.com,Password456!,Smith,Anna,Marie,BSIT,2,BSIT2B,2024-2025,Second Semester</pre>
        </div>
    </div>
    </body>
    </html>
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        render_form('Failed to open uploaded file.');
        exit;
    }
    $header = fgetcsv($handle);
    $expected = ['studentNo','username','email','password','lastName','firstName','middleName','programCode','level','sectionCode','academicYear','semester'];
    if ($header !== $expected) {
        render_form('CSV header does not match expected columns.');
        exit;
    }
    $success = 0;
    $errors = [];
    $rowNum = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        $data = array_combine($expected, $row);
        // Validate required fields
        foreach (['studentNo','username','email','password','lastName','firstName','programCode','level','sectionCode','academicYear','semester'] as $field) {
            if (empty($data[$field])) {
                $errors[] = "Row $rowNum: Missing $field.";
                continue 2;
            }
        }
        // Check for duplicate studentNo or email
        $stmt = $conn->prepare("SELECT studentNo, Email FROM students WHERE studentNo = ? OR Email = ?");
        $stmt->bind_param("ss", $data['studentNo'], $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $rowx = $result->fetch_assoc();
            if ($rowx['studentNo'] === $data['studentNo']) {
                $errors[] = "Row $rowNum: Student number already exists.";
            } else {
                $errors[] = "Row $rowNum: Email already exists.";
            }
            $stmt->close();
            continue;
        }
        $stmt->close();
        // Generate RegistrationNo
        $current_year = date('Y');
        $last_reg_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
        $stmt = $conn->prepare($last_reg_query);
        $year_pattern = $current_year . "-%";
        $stmt->bind_param("s", $year_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $last_reg = $result->fetch_assoc()['RegistrationNo'];
            $last_number = intval(substr($last_reg, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        $registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
        $stmt->close();
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        // Insert student
        $insert_query = "INSERT INTO students (RegistrationNo, studentNo, Username, Email, PasswordHash, LastName, FirstName, Mname, ProgramCode, Level, SectionCode, AcademicYear, Semester, AccountType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Student')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssssssssssss", $registration_no, $data['studentNo'], $data['username'], $data['email'], $password_hash, $data['lastName'], $data['firstName'], $data['middleName'], $data['programCode'], $data['level'], $data['sectionCode'], $data['academicYear'], $data['semester']);
        if ($stmt->execute()) {
            $success++;
        } else {
            $errors[] = "Row $rowNum: DB error: " . $stmt->error;
        }
        $stmt->close();
    }
    fclose($handle);
    $msg = "$success students added successfully.";
    if ($errors) $msg .= '<br>Errors:<br>' . implode('<br>', $errors);
    render_form($msg, $success > 0 && count($errors) == 0);
    exit;
}
render_form(); 