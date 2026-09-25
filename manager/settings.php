<?php
include "out.php";
?>
<?php
// Establish connection to database
include "../include/dbcon.php";

// Fetch existing rule thresholds & bypass modes (excluding unused exhaust variable)
$temp_on = $temp_off = $humid_on = $humid_off = $heater_on = "";
$fan_bypass = $pump_bypass = $heater_bypass = "AUTO";

$get_rules = $conn->query("SELECT * FROM settings_rule WHERE id = 1");
if ($get_rules && $get_rules->num_rows > 0) {
    $rule = $get_rules->fetch_assoc();
    $temp_on        = $rule['temperature_on'] ?? '';
    $temp_off       = $rule['temperature_off'] ?? '';
    $humid_on       = $rule['humidity_on'] ?? '';
    $humid_off      = $rule['humidity_off'] ?? '';
    $heater_on      = $rule['heater_on'] ?? '';
    $fan_bypass     = $rule['fan_bypass'] ?? 'AUTO';
    $pump_bypass    = $rule['water_pump_bypass'] ?? 'AUTO';
    $heater_bypass  = $rule['heater_bypass'] ?? 'AUTO';
}

// Fetch latest live telemetry for immediate accurate rendering
$init_temp = '--.-';
$init_humid = '--.-';
$init_temp_class = 'text-green';
$init_temp_icon = 'fa-circle-check';
$init_temp_text = 'Optimal';
$init_humid_class = 'text-green';
$init_humid_icon = 'fa-circle-check';
$init_humid_text = 'Normal';

