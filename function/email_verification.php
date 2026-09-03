<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Include database connection and mailer
require_once __DIR__ . '/../include/dbcon.php';
if (!isset($conn) || !($conn instanceof mysqli) || @mysqli_ping($conn) === false) {
    include __DIR__ . '/../include/dbcon.php';
}
$conn = $GLOBALS['conn'] ?? $conn;
require_once __DIR__ . '/../include/mailer.php';

// Check if pending authorization exists
if (!isset($_SESSION['pending_auth'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Session expired or not authorized. Please log in again.'
    ]);
    exit;
}

$pending   = &$_SESSION['pending_auth'];
$userId    = (int)($pending['id'] ?? 0);
$userType  = $pending['type'] ?? 'user'; // 'user' or 'admin'
$userName  = $pending['name'] ?? 'User';
$tableName = ($userType === 'admin') ? 'admin' : 'users';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ========================================================
// 1. ACTION: Send or Resend Verification Code
// ========================================================
if ($action === 'send_code' || $action === 'resend_code') {
    $email = trim($_POST['email'] ?? ($pending['email'] ?? ''));

    if (empty($email)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please enter a valid Gmail address.'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'The email address format is invalid.'
        ]);
        exit;
    }

    // Check if email is already taken and verified by another user
    $chkStmt = mysqli_prepare($conn, "SELECT id FROM $tableName WHERE email = ? AND id != ? AND email_verified = 1 LIMIT 1");
    if ($chkStmt) {
        mysqli_stmt_bind_param($chkStmt, "si", $email, $userId);
        mysqli_stmt_execute($chkStmt);
        mysqli_stmt_store_result($chkStmt);
        if (mysqli_stmt_num_rows($chkStmt) > 0) {
            mysqli_stmt_close($chkStmt);
            echo json_encode([
                'status'  => 'error',
                'message' => 'This email address is already linked to another account.'
            ]);
            exit;
        }
        mysqli_stmt_close($chkStmt);
    }

    // Generate secure 6-digit numeric OTP
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    // Save email, code, and 10-minute expiry in database
    $upStmt = mysqli_prepare($conn, "UPDATE $tableName SET email = ?, email_verified = 0, verification_code = ?, code_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
    if (!$upStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database error preparing verification code: ' . mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($upStmt, "ssi", $email, $otpCode, $userId);
    if (!mysqli_stmt_execute($upStmt)) {
        mysqli_stmt_close($upStmt);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Failed to save verification code.'
        ]);
        exit;
    }
    mysqli_stmt_close($upStmt);

    // Update pending session
    $pending['email'] = $email;

    // Detect host and construct direct verification link
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Compute basePath
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(dirname($scriptName), '/'); // strip /function
    $verifyLink = $protocol . $host . $basePath . '/add_email.php?code=' . urlencode($otpCode) . '&email=' . urlencode($email);

    // Send email via mailer
    $mailResult = sendVerificationEmail($email, $otpCode, $verifyLink, $userName);

    $response = [
        'status'   => 'success',
        'message'  => 'A 6-digit verification code has been sent to ' . htmlspecialchars($email) . '.',
        'email'    => $email,
        'mode'     => $mailResult['mode'] ?? 'smtp'
    ];

    // Include dev_code in response only if running in development mode
    $configFile = __DIR__ . '/../include/email_config.php';
    $config = file_exists($configFile) ? include($configFile) : [];
    $isDev = !empty($config['dev_mode']) && $config['dev_mode'] !== 'false' && $config['dev_mode'] !== false;

    if ($isDev) {
        $response['dev_mode'] = true;
        $response['dev_code'] = $otpCode;
        $response['message'] .= ' (Development mode OTP: ' . $otpCode . ')';
    }

    echo json_encode($response);
    exit;
}

// ========================================================
// 2. ACTION: Verify OTP Code
// ========================================================
if ($action === 'verify_code') {
    $code = trim($_POST['code'] ?? ($_GET['code'] ?? ''));

    if (empty($code)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please enter the 6-digit verification code.'
        ]);
        exit;
    }

    // Query user record for code match and expiry check
    $vStmt = mysqli_prepare($conn, "SELECT email, verification_code, code_expires_at, (code_expires_at >= NOW()) AS is_valid FROM $tableName WHERE id = ?");
    if (!$vStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database error checking verification: ' . mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($vStmt, "i", $userId);
    mysqli_stmt_execute($vStmt);
    mysqli_stmt_bind_result($vStmt, $dbEmail, $dbCode, $dbExpires, $isValid);

    if (!mysqli_stmt_fetch($vStmt)) {
        mysqli_stmt_close($vStmt);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Account record not found.'
        ]);
        exit;
    }
    mysqli_stmt_close($vStmt);

    // Check code correctness
    if (empty($dbCode) || $dbCode !== $code) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid verification code. Please check your Gmail or request a new code.'
        ]);
        exit;
    }

    // Check expiration
    if (!$isValid) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Verification code has expired. Please request a new code.'
        ]);
        exit;
    }

    // Mark email verified and clear verification code
    $clearStmt = mysqli_prepare($conn, "UPDATE $tableName SET email_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
    if ($clearStmt) {
        mysqli_stmt_bind_param($clearStmt, "i", $userId);
        mysqli_stmt_execute($clearStmt);
        mysqli_stmt_close($clearStmt);
    }

    // Promote session from pending_auth to fully logged in
    $redirectUrl = 'client/dashboard.php';

    if ($userType === 'admin') {
        $_SESSION['admin']    = $pending['id'];
        $_SESSION['name']     = $pending['name'];
        $_SESSION['username'] = $pending['username'];
        $redirectUrl          = 'admin/dashboard.php';
    } else {
        $_SESSION['user']          = $pending['id'];
        $_SESSION['user_name']     = $pending['name'];
        $_SESSION['user_username'] = $pending['username'];
        $_SESSION['role']          = $pending['role'];

        if (strtolower(trim($pending['role'])) === 'manager') {
            $redirectUrl = 'manager/dashboard.php';
        } else {
            $redirectUrl = 'client/dashboard.php';
        }
    }

    // Clear temporary pending authentication
    unset($_SESSION['pending_auth']);

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Gmail verified successfully! Proceeding to dashboard...',
        'redirect' => $redirectUrl
    ]);
    exit;
}

// ========================================================
// 3. ACTION: Cancel Pending Verification & Logout
// ========================================================
if ($action === 'cancel_pending') {
    $returnUrl = ($userType === 'admin') ? 'index.php' : 'login.php';
    unset($_SESSION['pending_auth']);
    echo json_encode([
        'status'   => 'success',
        'redirect' => $returnUrl
    ]);
    exit;
}

echo json_encode([
    'status'  => 'error',
    'message' => 'Invalid action requested.'
]);
exit;
