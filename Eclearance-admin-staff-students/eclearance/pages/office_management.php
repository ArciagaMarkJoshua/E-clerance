<?php
session_start();
include '../includes/db_connect.php';

// Initialize variables
$search_query = isset($_POST['search_query']) ? $_POST['search_query'] : '';
$message = '';
$message_type = '';

// Fetch offices for display
$office_query = "SELECT o.*, d.DepartmentName 
                 FROM offices o 
                 LEFT JOIN departments d ON o.DepartmentID = d.DepartmentID";
if (!empty($search_query)) {
    $search_term = $conn->real_escape_string($search_query);
    $office_query .= " WHERE o.OfficeName LIKE '%" . $search_term . "%' 
                       OR d.DepartmentName LIKE '%" . $search_term . "%'
                       OR o.Description LIKE '%" . $search_term . "%'";
}

// Add filter by department
$filter_department = isset($_POST['filter_department']) ? $_POST['filter_department'] : '';
if (!empty($filter_department)) {
    // If a search query was already applied, use AND, otherwise use WHERE
    if (empty($search_query)) {
        $office_query .= " WHERE o.DepartmentID = " . intval($filter_department);
    } else {
        $office_query .= " AND o.DepartmentID = " . intval($filter_department);
    }
}

// Add sorting
$sort_by = isset($_POST['sort_by']) ? $_POST['sort_by'] : 'OfficeName';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'asc';

$allowed_sort_columns = ['OfficeID', 'OfficeName', 'DepartmentName', 'Description'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'OfficeName'; // Default to a safe column
}

$sort_order = (strtolower($sort_order) == 'desc') ? 'DESC' : 'ASC';

$office_query .= " ORDER BY " . $sort_by . " " . $sort_order;


$office_result = $conn->query($office_query);

// Fetch departments for dropdown
$department_query = "SELECT * FROM departments ORDER BY DepartmentName";
$department_result = $conn->query($department_query);
$departments = [];
while ($dept = $department_result->fetch_assoc()) {
    $departments[] = $dept;
}

// Office CRUD Operations
if (isset($_POST['add_office'])) {
    try {
        if (empty($_POST['office_name']) || empty($_POST['department_id'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $office_name = $conn->real_escape_string($_POST['office_name']);
        $department_id = intval($_POST['department_id']);
        $description = $conn->real_escape_string($_POST['description']);

        // Check if office name already exists for this department
        $check_query = "SELECT OfficeID FROM offices WHERE OfficeName = ? AND DepartmentID = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $office_name, $department_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Office name already exists for this department!");
        }
        $stmt->close();

        // Insert new office
        $insert_query = "INSERT INTO offices (OfficeName, DepartmentID, Description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sis", $office_name, $department_id, $description);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding office: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Office added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: office_management.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: office_management.php");
        exit();
    }
}

