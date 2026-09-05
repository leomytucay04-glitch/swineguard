<?php
require_once __DIR__ . '/../../include/dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize Automation Rule POST inputs
    $temp_on   = isset($_POST['fan_trigger_temp']) ? mysqli_real_escape_string($conn, $_POST['fan_trigger_temp']) : '';
    $temp_off  = isset($_POST['fan_stop_temp']) ? mysqli_real_escape_string($conn, $_POST['fan_stop_temp']) : '';
    $humid_on  = isset($_POST['pump_trigger_humidity']) ? mysqli_real_escape_string($conn, $_POST['pump_trigger_humidity']) : '';
    $humid_off = isset($_POST['pump_stop_humidity']) ? mysqli_real_escape_string($conn, $_POST['pump_stop_humidity']) : '';
    $heater_on = isset($_POST['heater_trigger_temp']) ? mysqli_real_escape_string($conn, $_POST['heater_trigger_temp']) : '';

    // Collect and sanitize Bypass / Emergency controls
    $fan_bypass     = isset($_POST['fan_bypass']) ? mysqli_real_escape_string($conn, $_POST['fan_bypass']) : 'AUTO';
    $exhaust_bypass = isset($_POST['exhaust_bypass']) ? mysqli_real_escape_string($conn, $_POST['exhaust_bypass']) : 'AUTO';
    $pump_bypass    = isset($_POST['water_pump_bypass']) ? mysqli_real_escape_string($conn, $_POST['water_pump_bypass']) : 'AUTO';
    $heater_bypass  = isset($_POST['heater_bypass']) ? mysqli_real_escape_string($conn, $_POST['heater_bypass']) : 'AUTO';

    // Save / Update settings_rule (id = 1)
    $check_query = "SELECT id FROM settings_rule WHERE id = 1";
    $result = $conn->query($check_query);

    if ($result && $result->num_rows > 0) {
        $query = "UPDATE settings_rule SET 
                  temperature_on = '$temp_on', 
                  temperature_off = '$temp_off', 
                  humidity_on = '$humid_on', 
                  humidity_off = '$humid_off',
                  heater_on = '$heater_on',
                  fan_bypass = '$fan_bypass',
                  exhaust_bypass = '$exhaust_bypass',
                  water_pump_bypass = '$pump_bypass',
                  heater_bypass = '$heater_bypass'
                  WHERE id = 1";
    } else {
        $query = "INSERT INTO settings_rule (id, temperature_on, temperature_off, humidity_on, humidity_off, heater_on, fan_bypass, exhaust_bypass, water_pump_bypass, heater_bypass) 
                  VALUES (1, '$temp_on', '$temp_off', '$humid_on', '$humid_off', '$heater_on', '$fan_bypass', '$exhaust_bypass', '$pump_bypass', '$heater_bypass')";
    }

    if (!$conn->query($query)) {
        echo "Failed to save automation rules: " . $conn->error;
        $conn->close();
        exit;
    }

    // Save / Update sms_alerts_config (id = 1) if SMS fields are posted
    if (isset($_POST['sms_phone_number']) || isset($_POST['sms_api_key']) || isset($_POST['sms_warning_temp'])) {
        $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
        $sms_phone   = mysqli_real_escape_string($conn, trim($_POST['sms_phone_number'] ?? ''));
        $sms_api_key = mysqli_real_escape_string($conn, trim($_POST['sms_api_key'] ?? '730ab64ad28cb82ace198506beec6218'));
        $sms_sender  = mysqli_real_escape_string($conn, trim($_POST['sms_sender_name'] ?? ''));
        $sms_warning = floatval($_POST['sms_warning_temp'] ?? 30.0);
        $sms_crit    = floatval($_POST['sms_critical_temp'] ?? 32.0);
        $sms_cool    = intval($_POST['sms_cooldown_minutes'] ?? 10);
        if ($sms_cool < 1) $sms_cool = 1;

        $check_sms = "SELECT id FROM sms_alerts_config WHERE id = 1";
        $res_sms = $conn->query($check_sms);

        if ($res_sms && $res_sms->num_rows > 0) {
            $sms_query = "UPDATE sms_alerts_config SET 
                          phone_number = '$sms_phone',
                          api_key = '$sms_api_key',
                          sender_name = '$sms_sender',
                          is_enabled = '$sms_enabled',
                          warning_temp = '$sms_warning',
                          critical_temp = '$sms_crit',
                          cooldown_minutes = '$sms_cool'
                          WHERE id = 1";
        } else {
            $sms_query = "INSERT INTO sms_alerts_config (id, phone_number, api_key, sender_name, is_enabled, warning_temp, critical_temp, cooldown_minutes)
                          VALUES (1, '$sms_phone', '$sms_api_key', '$sms_sender', '$sms_enabled', '$sms_warning', '$sms_crit', '$sms_cool')";
        }

        if (!$conn->query($sms_query)) {
            echo "Failed to save SMS alert settings: " . $conn->error;
            $conn->close();
            exit;
        }
    }

    echo "success";
} else {
    echo "Invalid Request Method.";
}

$conn->close();
?>