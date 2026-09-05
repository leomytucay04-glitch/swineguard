<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../include/dbcon.php';
require_once __DIR__ . '/../../include/sms_helper.php';

$apiKey = trim($_GET['api_key'] ?? '');
if (empty($apiKey)) {
    $config = get_sms_config();
    $apiKey = $config['api_key'] ?? '730ab64ad28cb82ace198506beec6218';
}

$info = get_semaphore_account_info($apiKey);
echo json_encode($info);
