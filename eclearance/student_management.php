<?php
session_start();
include 'db_connect.php'; // Database connection

// Fetch next available control number
$ctrl_no_query = "SELECT MAX(CtrlNo) AS max_ctrl FROM students";
$ctrl_no_result = $conn->query($ctrl_no_query);
$row = $ctrl_no_result->fetch_assoc();
$next_ctrl_no = $row['max_ctrl'] + 1;

// Fetch programs for dropdown
$program_query = "SELECT * FROM programs"; 
$program_result = $conn->query($program_query);

// Fetch sections for dropdown
$section_query = "SELECT * FROM sections";
$section_result = $conn->query($section_query);

// Fetch levels for dropdown
$level_query = "SELECT * FROM levels";
$level_result = $conn->query($level_query);

// Search functionality
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
    $sql = "SELECT * FROM students WHERE 
            studentNo LIKE '%$search_query%' OR 
            Username LIKE '%$search_query%' OR 
            LastName LIKE '%$search_query%' OR 
            SectionCode LIKE '%$search_query%' OR 
            FirstName LIKE '%$search_query%'";
} else {
    $sql = "SELECT * FROM students";
}
$result = $conn->query($sql);

// Add Student
if (isset($_POST['add_user'])) {
    $student_no = $_POST['student_no'];
    $username = $_POST['username'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $program_code = $_POST['program_code'];
    $section_code = $_POST['section_code'];
    $level = $_POST['level']; // Use 'Level' instead of 'LevelID'
    $account_type = "Student"; // Default account type

    $sql = "INSERT INTO students (CtrlNo, studentNo, Username, LastName, FirstName, Mname, Email, PasswordHash, ProgramCode, SectionCode, Level, AccountType) 
            VALUES ('$next_ctrl_no', '$student_no', '$username', '$last_name', '$first_name', '$middle_name', '$email', '$password', '$program_code', '$section_code', '$level', '$account_type')";

    if (!$conn->query($sql)) {
        die("Insert Error: " . $conn->error);
    }

    header("Location: student_management.php");
}

// Edit Student
if (isset($_POST['edit_user'])) {
    $ctrl_no = $_POST['ctrl_no'];
    $student_no = $_POST['student_no'];
    $username = $_POST['username'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $program_code = $_POST['program_code'];
    $section_code = $_POST['section_code'];
    $level = $_POST['level']; // Use 'Level' instead of 'LevelID'

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $sql = "UPDATE students SET 
                studentNo='$student_no',
                Username='$username',
                LastName='$last_name', 
                FirstName='$first_name', 
                Mname='$middle_name', 
                Email='$email', 
                PasswordHash='$password',
                ProgramCode='$program_code',
                SectionCode='$section_code',
                Level='$level'
                WHERE CtrlNo='$ctrl_no'";
    } else {
        $sql = "UPDATE students SET 
                studentNo='$student_no',
                Username='$username',
                LastName='$last_name', 
                FirstName='$first_name', 
                Mname='$middle_name', 
                Email='$email', 
                ProgramCode='$program_code',
                SectionCode='$section_code',
                Level='$level'
                WHERE CtrlNo='$ctrl_no'";
    }

    if (!$conn->query($sql)) {
        die("Update Error: " . $conn->error);
    }

    header("Location: student_management.php");
}

// Delete Student
if (isset($_POST['delete_user'])) {
    $ctrl_no = $_POST['ctrl_no'];
    $sql = "DELETE FROM students WHERE CtrlNo='$ctrl_no'";

    if (!$conn->query($sql)) {
        die("Delete Error: " . $conn->error);
    }

    header("Location: student_management.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function selectUser(ctrlNo, studentNo, username, lastName, firstName, middleName, email, programCode, sectionCode, level) {
            // Populate the form fields with the selected user's data
            document.getElementById('ctrl_no').value = ctrlNo;
            document.getElementById('student_no').value = studentNo;
            document.getElementById('username').value = username;
            document.getElementById('last_name').value = lastName;
            document.getElementById('first_name').value = firstName;
            document.getElementById('middle_name').value = middleName;
            document.getElementById('email').value = email;
            document.getElementById('program_code').value = programCode;
            document.getElementById('section_code').value = sectionCode;
            document.getElementById('level').value = level;
        }
    </script>
</head>
<body>
<nav class="sidebar">
    <div class="logo-container">
        <img src="dyci_logo.svg" alt="College Logo" class="logo">
        <div class="logo-text">
            <h2>DR. YANGA'S COLLEGES INC.</h2>
            <p>Administrator</p>
        </div>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="icon-home"></i> Home</a></li>
        <li><a href="staff_management.php"><i class="icon-users"></i> Staff Management</a></li>
        <li><a href="eclearance.php"><i class="icon-doc"></i> E-Clearance</a></li>
        <li><a href="program_section.php"><i class="icon-grid"></i> Program & Section</a></li>
        <li><a href="ay_semester.php"><i class="icon-calendar"></i> AY & Semester</a></li>
        <li class="active"><a href="student_management.php"><i class="icon-user"></i> Student Management</a></li>
        <li class="logout"><a href="logout.php"><i class="icon-logout"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="user-panel">
        <h3>Student Information</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Ctrl No.</label>
                    <input type="text" id="ctrl_no" name="ctrl_no" value="<?php echo $next_ctrl_no; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Student No.</label>
                    <input type="text" id="student_no" name="student_no" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="New Password (Leave blank to keep current)">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Program</label>
                    <select id="program_code" name="program_code" required>
                        <?php while ($program = $program_result->fetch_assoc()) { ?>
                            <option value="<?php echo $program['ProgramCode']; ?>"><?php echo $program['ProgramTitle']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_code" name="section_code" required>
                        <?php while ($section = $section_result->fetch_assoc()) { ?>
                            <option value="<?php echo $section['SectionCode']; ?>"><?php echo $section['SectionTitle']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Level</label>
                    <select id="level" name="level" required>
                        <?php while ($level = $level_result->fetch_assoc()) { ?>
                            <option value="<?php echo $level['LevelID']; ?>"><?php echo $level['LevelName']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" name="account_type" value="Student" readonly>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_user" class="active">Add</button>
                <button type="submit" name="edit_user">Edit</button>
                <button type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this student?');">Delete</button>
                <button type="button" onclick="clearForm();">Clear</button>
            </div>
        </form>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" placeholder="Search staff by ID, name, username or department..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="student_management.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Scrollable Content Area -->
    <div class="scrollable-content">
        <!-- Student List Table -->
        <div class="user-list-container">
            <h3>Student List</h3>
            <?php if ($result->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Ctrl No.</th>
                                <th>Student No.</th>
                                <th>Username</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Section</th>
                                <th>Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr onclick="selectUser('<?php echo $row['CtrlNo']; ?>', '<?php echo $row['studentNo']; ?>', '<?php echo $row['Username']; ?>', '<?php echo $row['LastName']; ?>', '<?php echo $row['FirstName']; ?>', '<?php echo $row['Mname']; ?>', '<?php echo $row['Email']; ?>', '<?php echo $row['ProgramCode']; ?>', '<?php echo $row['SectionCode']; ?>', '<?php echo $row['Level']; ?>')">
                                    <td><?php echo $row['CtrlNo']; ?></td>
                                    <td><?php echo $row['studentNo']; ?></td>
                                    <td><?php echo $row['Username']; ?></td>
                                    <td><?php echo $row['LastName']; ?></td>
                                    <td><?php echo $row['FirstName']; ?></td>
                                    <td><?php echo $row['Mname']; ?></td>
                                    <td><?php echo $row['Email']; ?></td>
                                    <td><?php echo $row['ProgramCode']; ?></td>
                                    <td><?php echo $row['SectionCode']; ?></td>
                                    <td><?php echo $row['Level']; ?></td>
                                    <td><button type="button" class="select-btn">Select</button></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-results">No students found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function selectUser(ctrlNo, studentNo, username, lastName, firstName, middleName, email, programCode, sectionCode, level) {
    document.getElementById('ctrl_no').value = ctrlNo;
    document.getElementById('student_no').value = studentNo;
    document.getElementById('username').value = username;
    document.getElementById('last_name').value = lastName;
    document.getElementById('first_name').value = firstName;
    document.getElementById('middle_name').value = middleName;
    document.getElementById('email').value = email;
    document.getElementById('program_code').value = programCode;
    document.getElementById('section_code').value = sectionCode;
    document.getElementById('level').value = level;
}

function clearForm() {
    document.querySelector('form').reset();
    document.getElementById('ctrl_no').value = '<?php echo $next_ctrl_no; ?>';
}
</script>

</body>
</html>
<?php
$conn->close();
?>