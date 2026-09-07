<?php
/**
 * SwineGuard SMS Helper Module (Textbee & Semaphore Gateways)
 * Handles automated SMS alerts for swine monitoring with anti-spam cooldown protection.
 * Default Gateway: Textbee (Android Phone SMS Gateway via personal SIM unli promo).
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
            'phone_number' => '09535919032, 09055823856',
            'api_key' => 'txb_Rh0dtjm5OI7bLCU0JrUH5i7Bil3OkCYq',
            'device_id' => '6a9ce8c9ccb6c72709608e32',
            'gateway' => 'TEXTBEE',
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

/**
 * Format Philippine phone number to E.164 (+639XXXXXXXXX)
 */
if (!function_exists('format_ph_phone_e164')) {
    function format_ph_phone_e164($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
        if (substr($digits, 0, 2) === '63' && strlen($digits) === 12) {
            return '+' . $digits;
        }
        if (substr($digits, 0, 1) === '0' && strlen($digits) === 11) {
            return '+63' . substr($digits, 1);
        }
        if (substr($digits, 0, 1) === '9' && strlen($digits) === 10) {
            return '+63' . $digits;
        }
        return '+' . $digits;
    }
}

/**
 * Send SMS via Textbee Android SMS Gateway
 * Supports single phone number, comma-separated list, or array of numbers
 */
if (!function_exists('send_textbee_sms')) {
    function send_textbee_sms($phoneNumber, $message, $alertType = 'ALERT', $apiKey = null, $deviceId = null)
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($apiKey)) {
            $apiKey = $config['api_key'] ?? 'txb_Rh0dtjm5OI7bLCU0JrUH5i7Bil3OkCYq';
        }
        if (empty($deviceId)) {
            $deviceId = $config['device_id'] ?? '6a9ce8c9ccb6c72709608e32';
        }

        // Parse one or more recipients
        $rawRecipients = is_array($phoneNumber) ? $phoneNumber : explode(',', (string)$phoneNumber);
        $formattedRecipients = [];
        $displayNumbers = [];

        foreach ($rawRecipients as $raw) {
            $clean = trim($raw);
            if (!empty($clean)) {
                $e164 = format_ph_phone_e164($clean);
                if (!empty($e164) && strlen($e164) >= 11) {
                    $formattedRecipients[] = $e164;
                    $displayNumbers[] = $clean;
                }
            }
        }

        if (empty($formattedRecipients)) {
            return [
                'success' => false,
                'message' => 'No valid destination phone numbers provided.'
            ];
        }

        $payload = [
            'recipients' => $formattedRecipients,
            'message'    => $message
        ];

        if (!empty($deviceId)) {
            $payload['deviceId'] = $deviceId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.textbee.dev/api/v1/gateway/send-sms");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

            if ($httpCode === 200 || $httpCode === 201) {
                if (isset($jsonResp['data']['success']) && $jsonResp['data']['success'] === true) {
                    $success = true;
                    $statusStr = 'SUCCESS';
                } elseif (isset($jsonResp['success']) && $jsonResp['success'] === true) {
                    $success = true;
                    $statusStr = 'SUCCESS';
                } else {
                    $success = true;
                    $statusStr = 'QUEUED';
                }
            } else {
                $statusStr = 'FAILED';
            }
        }

        // Log SMS attempt in database
        if ($conn) {
            $loggedNumbers = implode(', ', $displayNumbers);
            $stmt = $conn->prepare("INSERT INTO sms_alerts_log (phone_number, message, alert_type, status, response) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $loggedNumbers, $message, $alertType, $statusStr, $responseStr);
                $stmt->execute();
                $stmt->close();
            }
        }

        return [
            'success' => $success,
            'status'  => $statusStr,
            'message' => $success ? 'SMS dispatched successfully to phone queue.' : 'Textbee Error: ' . $responseStr,
            'raw'     => $responseStr
        ];
    }
}

/**
 * Send SMS via Semaphore (Legacy / Alternative)
 */
if (!function_exists('send_semaphore_sms')) {
    function send_semaphore_sms($phoneNumber, $message, $alertType = 'ALERT', $apiKey = null)
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($apiKey)) {
            $apiKey = '730ab64ad28cb82ace198506beec6218';
        }

        $cleanNumber = preg_replace('/[^0-9]/', '', (string)$phoneNumber);
        if (substr($cleanNumber, 0, 1) === '9' && strlen($cleanNumber) === 10) {
            $cleanNumber = '0' . $cleanNumber;
        }

        $params = [
            'apikey'  => $apiKey,
            'number'  => $cleanNumber,
            'message' => $message
        ];

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

        $success = ($httpCode === 200 || $httpCode === 201);
        $statusStr = $success ? 'SUCCESS' : 'FAILED';
        $responseStr = $curlError ? "Curl Error: $curlError" : (string)$output;

        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO sms_alerts_log (phone_number, message, alert_type, status, response) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $cleanNumber, $message, $alertType, $statusStr, $responseStr);
                $stmt->execute();
                $stmt->close();
            }
        }

        return [
            'success' => $success,
            'status'  => $statusStr,
            'message' => $responseStr,
            'raw'     => $responseStr
        ];
    }
}

/**
 * Universal SMS Dispatcher (routes to Textbee or Semaphore based on config)
 */
