<?php
include "out.php";
include "../include/dbcon.php";
date_default_timezone_set('Asia/Manila');

// 1. Determine Current View Mode (daily, monthly, yearly)
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'daily';

// 2. Build aggregate metrics query based on selected timeframe
if ($mode == 'yearly') {
    $select_group = "SUBSTRING(date, 1, 4) as period"; // Groups by YYYY
    $where_filter = "1=1";
    $mode_title   = "Multi-Year Overview";
    $period_unit  = "Years";
} elseif ($mode == 'monthly') {
    $current_year = date('Y');
    $select_group = "SUBSTRING(date, 1, 7) as period"; // Groups by YYYY-MM
    $where_filter = "date LIKE '$current_year%'";
    $mode_title   = "Year $current_year Monthly Breakdown";
    $period_unit  = "Months";
} else {
    // Daily view: Filters for TODAY only and breaks down hourly using the 'time' column
    $current_date = date('Y-m-d');
    $select_group = "SUBSTRING(time, 1, 2) as period_hour, CONCAT(SUBSTRING(time, 1, 2), ':00') as period"; // Groups by HH:00
    $where_filter = "date = '$current_date'";
    $mode_title   = "Today's Hourly Cycle (" . date('M d, Y') . ")";
    $period_unit  = "Hours";
}

// Extract stats, filtering out the 0.0 sensor fallback values
$stats_query = "SELECT 
                    $select_group,
                    MAX(CAST(temperature AS DECIMAL(4,1))) as max_temp,
                    AVG(CAST(temperature AS DECIMAL(4,1))) as avg_temp,
                    MIN(CAST(temperature AS DECIMAL(4,1))) as min_temp,
                    MAX(CAST(humidity AS DECIMAL(4,1))) as max_hum,
                    AVG(CAST(humidity AS DECIMAL(4,1))) as avg_hum,
                    MIN(CAST(humidity AS DECIMAL(4,1))) as min_hum
                FROM sensor_data 
                WHERE $where_filter AND temperature != '0.0' AND humidity != '0.0'
                GROUP BY period 
                ORDER BY period DESC";

$result = $conn->query($stats_query);

// 3. Format overall summary metrics for top cards (Contextual to filter mode)
$summary_query = "SELECT 
                    MAX(CAST(temperature AS DECIMAL(4,1))) as high_t, 
                    MIN(CAST(temperature AS DECIMAL(4,1))) as low_t, 
                    AVG(CAST(temperature AS DECIMAL(4,1))) as avg_t,
                    MAX(CAST(humidity AS DECIMAL(4,1))) as high_h, 
                    MIN(CAST(humidity AS DECIMAL(4,1))) as low_h, 
                    AVG(CAST(humidity AS DECIMAL(4,1))) as avg_h
                  FROM sensor_data WHERE $where_filter AND temperature != '0.0' AND humidity != '0.0'";
$sum_res = $conn->query($summary_query)->fetch_assoc();

$high_t = isset($sum_res['high_t']) ? (float)$sum_res['high_t'] : 0;
$low_t  = isset($sum_res['low_t'])  ? (float)$sum_res['low_t']  : 0;
$avg_t  = isset($sum_res['avg_t'])  ? (float)$sum_res['avg_t']  : 0;
$high_h = isset($sum_res['high_h']) ? (float)$sum_res['high_h'] : 0;
$low_h  = isset($sum_res['low_h'])  ? (float)$sum_res['low_h']  : 0;
$avg_h  = isset($sum_res['avg_h'])  ? (float)$sum_res['avg_h']  : 0;

$t_spread = round($high_t - $low_t, 1);
$h_spread = round($high_h - $low_h, 1);

