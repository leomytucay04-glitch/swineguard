<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
require_once __DIR__ . '/../include/dbcon.php';
$conn = $GLOBALS['conn'] ?? $conn;

$username = $_POST['username'];
$password = $_POST['password'];

// Create a prepared statement
$stmt = mysqli_prepare($conn, "SELECT id, name, username, email, email_verified FROM admin WHERE username = ? AND password = ? ");

// Bind parameters to the statement
mysqli_stmt_bind_param($stmt, "ss", $username, $password);

// Execute the statement
mysqli_stmt_execute($stmt);

// Bind the result to variables
mysqli_stmt_bind_result($stmt, $adminId, $name, $adminUsername, $adminEmail, $adminEmailVerified);

if (mysqli_stmt_fetch($stmt)) {
    // Admin found
    $adminEmail = trim((string)$adminEmail);
    $isVerified = (int)$adminEmailVerified;

    if (empty($adminEmail) || $isVerified !== 1) {
        // Missing email or unverified -> pre-auth session
        $_SESSION['pending_auth'] = [
            'id'             => $adminId,
            'name'           => $name,
            'username'       => $adminUsername,
            'role'           => 'admin',
            'type'           => 'admin',
            'email'          => $adminEmail,
            'email_verified' => $isVerified
        ];

        echo "require_email";
    } else {
        // Verified email on file -> full admin session
        $_SESSION['admin']    = $adminId;
        $_SESSION['name']     = $name;
        $_SESSION['username'] = $adminUsername;

        echo "success";
    }

   
} else {
    // No matching user found
    echo "Invalid email or password.";
}

// Close the prepared statement
mysqli_stmt_close($stmt);

// Close the database connection
mysqli_close($conn);
$GLOBALS['conn'] = null;
unset($conn);
