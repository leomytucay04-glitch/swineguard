<?php
include "out.php";
?>
<?php
// Establish connection to database
include "../include/dbcon.php";

// Fetch existing rule thresholds & bypass modes
$temp_on = $temp_off = $humid_on = $humid_off = $heater_on = "";
$fan_bypass = $exhaust_bypass = $pump_bypass = $heater_bypass = "AUTO";

$get_rules = $conn->query("SELECT * FROM settings_rule WHERE id = 1");
if ($get_rules && $get_rules->num_rows > 0) {
    $rule = $get_rules->fetch_assoc();
    $temp_on        = $rule['temperature_on'] ?? '';
    $temp_off       = $rule['temperature_off'] ?? '';
    $humid_on       = $rule['humidity_on'] ?? '';
    $humid_off      = $rule['humidity_off'] ?? '';
    $heater_on      = $rule['heater_on'] ?? '';
    $fan_bypass     = $rule['fan_bypass'] ?? 'AUTO';
    $exhaust_bypass = $rule['exhaust_bypass'] ?? 'AUTO';
    $pump_bypass    = $rule['water_pump_bypass'] ?? 'AUTO';
    $heater_bypass  = $rule['heater_bypass'] ?? 'AUTO';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Settings - Swine Guard</title>

    <link rel="stylesheet" href="../include/fonts.css">
    <link rel="stylesheet" href="../include/bootstrap.css">
    <link rel="stylesheet" href="../include/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="../include/animate.min.css">
    <script src="../include/bootstrap.js"></script>
    <script src="../include/sweetalert.js"></script>
    <script src="../include/popper.js"></script>
    <script src="../include/chart.js"></script>
    <script src="../include/jquery.js"></script>
    <style>
        :root {
            --primary-color: #10b981;
            --dark-color: #0f172a;
            --bg-color: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: #334155;
            min-height: 100vh;
            font-size: 1.25rem;
        }

        .settings-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            margin-bottom: 28px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            background-color: #f1f5f9;
            padding: 14px 18px;
            border: 1px solid transparent;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 16px 32px;
            font-weight: 700;
            font-size: 1.25rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #059669;
        }

        .config-icon {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bg-fan-light {
            background-color: #f3e8ff;
            color: #7c3aed;
        }

        .bg-pump-light {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .bg-warning-light {
            background-color: #fef3c7;
            color: #d97706;
        }

        .bg-heater-light {
            background-color: #ffe4e6;
            color: #e11d48;
        }

        .live-inline-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 22px;
            border: 1px solid #e2e8f0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .live-icon-temp {
            background-color: #fef3c7;
            color: #d97706;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .live-icon-humid {
            background-color: #e0f2fe;
            color: #0284c7;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .live-icon-heater {
            background-color: #ffe4e6;
            color: #e11d48;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-4 animate__animated animate__fadeIn">
        <div class="mb-4">
            <h1 class="display-5 fw-bold text-dark m-0">Automation Configuration</h1>
            <p class="fs-4 text-secondary m-0">Establish trigger rules and emergency overrides for smart relays</p>
        </div>

        <div class="row">
            <div class="col-12">
                <!-- Smart Logic Info Quick Guide -->
                <div class="settings-card bg-dark text-white border-0">
                    <h3 class="fw-bold mb-3 text-success fs-2"><i class="fa-solid fa-circle-info me-2"></i>Smart Logic Info</h3>
                    <p class="fs-4 text-white-50">When real-time sensor evaluations meet or pass your preset thresholds, commands are securely broadcasted to your hardware relays instantly.</p>
                    <hr class="border-secondary my-3">
                    <p class="fs-5 text-white-50 mb-0"><strong class="text-white">Note:</strong> Switch any relay to <strong>FORCE ON / FORCE OFF</strong> if your Temperature sensor is giving false readings or malfunctioning.</p>
                </div>

                <form id="settingsForm">

                    <!-- Exhaust Fan Rule Card -->
                    <div class="settings-card">
                        <div class="d-flex align-items-center mb-4">
                            <div class="config-icon bg-fan-light me-3">
                                <i class="fa-solid fa-fan"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold m-0 fs-3">Exhaust / Cooling Fan Thresholds</h3>
                                <p class="text-muted fs-5 m-0">Define temperatures that prompt automatic cooling fan activation</p>
                            </div>
                        </div>

                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fs-5 fw-bold text-secondary">Turn ON if Temperature is Above or Equal (≥)</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" step="0.1" class="form-control" name="fan_trigger_temp" value="<?php echo htmlspecialchars($temp_on); ?>" placeholder="e.g. 28.0" required>
                                            <span class="input-group-text bg-white text-muted fs-4">°C</span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fs-5 fw-bold text-secondary">Target Cool-down Threshold (Turn OFF below)</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" step="0.1" class="form-control" name="fan_stop_temp" value="<?php echo htmlspecialchars($temp_off); ?>" placeholder="e.g. 25.5" required>
                                            <span class="input-group-text bg-white text-muted fs-4">°C</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="live-inline-card">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="live-icon-temp">
                                                <i class="fa-solid fa-temperature-half"></i>
                                            </div>
                                            <div>
                                                <div class="text-secondary fs-6 fw-bold">Temperature</div>
                                                <h2 class="fw-bold m-0 text-dark display-6" id="live_temp">--.-°C</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-success fs-5 fw-bold d-flex align-items-center gap-2 mt-2">
                                        <i class="fa-solid fa-circle-check"></i> <span id="temp_status">Reading...</span>
                                    </div>
                                    <div class="mt-3 pt-2 border-top text-muted fs-6">
                                        <i class="fa-regular fa-clock me-1"></i>Last updated: <strong class="text-dark last-updated-text">Loading...</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Water Pump Rule Card -->
                    <div class="settings-card">
                        <div class="d-flex align-items-center mb-4">
                            <div class="config-icon bg-pump-light me-3">
                                <i class="fa-solid fa-faucet"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold m-0 fs-3">Water Pump & Misting Control</h3>
                                <p class="text-muted fs-5 m-0">Set rules to balance pen humidity levels</p>
                            </div>
                        </div>

                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fs-5 fw-bold text-secondary">Turn ON if Humidity is Below or Equal (≤)</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" step="0.1" class="form-control" name="pump_trigger_humidity" value="<?php echo htmlspecialchars($humid_on); ?>" placeholder="e.g. 60.0" required>
                                            <span class="input-group-text bg-white text-muted fs-4">%</span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fs-5 fw-bold text-secondary">Maximum Target Threshold (Turn OFF above)</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" step="0.1" class="form-control" name="pump_stop_humidity" value="<?php echo htmlspecialchars($humid_off); ?>" placeholder="e.g. 75.0" required>
                                            <span class="input-group-text bg-white text-muted fs-4">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="live-inline-card">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="live-icon-humid">
                                                <i class="fa-solid fa-droplet"></i>
                                            </div>
                                            <div>
                                                <div class="text-secondary fs-6 fw-bold">Humidity</div>
                                                <h2 class="fw-bold m-0 text-dark display-6" id="live_humid">--.-%</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-success fs-5 fw-bold d-flex align-items-center gap-2 mt-2">
                                        <i class="fa-solid fa-circle-check"></i> <span id="humid_status">Reading...</span>
                                    </div>
                                    <div class="mt-3 pt-2 border-top text-muted fs-6">
                                        <i class="fa-regular fa-clock me-1"></i>Last updated: <strong class="text-dark last-updated-text">Loading...</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Heating Lamp Rule Card -->
                    <div class="settings-card">
                        <div class="d-flex align-items-center mb-4">
                            <div class="config-icon bg-heater-light me-3">
                                <i class="fa-solid fa-fire-flame-curved"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold m-0 fs-3">Heating Control Threshold</h3>
                                <p class="text-muted fs-5 m-0">Turn ON heating light when temperature drops below or reaches threshold</p>
                            </div>
                        </div>

                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fs-5 fw-bold text-secondary">Turn ON Heater if Temperature is Below or Equal (≤)</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" step="0.1" class="form-control" name="heater_trigger_temp" id="heater_trigger_temp" value="<?php echo htmlspecialchars($heater_on); ?>" placeholder="e.g. 20.0" required>
                                            <span class="input-group-text bg-white text-muted fs-4">°C</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="live-inline-card">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="live-icon-heater">
                                                <i class="fa-solid fa-temperature-arrow-up"></i>
                                            </div>
                                            <div>
                                                <div class="text-secondary fs-6 fw-bold">Heating Temp</div>
                                                <h2 class="fw-bold m-0 text-dark display-6" id="live_heater_temp">--.-°C</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-success fs-5 fw-bold d-flex align-items-center gap-2 mt-2">
                                        <i class="fa-solid fa-circle-check"></i> <span id="heater_status">Standby</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Bypass / Malfunction Override Controls Card -->
                    <div class="settings-card border-warning">
                        <div class="d-flex align-items-center mb-4">
                            <div class="config-icon bg-warning-light me-3">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold m-0 text-dark fs-3">Hardware Bypass / Emergency Controls</h3>
                                <p class="text-muted fs-5 m-0">Override sensor rules in case of sensor malfunction or maintenance</p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label fs-5 fw-bold text-secondary">Fan Mode</label>
                                <select class="form-select form-select-lg" name="fan_bypass">
                                    <option value="AUTO" <?php echo ($fan_bypass == 'AUTO') ? 'selected' : ''; ?>>AUTO (Use Sensor)</option>
                                    <option value="FORCE_ON" <?php echo ($fan_bypass == 'FORCE_ON') ? 'selected' : ''; ?>>FORCE ON (Always ON)</option>
                                    <option value="FORCE_OFF" <?php echo ($fan_bypass == 'FORCE_OFF') ? 'selected' : ''; ?>>FORCE OFF (Always OFF)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fs-5 fw-bold text-secondary">Exhaust Mode</label>
                                <select class="form-select form-select-lg" name="exhaust_bypass">
                                    <option value="AUTO" <?php echo ($exhaust_bypass == 'AUTO') ? 'selected' : ''; ?>>AUTO (Use Sensor)</option>
                                    <option value="FORCE_ON" <?php echo ($exhaust_bypass == 'FORCE_ON') ? 'selected' : ''; ?>>FORCE ON (Always ON)</option>
                                    <option value="FORCE_OFF" <?php echo ($exhaust_bypass == 'FORCE_OFF') ? 'selected' : ''; ?>>FORCE OFF (Always OFF)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fs-5 fw-bold text-secondary">Water Pump Mode</label>
                                <select class="form-select form-select-lg" name="water_pump_bypass">
                                    <option value="AUTO" <?php echo ($pump_bypass == 'AUTO') ? 'selected' : ''; ?>>AUTO (Use Sensor)</option>
                                    <option value="FORCE_ON" <?php echo ($pump_bypass == 'FORCE_ON') ? 'selected' : ''; ?>>FORCE ON (Always ON)</option>
                                    <option value="FORCE_OFF" <?php echo ($pump_bypass == 'FORCE_OFF') ? 'selected' : ''; ?>>FORCE OFF (Always OFF)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fs-5 fw-bold text-secondary">Heater Lamp Mode</label>
                                <select class="form-select form-select-lg" name="heater_bypass">
                                    <option value="AUTO" <?php echo ($heater_bypass == 'AUTO') ? 'selected' : ''; ?>>AUTO (Use Sensor)</option>
                                    <option value="FORCE_ON" <?php echo ($heater_bypass == 'FORCE_ON') ? 'selected' : ''; ?>>FORCE ON (Always ON)</option>
                                    <option value="FORCE_OFF" <?php echo ($heater_bypass == 'FORCE_OFF') ? 'selected' : ''; ?>>FORCE OFF (Always OFF)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Save Action Button -->
                    <div class="d-flex justify-content-end mb-5">
                        <button type="submit" class="btn btn-primary shadow px-5 py-3 fs-4">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Automation Rules
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Script Processing Engine -->
    <script>
        var lastSensorTimestamp = null;

        function fetchLiveSensorData() {
            $.ajax({
                url: "function/get_latest_sensor.php",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if (data && !data.error) {
                        // Update temperature and humidity
                        $('#live_temp').text(data.temperature + '°C');
                        $('#live_humid').text(data.humidity + '%');

                        // Update heating card to show live temperature
                        $('#live_heater_temp').text(data.temperature + '°C');

                        // Status badges
                        $('#temp_status').text(data.temp_status || 'Optimal');
                        $('#humid_status').text(data.humid_status || 'Normal');
                        $('#heater_status').text(data.heater_status || 'Active');

                        if (data.timestamp) {
                            lastSensorTimestamp = new Date(data.timestamp);
                        } else if (data.date && data.time) {
                            lastSensorTimestamp = new Date(data.date + ' ' + data.time);
                        } else {
                            lastSensorTimestamp = new Date();
                        }
                        updateTimeAgo();
                    } else {
                        $('#live_temp').text('N/A');
                        $('#live_humid').text('N/A');
                        $('#live_heater_temp').text('N/A');
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
            setInterval(fetchLiveSensorData, 3000);
            setInterval(updateTimeAgo, 1000);

            $("#settingsForm").on("submit", function(e) {
                e.preventDefault();

                var fanOn = parseFloat($("input[name='fan_trigger_temp']").val());
                var fanOff = parseFloat($("input[name='fan_stop_temp']").val());
                var pumpOn = parseFloat($("input[name='pump_trigger_humidity']").val());
                var pumpOff = parseFloat($("input[name='pump_stop_humidity']").val());
                var heaterOn = parseFloat($("input[name='heater_trigger_temp']").val());

                if (fanOn <= fanOff) {
                    Swal.fire({
                        title: "Confusing Fan Logic Detected",
                        html: `<span class="fs-5">Your <b>Turn ON</b> threshold (<b>${fanOn}°C</b>) must be higher than your <b>Turn OFF</b> target (<b>${fanOff}°C</b>).</span>`,
                        icon: "warning",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "Fix Thresholds"
                    });
                    return;
                }

                if (pumpOn >= pumpOff) {
                    Swal.fire({
                        title: "Confusing Pump Logic Detected",
                        html: `<span class="fs-5">Your <b>Turn ON</b> threshold (<b>${pumpOn}%</b>) must be lower than your <b>Maximum Target</b> (<b>${pumpOff}%</b>).</span>`,
                        icon: "warning",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "Fix Thresholds"
                    });
                    return;
                }

                if (heaterOn >= fanOff) {
                    Swal.fire({
                        title: "Temperature Conflict Detected",
                        html: `<span class="fs-5">Your <b>Heater ON</b> threshold (<b>${heaterOn}°C</b>) must be lower than your <b>Fan Cut-off</b> target (<b>${fanOff}°C</b>) to avoid running heating and cooling at the same time.</span>`,
                        icon: "warning",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "Fix Thresholds"
                    });
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
                        response = response.trim();
                        if (response === "success") {
                            Swal.fire({
                                title: "UPDATED",
                                text: "Automation configuration and bypass modes saved successfully!",
                                icon: "success",
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "ERROR",
                                text: response,
                                icon: "error",
                                confirmButtonColor: "#ef4444",
                                confirmButtonText: "OK"
                            });
                        }
                    },
                    error: function(xhr, status, error) {
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