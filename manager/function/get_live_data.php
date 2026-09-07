<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/dbcon.php';

// 1. Trigger hardware offline watchdog if ESP32 has stopped transmitting (> 5 minutes)
require_once __DIR__ . '/../../include/sms_helper.php';
if (function_exists('check_hardware_offline_watchdog')) {
    check_hardware_offline_watchdog(5);
}

// 2. Fetch latest Sensor Data
$sensor_query = "SELECT temperature, humidity, water, date, time FROM sensor_data ORDER BY id DESC LIMIT 1";
$sensor_result = $conn->query($sensor_query);

if ($sensor_result && $sensor_result->num_rows > 0) {
    $sensor_data = $sensor_result->fetch_assoc();
} else {
    $current_date = date("Y-m-d");
    $current_time = date("h:i A");
    $sensor_data = [
        'temperature' => '0.0',
        'humidity'    => '0.0',
        'water'       => 'off',
        'date'        => $current_date,
        'time'        => $current_time
    ];
}

// 3. Fetch Rules and Bypass Controls directly from settings_rule
$settings_query = "SELECT temperature_on, temperature_off, humidity_on, humidity_off, heater_on, fan_bypass, exhaust_bypass, water_pump_bypass, heater_bypass FROM settings_rule ORDER BY id DESC LIMIT 1";
$settings_result = $conn->query($settings_query);

if ($settings_result && $settings_result->num_rows > 0) {
    $settings_data = $settings_result->fetch_assoc();
} else {
    $settings_data = [
        'temperature_on'    => '30.0',
        'temperature_off'   => '26.0',
        'humidity_on'       => '60.0',
        'humidity_off'      => '75.0',
        'heater_on'         => '20.0',
        'fan_bypass'        => 'AUTO',
        'exhaust_bypass'    => 'AUTO',
        'water_pump_bypass' => 'AUTO',
        'heater_bypass'     => 'AUTO'
    ];
}

$temp_val     = floatval($sensor_data['temperature']);
$humidity_val = floatval($sensor_data['humidity']);

$temp_on      = floatval($settings_data['temperature_on'] ?? 30.0);
$temp_off     = floatval($settings_data['temperature_off'] ?? 26.0);
$humidity_on  = floatval($settings_data['humidity_on'] ?? 60.0);
$humidity_off = floatval($settings_data['humidity_off'] ?? 75.0);

// Evaluate Auto Modes based on Thresholds
$auto_fan     = ($temp_val >= $temp_on);
$auto_exhaust = ($temp_val >= $temp_on);
$auto_pump    = ($humidity_val <= $humidity_on);

// Function to handle FORCE_ON / FORCE_OFF / AUTO logic
if (!function_exists('evalComponentState')) {
    function evalComponentState($bypass_mode, $auto_state) {
        $mode = strtoupper(trim((string)$bypass_mode));
        if ($mode === 'FORCE_ON') return true;
        if ($mode === 'FORCE_OFF') return false;
        return (bool)$auto_state; // Defaults to AUTO mode rule
    }
}

$is_fan_running     = evalComponentState($settings_data['fan_bypass'] ?? 'AUTO', $auto_fan);
$is_exhaust_running = evalComponentState($settings_data['exhaust_bypass'] ?? 'AUTO', $auto_exhaust);
$is_pump_running    = evalComponentState($settings_data['water_pump_bypass'] ?? 'AUTO', $auto_pump);

echo json_encode([
    'temperature'        => $sensor_data['temperature'],
    'humidity'           => $sensor_data['humidity'],
    'water'              => $sensor_data['water'],
    'sensor_date'        => $sensor_data['date'],
    'sensor_time'        => $sensor_data['time'],
    'is_fan_running'     => $is_fan_running,
    'is_exhaust_running' => $is_exhaust_running,
    'is_pump_running'    => $is_pump_running,
    'temp_on'            => $temp_on,
    'temp_off'           => $temp_off,
    'humidity_on'        => $humidity_on,
    'humidity_off'       => $humidity_off,
    'exhaust_bypass'     => $settings_data['exhaust_bypass'] ?? 'AUTO',
    'water_pump_bypass'  => $settings_data['water_pump_bypass'] ?? 'AUTO'
]);

$conn->close();
?>