if (isset($_POST['edit_office'])) {
    try {
        if (empty($_POST['office_id']) || empty($_POST['office_name']) || empty($_POST['department_id'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $office_id = intval($_POST['office_id']);
        $office_name = $conn->real_escape_string($_POST['office_name']);
        $department_id = intval($_POST['department_id']);
        $description = $conn->real_escape_string($_POST['description']);

        // Check if office exists
        $check_query = "SELECT OfficeID FROM offices WHERE OfficeID = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            throw new Exception("Office not found.");
        }
        $stmt->close();

        // Check for duplicate office name within the same department, excluding the current office
        $check_duplicate_query = "SELECT OfficeID FROM offices WHERE OfficeName = ? AND DepartmentID = ? AND OfficeID != ?";
        $stmt = $conn->prepare($check_duplicate_query);
        $stmt->bind_param("sii", $office_name, $department_id, $office_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Office name already exists for this department!");
        }
        $stmt->close();


        // Update office
        $update_query = "UPDATE offices SET OfficeName = ?, DepartmentID = ?, Description = ? WHERE OfficeID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sisi", $office_name, $department_id, $description, $office_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating office: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Office updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: office_management.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: office_management.php");
        exit();
    }
}

if (isset($_POST['delete_office'])) {
    try {
        if (empty($_POST['office_id'])) {
            throw new Exception("Please select an office to delete.");
        }

        $office_id = intval($_POST['office_id']);
        
        // Check if office is in use by staff
        $check_usage_query = "SELECT StaffID, FirstName, LastName 
                            FROM staff 
                            WHERE Department = (SELECT OfficeName FROM offices WHERE OfficeID = ?)";
        $stmt = $conn->prepare($check_usage_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $staff = [];
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['StaffID'] . ')';
            }
            throw new Exception("Cannot delete office: It is currently assigned to the following staff members:\n" . implode("\n", $staff));
        }
        $stmt->close();

        // Check if office is in use by clearance requirements
        $check_usage_query = "SELECT requirement_id FROM clearance_requirements WHERE department_id = (SELECT DepartmentID FROM offices WHERE OfficeID = ?)";
        $stmt = $conn->prepare($check_usage_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete office: It has associated clearance requirements.");
        }
        $stmt->close();

        // Delete office
        $delete_query = "DELETE FROM offices WHERE OfficeID = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $office_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting office: " . $stmt->error);
        }
        
        $_SESSION['message'] = "Office deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: office_management.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: office_management.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Management - DYCI E-Clearance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            flex: 1;
            margin-left: 300px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .user-panel, .filter-sort-container, .search-container, .user-list-container {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .user-panel h3, .filter-sort-container h3, .user-list-container h3 {
            margin-top: 0;
            color: #343079;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 10px; /* Adjust spacing between form groups */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #343079;
            box-shadow: 0 0 0 2px rgba(52, 48, 121, 0.15);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end; /* Align buttons to the right */
        }

        .form-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .form-buttons button.active {
            background-color: #343079;
            color: white;
        }

        .form-buttons button.active:hover {
            background-color: #2a265e;
        }

        .form-buttons button:not(.active) {
            background-color: #6c757d;
            color: white;
        }

        .form-buttons button:not(.active):hover {
            background-color: #5a6268;
        }

        /* Specific styles for filter/sort section */
        .filter-sort-container .form-row {
            align-items: flex-end; /* Align items at the bottom for consistent input alignment */
        }

        .filter-sort-container .form-buttons {
            justify-content: flex-start; /* Align filter buttons to the left */
        }

        .search-input-group {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            width: 100%;
        }

        .search-input-group input[type="text"] {
            border: none;
            flex-grow: 1;
            padding: 10px;
            font-size: 15px;
        }

        .search-input-group .search-button,
        .search-input-group .clear-search-button {
            background-color: #e9ecef;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-input-group .search-button:hover,
        .search-input-group .clear-search-button:hover {
            background-color: #dee2e6;
        }

        .search-input-group .search-button i,
        .search-input-group .clear-search-button i {
            color: #555;
        }

        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .search-form {
            width: 100%;
            max-width: 600px; /* Adjust as needed */
        }

        /* Table Styling */
        .table-wrapper {
            overflow-x: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-buttons .btn-edit {
            background-color: #007bff;
            color: white;
        }

        .action-buttons .btn-edit:hover {
            background-color: #0056b3;
        }

        .action-buttons .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .action-buttons .btn-delete:hover {
            background-color: #c82333;
        }

        /* Message boxes */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
    <div class="logo-container">
        <img src="../assets/dycilogo.png" alt="DYCI Logo" class="logo">
        <div class="logo-text">
            <h2>DYCI E-Clearance</h2>
            <p>E-Clearance System</p>
        </div>
    </div>
    <ul>
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <li <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>><a href="dashboard.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a></li>
        <li <?php echo ($current_page == 'student_management.php') ? 'class="active"' : ''; ?>><a href="student_management.php"><i class="fas fa-user-graduate icon"></i> Student Management</a></li>
        <li <?php echo ($current_page == 'staff_management.php') ? 'class="active"' : ''; ?>><a href="staff_management.php"><i class="fas fa-users-cog icon"></i> Staff Management</a></li>
        <li <?php echo ($current_page == 'program_section.php') ? 'class="active"' : ''; ?>><a href="program_section.php"><i class="fas fa-chalkboard-teacher icon"></i> Program & Section</a></li>
        <li <?php echo ($current_page == 'office_management.php') ? 'class="active"' : ''; ?>><a href="office_management.php"><i class="fas fa-building icon"></i> Office Management</a></li>
        <li <?php echo ($current_page == 'academicyear.php') ? 'class="active"' : ''; ?>><a href="academicyear.php"><i class="fas fa-calendar-alt icon"></i> Academic Year</a></li>
        <li <?php echo ($current_page == 'reports.php') ? 'class="active"' : ''; ?>><a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports</a></li>
        <li class="logout"><a href="../includes/logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
    </ul>
</nav>

    <?php echo "<!-- Current Page: " . basename($_SERVER['PHP_SELF']) . " -->"; ?>
<div class="container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?php echo $_SESSION['message_type'] === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="user-panel">
        <h3>Add/Edit Office</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Office ID</label>
                    <input type="text" id="office_id" name="office_id" readonly>
                </div>
                <div class="form-group">
                    <label>Office Name</label>
                    <input type="text" id="office_name" name="office_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo htmlspecialchars($dept['DepartmentName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="add_office" class="active">Add</button>
                <button type="submit" name="edit_office">Update</button>
                <button type="submit" name="delete_office" onclick="return confirmDelete();">Delete</button>
                <button type="button" onclick="clearForm();">Clear</button>
            </div>
        </form>
    </div>

    <!-- Filter and Sort Section -->
    <div class="filter-sort-container">
        <h3>Filter & Sort Offices</h3>
        <form method="POST" id="filterSortForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="filter_department">Filter by Department:</label>
                    <select id="filter_department" name="filter_department" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['DepartmentID']); ?>" <?php echo (isset($_POST['filter_department']) && $_POST['filter_department'] == $dept['DepartmentID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['DepartmentName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_by">Sort By:</label>
                    <select id="sort_by" name="sort_by" class="form-control">
                        <option value="OfficeName" <?php echo ($sort_by == 'OfficeName') ? 'selected' : ''; ?>>Office Name</option>
                        <option value="DepartmentName" <?php echo ($sort_by == 'DepartmentName') ? 'selected' : ''; ?>>Department</option>
                        <option value="OfficeID" <?php echo ($sort_by == 'OfficeID') ? 'selected' : ''; ?>>Office ID</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order:</label>
                    <select id="sort_order" name="sort_order">
                        <option value="asc" <?php echo ($sort_order == 'asc') ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo ($sort_order == 'desc') ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>
            <!-- Hidden input to preserve search query across filter/sort submits -->
            <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">

            <div class="form-buttons">
                <button type="submit" name="apply_filters" class="btn active">Apply Filters</button>
                <button type="button" name="reset_filters" onclick="resetFilters();" class="btn">Reset</button>
            </div>
        </form>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="POST" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search_query" id="search_query" placeholder="Search offices by name or description..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" name="search" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="office_management.php" class="clear-search-button" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="user-list-container">
        <h3>Office List</h3>
        <?php if ($office_result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Office ID</th>
                            <th>Office Name</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $office_result->fetch_assoc()): ?>
                            <tr onclick="populateForm(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                <td><?php echo htmlspecialchars($row['OfficeID']); ?></td>
                                <td><?php echo htmlspecialchars($row['OfficeName']); ?></td>
                                <td><?php echo htmlspecialchars($row['DepartmentName']); ?></td>
                                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-edit" onclick="event.stopPropagation(); populateForm(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                    <button class="btn btn-delete" onclick="event.stopPropagation(); confirmDelete(<?php echo $row['OfficeID']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No offices found.</p>
        <?php endif; ?>
    </div>

    <script>
        function populateForm(office) {
            document.getElementById('office_id').value = office.OfficeID;
            document.getElementById('office_name').value = office.OfficeName;
            document.getElementById('department_id').value = office.DepartmentID;
            document.getElementById('description').value = office.Description;

            // Set active button for Update
            document.querySelector('button[name="add_office"]').classList.remove('active');
            document.querySelector('button[name="edit_office"]').classList.add('active');
        }

        function clearForm() {
            document.getElementById('office_id').value = '';
            document.getElementById('office_name').value = '';
            document.getElementById('department_id').value = '';
            document.getElementById('description').value = '';
            
            // Set active button for Add
            document.querySelector('button[name="edit_office"]').classList.remove('active');
            document.querySelector('button[name="add_office"]').classList.add('active');
        }

        function confirmDelete(officeId) {
            if (confirm('Are you sure you want to delete this office? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'office_id';
                inputId.value = officeId;
                form.appendChild(inputId);

                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_office';
                inputDelete.value = '1';
                form.appendChild(inputDelete);

                document.body.appendChild(form);
                form.submit();
            }
            return false; // Prevent default form submission if not confirmed
        }

        function resetFilters() {
            document.getElementById('filter_department').value = '';
            document.getElementById('sort_by').value = 'OfficeName';
            document.getElementById('sort_order').value = 'asc';
            document.getElementById('search_query').value = '';
            document.getElementById('filterSortForm').submit();
        }

        // Handle initial load to set active button for Add
        document.addEventListener('DOMContentLoaded', function() {
            const officeIdInput = document.getElementById('office_id');
            if (officeIdInput.value === '') {
                document.querySelector('button[name="add_office"]').classList.add('active');
                document.querySelector('button[name="edit_office"]').classList.remove('active');
            } else {
                document.querySelector('button[name="add_office"]').classList.remove('active');
                document.querySelector('button[name="edit_office"]').classList.add('active');
            }
        });
    </script>
</body>
</html>
