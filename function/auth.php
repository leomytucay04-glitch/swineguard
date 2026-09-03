<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
require_once __DIR__ . '/../include/dbcon.php';
$conn = $GLOBALS['conn'] ?? $conn;

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    echo "Please enter both username and password.";
    exit;
}

// Ensure admin table has email verification columns if missing
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM `admin` LIKE 'email_verified'");
if ($checkCol && mysqli_num_rows($checkCol) === 0) {
    @mysqli_query($conn, "ALTER TABLE `admin` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 1 AFTER `email`");
    @mysqli_query($conn, "ALTER TABLE `admin` ADD COLUMN `verification_code` VARCHAR(10) DEFAULT NULL AFTER `email_verified`");
    @mysqli_query($conn, "ALTER TABLE `admin` ADD COLUMN `code_expires_at` DATETIME DEFAULT NULL AFTER `verification_code`");
}

// Create a prepared statement
$stmt = mysqli_prepare($conn, "SELECT id, name, username, email, email_verified FROM admin WHERE username = ? AND password = ? ");

if ($stmt) {
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
        echo "Invalid username or password.";
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Fallback if admin table doesn't have email_verified column
    $fallbackStmt = mysqli_prepare($conn, "SELECT id, name, username FROM admin WHERE username = ? AND password = ?");
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, "ss", $username, $password);
        mysqli_stmt_execute($fallbackStmt);
        mysqli_stmt_bind_result($fallbackStmt, $adminId, $name, $adminUsername);

        if (mysqli_stmt_fetch($fallbackStmt)) {
            $_SESSION['admin']    = $adminId;
            $_SESSION['name']     = $name;
            $_SESSION['username'] = $adminUsername;
            echo "success";
        } else {
            echo "Invalid username or password.";
        }
        mysqli_stmt_close($fallbackStmt);
    } else {
        echo "Database error: " . mysqli_error($conn);
    }
}

// Close the database connection
mysqli_close($conn);
$GLOBALS['conn'] = null;
unset($conn);