if (!function_exists('send_sms')) {
    function send_sms($phoneNumber, $message, $alertType = 'ALERT')
    {
        $config = get_sms_config();
        $gateway = strtoupper($config['gateway'] ?? 'TEXTBEE');

        if ($gateway === 'SEMAPHORE') {
            return send_semaphore_sms($phoneNumber, $message, $alertType, $config['api_key']);
        } else {
            return send_textbee_sms($phoneNumber, $message, $alertType, $config['api_key'], $config['device_id']);
        }
    }
}

/**
 * Automatic Environmental Trigger for ESP32 Sensor Readings
 * Checks Temperature thresholds and Water Float Level.
 * Enforces 10-minute anti-spam cooldown protection.
 */
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
                $msg = "SWINEGUARD CRITICAL ALERT: Pen 1 temperature reached " . number_format($temp, 1) . "°C! Possible heat stress. Urgent cooling is active.";
                send_sms($phone, $msg, 'CRITICAL_TEMP');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_critical_sent = NOW() WHERE id = 1");
                }
            }
        }
        // 2. High Temperature Warning Alert (e.g. >= 30.0 C and < 32.0 C)
        elseif ($temp >= floatval($config['warning_temp'] ?? 30.0)) {
            $lastWarning = !empty($config['last_warning_sent']) ? strtotime($config['last_warning_sent']) : 0;
            if (($now - $lastWarning) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD ALERT: High temperature detected in Pen 1 (" . number_format($temp, 1) . "°C). Automated cooling fan system is active.";
                send_sms($phone, $msg, 'HIGH_TEMP_WARNING');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_warning_sent = NOW() WHERE id = 1");
                }
            }
        }

        // 3. Critical Water Level Alert
        if (strcasecmp($waterStatus, 'Critical') === 0) {
            $lastWater = !empty($config['last_water_sent']) ? strtotime($config['last_water_sent']) : 0;
            if (($now - $lastWater) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD ALERT: Critical water level detected in Pen 1! Float switch is active.";
                send_sms($phone, $msg, 'WATER_CRITICAL');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_water_sent = NOW() WHERE id = 1");
                }
            }
        }
    }
}

/**
 * Trigger SMS Alert when Sensor Malfunction or Disconnection is detected (e.g., 0.0°C readings)
 * Dispatches to both Manager and Client with 10-minute anti-spam cooldown protection.
 */
if (!function_exists('check_and_trigger_sensor_malfunction_alert')) {
    function check_and_trigger_sensor_malfunction_alert()
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($config['is_enabled']) || empty($config['phone_number'])) {
            return;
        }

        $phone = $config['phone_number'];
        $cooldown = intval($config['cooldown_minutes'] ?? 10);
        if ($cooldown < 1) $cooldown = 1;

        $now = time();
        $lastSent = !empty($config['last_sensor_error_sent']) ? strtotime($config['last_sensor_error_sent']) : 0;

        if (($now - $lastSent) >= ($cooldown * 60)) {
            $msg = "SWINEGUARD ALERT: Temperature sensor malfunction or disconnected in Pen 1! Emergency fallback mode is active.";
            send_sms($phone, $msg, 'SENSOR_MALFUNCTION');
            if ($conn) {
                $conn->query("UPDATE sms_alerts_config SET last_sensor_error_sent = NOW() WHERE id = 1");
            }
        }
    }
}

/**
 * Watchdog: Trigger SMS Alert when ESP32 Hardware is Offline / Power Lost (> 5 mins)
 * Dispatches to both Manager and Client with 10-minute anti-spam cooldown protection.
 */
if (!function_exists('check_hardware_offline_watchdog')) {
    function check_hardware_offline_watchdog($timeoutMinutes = 5)
    {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $conn = $GLOBALS['conn'] ?? null;
        }

        $config = get_sms_config();
        if (empty($config['is_enabled']) || empty($config['phone_number'])) {
            return;
        }

        // Check last sensor transmission timestamp
        $lastReceivedTime = null;
        if (!empty($config['last_sensor_received'])) {
            $lastReceivedTime = strtotime($config['last_sensor_received']);
        }

        // Fallback: check latest row in sensor_data if column timestamp is empty
        if (!$lastReceivedTime && $conn) {
            $latestRes = $conn->query("SELECT date, time FROM sensor_data ORDER BY id DESC LIMIT 1");
            if ($latestRes && ($row = $latestRes->fetch_assoc())) {
                $timeStr = trim(($row['date'] ?? '') . ' ' . ($row['time'] ?? ''));
                if (!empty($timeStr)) {
                    $lastReceivedTime = strtotime($timeStr);
                }
            }
        }

        if (!$lastReceivedTime) {
            return; // No telemetry history available to compare
        }

        $now = time();
        $offlineSeconds = $timeoutMinutes * 60;

        // If time elapsed since last reading exceeds timeout threshold
        if (($now - $lastReceivedTime) >= $offlineSeconds) {
            $cooldown = intval($config['cooldown_minutes'] ?? 10);
            if ($cooldown < 1) $cooldown = 1;

            $lastOfflineSent = !empty($config['last_offline_sent']) ? strtotime($config['last_offline_sent']) : 0;
            if (($now - $lastOfflineSent) >= ($cooldown * 60)) {
                $msg = "SWINEGUARD ALERT: Hardware offline or power lost in Pen 1! No sensor signal received. System watchdog is active.";
                send_sms($config['phone_number'], $msg, 'HARDWARE_OFFLINE');
                if ($conn) {
                    $conn->query("UPDATE sms_alerts_config SET last_offline_sent = NOW() WHERE id = 1");
                }
            }
        }
    }
}

