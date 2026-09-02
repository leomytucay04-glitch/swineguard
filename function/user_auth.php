<?php
// Start session
session_start();

// Include database connection
include '../include/dbcon.php';

// Retrieve POST variables safely
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    echo "Please enter both username and password.";
    exit;
}

// Query the 'users' table including the status field
$stmt = mysqli_prepare($conn, "SELECT id, name, username, role, status FROM users WHERE username = ? AND password = ?");

if ($stmt) {
    // Bind parameters
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);

    // Execute query
    mysqli_stmt_execute($stmt);

    // Bind result variables
    mysqli_stmt_bind_result($stmt, $userId, $name, $userUsername, $role, $status);

    if (mysqli_stmt_fetch($stmt)) {
        // Check if account status is disabled
        if (strtolower(trim($status)) === 'disable' || strtolower(trim($status)) === 'disabled') {
            echo "disable";
        } else {
            // User authenticated successfully -> set session variables
            $_SESSION['user']          = $userId;
            $_SESSION['user_name']     = $name;
            $_SESSION['user_username'] = $userUsername;
            $_SESSION['role']          = $role;

            // Echo the exact role string for AJAX redirection
            echo trim($role);
        }
    } else {
        // Authentication failed
        echo "Invalid username or password.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
} else {
    echo "Database error occurred.";
}

// Close database connection
mysqli_close($conn);
?>