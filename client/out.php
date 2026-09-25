<?php
// 1. Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Check if user or admin is logged in
if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

// Set fallback user display info if accessed by admin
if (!isset($_SESSION['user_name']) && isset($_SESSION['name'])) {
    $_SESSION['user_name'] = $_SESSION['name'];
}

// 3. Include dbcon.php to perform status check
require_once __DIR__ . '/../include/dbcon.php';

// 4. Verify user status in database if logged in as user
if (isset($_SESSION['user']) && isset($conn) && $conn instanceof mysqli) {
    $userId = $_SESSION['user'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $status = strtolower(trim($user['status'] ?? ''));
            if ($status === 'disable' || $status === 'disabled') {
                session_unset();
                session_destroy();
                header("Location: ../login.php?error=disabled");
                exit();
            }
        } else {
            session_unset();
            session_destroy();
            header("Location: ../login.php");
            exit();
        }
        $stmt->close();
    }
}
?>