// Swine Thermal Comfort Assessment
if ($avg_t == 0) {
    $comfort_class = 'sg-zone-warn';
    $comfort_icon  = 'fa-solid fa-circle-question';
    $comfort_text  = 'No Sensor Data';
} elseif ($avg_t >= 18 && $avg_t <= 26) {
    $comfort_class = 'sg-zone-optimal';
    $comfort_icon  = 'fa-solid fa-circle-check';
    $comfort_text  = 'Optimal Comfort Zone (18°C–26°C)';
} elseif ($avg_t > 26 && $avg_t <= 30) {
    $comfort_class = 'sg-zone-warn';
    $comfort_icon  = 'fa-solid fa-triangle-exclamation';
    $comfort_text  = 'Warm Warning (26°C–30°C)';
} elseif ($avg_t > 30) {
    $comfort_class = 'sg-zone-danger';
    $comfort_icon  = 'fa-solid fa-fire';
    $comfort_text  = 'Heat Stress Alert (>30°C)';
} else {
    $comfort_class = 'sg-zone-warn';
    $comfort_icon  = 'fa-solid fa-snowflake';
    $comfort_text  = 'Cold Stress Warning (<18°C)';
}

// 4. Prepare arrays to feed into the Javascript Graph
$chart_labels = [];
$chart_temp = [];
$chart_hum = [];

