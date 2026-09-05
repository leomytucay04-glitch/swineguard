<?php
header('Content-Type: application/json');

// Include db connection and SMS helper
require_once __DIR__ . '/../../include/dbcon.php';
require_once __DIR__ . '/../../include/sms_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$phone = trim($_POST['phone_number'] ?? '');
$apiKey = trim($_POST['api_key'] ?? '');

$config = get_sms_config();
if (empty($phone)) {
    $phone = $config['phone_number'] ?? '';
}

if (empty($apiKey)) {
    $apiKey = $config['api_key'] ?? '730ab64ad28cb82ace198506beec6218';
}

if (empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid recipient phone number (e.g., 09XXXXXXXXX).'
    ]);
    exit;
}

// Clean phone format
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (substr($cleanPhone, 0, 1) === '9' && strlen($cleanPhone) === 10) {
    $cleanPhone = '0' . $cleanPhone;
}

if (strlen($cleanPhone) < 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid phone number format. Please enter a valid 11-digit Philippine mobile number (09XXXXXXXXX).'
    ]);
    exit;
}

$testMessage = "[SWINEGUARD TEST ALERT] Your Semaphore SMS gateway is connected! Automated heat stress and water level text alerts are now active.";

$result = send_semaphore_sms($cleanPhone, $testMessage, 'TEST_SMS', $apiKey);

// Also fetch account balance/status to return in the UI
$accountInfo = get_semaphore_account_info($apiKey);

$response = [
    'success'        => $result['success'],
    'status'         => $result['status'],
    'message'        => $result['message'],
    'raw_response'   => $result['raw'],
    'account_status' => $accountInfo['status'] ?? 'Unknown',
    'credit_balance' => $accountInfo['credit_balance'] ?? 0
];

echo json_encode($response);
