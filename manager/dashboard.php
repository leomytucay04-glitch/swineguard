<?php
include "out.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Swine Guard</title>

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
        }

        .metric-card,
        .log-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card {
            height: 100%;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }

        .icon-temp {
            background-color: #fef3c7;
            color: #d97706;
        }

        .icon-humidity {
            background-color: #e0f2fe;
            color: #0284c7;
        }

        .icon-fan {
            background-color: #f3e8ff;
            color: #7c3aed;
        }

        .icon-pump {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .badge-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 50px;
        }

        .status-active {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-idle {
            background-color: #f1f5f9;
            color: #64748b;
        }

        .spin-slow {
            animation: fa-spin 3s linear infinite;
        }

        /* Clean logging table layout updates */
        .table {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            color: #475569;
            background-color: #f8fafc;
        }

        .metric-card {
            height: 100%;
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 14px;
            /* Reduced from 24px to give text more horizontal room */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        /* Prevents headings from breaking awkwardly onto 2 lines */
        .metric-card h3 {
            font-size: 1.35rem;
            white-space: nowrap;
        }

        /* Card titles text adjustment */
        .metric-card .card-label {
            font-size: 0.78rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Subtext single-line enforcement */
        .metric-card .card-subtext {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-2 animate__animated animate__fadeIn">

        <!-- Welcome banner section -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 g-3">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Environmental Overview</h1>
                <p class="text-secondary small m-0">Real-time pen atmosphere monitoring metrics</p>
            </div>
            <div>
                <span class="badge bg-white text-dark border p-2 rounded-3 small text-secondary">
                    <i class="fa-solid fa-clock me-1 text-success"></i> Live Monitoring Active
                </span>
            </div>
        </div>

        <div class="row g-2 mb-5">

            <!-- Temperature Sensor Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="icon-shape icon-temp mb-0">
                            <i class="fa-solid fa-temperature-half"></i>
                        </div>
                    </div>
                    <p class="text-secondary card-label fw-medium mb-1">Temperature</p>
                    <h3 class="fw-bold mb-1 text-dark" id="live-temp">--°C</h3>
                    <div class="text-success card-subtext fw-medium">
                        <i class="fa-solid fa-circle-check me-1"></i> Optimal range
                    </div>
                    <div class="text-muted card-subtext mt-1">
                        <i class="fa-regular fa-clock me-1"></i> <span id="temp-last-updated">Awaiting updates...</span>
                    </div>
                </div>
            </div>

            <!-- Humidity Sensor Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="icon-shape icon-humidity mb-0">
                            <i class="fa-solid fa-droplet"></i>
                        </div>
                    </div>
                    <p class="text-secondary card-label fw-medium mb-1">Relative Humidity</p>
                    <h3 class="fw-bold mb-1 text-dark" id="live-humidity">--%</h3>
                    <div class="text-success card-subtext fw-medium">
                        <i class="fa-solid fa-circle-check me-1"></i> Normal thresholds
                    </div>
                    <div class="text-muted card-subtext mt-1">
                        <i class="fa-regular fa-clock me-1"></i> <span id="humidity-last-updated">Awaiting updates...</span>
                    </div>
                </div>
            </div>

            <!-- Water Level Sensor Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="icon-shape icon-humidity mb-0" style="background-color: #e0f2fe; color: #0284c7;">
                            <i class="fa-solid fa-water"></i>
                        </div>
                        <span id="water-badge" class="badge badge-status status-idle">UNKNOWN</span>
                    </div>
                    <p class="text-secondary card-label fw-medium mb-1">Water Supply Level</p>
                    <h3 class="fw-bold mb-1 text-dark" id="live-water">--</h3>
                    <div class="card-subtext fw-medium" id="water-subtext">
                        <i class="fa-solid fa-circle-info me-1"></i> Checking...
                    </div>
                    <div class="text-muted card-subtext mt-1">
                        <i class="fa-regular fa-clock me-1"></i> <span id="water-last-updated">Awaiting updates...</span>
                    </div>
                </div>
            </div>

            <!-- Exhaust Fan Relay Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="icon-shape icon-fan mb-0">
                            <i id="fan-icon" class="fa-solid fa-fan"></i>
                        </div>
                        <span id="fan-badge" class="badge badge-status status-idle">IDLE</span>
                    </div>
                    <p class="text-secondary card-label fw-medium mb-1">Exhaust Fan</p>
                    <h3 id="fan-text" class="fw-bold mb-1 text-dark">Standby</h3>
                    <p class="text-muted card-subtext m-0">Auto-High Speed</p>
                </div>
            </div>

            <!-- Water Pump Relay Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="icon-shape icon-pump mb-0">
                            <i class="fa-solid fa-faucet-drip"></i>
                        </div>
                        <span id="pump-badge" class="badge badge-status status-idle">IDLE</span>
                    </div>
                    <p class="text-secondary card-label fw-medium mb-1">Water Pump</p>
                    <h3 id="pump-text" class="fw-bold mb-1 text-dark">Standby</h3>
                    <p class="text-muted card-subtext m-0">Relay State</p>
                </div>
            </div>

        </div>
        <!-- NEW: Real-time Logging Data Tables Layout -->
        <div class="row g-4">
            <!-- Sensor Data Logs Table -->
            <div class="col-12 col-xl-6">
                <div class="log-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-microchip text-warning me-2"></i>Recent Sensor Telemetry</h5>
                        <span class="badge bg-light text-muted fw-normal">Last 5 records</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover border-top">
                            <thead>
                                <tr>
                                    <th>Temp</th>
                                    <th>Humidity</th>
                                    <th>Water State</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="sensor-table-body">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Awaiting system payload updates...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Machine Logs Table -->
            <div class="col-12 col-xl-6">
                <div class="log-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-gears text-purple me-2"></i>Recent Machine Status Activity</h5>
                        <span class="badge bg-light text-muted fw-normal">Last 5 records</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover border-top">
                            <thead>
                                <tr>
                                    <th>Fan</th>
                                    <th>Exhaust</th>
                                    <th>Water Pump</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="machine-table-body">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Awaiting system payload updates...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

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
                            <div class="text-start fs-6">
                                <p class="text-danger fw-semibold mb-2">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Device Communication Suspended</strong>
                                </p>
                                <p class="text-muted small mb-3">
                                    The system has detected no active telemetry data updates for over <strong>5 minutes</strong> (Last recorded transmission: <em>${formattedAge}</em>).
                                </p>
                                <hr>
                                <span class="fw-bold d-block mb-1 text-dark">Possible Operational Causes:</span>
                                <ul class="text-secondary small ps-3 mb-0">
                                    <li>Hardware controller power disruption or power loss</li>
                                    <li>Local Wi-Fi / LAN network connection failure</li>
                                    <li>Sensor interface cable disconnect or module hardware error</li>
                                </ul>
                            </div>
                        `,
                            icon: 'warning',
                            confirmButtonText: 'Acknowledge & Dismiss',
                            confirmButtonColor: '#dc2626',
                            allowOutsideClick: false,
                            backdrop: `rgba(15, 23, 42, 0.6)`
                        }).then(() => {
                            isWarningModalOpen = false;
                        });
                    }
                }
            }

            // Function 1: Updates Real-time Summary Cards
            function updateDashboard() {
                $.ajax({
                    url: 'function/get_live_data.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) return;

                        // Update core sensor metrics
                        $('#live-temp').text(data.temperature + '°C');
                        $('#live-humidity').text(data.humidity + '%');

                        // Water Level State Handling
                        const waterVal = data.water ? data.water.toString().toLowerCase() : 'off';

                        if (['on', 'active', 'normal', 'high'].includes(waterVal)) {
                            $('#live-water').text('NORMAL');
                            $('#water-badge')
                                .text('OK')
                                .removeClass('status-idle bg-danger text-white')
                                .addClass('status-active');
                            $('#water-subtext').html('<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Level adequate</span>');
                        } else {
                            $('#live-water').text('LOW LEVEL');
                            $('#water-badge')
                                .text('WARN')
                                .removeClass('status-active status-idle')
                                .addClass('bg-danger text-white');
                            $('#water-subtext').html('<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Supply low</span>');
                        }

                        // Timestamps
                        const timeAgoText = formatTimeAgo(data.sensor_date, data.sensor_time);
                        $('#temp-last-updated').text(timeAgoText);
                        $('#humidity-last-updated').text(timeAgoText);
                        $('#water-last-updated').text(timeAgoText);

                        // Exhaust Fan UI
                        if (data.exhaust === 'on' || data.exhaust === 'active' || data.fan === 'on') {
                            $('#fan-badge').text('ACTIVE').removeClass('status-idle').addClass('status-active');
                            $('#fan-text').text('Running');
                            $('#fan-icon').addClass('spin-slow');
                        } else {
                            $('#fan-badge').text('IDLE').removeClass('status-active').addClass('status-idle');
                            $('#fan-text').text('Standby');
                            $('#fan-icon').removeClass('spin-slow');
                        }

                        // Water Pump UI
                        if (data.water_pump === 'on' || data.water_pump === 'active') {
                            $('#pump-badge').text('ACTIVE').removeClass('status-idle').addClass('status-active');
                            $('#pump-text').text('Running');
                        } else {
                            $('#pump-badge').text('IDLE').removeClass('status-active').addClass('status-idle');
                            $('#pump-text').text('Standby');
                        }

                        // Run Stale Data Check (Triggers modal if telemetry date/time exceeds 5 mins)
                        checkStaleData(data.sensor_date, data.sensor_time);
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
                                const waterBadge = (row.water === 'on' || row.water === 'active') ? 'bg-success' : 'bg-secondary';
                                const formattedTime = format12Hour(row.time);

                                sensorHtml += `<tr>
                        <td class="fw-semibold text-amber">${row.temperature}°C</td>
                        <td class="text-info">${row.humidity}%</td>
                        <td><span class="badge ${waterBadge}">${row.water}</span></td>
                        <td class="text-muted small">${row.date} | ${formattedTime}</td>
                    </tr>`;
                            });
                        } else {
                            sensorHtml = '<tr><td colspan="4" class="text-center text-muted">No telemetry logs logged yet.</td></tr>';
                        }
                        $('#sensor-table-body').html(sensorHtml);

                        // Machine Table
                        let machineHtml = '';
                        if (data.machine_logs && data.machine_logs.length > 0) {
                            data.machine_logs.forEach(function(row) {
                                const formattedTime = format12Hour(row.time);

                                machineHtml += `<tr>
                        <td><span class="badge ${row.fan === 'on' ? 'bg-success' : 'bg-secondary'}">${row.fan}</span></td>
                        <td><span class="badge ${row.exhaust === 'on' ? 'bg-success' : 'bg-secondary'}">${row.exhaust}</span></td>
                        <td><span class="badge ${row.water_pump === 'on' ? 'bg-success' : 'bg-secondary'}">${row.water_pump}</span></td>
                        <td class="text-muted small">${row.date} | ${formattedTime}</td>
                    </tr>`;
                            });
                        } else {
                            machineHtml = '<tr><td colspan="4" class="text-center text-muted">No operational state logs found.</td></tr>';
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