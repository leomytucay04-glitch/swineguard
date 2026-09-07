<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/dbcon.php';

// 1. Fetch latest Sensor Data
$sensor_data = [
    'temperature' => '0.0',
    'humidity'    => '0.0',
    'water'       => 'off',
    'date'        => date("Y-m-d"),
    'time'        => date("h:i A")
];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $sensor_query = "SELECT temperature, humidity, water, date, time FROM sensor_data ORDER BY id DESC LIMIT 1";
        $sensor_result = $conn->query($sensor_query);
        if ($sensor_result && $sensor_result->num_rows > 0) {
            $sensor_data = array_merge($sensor_data, $sensor_result->fetch_assoc());
        }
    }
} catch (Throwable $e) {
    // Graceful fallback to default values
}

// 2. Fetch Rules and Bypass Controls safely
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

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $settings_query = "SELECT * FROM settings_rule ORDER BY id DESC LIMIT 1";
        $settings_result = $conn->query($settings_query);
        if ($settings_result && $settings_result->num_rows > 0) {
            $row = $settings_result->fetch_assoc();
            $settings_data = array_merge($settings_data, $row);
        }
    }
} catch (Throwable $e) {
    // Graceful fallback to default rules
}

$temp_val     = floatval($sensor_data['temperature'] ?? 0);
$humidity_val = floatval($sensor_data['humidity'] ?? 0);

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
        return (bool)$auto_state;
    }
}

$is_fan_running     = evalComponentState($settings_data['fan_bypass'] ?? 'AUTO', $auto_fan);
$is_exhaust_running = evalComponentState($settings_data['exhaust_bypass'] ?? 'AUTO', $auto_exhaust);
$is_pump_running    = evalComponentState($settings_data['water_pump_bypass'] ?? 'AUTO', $auto_pump);

echo json_encode([
    'temperature'        => (string)($sensor_data['temperature'] ?? '0.0'),
    'humidity'           => (string)($sensor_data['humidity'] ?? '0.0'),
    'water'              => (string)($sensor_data['water'] ?? 'off'),
    'sensor_date'        => (string)($sensor_data['date'] ?? date("Y-m-d")),
    'sensor_time'        => (string)($sensor_data['time'] ?? date("h:i A")),
    'is_fan_running'     => $is_fan_running,
    'is_exhaust_running' => $is_exhaust_running,
    'is_pump_running'    => $is_pump_running,
    'temp_on'            => $temp_on,
    'temp_off'           => $temp_off,
    'humidity_on'        => $humidity_on,
    'humidity_off'       => $humidity_off,
    'exhaust_bypass'     => (string)($settings_data['exhaust_bypass'] ?? 'AUTO'),
    'water_pump_bypass'  => (string)($settings_data['water_pump_bypass'] ?? 'AUTO')
]);

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?>