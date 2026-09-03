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
    echo "Database error occurred.";
}

// Close database connection
mysqli_close($conn);
$GLOBALS['conn'] = null;
unset($conn);
?>