<?php
include "out.php";

?>
<?php
include "../include/dbcon.php";
date_default_timezone_set('Asia/Manila');

// 1. Determine Current View Mode (daily, monthly, yearly)
$mode = isset($_POST['mode']) ? $_POST['mode'] : 'daily';

// 2. Build aggregate metrics query based on selected timeframe
if ($mode == 'yearly') {
    $select_group = "SUBSTRING(date, 1, 4) as period"; // Groups by YYYY
    $where_filter = "1=1";
} elseif ($mode == 'monthly') {
    $current_year = date('Y');
    $select_group = "SUBSTRING(date, 1, 7) as period"; // Groups by YYYY-MM
    $where_filter = "date LIKE '$current_year%'";
} else {
    // Daily view: Filters for TODAY only and breaks down hourly using the 'time' column
    $current_date = date('Y-m-d');
    $select_group = "SUBSTRING(time, 1, 2) as period_hour, CONCAT(SUBSTRING(time, 1, 2), ':00') as period"; // Groups by HH:00
    $where_filter = "date = '$current_date'";
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
    <title>Analytics Summary - Swine Guard</title>


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

        .metric-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .table-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .btn-group-toggle .btn {
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .table th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            padding: 14px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table td {
            padding: 14px 20px;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-4 animate__animated animate__fadeIn">

        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 g-3">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Environmental Analytics</h1>
                <p class="text-secondary small m-0">Aggregated tracking metrics for stable monitoring evaluations</p>
            </div>

            <!-- Filter Form Trigger -->
            <form method="POST" action="" id="modeForm">
                <div class="btn-group shadow-sm" role="group">
                    <button type="submit" name="mode" value="daily" class="btn <?php echo ($mode == 'daily') ? 'btn-success' : 'btn-outline-secondary bg-white'; ?>">Daily</button>
                    <button type="submit" name="mode" value="monthly" class="btn <?php echo ($mode == 'monthly') ? 'btn-success' : 'btn-outline-secondary bg-white'; ?>">Monthly</button>
                    <button type="submit" name="mode" value="yearly" class="btn <?php echo ($mode == 'yearly') ? 'btn-success' : 'btn-outline-secondary bg-white'; ?>">Yearly</button>
                </div>
            </form>
        </div>

        <!-- Quick Summary Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="metric-card border-start border-4 border-danger">
                    <h6 class="text-secondary small text-uppercase fw-bold m-0">Temperature Overview (<?php echo htmlspecialchars($mode); ?>)</h6>
                    <div class="row mt-3 text-center">
                        <div class="col-4"><span class="small text-muted d-block">Highest</span><strong class="h4 text-danger"><?php echo isset($sum_res['high_t']) ? round($sum_res['high_t'], 1) : '0'; ?>°C</strong></div>
                        <div class="col-4"><span class="small text-muted d-block">Average</span><strong class="h4 text-dark"><?php echo isset($sum_res['avg_t']) ? round($sum_res['avg_t'], 1) : '0'; ?>°C</strong></div>
                        <div class="col-4"><span class="small text-muted d-block">Lowest</span><strong class="h4 text-primary"><?php echo isset($sum_res['low_t']) ? round($sum_res['low_t'], 1) : '0'; ?>°C</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="metric-card border-start border-4 border-info">
                    <h6 class="text-secondary small text-uppercase fw-bold m-0">Humidity Overview (<?php echo htmlspecialchars($mode); ?>)</h6>
                    <div class="row mt-3 text-center">
                        <div class="col-4"><span class="small text-muted d-block">Highest</span><strong class="h4 text-info"><?php echo isset($sum_res['high_h']) ? round($sum_res['high_h'], 1) : '0'; ?>%</strong></div>
                        <div class="col-4"><span class="small text-muted d-block">Average</span><strong class="h4 text-dark"><?php echo isset($sum_res['avg_h']) ? round($sum_res['avg_h'], 1) : '0'; ?>%</strong></div>
                        <div class="col-4"><span class="small text-muted d-block">Lowest</span><strong class="h4 text-warning"><?php echo isset($sum_res['low_h']) ? round($sum_res['low_h'], 1) : '0'; ?>%</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Dynamic Chart Graphic Card -->
        <div class="table-card p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3">Historical Averages Trend</h5>
            <div style="height: 300px; width: 100%;">
                <canvas id="analyticsChart"></canvas>
            </div>
        </div>

        <!-- Data Aggregation Breakdown Table -->
        <div class="table-card">
            <div class="p-3 bg-light border-bottom">
                <h5 class="fw-bold text-dark m-0 text-capitalize"><?php echo $mode == 'daily' ? 'Hourly' : $mode; ?> Aggregation Logs</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Time Period</th>
                            <th class="text-danger">Max Temp</th>
                            <th class="text-dark">Avg Temp</th>
                            <th class="text-primary">Min Temp</th>
                            <th class="text-info">Max Hum</th>
                            <th class="text-dark">Avg Hum</th>
                            <th class="text-warning">Min Hum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($table_rows)) : ?>
                            <?php foreach ($table_rows as $row) : ?>
                                <tr>
                                    <td class="fw-bold text-secondary font-monospace"><?php echo htmlspecialchars($row['period']); ?></td>
                                    <td class="text-danger fw-semibold"><?php echo round($row['max_temp'], 1); ?> °C</td>
                                    <td class="text-dark"><?php echo round($row['avg_temp'], 1); ?> °C</td>
                                    <td class="text-primary"><?php echo round($row['min_temp'], 1); ?> °C</td>
                                    <td class="text-info fw-semibold"><?php echo round($row['max_hum'], 1); ?> %</td>
                                    <td class="text-dark"><?php echo round($row['avg_hum'], 1); ?> %</td>
                                    <td class="text-warning"><?php echo round($row['min_hum'], 1); ?> %</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No data logs recorded for this timeframe balance yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>



    <script>
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                        label: 'Avg Temperature (°C)',
                        data: <?php echo json_encode($chart_temp); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Avg Humidity (%)',
                        data: <?php echo json_encode($chart_hum); ?>,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>

</html>