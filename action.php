<?php
header('Content-Type: application/json');
include "include/dbcon.php";

date_default_timezone_set('Asia/Manila');

// 1. Fetch rules from settings_rule table
$rule_query = "SELECT temperature_on, temperature_off, humidity_on, humidity_off, heater_on, 
                    fan_bypass, exhaust_bypass, water_pump_bypass, heater_bypass, light_bypass 
               FROM settings_rule ORDER BY id DESC LIMIT 1";
$rule_result = $conn->query($rule_query);

if ($rule_result && $rule_result->num_rows > 0) {
    $rule = $rule_result->fetch_assoc();
} else {
    $rule = [
        'temperature_on'    => '30.0',
        'temperature_off'   => '26.0',
        'humidity_on'       => '60.0',
        'humidity_off'      => '75.0',
        'heater_on'         => '20.0',
        'fan_bypass'        => 'AUTO',
        'exhaust_bypass'    => 'AUTO',
        'water_pump_bypass' => 'AUTO',
        'heater_bypass'     => 'AUTO',
        'light_bypass'      => 'AUTO'
    ];
}

$fan_bypass     = strtoupper($rule['fan_bypass'] ?? 'AUTO');
$exhaust_bypass = strtoupper($rule['exhaust_bypass'] ?? 'AUTO');
$pump_bypass    = strtoupper($rule['water_pump_bypass'] ?? 'AUTO');
$heater_bypass  = strtoupper($rule['heater_bypass'] ?? 'AUTO');
$light_bypass   = strtoupper($rule['light_bypass'] ?? 'AUTO');

$temp      = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.0;
$humidity  = isset($_POST['humidity']) ? floatval($_POST['humidity']) : 0.0;
$light_val = isset($_POST['light_value']) ? floatval($_POST['light_value']) : 0.0;

$temp_on   = floatval($rule['temperature_on']);
$temp_off  = floatval($rule['temperature_off']);
$hum_on    = floatval($rule['humidity_on']);
$hum_off   = floatval($rule['humidity_off']);
$heater_on = floatval($rule['heater_on'] ?? 20.0);

// 2. Fetch Previous State
$prev_fan     = 'off';
$prev_exhaust = 'off';
$prev_pump    = 'off';
$prev_heater  = 'off';
$prev_light   = 'off';

$status_check_sql = "SELECT fan, exhaust, water_pump, heater, light FROM machine_status WHERE id = 1";
$status_check_res = $conn->query($status_check_sql);
if ($status_check_res && $status_check_res->num_rows > 0) {
    $row = $status_check_res->fetch_assoc();
    $prev_fan     = strtolower($row['fan'] ?? 'off');
    $prev_exhaust = strtolower($row['exhaust'] ?? 'off');
    $prev_pump    = strtolower($row['water_pump'] ?? 'off');
    $prev_heater  = strtolower($row['heater'] ?? 'off');
    $prev_light   = strtolower($row['light'] ?? 'off');
}

// 3. Cooling Fan Logic
if ($fan_bypass === 'FORCE_ON') {
    $new_cooling_status = 'on';
} elseif ($fan_bypass === 'FORCE_OFF') {
    $new_cooling_status = 'off';
} else {
    $new_cooling_status = $prev_fan;
    if ($temp >= $temp_on) {
        $new_cooling_status = 'on';
    } else if ($temp <= $temp_off) {
        $new_cooling_status = 'off';
    }
}

// 4. Exhaust Fan Logic
if ($exhaust_bypass === 'FORCE_ON') {
    $new_exhaust_status = 'on';
} elseif ($exhaust_bypass === 'FORCE_OFF') {
    $new_exhaust_status = 'off';
} else {
    $new_exhaust_status = $prev_exhaust;
    if ($temp >= $temp_on) {
        $new_exhaust_status = 'on';
    } else if ($temp <= $temp_off) {
        $new_exhaust_status = 'off';
    }
}

// 5. Water Pump Logic
if ($pump_bypass === 'FORCE_ON') {
    $new_pump_status = 'on';
} elseif ($pump_bypass === 'FORCE_OFF') {
    $new_pump_status = 'off';
} else {
    $new_pump_status = $prev_pump;
    if ($humidity <= $hum_on) {
        $new_pump_status = 'on';
    } else if ($humidity >= $hum_off) {
        $new_pump_status = 'off';
    }
}

// 6. Heating Lamp Logic (Single Threshold - No heater_off used)
if ($heater_bypass === 'FORCE_ON') {
    $new_heater_status = 'on';
} elseif ($heater_bypass === 'FORCE_OFF') {
    $new_heater_status = 'off';
} else {
    if ($temp <= $heater_on) {
        $new_heater_status = 'on';
    } else {
        $new_heater_status = 'off';
    }
}

// 7. Bug Light Logic
if ($light_bypass === 'FORCE_ON') {
    $new_light_status = 'on';
} elseif ($light_bypass === 'FORCE_OFF') {
    $new_light_status = 'off';
} else {
    $new_light_status = ($light_val == 1) ? 'on' : 'off';
}

// 8. Update Machine Status
$update_sql = "INSERT INTO machine_status (id, fan, exhaust, water_pump, heater, light) 
               VALUES (1, '$new_cooling_status', '$new_exhaust_status', '$new_pump_status', '$new_heater_status', '$new_light_status')
               ON DUPLICATE KEY UPDATE 
               fan = '$new_cooling_status', 
               exhaust = '$new_exhaust_status', 
               water_pump = '$new_pump_status',
               heater = '$new_heater_status',
               light = '$new_light_status'";
$conn->query($update_sql);

// 9. Logging
if ($prev_fan !== $new_cooling_status || 
    $prev_exhaust !== $new_exhaust_status || 
    $prev_pump !== $new_pump_status || 
    $prev_heater !== $new_heater_status ||
    $prev_light !== $new_light_status) {
    
    $current_date = date("Y-m-d");
    $current_time = date("H:i:s");

    $log_sql = "INSERT INTO machine_logs (fan, exhaust, water_pump, heater, light, date, time) 
                VALUES ('$new_cooling_status', '$new_exhaust_status', '$new_pump_status', '$new_heater_status', '$new_light_status', '$current_date', '$current_time')";
    $conn->query($log_sql);
}

echo json_encode([
    'cooling' => $new_cooling_status,
    'exhaust' => $new_exhaust_status,
    'pump'    => $new_pump_status,
    'heater'  => $new_heater_status,
    'light'   => $new_light_status
]);

$conn->close();
?>