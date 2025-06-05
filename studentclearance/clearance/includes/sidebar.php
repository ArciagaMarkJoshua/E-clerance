<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <div class="logo-container">
        <img src="../dyci_logo.png" alt="College Logo" class="logo">
        <div class="logo-text">
            <h2>DR. YANGA'S COLLEGES INC.</h2>
            <p>Staff Portal</p>
        </div>
    </div>
    <ul>
        <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-home icon"></i> Dashboard
            </a>
        </li>
        <li class="<?php echo $current_page === 'clearance.php' ? 'active' : ''; ?>">
            <a href="clearance.php">
                <i class="fas fa-file-alt icon"></i> Clearance
            </a>
        </li>
        <?php if ($_SESSION['account_type'] === 'Admin'): ?>
        <li class="<?php echo $current_page === 'registration_requests.php' ? 'active' : ''; ?>">
            <a href="registration_requests.php">
                <i class="fas fa-user-plus icon"></i> Registration Requests
            </a>
        </li>
        <li class="<?php echo $current_page === 'manage_staff.php' ? 'active' : ''; ?>">
            <a href="manage_staff.php">
                <i class="fas fa-users-cog icon"></i> Manage Staff
            </a>
        </li>
        <li class="<?php echo $current_page === 'manage_requirements.php' ? 'active' : ''; ?>">
            <a href="manage_requirements.php">
                <i class="fas fa-tasks icon"></i> Manage Requirements
            </a>
        </li>
        <?php endif; ?>
        <li class="logout">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt icon"></i> Logout
            </a>
        </li>
    </ul>
</nav> 