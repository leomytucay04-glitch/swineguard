<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../include/dbcon.php';
$conn = $GLOBALS['conn'] ?? $conn;

// Retrieve POST variables safely
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    echo "Please enter both username and password.";
    exit;
}

// Ensure users table has email verification columns if missing
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'email'");
if ($checkCol && mysqli_num_rows($checkCol) === 0) {
    @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `password`");
    @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email`");
    @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `verification_code` VARCHAR(10) DEFAULT NULL AFTER `email_verified`");
    @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `code_expires_at` DATETIME DEFAULT NULL AFTER `verification_code`");
}

// Query the 'users' table including email and verification status
$stmt = mysqli_prepare($conn, "SELECT id, name, username, role, status, email, email_verified FROM users WHERE username = ? AND password = ?");

if ($stmt) {
    // Bind parameters
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);

    // Execute query
    mysqli_stmt_execute($stmt);

    // Bind result variables
    mysqli_stmt_bind_result($stmt, $userId, $name, $userUsername, $role, $status, $email, $emailVerified);

    if (mysqli_stmt_fetch($stmt)) {
        // Check if account status is disabled
        if (strtolower(trim($status)) === 'disable' || strtolower(trim($status)) === 'disabled') {
            echo "disable";
        } else {
            $userEmail = trim((string)$email);
            $isVerified = (int)$emailVerified;

            // Gatekeeper: If email is missing or not verified, require email verification
            if (empty($userEmail) || $isVerified !== 1) {
                // DO NOT grant full login session yet
                $_SESSION['pending_auth'] = [
                    'id'             => $userId,
                    'name'           => $name,
                    'username'       => $userUsername,
                    'role'           => $role,
                    'type'           => 'user',
                    'email'          => $userEmail,
                    'email_verified' => $isVerified
                ];

                echo "require_email";
            } else {
                // User authenticated successfully with verified email -> set full session variables
                $_SESSION['user']          = $userId;
                $_SESSION['user_name']     = $name;
                $_SESSION['user_username'] = $userUsername;
                $_SESSION['role']          = $role;

                // Echo the exact role string for AJAX redirection
                echo trim($role);
            }
        }
    } else {
        // Authentication failed
        echo "Invalid username or password.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
} else {
    // Fallback if users table doesn't have email columns
    $fallbackStmt = mysqli_prepare($conn, "SELECT id, name, username, role, status FROM users WHERE username = ? AND password = ?");
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, "ss", $username, $password);
        mysqli_stmt_execute($fallbackStmt);
        mysqli_stmt_bind_result($fallbackStmt, $userId, $name, $userUsername, $role, $status);

        if (mysqli_stmt_fetch($fallbackStmt)) {
            if (strtolower(trim($status)) === 'disable' || strtolower(trim($status)) === 'disabled') {
                echo "disable";
            } else {
                $_SESSION['pending_auth'] = [
                    'id'             => $userId,
                    'name'           => $name,
                    'username'       => $userUsername,
                    'role'           => $role,
                    'type'           => 'user',
                    'email'          => '',
                    'email_verified' => 0
                ];
                echo "require_email";
            }
        } else {
            echo "Invalid username or password.";
        }
        mysqli_stmt_close($fallbackStmt);
    } else {
        echo "Database error: " . mysqli_error($conn);
    }
}

// Close database connection
mysqli_close($conn);
$GLOBALS['conn'] = null;
unset($conn);
?>