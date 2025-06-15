<?php
include '../includes/db_connect.php';

// Fetch next available registration number in YYYY-XXXX format
$current_year = date('Y');
$registration_no_query = "SELECT RegistrationNo FROM students WHERE RegistrationNo LIKE ? ORDER BY RegistrationNo DESC LIMIT 1";
$stmt = $conn->prepare($registration_no_query);
$year_pattern = $current_year . "-%";
$stmt->bind_param("s", $year_pattern);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $last_reg = $row['RegistrationNo'];
    // Extract the numeric part (XXXX) from the last registration number
    $last_number = intval(substr($last_reg, -4));
    $new_number = $last_number + 1;
} else {
    $new_number = 1;
}

$next_registration_no = $current_year . "-" . str_pad($new_number, 4, '0', STR_PAD_LEFT);

echo $next_registration_no;
?> 