$sensor_res = $conn->query("SELECT temperature, humidity FROM sensor_data ORDER BY id DESC LIMIT 1");
if ($sensor_res && $sensor_res->num_rows > 0) {
    $s_row = $sensor_res->fetch_assoc();
    $init_temp = $s_row['temperature'] ?? '--.-';
    $init_humid = $s_row['humidity'] ?? '--.-';
    
    $f_temp = floatval($init_temp);
    $f_humid = floatval($init_humid);
    $f_temp_on = floatval($temp_on ?: 30.0);
    $f_temp_off = floatval($temp_off ?: 26.0);
    $f_humid_on = floatval($humid_on ?: 60.0);
    $f_humid_off = floatval($humid_off ?: 75.0);

    if ($f_temp >= $f_temp_on) {
        $init_temp_class = 'text-red';
        $init_temp_icon = 'fa-triangle-exclamation';
        $init_temp_text = 'High (Above ' . $f_temp_on . '°C)';
    } elseif ($f_temp <= $f_temp_off) {
        $init_temp_class = 'text-amber';
        $init_temp_icon = 'fa-temperature-arrow-down';
        $init_temp_text = 'Low (Below ' . $f_temp_off . '°C)';
    } else {
        $init_temp_class = 'text-green';
        $init_temp_icon = 'fa-circle-check';
        $init_temp_text = 'Optimal (' . $f_temp_off . ' - ' . $f_temp_on . '°C)';
    }

    if ($f_humid < $f_humid_on) {
        $init_humid_class = 'text-amber';
        $init_humid_icon = 'fa-droplet-slash';
        $init_humid_text = 'Low (Below ' . $f_humid_on . '%)';
    } elseif ($f_humid > $f_humid_off) {
        $init_humid_class = 'text-red';
        $init_humid_icon = 'fa-triangle-exclamation';
        $init_humid_text = 'High (Above ' . $f_humid_off . '%)';
    } else {
        $init_humid_class = 'text-green';
        $init_humid_icon = 'fa-circle-check';
        $init_humid_text = 'Normal (' . $f_humid_on . ' - ' . $f_humid_off . '%)';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Settings - Swine Guard Manager</title>

    <link rel="stylesheet" href="../include/fonts.css">
    <link rel="stylesheet" href="../include/bootstrap.css">
    <link rel="stylesheet" href="../include/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="../include/animate.min.css">
    <link rel="stylesheet" href="../include/theme.css">
    <script src="../include/bootstrap.js"></script>
    <script src="../include/sweetalert.js"></script>
    <script src="../include/popper.js"></script>
    <script src="../include/chart.js"></script>
    <script src="../include/jquery.js"></script>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="sg-layout">
        <main class="sg-main animate__animated animate__fadeIn">
            
            <!-- Page Header -->
            <div class="sg-page-header">
                <div>
                    <h1 class="sg-page-title"><i class="fa-solid fa-sliders text-purple me-2"></i>Automation Configuration</h1>
                    <p class="sg-page-subtitle">Configure autonomous climate thresholds, relay triggers, and emergency hardware overrides</p>
                </div>
                <div class="d-none d-md-flex align-items-center gap-2">
                    <span class="sg-zone-badge sg-zone-optimal">
                        <i class="fa-solid fa-circle-check"></i> System Autonomous Mode
                    </span>
                </div>
            </div>

            <!-- Smart Automation Info Guide -->
            <div class="sg-card mb-4" style="border-left: 3px solid var(--purple); background: linear-gradient(90deg, rgba(124, 58, 237, 0.08) 0%, transparent 100%);">
                <div class="d-flex align-items-start gap-3">
                    <div class="sg-icon-box sg-icon-fan mt-1">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="sg-card-title fs-5 m-0 mb-1">Autonomous Microcontroller Telemetry Engine</h4>
                        <p class="text-sg-muted m-0 small">
                            When pen sensors detect temperature or humidity crossing defined thresholds, command payloads are broadcasted to hardware relays in real-time. Use <strong>FORCE ON</strong> or <strong>FORCE OFF</strong> bypass modes during maintenance or sensor calibration.
                        </p>
                    </div>
                </div>
            </div>

            <form id="settingsForm">

                <!-- 1. Cooling Fan Regulation Card -->
                <div class="sg-settings-card mb-4">
                    <div class="sg-settings-card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sg-icon-box sg-icon-fan m-0">
                                <i class="fa-solid fa-fan"></i>
                            </div>
                            <div>
                                <h3 class="sg-card-title fs-5 m-0">Cooling Fan Regulation</h3>
                                <p class="text-sg-muted small m-0">Thermal hysteresis boundaries for pen ventilation</p>
                            </div>
                        </div>
                        <span class="sg-badge sg-badge-purple"><i class="fa-solid fa-temperature-half me-1"></i>Thermal Trigger</span>
                    </div>

                    <div class="sg-settings-card-body">
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-lg-8">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="sg-form-label d-flex justify-content-between">
                                            <span>Activation Threshold</span>
                                            <span class="text-red fw-bold">&ge; Turn ON</span>
                                        </label>
                                        <div class="sg-input-unit-wrap">
                                            <input type="number" step="0.1" class="form-control" name="fan_trigger_temp" value="<?php echo htmlspecialchars($temp_on); ?>" placeholder="e.g. 28.0" required>
                                            <span class="sg-input-unit">&deg;C</span>
                                        </div>
                                        <small class="text-sg-muted d-block mt-1">Fans turn ON when pen heats to this degree</small>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="sg-form-label d-flex justify-content-between">
                                            <span>Deactivation Target</span>
                                            <span class="text-cyan fw-bold">&le; Turn OFF</span>
                                        </label>
                                        <div class="sg-input-unit-wrap">
                                            <input type="number" step="0.1" class="form-control" name="fan_stop_temp" value="<?php echo htmlspecialchars($temp_off); ?>" placeholder="e.g. 25.5" required>
                                            <span class="sg-input-unit">&deg;C</span>
                                        </div>
                                        <small class="text-sg-muted d-block mt-1">Fans shut down once cooled to target</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Temperature Telemetry -->
                            <div class="col-12 col-lg-4">
                                <div class="live-inline-card">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="live-icon-temp">
                                            <i class="fa-solid fa-temperature-half"></i>
                                        </div>
                                        <div>
                                            <div class="sg-metric-label">Live Pen Temperature</div>
                                            <h2 class="sg-metric-value m-0 text-red" id="live_temp"><?php echo htmlspecialchars($init_temp); ?>&deg;C</h2>
                                        </div>
                                    </div>
                                    <div class="<?php echo $init_temp_class; ?> small fw-bold d-flex align-items-center gap-2 mt-1" id="temp_status_wrap">
                                        <i class="fa-solid <?php echo $init_temp_icon; ?>"></i> <span id="temp_status"><?php echo htmlspecialchars($init_temp_text); ?></span>
                                    </div>
                                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 text-sg-muted" style="font-size: 0.72rem;">
                                        <i class="fa-regular fa-clock me-1"></i>Last updated: <strong class="text-white last-updated-text">Loading...</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Water Pump & Misting Regulation Card -->
                <div class="sg-settings-card mb-4">
                    <div class="sg-settings-card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sg-icon-box sg-icon-pump m-0">
                                <i class="fa-solid fa-faucet-drip"></i>
                            </div>
                            <div>
                                <h3 class="sg-card-title fs-5 m-0">Water Pump &amp; Misting Regulation</h3>
                                <p class="text-sg-muted small m-0">Relative humidity boundaries to regulate air moisture</p>
                            </div>
                        </div>
                        <span class="sg-badge sg-badge-cyan"><i class="fa-solid fa-droplet me-1"></i>Humidity Trigger</span>
                    </div>

                    <div class="sg-settings-card-body">
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-lg-8">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="sg-form-label d-flex justify-content-between">
                                            <span>Activation Threshold</span>
                                            <span class="text-cyan fw-bold">&le; Turn ON</span>
                                        </label>
                                        <div class="sg-input-unit-wrap">
                                            <input type="number" step="0.1" class="form-control" name="pump_trigger_humidity" value="<?php echo htmlspecialchars($humid_on); ?>" placeholder="e.g. 60.0" required>
                                            <span class="sg-input-unit">%</span>
                                        </div>
                                        <small class="text-sg-muted d-block mt-1">Misting begins when air dries below this level</small>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="sg-form-label d-flex justify-content-between">
                                            <span>Deactivation Target</span>
                                            <span class="text-green fw-bold">&ge; Turn OFF</span>
                                        </label>
                                        <div class="sg-input-unit-wrap">
                                            <input type="number" step="0.1" class="form-control" name="pump_stop_humidity" value="<?php echo htmlspecialchars($humid_off); ?>" placeholder="e.g. 75.0" required>
                                            <span class="sg-input-unit">%</span>
                                        </div>
                                        <small class="text-sg-muted d-block mt-1">Misting cuts off when moisture target is satisfied</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Humidity Telemetry -->
                            <div class="col-12 col-lg-4">
                                <div class="live-inline-card">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="live-icon-humid">
                                            <i class="fa-solid fa-droplet"></i>
                                        </div>
                                        <div>
                                            <div class="sg-metric-label">Live Pen Humidity</div>
                                            <h2 class="sg-metric-value m-0 text-cyan" id="live_humid"><?php echo htmlspecialchars($init_humid); ?>%</h2>
                                        </div>
                                    </div>
                                    <div class="<?php echo $init_humid_class; ?> small fw-bold d-flex align-items-center gap-2 mt-1" id="humid_status_wrap">
                                        <i class="fa-solid <?php echo $init_humid_icon; ?>"></i> <span id="humid_status"><?php echo htmlspecialchars($init_humid_text); ?></span>
                                    </div>
                                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 text-sg-muted" style="font-size: 0.72rem;">
                                        <i class="fa-regular fa-clock me-1"></i>Last updated: <strong class="text-white last-updated-text">Loading...</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Hardware Bypass & Emergency Overrides -->
                <div class="sg-settings-card mb-4" style="border-left: 3px solid var(--amber);">
                    <div class="sg-settings-card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sg-icon-box sg-icon-warning m-0">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h3 class="sg-card-title fs-5 m-0">Hardware Bypass &amp; Emergency Overrides</h3>
                                <p class="text-sg-muted small m-0">Manual relay overrides for maintenance, emergency ventilation, or sensor failures</p>
                            </div>
                        </div>
                        <span class="sg-badge sg-badge-amber"><i class="fa-solid fa-hand me-1"></i>Manual Controls</span>
                    </div>

                    <div class="sg-settings-card-body">
                        <div class="row g-4">
                            <!-- Cooling Fan Bypass Mode -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 rounded-3" style="background: var(--surface2); border: 1px solid var(--border);">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-fan text-purple"></i>
                                            <strong class="text-white">Cooling Fan Mode</strong>
                                        </div>
                                        <span class="small text-sg-muted font-monospace">RELAY #1</span>
                                    </div>
                                    <div class="sg-bypass-pill-group">
                                        <input type="radio" class="sg-bypass-radio" name="fan_bypass" id="fan_auto" value="AUTO" <?php echo ($fan_bypass == 'AUTO') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="fan_auto"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>AUTO</label>

                                        <input type="radio" class="sg-bypass-radio" name="fan_bypass" id="fan_force_on" value="FORCE_ON" <?php echo ($fan_bypass == 'FORCE_ON') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="fan_force_on"><i class="fa-solid fa-bolt me-1"></i>FORCE ON</label>

                                        <input type="radio" class="sg-bypass-radio" name="fan_bypass" id="fan_force_off" value="FORCE_OFF" <?php echo ($fan_bypass == 'FORCE_OFF') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="fan_force_off"><i class="fa-solid fa-power-off me-1"></i>FORCE OFF</label>
                                    </div>
                                    <p class="text-sg-muted small m-0 mt-2">
                                        <?php if($fan_bypass == 'AUTO'): ?>
                                            Operated automatically based on preset temperature targets.
                                        <?php elseif($fan_bypass == 'FORCE_ON'): ?>
                                            <span class="text-green fw-semibold">Continuous Operation:</span> Running continuously without sensor dependency.
                                        <?php else: ?>
                                            <span class="text-red fw-semibold">Emergency Cutoff:</span> Kept strictly powered off.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Water Pump Bypass Mode -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 rounded-3" style="background: var(--surface2); border: 1px solid var(--border);">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-faucet-drip text-cyan"></i>
                                            <strong class="text-white">Water Pump Mode</strong>
                                        </div>
                                        <span class="small text-sg-muted font-monospace">RELAY #2</span>
                                    </div>
                                    <div class="sg-bypass-pill-group">
                                        <input type="radio" class="sg-bypass-radio" name="water_pump_bypass" id="pump_auto" value="AUTO" <?php echo ($pump_bypass == 'AUTO') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="pump_auto"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>AUTO</label>

                                        <input type="radio" class="sg-bypass-radio" name="water_pump_bypass" id="pump_force_on" value="FORCE_ON" <?php echo ($pump_bypass == 'FORCE_ON') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="pump_force_on"><i class="fa-solid fa-bolt me-1"></i>FORCE ON</label>

                                        <input type="radio" class="sg-bypass-radio" name="water_pump_bypass" id="pump_force_off" value="FORCE_OFF" <?php echo ($pump_bypass == 'FORCE_OFF') ? 'checked' : ''; ?>>
                                        <label class="sg-bypass-label" for="pump_force_off"><i class="fa-solid fa-power-off me-1"></i>FORCE OFF</label>
                                    </div>
                                    <p class="text-sg-muted small m-0 mt-2">
                                        <?php if($pump_bypass == 'AUTO'): ?>
                                            Operated automatically based on humidity thresholds.
                                        <?php elseif($pump_bypass == 'FORCE_ON'): ?>
                                            <span class="text-green fw-semibold">Continuous Misting:</span> Pump running continuously.
                                        <?php else: ?>
                                            <span class="text-red fw-semibold">Emergency Cutoff:</span> Pump kept strictly powered off.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Action Buttons Bar -->
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-4" style="background: var(--surface); border: 1px solid var(--border);">
                    <div class="text-sg-muted small d-none d-sm-block">
                        <i class="fa-solid fa-shield-halved text-purple me-1"></i> Changes take effect on next microcontroller polling cycle (1-3s)
                    </div>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="reset" class="sg-btn sg-btn-secondary px-3">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset
                        </button>
                        <button type="submit" id="saveRulesBtn" class="sg-btn sg-btn-primary px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Automation Rules
                        </button>
                    </div>
                </div>

            </form>
        </main>
    </div>

    <!-- Script Processing Engine -->
    <script>
        var lastSensorTimestamp = null;
        var latestTelemetry = null;

        function renderDynamicStatuses() {
            if (!latestTelemetry) return;

            const temp = parseFloat(latestTelemetry.temperature);
            const humidity = parseFloat(latestTelemetry.humidity);

            const tempOn = parseFloat($("input[name='fan_trigger_temp']").val()) || parseFloat(latestTelemetry.temp_on) || 30.0;
            const tempOff = parseFloat($("input[name='fan_stop_temp']").val()) || parseFloat(latestTelemetry.temp_off) || 26.0;
            const humidOn = parseFloat($("input[name='pump_trigger_humidity']").val()) || parseFloat(latestTelemetry.humidity_on) || 60.0;
            const humidOff = parseFloat($("input[name='pump_stop_humidity']").val()) || parseFloat(latestTelemetry.humidity_off) || 75.0;

            // Temperature Status Evaluation
            if (!isNaN(temp)) {
                if (temp >= tempOn) {
                    $('#temp_status_wrap')
                        .attr('class', 'text-red small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-triangle-exclamation"></i> <span>High (Above ' + tempOn + '&deg;C)</span>');
                } else if (temp <= tempOff) {
                    $('#temp_status_wrap')
                        .attr('class', 'text-amber small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-temperature-arrow-down"></i> <span>Low (Below ' + tempOff + '&deg;C)</span>');
                } else {
                    $('#temp_status_wrap')
                        .attr('class', 'text-green small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-circle-check"></i> <span>Optimal (' + tempOff + ' - ' + tempOn + '&deg;C)</span>');
                }
            }

            // Humidity Status Evaluation (Water Pump & Misting Regulation)
            if (!isNaN(humidity)) {
                if (humidity < humidOn) {
                    $('#humid_status_wrap')
                        .attr('class', 'text-amber small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-droplet-slash"></i> <span>Low (Below ' + humidOn + '%)</span>');
                } else if (humidity > humidOff) {
                    $('#humid_status_wrap')
                        .attr('class', 'text-red small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-triangle-exclamation"></i> <span>High (Above ' + humidOff + '%)</span>');
                } else {
                    $('#humid_status_wrap')
                        .attr('class', 'text-green small fw-bold d-flex align-items-center gap-2 mt-1')
                        .html('<i class="fa-solid fa-circle-check"></i> <span>Normal (' + humidOn + ' - ' + humidOff + '%)</span>');
                }
            }
        }

        function fetchLiveSensorData() {
            $.ajax({
                url: "function/get_live_data.php",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if (data && !data.error) {
                        latestTelemetry = data;
                        $('#live_temp').text(data.temperature + '°C');
                        $('#live_humid').text(data.humidity + '%');

                        renderDynamicStatuses();

                        const dbDate = data.sensor_date;
                        const dbTime = data.sensor_time;

                        if (dbDate && dbTime) {
                            const isPM = dbTime.toUpperCase().includes('PM');
                            const isAM = dbTime.toUpperCase().includes('AM');
                            const cleanTime = dbTime.replace(/(AM|PM)/i, '').trim();
                            const timeParts = cleanTime.split(':');
                            let hours = parseInt(timeParts[0], 10);
                            const minutes = parseInt(timeParts[1], 10);
                            const seconds = timeParts[2] ? parseInt(timeParts[2], 10) : 0;
                            if (isPM && hours < 12) hours += 12;
                            if (isAM && hours === 12) hours = 0;
                            const dateParts = dbDate.split('-');
                            lastSensorTimestamp = new Date(parseInt(dateParts[0], 10), parseInt(dateParts[1], 10) - 1, parseInt(dateParts[2], 10), hours, minutes, seconds);
                        } else {
                            lastSensorTimestamp = new Date();
                        }
                        updateTimeAgo();
                    } else {
                        $('#live_temp').text('N/A');
                        $('#live_humid').text('N/A');
                        $('.last-updated-text').text('No sensor data found');
                    }
                },
                error: function() {
                    console.error("Failed to fetch live sensor data.");
                }
            });
        }

        function updateTimeAgo() {
            if (!lastSensorTimestamp || isNaN(lastSensorTimestamp.getTime())) {
                $('.last-updated-text').text('less than a minute ago');
                return;
            }

            var now = new Date();
            var diffInSeconds = Math.floor((now - lastSensorTimestamp) / 1000);

            if (diffInSeconds < 0) diffInSeconds = 0;

            var timeString = '';
            if (diffInSeconds < 10) {
                timeString = 'just now';
            } else if (diffInSeconds < 60) {
                timeString = diffInSeconds + ' seconds ago';
            } else if (diffInSeconds < 3600) {
                var mins = Math.floor(diffInSeconds / 60);
                timeString = mins + (mins === 1 ? ' minute ago' : ' minutes ago');
            } else {
                var hours = Math.floor(diffInSeconds / 3600);
                timeString = hours + (hours === 1 ? ' hour ago' : ' hours ago');
            }

            $('.last-updated-text').text(timeString);
        }

        $(document).ready(function() {
            fetchLiveSensorData();
            setInterval(fetchLiveSensorData, 2000);
            setInterval(updateTimeAgo, 1000);

            $("input[name='fan_trigger_temp'], input[name='fan_stop_temp'], input[name='pump_trigger_humidity'], input[name='pump_stop_humidity']").on('input change', function() {
                renderDynamicStatuses();
            });

            $("#settingsForm").on("submit", function(e) {
                e.preventDefault();
                var btn = $("#saveRulesBtn");
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving Rules...');

                var fanOn = parseFloat($("input[name='fan_trigger_temp']").val());
                var fanOff = parseFloat($("input[name='fan_stop_temp']").val());
                var pumpOn = parseFloat($("input[name='pump_trigger_humidity']").val());
                var pumpOff = parseFloat($("input[name='pump_stop_humidity']").val());

                if (fanOn <= fanOff) {
                    Swal.fire({
                        title: "Thermal Logic Collision",
                        html: `<span class="fs-6">Your <b>Turn ON</b> threshold (<b>${fanOn}°C</b>) must be higher than your <b>Turn OFF</b> target (<b>${fanOff}°C</b>).</span>`,
                        icon: "warning",
                        confirmButtonColor: "#7c3aed",
                        confirmButtonText: "Adjust Thresholds"
                    });
                    btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Automation Rules');
                    return;
                }

                if (pumpOn >= pumpOff) {
                    Swal.fire({
                        title: "Humidity Logic Collision",
                        html: `<span class="fs-6">Your <b>Turn ON</b> threshold (<b>${pumpOn}%</b>) must be lower than your <b>Maximum Target</b> (<b>${pumpOff}%</b>).</span>`,
                        icon: "warning",
                        confirmButtonColor: "#7c3aed",
                        confirmButtonText: "Adjust Thresholds"
                    });
                    btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Automation Rules');
                    return;
                }

                var formData = new FormData(this);

                $.ajax({
                    url: "function/save_settings.php",
                    type: "POST",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Automation Rules');
                        response = response.trim();
                        if (response === "success") {
                            Swal.fire({
                                title: "CONFIGURATION SAVED",
                                text: "Automation trigger thresholds and hardware bypass modes updated successfully!",
                                icon: "success",
                                timer: 2000,
                                timerProgressBar: true,
                                confirmButtonColor: "#7c3aed",
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "Save Error",
                                text: response,
                                icon: "error",
                                confirmButtonColor: "#ef4444",
                                confirmButtonText: "OK"
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Automation Rules');
                        Swal.fire({
                            title: "SYSTEM FAILURE",
                            text: "Unable to process automation settings changes.",
                            icon: "error",
                            confirmButtonColor: "#ef4444",
                            confirmButtonText: "Close"
                        });
                    }
                });
            });
        });
    </script>
</body>

</html> 