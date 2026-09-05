<?php
/**
 * SwineGuard SMS Helper Module (Semaphore API)
 * Handles automated SMS alerts for swine monitoring with anti-spam cooldown protection.
 */

require_once __DIR__ . '/dbcon.php';

if (!function_exists('get_sms_config')) {
    function get_sms_config()
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $default = [
            'id' => 1,
            'phone_number' => '',
            'api_key' => '730ab64ad28cb82ace198506beec6218',
            'sender_name' => '',
            'is_enabled' => 1,
            'warning_temp' => 30.0,
            'critical_temp' => 32.0,
            'cooldown_minutes' => 10,
            'last_warning_sent' => null,
            'last_critical_sent' => null,
            'last_water_sent' => null
        ];

        if (!$conn) return $default;

        $res = $conn->query("SELECT * FROM sms_alerts_config WHERE id = 1 LIMIT 1");
        if ($res && $res->num_rows > 0) {
            return array_merge($default, $res->fetch_assoc());
        }

        return $default;
    }
}

if (!function_exists('get_semaphore_account_info')) {
    function get_semaphore_account_info($apiKey = null)
    {
        if (empty($apiKey)) {
            $config = get_sms_config();
            $apiKey = $config['api_key'];
        }

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'No Semaphore API key provided.'];
        }

        $url = "https://api.semaphore.co/api/v4/account?apikey=" . urlencode($apiKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'Curl Error: ' . $err];
        }

        $data = json_decode($response, true);
        if ($httpCode === 200 && is_array($data)) {
            return [
                'success'        => true,
                'account_id'     => $data['account_id'] ?? '',
                'account_name'   => $data['account_name'] ?? '',
                'status'         => $data['status'] ?? 'Unknown',
                'credit_balance' => $data['credit_balance'] ?? 0,
                'raw'            => $data
            ];
        }

        return [
            'success' => false,
            'message' => 'API returned HTTP ' . $httpCode,
            'raw'     => $response
        ];
    }
}

if (!function_exists('send_semaphore_sms')) {
    function send_semaphore_sms($phoneNumber, $message, $alertType = 'ALERT', $apiKey = null)
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($apiKey)) {
            $apiKey = $config['api_key'] ?? '730ab64ad28cb82ace198506beec6218';
        }

        // Clean phone number format
        $cleanNumber = preg_replace('/[^0-9]/', '', (string)$phoneNumber);
        if (substr($cleanNumber, 0, 1) === '9' && strlen($cleanNumber) === 10) {
            $cleanNumber = '0' . $cleanNumber;
        }

        if (empty($cleanNumber) || strlen($cleanNumber) < 10) {
            return [
                'success' => false,
                'message' => 'Invalid destination phone number provided.'
            ];
        }

        $params = [
            'apikey'  => $apiKey,
            'number'  => $cleanNumber,
            'message' => $message
        ];

        if (!empty($config['sender_name'])) {
            $params['sendername'] = $config['sender_name'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/messages");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = false;
        $statusStr = 'FAILED';

        if ($curlError) {
            $responseStr = "Curl Error: " . $curlError;
        } else {
            $responseStr = (string)$output;
            $jsonResp = json_decode($output, true);

            // Semaphore returns an array of message objects on success e.g. [{"message_id": ...}]
            if ($httpCode === 200 || $httpCode === 201) {
                if (is_array($jsonResp) && !empty($jsonResp) && isset($jsonResp[0]['message_id'])) {
                    $success = true;
                    $statusStr = 'SUCCESS';
                } elseif (is_array($jsonResp) && isset($jsonResp['status']) && strtolower($jsonResp['status']) === 'success') {
                    $success = true;
                    $statusStr = 'SUCCESS';
                } else {
                    $statusStr = 'PENDING_OR_QUEUED';
                    $success = true;
                }
            } else {
                if (strpos($output, 'balance') !== false || strpos($output, 'credit') !== false) {
                    $statusStr = 'INSUFFICIENT_CREDITS';
                } elseif (strpos($output, 'Pending') !== false) {
                    $statusStr = 'ACCOUNT_PENDING';
                } else {
                    $statusStr = 'FAILED';
                }
            }
        }

        // Log the SMS attempt into database
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO sms_alerts_log (phone_number, message, alert_type, status, response) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $cleanNumber, $message, $alertType, $statusStr, $responseStr);
                $stmt->execute();
                $stmt->close();
            }
        }

        return [
            'success'   => $success,
            'status'    => $statusStr,
            'message'   => $success ? 'SMS dispatched successfully.' : 'Semaphore API returned: ' . $responseStr,
            'raw'       => $responseStr
        ];
    }
}

if (!function_exists('check_and_trigger_sms_alerts')) {
    function check_and_trigger_sms_alerts($temperature, $humidity, $water)
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($config['is_enabled']) || empty($config['phone_number'])) {
            return; // Alerts disabled or no phone number configured
        }

        $phone = $config['phone_number'];
        $temp = floatval($temperature);
        $waterStatus = trim((string)$water);
        $cooldown = intval($config['cooldown_minutes'] ?? 10);
        if ($cooldown < 1) $cooldown = 1;

        $now = time();

        // 1. Critical Temperature Alert (e.g. >= 32.0 C)
        $criticalThreshold = floatval($config['critical_temp'] ?? 32.0);
        if ($temp >= $criticalThreshold) {
            $lastCritical = !empty($config['last_critical_sent']) ? strtotime($config['last_critical_sent']) : 0;
            if (($now - $lastCritical) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD CRITICAL ALERT: Pen 1 temperature reached " . number_format($temp, 1) . "°C! Possible heat stress. Urgent inspection and cooling required!";
                send_semaphore_sms($phone, $msg, 'CRITICAL_TEMP');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_critical_sent = NOW() WHERE id = 1");
                }
            }
        }
        // 2. High Temperature Warning Alert (e.g. >= 30.0 C and < 32.0 C)
        elseif ($temp >= floatval($config['warning_temp'] ?? 30.0)) {
            $lastWarning = !empty($config['last_warning_sent']) ? strtotime($config['last_warning_sent']) : 0;
            if (($now - $lastWarning) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD ALERT: High temperature detected in Pen 1 (" . number_format($temp, 1) . "°C). Automated cooling fan system activated.";
                send_semaphore_sms($phone, $msg, 'HIGH_TEMP_WARNING');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_warning_sent = NOW() WHERE id = 1");
                }
            }
        }

        // 3. Critical Water Level Alert
        if (strcasecmp($waterStatus, 'Critical') === 0) {
            $lastWater = !empty($config['last_water_sent']) ? strtotime($config['last_water_sent']) : 0;
            if (($now - $lastWater) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD ALERT: Critical water level detected in Pen 1! Float switch activated. Please check water tank and refill supply.";
                send_semaphore_sms($phone, $msg, 'WATER_CRITICAL');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_water_sent = NOW() WHERE id = 1");
                }
            }
        }
    }
}
