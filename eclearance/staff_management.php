<?php
session_start();
include 'db_connect.php';

// Initialize message variables
$message = '';
$message_type = ''; // 'success' or 'error'

// Fetch the next available control number
$ctrl_no_query = "SELECT MAX(CtrlNo) AS max_ctrl FROM staff";
$ctrl_no_result = $conn->query($ctrl_no_query);
$row = $ctrl_no_result->fetch_assoc();
$next_ctrl_no = $row['max_ctrl'] + 1;

// Fetch departments for dropdown (only once)
$dept_query = "SELECT * FROM departments"; 
$dept_result = $conn->query($dept_query);
$departments = [];
while ($dept = $dept_result->fetch_assoc()) {
    $departments[] = $dept;
}

// Search functionality
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $conn->real_escape_string($_POST['search_query']);
    $sql = "SELECT * FROM staff WHERE 
            StaffID LIKE '%$search_query%' OR 
            Username LIKE '%$search_query%' OR 
            LastName LIKE '%$search_query%' OR 
            FirstName LIKE '%$search_query%' OR 
            Department LIKE '%$search_query%'";
} else {
    $sql = "SELECT * FROM staff";
}

// Add User
if (isset($_POST['add_user'])) {
    $staff_id = $conn->real_escape_string($_POST['staff_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $department = $conn->real_escape_string($_POST['department']);

    // Check if staff ID or username already exists
    $check_query = "SELECT * FROM staff WHERE StaffID='$staff_id' OR Username='$username'";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $message = "Error: Staff ID or Username already exists!";
        $message_type = "error";
    } else {
        $sql = "INSERT INTO staff (CtrlNo, StaffID, Username, LastName, FirstName, Mname, Email, PasswordHash, AccountType, Department) 
                VALUES ('$next_ctrl_no', '$staff_id', '$username', '$last_name', '$first_name', '$middle_name', '$email', '$password', 'Staff', '$department')";
        
        if ($conn->query($sql)) {
            $message = "User added successfully!";
            $message_type = "success";
            // Refresh the control number
            $ctrl_no_result = $conn->query("SELECT MAX(CtrlNo) AS max_ctrl FROM staff");
            $row = $ctrl_no_result->fetch_assoc();
            $next_ctrl_no = $row['max_ctrl'] + 1;
        } else {
            $message = "Error adding user: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Edit User
if (isset($_POST['edit_user'])) {
    $ctrl_no = $conn->real_escape_string($_POST['ctrl_no']);
    $staff_id = $conn->real_escape_string($_POST['staff_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    
    // Check if staff ID or username already exists (excluding current user)
    $check_query = "SELECT * FROM staff WHERE (StaffID='$staff_id' OR Username='$username') AND CtrlNo != '$ctrl_no'";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $message = "Error: Staff ID or Username already exists for another user!";
        $message_type = "error";
    } else {
        // Check if a new password was entered
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql = "UPDATE staff SET 
                    StaffID='$staff_id',
                    Username='$username',
                    LastName='$last_name', 
                    FirstName='$first_name', 
                    Mname='$middle_name', 
                    Email='$email', 
                    PasswordHash='$password',
                    Department='$department'
                    WHERE CtrlNo='$ctrl_no'";
        } else {
            $sql = "UPDATE staff SET 
                    StaffID='$staff_id',
                    Username='$username',
                    LastName='$last_name', 
                    FirstName='$first_name', 
                    Mname='$middle_name', 
                    Email='$email', 
                    Department='$department'
                    WHERE CtrlNo='$ctrl_no'";
        }
        
        if ($conn->query($sql)) {
            $message = "User updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating user: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Delete User
if (isset($_POST['delete_user'])) {
    $ctrl_no = $conn->real_escape_string($_POST['ctrl_no']);
    $sql = "DELETE FROM staff WHERE CtrlNo='$ctrl_no'";
    
    if ($conn->query($sql)) {
        $message = "User deleted successfully!";
        $message_type = "success";
        // Refresh the control number
        $ctrl_no_result = $conn->query("SELECT MAX(CtrlNo) AS max_ctrl FROM staff");
        $row = $ctrl_no_result->fetch_assoc();
        $next_ctrl_no = $row['max_ctrl'] + 1;
    } else {
        $message = "Error deleting user: " . $conn->error;
        $message_type = "error";
    }
}

// Clear Form
if (isset($_POST['clear_form'])) {
    // This will just reset the form to add new user mode
    $next_ctrl_no = $conn->query("SELECT MAX(CtrlNo) AS max_ctrl FROM staff")->fetch_assoc()['max_ctrl'] + 1;
}

// Fetch staff users (again after possible changes)
$sql = "SELECT * FROM staff";
if (!empty($search_query)) {
    $sql = "SELECT * FROM staff WHERE 
            StaffID LIKE '%$search_query%' OR 
            Username LIKE '%$search_query%' OR 
            LastName LIKE '%$search_query%' OR 
            FirstName LIKE '%$search_query%' OR 
            Department LIKE '%$search_query%'";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <script>
        function selectUser(ctrlNo, staffId, username, lastName, firstName, middleName, email, department) {
            document.getElementById("ctrl_no").value = ctrlNo;
            document.getElementById("staff_id").value = staffId;
            document.getElementById("username").value = username;
            document.getElementById("last_name").value = lastName;
            document.getElementById("first_name").value = firstName;
            document.getElementById("middle_name").value = middleName;
            document.getElementById("email").value = email;
            document.getElementById("department").value = department;
            
            // Change button focus to Edit/Delete
            document.querySelector("button[name='add_user']").classList.remove('active');
            document.querySelector("button[name='edit_user']").classList.add('active');
            document.querySelector("button[name='delete_user']").classList.add('active');
        }
        
        function clearForm() {
            document.getElementById("staff_id").value = '';
            document.getElementById("username").value = '';
            document.getElementById("last_name").value = '';
            document.getElementById("first_name").value = '';
            document.getElementById("middle_name").value = '';
            document.getElementById("email").value = '';
            document.getElementById("department").value = document.getElementById("department").options[0].value;
            document.querySelector("input[name='password']").value = '';
            
            // Reset to add mode
            document.querySelector("button[name='add_user']").classList.add('active');
            document.querySelector("button[name='edit_user']").classList.remove('active');
            document.querySelector("button[name='delete_user']").classList.remove('active');
            
            // Update control number to next available
            document.getElementById("ctrl_no").value = <?php echo $next_ctrl_no; ?>;
        }
        
        function confirmDelete() {
            return confirm("Are you sure you want to delete this user?");
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
        <li class="active"><a href="staff_management.php"><i class="icon-users"></i> Staff Management</a></li>
        <li><a href="eclearance.php"><i class="icon-doc"></i> E-Clearance</a></li>
        <li><a href="program_section.php"><i class="icon-grid"></i> Program & Section</a></li>
        <li><a href="ay_semester.php"><i class="icon-calendar"></i> AY & Semester</a></li>
        <li><a href="student_management.php"><i class="icon-user"></i> Student Management</a></li>
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
        <h3>Account Information</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Ctrl No.</label>
                    <input type="text" id="ctrl_no" name="ctrl_no" value="<?php echo $next_ctrl_no; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" id="staff_id" name="staff_id" required>
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
                    <label>Department</label>
                    <select id="department" name="department" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['DepartmentName']; ?>"><?php echo $dept['DepartmentName']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" name="account_type" value="Staff" readonly>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_user" class="active">Add</button>
                <button type="submit" name="edit_user">Edit</button>
                <button type="submit" name="delete_user" onclick="return confirmDelete();">Delete</button>
                <button type="button" onclick="clearForm();">Clear</button>
            </div>
        </form>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST">
            <input type="text" name="search_query" placeholder="Search staff..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" name="search"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="staff_management.php" class="clear-search">Clear Search</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- User List Table -->
    <div class="user-list-container">
        <h3>Staff List</h3>
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ctrl No.</th>
                        <th>Staff ID</th>
                        <th>Username</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr onclick="selectUser('<?php echo $row['CtrlNo']; ?>', '<?php echo htmlspecialchars($row['StaffID']); ?>', '<?php echo htmlspecialchars($row['Username']); ?>', '<?php echo htmlspecialchars($row['LastName']); ?>', '<?php echo htmlspecialchars($row['FirstName']); ?>', '<?php echo htmlspecialchars($row['Mname']); ?>', '<?php echo htmlspecialchars($row['Email']); ?>', '<?php echo htmlspecialchars($row['Department']); ?>')">
                            <td><?php echo $row['CtrlNo']; ?></td>
                            <td><?php echo htmlspecialchars($row['StaffID']); ?></td>
                            <td><?php echo htmlspecialchars($row['Username']); ?></td>
                            <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($row['Mname']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Department']); ?></td>
                            <td><button type="button">Select</button></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No staff members found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>