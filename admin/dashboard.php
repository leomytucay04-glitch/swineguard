<?php
include "out.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Swine Guard</title>
    <meta name="description" content="Swine Guard live environmental monitoring dashboard for pig farm automation.">

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
                    <h1 class="sg-page-title">Environmental Overview</h1>
                    <p class="sg-page-subtitle">Real-time pen atmosphere monitoring metrics</p>
                </div>
                <span class="sg-live-badge">
                    <span class="dot"></span> Live Monitoring Active
                </span>
            </div>

            <!-- Sensor Metric Cards -->
            <div class="row g-3 mb-4">

                <!-- Temperature -->
                <div class="col-12 col-sm-6 col-md-4 col-xl">
                    <div class="sg-metric">
                        <div class="sg-icon-box sg-icon-temp"><i class="fa-solid fa-temperature-half"></i></div>
                        <div class="sg-metric-label">Temperature</div>
                        <div class="sg-metric-value" id="live-temp">--°C</div>
                        <div class="sg-metric-sub fw-medium" id="temp-subtext">
                            <i class="fa-solid fa-circle-check me-1"></i> Checking...
                        </div>
                        <div class="sg-metric-sub mt-1">
                            <i class="fa-regular fa-clock me-1"></i> <span id="temp-last-updated">Awaiting...</span>
                        </div>
                    </div>
                </div>

                <!-- Humidity -->
                <div class="col-12 col-sm-6 col-md-4 col-xl">
                    <div class="sg-metric">
                        <div class="sg-icon-box sg-icon-humid"><i class="fa-solid fa-droplet"></i></div>
                        <div class="sg-metric-label">Relative Humidity</div>
                        <div class="sg-metric-value" id="live-humidity">--%</div>
                        <div class="sg-metric-sub fw-medium" id="humidity-subtext">
                            <i class="fa-solid fa-circle-check me-1"></i> Checking...
                        </div>
                        <div class="sg-metric-sub mt-1">
                            <i class="fa-regular fa-clock me-1"></i> <span id="humidity-last-updated">Awaiting...</span>
                        </div>
                    </div>
                </div>

                <!-- Water Level -->
                <div class="col-12 col-sm-6 col-md-4 col-xl">
                    <div class="sg-metric">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="sg-icon-box sg-icon-water"><i class="fa-solid fa-water"></i></div>
                            <span id="water-badge" class="sg-badge sg-badge-idle">UNKNOWN</span>
                        </div>
                        <div class="sg-metric-label">Water Supply Level</div>
                        <div class="sg-metric-value" id="live-water">--</div>
                        <div class="sg-metric-sub fw-medium" id="water-subtext">
                            <i class="fa-solid fa-circle-info me-1"></i> Checking...
                        </div>
                        <div class="sg-metric-sub mt-1">
                            <i class="fa-regular fa-clock me-1"></i> <span id="water-last-updated">Awaiting...</span>
                        </div>
                    </div>
                </div>

                <!-- Cooling Fan -->
                <div class="col-12 col-sm-6 col-md-4 col-xl">
                    <div class="sg-metric">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="sg-icon-box sg-icon-fan"><i id="cooling-fan-icon" class="fa-solid fa-fan"></i></div>
                            <span id="cooling-fan-badge" class="sg-badge sg-badge-idle">IDLE</span>
                        </div>
                        <div class="sg-metric-label">Cooling Fan</div>
                        <div class="sg-metric-value" style="font-size:1.2rem;" id="cooling-fan-text">Standby</div>
                        <div class="sg-metric-sub">Auto-Cooling</div>
                    </div>
                </div>


                <!-- Water Pump -->
                <div class="col-12 col-sm-6 col-md-4 col-xl">
                    <div class="sg-metric">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="sg-icon-box sg-icon-pump"><i class="fa-solid fa-faucet-drip"></i></div>
                            <span id="pump-badge" class="sg-badge sg-badge-idle">IDLE</span>
                        </div>
                        <div class="sg-metric-label">Water Pump</div>
                        <div class="sg-metric-value" style="font-size:1.2rem;" id="pump-text">Standby</div>
                        <div class="sg-metric-sub">Relay State</div>
                    </div>
                </div>

            </div>

            <!-- Log Tables -->
            <div class="row g-4">
                <!-- Sensor Telemetry -->
                <div class="col-12 col-xl-6">
                    <div class="sg-card">
                        <div class="sg-card-header">
                            <h5 class="sg-card-title"><i class="fa-solid fa-microchip"></i> Recent Sensor Telemetry</h5>
                            <span class="sg-badge sg-badge-idle">Last 5 records</span>
                        </div>
                        <div class="sg-table-wrap">
                            <table class="sg-table">
                                <thead>
                                    <tr>
                                        <th>Temp</th><th>Humidity</th><th>Water State</th><th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody id="sensor-table-body">
                                    <tr><td colspan="4" class="text-center text-sg-muted py-3">Awaiting system payload updates...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Machine Logs -->
                <div class="col-12 col-xl-6">
                    <div class="sg-card">
                        <div class="sg-card-header">
                            <h5 class="sg-card-title"><i class="fa-solid fa-gears"></i> Recent Machine Status Activity</h5>
                            <span class="sg-badge sg-badge-idle">Last 5 records</span>
                        </div>
                        <div class="sg-table-wrap">
                            <table class="sg-table">
                                <thead>
                                    <tr>
                                        <th>Fan</th><th>Exhaust</th><th>Water Pump</th><th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody id="machine-table-body">
                                    <tr><td colspan="4" class="text-center text-sg-muted py-3">Awaiting system payload updates...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Bootstrap JS Bundle -->


    <!-- Real-time AJAX Processing Script -->
    <script>
        $(document).ready(function() {

            let isWarningModalOpen = false;
            let lastAlertTime = 0;

            // Helper function to convert DB date/time to total seconds elapsed
            function getSecondsElapsed(dbDate, dbTime) {
                if (!dbDate || !dbTime) return null;

                const dateParts = dbDate.split('-');
                if (dateParts.length !== 3) return null;

                const isPM = dbTime.toUpperCase().includes('PM');
                const isAM = dbTime.toUpperCase().includes('AM');
                const cleanTime = dbTime.replace(/(AM|PM)/i, '').trim();
                const timeParts = cleanTime.split(':');

                let hours = parseInt(timeParts[0], 10);
                const minutes = parseInt(timeParts[1], 10);
                const seconds = timeParts[2] ? parseInt(timeParts[2], 10) : 0;

                if (isPM && hours < 12) hours += 12;
                if (isAM && hours === 12) hours = 0;

                const recordTime = new Date(
                    parseInt(dateParts[0], 10),
                    parseInt(dateParts[1], 10) - 1,
                    parseInt(dateParts[2], 10),
                    hours,
                    minutes,
                    seconds
                );

                const currentTime = new Date();
                return Math.floor((currentTime - recordTime) / 1000);
            }

            // Professional Telemetry Offline Modal Alert Trigger
            function checkStaleData(dbDate, dbTime) {
                const diffInSeconds = getSecondsElapsed(dbDate, dbTime);

                // 5 minutes = 300 seconds
                const STALE_THRESHOLD_SECONDS = 300;
                const REPEAT_INTERVAL_MS = 60000; // 1 minute repeat interval
                const now = Date.now();

                if (diffInSeconds !== null && diffInSeconds >= STALE_THRESHOLD_SECONDS) {
                    // Trigger alert if modal is not currently displayed AND 1 minute has elapsed since last prompt
                    if (!isWarningModalOpen && (now - lastAlertTime >= REPEAT_INTERVAL_MS)) {
                        isWarningModalOpen = true;
                        lastAlertTime = now;

                        const formattedAge = formatTimeAgo(dbDate, dbTime);

                        Swal.fire({
                            title: 'Hardware Telemetry Warning',
                            html: `
                            <div class="text-start" style="font-size: 0.95rem; line-height: 1.6;">
                                <p class="text-red fw-semibold mb-2" style="font-size: 1rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Device Communication Suspended</strong>
                                </p>
                                <p class="mb-3" style="color: #cbd5e1;">
                                    The system has detected no active telemetry data updates for over <strong class="text-white">5 minutes</strong> (Last recorded transmission: <em class="text-white">${formattedAge}</em>).
                                </p>
                                <hr style="border-color: rgba(255, 255, 255, 0.15);">
                                <span class="fw-bold d-block mb-2 text-white">Possible Operational Causes:</span>
                                <ul class="ps-3 mb-0" style="color: #cbd5e1; font-size: 0.88rem;">
                                    <li class="mb-1">Hardware controller power disruption or power loss</li>
                                    <li class="mb-1">Local Wi-Fi / LAN network connection failure</li>
                                    <li>Sensor interface cable disconnect or module hardware error</li>
                                </ul>
                            </div>
                        `,
                            icon: 'warning',
                            confirmButtonText: 'Acknowledge & Dismiss',
                            confirmButtonColor: '#7c3aed',
                            allowOutsideClick: false,
                            backdrop: `rgba(15, 23, 42, 0.75)`
                        }).then(() => {
                            isWarningModalOpen = false;
                        });
                    }
                }
            }

            function updateDashboard() {
                $.ajax({
                    url: 'function/get_live_data.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) return;

                        const temp = parseFloat(data.temperature) || 0;
                        const humidity = parseFloat(data.humidity) || 0;

                        // 1. Core Sensor Values
                        $('#live-temp').text(data.temperature + '°C');
                        $('#live-humidity').text(data.humidity + '%');

                        // 2. Temperature Ranges (from settings_rule)
                        if (temp >= data.temp_on) {
                            $('#temp-subtext')
                                .attr('class', 'sg-metric-sub text-red fw-medium')
                                .html('<i class="fa-solid fa-triangle-exclamation me-1"></i> High (Above ' + data.temp_on + '°C)');
                        } else if (temp <= data.temp_off) {
                            $('#temp-subtext')
                                .attr('class', 'sg-metric-sub text-amber fw-medium')
                                .html('<i class="fa-solid fa-temperature-arrow-down me-1"></i> Low (Below ' + data.temp_off + '°C)');
                        } else {
                            $('#temp-subtext')
                                .attr('class', 'sg-metric-sub text-green fw-medium')
                                .html('<i class="fa-solid fa-circle-check me-1"></i> Optimal range (' + data.temp_off + ' - ' + data.temp_on + '°C)');
                        }

                        // 3. Humidity Ranges (from settings_rule)
                        if (humidity < data.humidity_on) {
                            $('#humidity-subtext')
                                .attr('class', 'sg-metric-sub text-amber fw-medium')
                                .html('<i class="fa-solid fa-droplet-slash me-1"></i> Low (Below ' + data.humidity_on + '%)');
                        } else if (humidity > data.humidity_off) {
                            $('#humidity-subtext')
                                .attr('class', 'sg-metric-sub text-red fw-medium')
                                .html('<i class="fa-solid fa-triangle-exclamation me-1"></i> High (Above ' + data.humidity_off + '%)');
                        } else {
                            $('#humidity-subtext')
                                .attr('class', 'sg-metric-sub text-green fw-medium')
                                .html('<i class="fa-solid fa-circle-check me-1"></i> Normal range (' + data.humidity_on + ' - ' + data.humidity_off + '%)');
                        }

                        // 4. Water Level Indicator
                        const waterVal = data.water ? data.water.toString().toLowerCase() : 'off';
                        if (['on', 'active', 'normal', 'high'].includes(waterVal)) {
                            $('#live-water').text('NORMAL');
                            $('#water-badge')
                                .text('OK')
                                .removeClass('sg-badge-idle sg-badge-danger')
                                .addClass('sg-badge-active');
                            $('#water-subtext').html('<span class="text-green"><i class="fa-solid fa-circle-check me-1"></i> Level adequate</span>');
                        } else {
                            $('#live-water').text('LOW LEVEL');
                            $('#water-badge')
                                .text('WARN')
                                .removeClass('sg-badge-active sg-badge-idle')
                                .addClass('sg-badge-danger');
                            $('#water-subtext').html('<span class="text-red"><i class="fa-solid fa-triangle-exclamation me-1"></i> Supply low</span>');
                        }

                        // 5. Timestamps
                        const timeAgoText = formatTimeAgo(data.sensor_date, data.sensor_time);
                        $('#temp-last-updated').text(timeAgoText);
                        $('#humidity-last-updated').text(timeAgoText);
                        $('#water-last-updated').text(timeAgoText);

                        // 6. Cooling Fan UI State
                        if (data.is_fan_running) {
                            $('#cooling-fan-badge, #fan-badge').text('ACTIVE').removeClass('sg-badge-idle').addClass('sg-badge-active');
                            $('#cooling-fan-text, #fan-text').text('Running');
                            $('#cooling-fan-icon, #fan-icon').addClass('spin-slow');
                        } else {
                            $('#cooling-fan-badge, #fan-badge').text('IDLE').removeClass('sg-badge-active').addClass('sg-badge-idle');
                            $('#cooling-fan-text, #fan-text').text('Standby');
                            $('#cooling-fan-icon, #fan-icon').removeClass('spin-slow');
                        }

                        // 7. Exhaust Fan UI State
                        if (data.is_exhaust_running) {
                            $('#exhaust-fan-badge, #exhaust-badge').text('ACTIVE').removeClass('sg-badge-idle').addClass('sg-badge-active');
                            $('#exhaust-fan-text, #exhaust-text').text('Running');
                            $('#exhaust-fan-icon, #exhaust-icon').addClass('spin-slow');
                        } else {
                            $('#exhaust-fan-badge, #exhaust-badge').text('IDLE').removeClass('sg-badge-active').addClass('sg-badge-idle');
                            $('#exhaust-fan-text, #exhaust-text').text('Standby');
                            $('#exhaust-fan-icon, #exhaust-icon').removeClass('spin-slow');
                        }

                        // 8. Water Pump UI State
                        if (data.is_pump_running) {
                            $('#pump-badge').text('ACTIVE').removeClass('sg-badge-idle').addClass('sg-badge-active');
                            $('#pump-text').text('Running');
                        } else {
                            $('#pump-badge').text('IDLE').removeClass('sg-badge-active').addClass('sg-badge-idle');
                            $('#pump-text').text('Standby');
                        }

                        // Stale check execution
                        if (typeof checkStaleData === 'function') {
                            checkStaleData(data.sensor_date, data.sensor_time);
                        }
                    },
                    complete: function() {
                        setTimeout(updateDashboard, 1000);
                    }
                });
            }

            // Function 2: Updates Log History
            function updateLogs() {
                $.ajax({
                    url: 'function/get_logs.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Sensor Table
                        let sensorHtml = '';
                        if (data.sensor_logs && data.sensor_logs.length > 0) {
                            data.sensor_logs.forEach(function(row) {
                                const waterBadge = (row.water === 'on' || row.water === 'active') ? 'sg-badge-active' : 'sg-badge-idle';
                                const formattedTime = format12Hour(row.time);

                                sensorHtml += `<tr>
                        <td class="fw-semibold text-amber">${row.temperature}°C</td>
                        <td class="text-cyan">${row.humidity}%</td>
                        <td><span class="sg-badge ${waterBadge}">${row.water}</span></td>
                        <td class="text-sg-muted" style="font-size:0.78rem;">${row.date} | ${formattedTime}</td>
                    </tr>`;
                            });
                        } else {
                            sensorHtml = '<tr><td colspan="4" class="text-center text-sg-muted">No telemetry logs logged yet.</td></tr>';
                        }
                        $('#sensor-table-body').html(sensorHtml);

                        // Machine Table
                        let machineHtml = '';
                        if (data.machine_logs && data.machine_logs.length > 0) {
                            data.machine_logs.forEach(function(row) {
                                const formattedTime = format12Hour(row.time);

                                machineHtml += `<tr>
                        <td><span class="sg-badge ${row.fan === 'on' ? 'sg-badge-active' : 'sg-badge-idle'}">${row.fan}</span></td>
                        <td><span class="sg-badge ${row.exhaust === 'on' ? 'sg-badge-active' : 'sg-badge-idle'}">${row.exhaust}</span></td>
                        <td><span class="sg-badge ${row.water_pump === 'on' ? 'sg-badge-active' : 'sg-badge-idle'}">${row.water_pump}</span></td>
                        <td class="text-sg-muted" style="font-size:0.78rem;">${row.date} | ${formattedTime}</td>
                    </tr>`;
                            });
                        } else {
                            machineHtml = '<tr><td colspan="4" class="text-center text-sg-muted">No operational state logs found.</td></tr>';
                        }
                        $('#machine-table-body').html(machineHtml);
                    },
                    complete: function() {
                        setTimeout(updateLogs, 1000);
                    }
                });
            }

            // Initial execution
            updateDashboard();
            updateLogs();
        });

        // Helper: Converts DB Date/Time to formatted relative time string
        function formatTimeAgo(dbDate, dbTime) {
            if (!dbDate || !dbTime) return 'No data';

            const dateParts = dbDate.split('-');
            if (dateParts.length !== 3) return `${dbDate} ${dbTime}`;

            const isPM = dbTime.toUpperCase().includes('PM');
            const isAM = dbTime.toUpperCase().includes('AM');

            const cleanTime = dbTime.replace(/(AM|PM)/i, '').trim();
            const timeParts = cleanTime.split(':');

            let hours = parseInt(timeParts[0], 10);
            const minutes = parseInt(timeParts[1], 10);
            const seconds = timeParts[2] ? parseInt(timeParts[2], 10) : 0;

            if (isPM && hours < 12) hours += 12;
            if (isAM && hours === 12) hours = 0;

            const recordTime = new Date(
                parseInt(dateParts[0], 10),
                parseInt(dateParts[1], 10) - 1,
                parseInt(dateParts[2], 10),
                hours,
                minutes,
                seconds
            );

            const currentTime = new Date();
            const diffInSeconds = Math.floor((currentTime - recordTime) / 1000);

            if (diffInSeconds < 0) return 'Just now';
            if (diffInSeconds < 60) return 'less than a minute ago';

            const diffInMinutes = Math.floor(diffInSeconds / 60);
            if (diffInMinutes === 1) return '1 minute ago';
            if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;

            const diffInHours = Math.floor(diffInMinutes / 60);
            if (diffInHours === 1) return '1 hour ago';
            return `${diffInHours} hours ago`;
        }


        // Helper function to format 24-hour time string (HH:MM:SS) into 12-hour AM/PM format
        function format12Hour(timeStr) {
            if (!timeStr) return '';

            // Check if time is already formatted with AM/PM
            if (timeStr.toUpperCase().includes('AM') || timeStr.toUpperCase().includes('PM')) {
                return timeStr;
            }

            const parts = timeStr.split(':');
            if (parts.length < 2) return timeStr;

            let hours = parseInt(parts[0], 10);
            const minutes = parts[1];
            const seconds = parts[2] ? parts[2] : '00';

            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // convert 0 to 12
            const formattedHours = hours < 10 ? '0' + hours : hours;

            return `${formattedHours}:${minutes}:${seconds} ${ampm}`;
        }
    </script>
</body>

</html>