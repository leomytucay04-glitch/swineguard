<?php
// 1. Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// 3. Include dbcon.php to perform status check
require_once __DIR__ . '/../include/dbcon.php';

// 4. Verify user status in database
if (isset($conn) && $conn instanceof mysqli) {
    $userId = $_SESSION['user'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $status = strtolower(trim($user['status'] ?? ''));
            if ($status !== 'active') {
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

// =========================================================
// LAST ROWS: Unset/close connection so page can safely re-include dbcon.php
// =========================================================
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
    unset($conn);
}
?>