$table_rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $table_rows[] = $row;
        // Collect chart data (reverse order for left-to-right chronological visualization)
        array_unshift($chart_labels, $row['period']);
        array_unshift($chart_temp, round($row['avg_temp'], 1));
        array_unshift($chart_hum, round($row['avg_hum'], 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Analytics - Swine Guard</title>

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
            
            <!-- Page Header with Modern Segmented Filter -->
            <div class="sg-page-header">
                <div>
                    <h1 class="sg-page-title"><i class="fa-solid fa-chart-line text-purple me-2"></i>Environmental Analytics</h1>
                    <p class="sg-page-subtitle"><?php echo htmlspecialchars($mode_title); ?> &bull; Climate trends &amp; comfort threshold analysis</p>
                </div>
                <form method="POST" action="" id="modeForm">
                    <div class="sg-analytics-pill-nav">
                        <button type="submit" name="mode" value="daily" class="sg-analytics-pill <?php echo ($mode == 'daily') ? 'active' : ''; ?>">
                            <i class="fa-regular fa-clock"></i> Today (Hourly)
                        </button>
                        <button type="submit" name="mode" value="monthly" class="sg-analytics-pill <?php echo ($mode == 'monthly') ? 'active' : ''; ?>">
                            <i class="fa-regular fa-calendar"></i> This Year (Monthly)
                        </button>
                        <button type="submit" name="mode" value="yearly" class="sg-analytics-pill <?php echo ($mode == 'yearly') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-calendar-days"></i> Multi-Year
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Insight Bar -->
            <div class="sg-insight-bar">
                <div class="sg-insight-item">
                    <i class="fa-solid fa-database text-purple"></i>
                    <span>Logged Intervals: <strong><?php echo count($table_rows); ?> <?php echo htmlspecialchars($period_unit); ?></strong></span>
                </div>
                <div class="sg-insight-item">
                    <i class="fa-solid fa-arrows-left-right text-red"></i>
                    <span>Temp Variance: <strong><?php echo $t_spread; ?>&deg;C</strong></span>
                </div>
                <div class="sg-insight-item">
                    <i class="fa-solid fa-droplet text-cyan"></i>
                    <span>Humidity Variance: <strong><?php echo $h_spread; ?>%</strong></span>
                </div>
                <div class="ms-auto">
                    <span class="sg-zone-badge <?php echo $comfort_class; ?>">
                        <i class="<?php echo $comfort_icon; ?>"></i> <?php echo $comfort_text; ?>
                    </span>
                </div>
            </div>

            <!-- Dual Hero Metric Cards -->
            <div class="row g-4 mb-4">
                <!-- Temperature Analytics Card -->
                <div class="col-12 col-lg-6">
                    <div class="sg-analytics-card temp-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="sg-icon-box sg-icon-temp">
                                    <i class="fa-solid fa-temperature-half"></i>
                                </div>
                                <div>
                                    <h4 class="sg-card-title fs-5 m-0">Thermal Spectrum</h4>
                                    <p class="text-sg-muted small m-0">Aggregated thermal readings (<?php echo ucfirst($mode); ?>)</p>
                                </div>
                            </div>
                            <span class="sg-badge sg-badge-idle"><i class="fa-regular fa-clock me-1"></i><?php echo ucfirst($mode); ?></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-red"><i class="fa-solid fa-arrow-trend-up"></i> Peak High</div>
                                    <div class="stat-value text-red"><?php echo round($high_t, 1); ?>&deg;C</div>
                                    <div class="stat-sub">Max Registered</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-purple-light"><i class="fa-solid fa-chart-simple"></i> Mean Avg</div>
                                    <div class="stat-value text-purple-light"><?php echo round($avg_t, 1); ?>&deg;C</div>
                                    <div class="stat-sub">Thermal Baseline</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-cyan"><i class="fa-solid fa-arrow-trend-down"></i> Valley Low</div>
                                    <div class="stat-value text-cyan"><?php echo round($low_t, 1); ?>&deg;C</div>
                                    <div class="stat-sub">Min Registered</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Humidity Analytics Card -->
                <div class="col-12 col-lg-6">
                    <div class="sg-analytics-card hum-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="sg-icon-box sg-icon-humidity">
                                    <i class="fa-solid fa-droplet"></i>
                                </div>
                                <div>
                                    <h4 class="sg-card-title fs-5 m-0">Moisture Spectrum</h4>
                                    <p class="text-sg-muted small m-0">Relative moisture concentrations (<?php echo ucfirst($mode); ?>)</p>
                                </div>
                            </div>
                            <span class="sg-badge sg-badge-cyan"><i class="fa-solid fa-water me-1"></i><?php echo ucfirst($mode); ?></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-cyan"><i class="fa-solid fa-arrow-trend-up"></i> Peak High</div>
                                    <div class="stat-value text-cyan"><?php echo round($high_h, 1); ?>%</div>
                                    <div class="stat-sub">Max Moisture</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-cyan"><i class="fa-solid fa-chart-simple"></i> Mean Avg</div>
                                    <div class="stat-value text-cyan"><?php echo round($avg_h, 1); ?>%</div>
                                    <div class="stat-sub">Relative Average</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="sg-stat-micro-cell">
                                    <div class="stat-label text-amber"><i class="fa-solid fa-arrow-trend-down"></i> Valley Low</div>
                                    <div class="stat-value text-amber"><?php echo round($low_h, 1); ?>%</div>
                                    <div class="stat-sub">Min Moisture</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chronological Trend Chart -->
            <div class="sg-card mb-4">
                <div class="sg-card-header">
                    <div>
                        <h5 class="sg-card-title"><i class="fa-solid fa-chart-area text-purple me-2"></i>Environmental Variance &amp; Chronological Curves</h5>
                        <p class="text-sg-muted small m-0">Visual comparison of average temperature and humidity across recording intervals</p>
                    </div>
                    <span class="sg-badge sg-badge-purple"><i class="fa-solid fa-wave-square me-1"></i><?php echo ucfirst($mode); ?> Trend</span>
                </div>
                <div style="height:320px;width:100%;"><canvas id="analyticsChart"></canvas></div>
            </div>

            <!-- Detailed Aggregation Logs Table -->
            <div class="sg-card">
                <div class="sg-card-header">
                    <div>
                        <h5 class="sg-card-title"><i class="fa-solid fa-table-list text-purple me-2"></i><?php echo ($mode == 'daily') ? 'Hourly' : ucfirst($mode); ?> Telemetry Aggregation Logs</h5>
                        <p class="text-sg-muted small m-0">Summary records aggregated directly from calibrated pen sensors</p>
                    </div>
                    <span class="sg-badge sg-badge-idle"><?php echo count($table_rows); ?> recorded intervals</span>
                </div>
                <div class="sg-table-wrap">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Interval</th>
                                <th class="text-red">Max Temp</th>
                                <th>Avg Temp</th>
                                <th class="text-cyan">Min Temp</th>
                                <th class="text-cyan">Max Hum</th>
                                <th>Avg Hum</th>
                                <th class="text-amber">Min Hum</th>
                                <th>Thermal Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($table_rows)): foreach ($table_rows as $row): 
                                $rowAvgT = (float)$row['avg_temp'];
                                if ($rowAvgT >= 18 && $rowAvgT <= 26) {
                                    $rowStatusBadge = '<span class="sg-badge sg-badge-active"><i class="fa-solid fa-circle-check me-1"></i>Optimal</span>';
                                } elseif ($rowAvgT > 26 && $rowAvgT <= 30) {
                                    $rowStatusBadge = '<span class="sg-badge sg-badge-amber"><i class="fa-solid fa-triangle-exclamation me-1"></i>Elevated</span>';
                                } elseif ($rowAvgT > 30) {
                                    $rowStatusBadge = '<span class="sg-badge sg-badge-danger"><i class="fa-solid fa-fire me-1"></i>Heat Stress</span>';
                                } else {
                                    $rowStatusBadge = '<span class="sg-badge sg-badge-cyan"><i class="fa-solid fa-snowflake me-1"></i>Cold</span>';
                                }
                            ?>
                            <tr>
                                <td class="fw-semibold text-purple font-monospace"><?php echo htmlspecialchars($row['period']); ?></td>
                                <td class="text-red fw-semibold"><?php echo round($row['max_temp'], 1); ?> &deg;C</td>
                                <td class="fw-bold text-white"><?php echo round($row['avg_temp'], 1); ?> &deg;C</td>
                                <td class="text-cyan"><?php echo round($row['min_temp'], 1); ?> &deg;C</td>
                                <td class="text-cyan fw-semibold"><?php echo round($row['max_hum'], 1); ?> %</td>
                                <td class="fw-bold text-white"><?php echo round($row['avg_hum'], 1); ?> %</td>
                                <td class="text-amber"><?php echo round($row['min_hum'], 1); ?> %</td>
                                <td><?php echo $rowStatusBadge; ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-sg-muted">
                                        <i class="fa-solid fa-chart-pie fs-1 mb-3 d-block text-secondary opacity-50"></i>
                                        <span class="fs-6 d-block fw-medium">No telemetry aggregation records found</span>
                                        <small>Sensor data will populate here automatically as readings are logged.</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('analyticsChart').getContext('2d');

        // Create glowing area gradients
        const tempGradient = ctx.createLinearGradient(0, 0, 0, 300);
        tempGradient.addColorStop(0, 'rgba(239, 68, 68, 0.28)');
        tempGradient.addColorStop(1, 'rgba(239, 68, 68, 0.00)');

        const humGradient = ctx.createLinearGradient(0, 0, 0, 300);
        humGradient.addColorStop(0, 'rgba(6, 182, 212, 0.25)');
        humGradient.addColorStop(1, 'rgba(6, 182, 212, 0.00)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Avg Temperature (°C)',
                        data: <?php echo json_encode($chart_temp); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: tempGradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#0e0e18',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Avg Humidity (%)',
                        data: <?php echo json_encode($chart_hum); ?>,
                        borderColor: '#06b6d4',
                        backgroundColor: humGradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#06b6d4',
                        pointBorderColor: '#0e0e18',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#cbd5e1',
                            font: { family: "'Inter', sans-serif", size: 12, weight: '500' },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(22, 22, 38, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(124, 58, 237, 0.35)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        boxPadding: 6,
                        usePointStyle: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: { color: '#8892a4', font: { family: "'Inter', sans-serif", size: 11 } },
                        grid:  { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#8892a4', font: { family: "'Inter', sans-serif", size: 11 } },
                        grid:  { color: 'rgba(255, 255, 255, 0.05)' }
                    }
                }
            }
        });
    </script>
</body>

</html>