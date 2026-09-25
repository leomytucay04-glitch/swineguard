<?php
session_start();
require_once __DIR__ . '/../../include/dbcon.php';
require_once __DIR__ . '/../../include/password_validator.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    echo "Unauthorized session access.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_id         = $_SESSION['admin'];

    // 1. Verify inputs are complete
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "Please fill in all security parameter blocks.";
        exit;
    }

    // 2. Verify new password match
    if ($new_password !== $confirm_password) {
        echo "New password and confirmation do not match.";
        exit;
    }

    // 3. Retrieve current admin password and profile info from database
    $check_query = "SELECT password, username, name FROM admin WHERE id = ?";
    $stmt        = $conn->prepare($check_query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $res  = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        echo "Admin account record not found.";
        exit;
    }

    // 4. Check current password
    $db_password = $res['password'];
    $is_valid    = password_verify($current_password, $db_password) || ($current_password === $db_password);

    if (!$is_valid) {
        echo "Incorrect current credential password entered.";
        exit;
    }

    // 5. Enforce password validation rules
    $val = validate_password_strength($new_password, $res['username'] ?? '', $res['name'] ?? '');
    if (!$val['valid']) {
        echo implode("\n", $val['errors']);
        exit;
    }

    // 6. Update password for active admin session
    $update_query = "UPDATE admin SET password = ? WHERE id = ?";
    $up_stmt      = $conn->prepare($update_query);
    $up_stmt->bind_param("si", $new_password, $admin_id);

    if ($up_stmt->execute()) {
        echo "success";
    } else {
        echo "Database error: " . $conn->error;
    }

    $up_stmt->close();
}
?>