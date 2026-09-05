<?php
include "out.php";

// 1. Include database connection and set timezone
include "../include/dbcon.php";
date_default_timezone_set('Asia/Manila');

// 2. Handle Pagination Parameters
$limit = 10;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 3. Handle Date Input Parameter from POST
$today_ph = date('Y-m-d');
$selected_date = isset($_POST['selected_date']) && !empty($_POST['selected_date']) 
    ? trim(mysqli_real_escape_string($conn, $_POST['selected_date'])) 
    : $today_ph;

// 4. Build Query Filter
$where_clauses = ["1=1"];
$where_clauses[] = "m.date = '$selected_date'";
$where_str = implode(" AND ", $where_clauses);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM machine_logs m 
                LEFT JOIN sensor_data s ON m.date = s.date AND m.time = s.time 
                WHERE $where_str";
$count_result = $conn->query($count_query);
$total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $limit);

// Main Query to pull records
$query = "SELECT m.*, s.temperature, s.humidity, s.water 
          FROM machine_logs m 
          LEFT JOIN sensor_data s ON m.date = s.date AND m.time = s.time 
          WHERE $where_str 
          ORDER BY m.id DESC 
          LIMIT $offset, $limit";

$logs_result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Records - Swine Guard</title>

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

        .table-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .filter-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            background-color: #f1f5f9;
            border: 1px solid transparent;
            font-size: 0.9rem;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .table th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table td {
            padding: 14px 20px;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Pill Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 20px;
            text-transform: lowercase;
            text-align: center;
            min-width: 45px;
        }

        .badge-on {
            background-color: #059669;
            color: #ffffff;
        }

        .badge-off {
            background-color: #64748b;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-3 animate__animated animate__fadeIn">

        <!-- Header Panel -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 g-3">
            <div>
                <h1 class="h3 fw-bold text-dark m-0"><i class="fa-solid fa-gears me-2"></i>Recent Machine Status Activity</h1>
                <p class="text-secondary small m-0">Historical collection logs for environmental sensors and hardware triggers (Asia/Manila)</p>
            </div>
        </div>

        <!-- Filter Controls Bar -->
        <form method="POST" id="filterForm" action="">
            <input type="hidden" name="page" id="pageInput" value="<?php echo $page; ?>">

            <div class="filter-section">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label for="selected_date" class="form-label fw-bold text-secondary small mb-1">Select Date</label>
                        <input type="date" id="selected_date" name="selected_date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="resetPageAndSubmit()">
                    </div>
                </div>
            </div>
        </form>

        <!-- Data Presentation Area -->
        <div class="table-card mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fan</th>
                            <th>Exhaust</th>
                            <th>Water Pump</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($logs_result && $logs_result->num_rows > 0) {
                            while ($row = $logs_result->fetch_assoc()) {
                                $fan_state = strtolower($row['fan'] ?? 'off') === 'on' ? 'on' : 'off';
                                $exhaust_state = strtolower($row['exhaust'] ?? 'off') === 'on' ? 'on' : 'off';
                                $pump_state = strtolower($row['water_pump'] ?? 'off') === 'on' ? 'on' : 'off';

                                // Date & 12-Hour Time Format conversion
                                $raw_date = $row['date'] ?? '';
                                $raw_time = $row['time'] ?? '';
                                $full_datetime = trim($raw_date . ' ' . $raw_time);

                                if (!empty($full_datetime)) {
                                    $timestamp = strtotime($full_datetime);
                                    $formatted_date = date('Y-m-d', $timestamp);
                                    $formatted_time = date('h:i:s A', $timestamp);
                                    $display_timestamp = $formatted_date . ' | ' . $formatted_time;
                                } else {
                                    $display_timestamp = '-- | --';
                                }
                        ?>
                                <tr>
                                    <td>
                                        <span class="status-badge <?php echo ($fan_state === 'on') ? 'badge-on' : 'badge-off'; ?>">
                                            <?php echo $fan_state; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo ($exhaust_state === 'on') ? 'badge-on' : 'badge-off'; ?>">
                                            <?php echo $exhaust_state; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo ($pump_state === 'on') ? 'badge-on' : 'badge-off'; ?>">
                                            <?php echo $pump_state; ?>
                                        </span>
                                    </td>

                                    <td class="text-secondary font-monospace">
                                        <?php echo htmlspecialchars($display_timestamp); ?>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center py-4 text-secondary'>No matching activity history logs found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls Section -->
        <?php if ($total_pages > 1) : ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                        <button class="page-link" type="button" onclick="submitPage(<?php echo $page - 1; ?>)">Previous</button>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                            <button class="page-link" type="button" onclick="submitPage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                        <button class="page-link" type="button" onclick="submitPage(<?php echo $page + 1; ?>)">Next</button>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    </div>

    <script>
        function submitPage(pageNumber) {
            document.getElementById('pageInput').value = pageNumber;
            document.getElementById('filterForm').submit();
        }

        function resetPageAndSubmit() {
            document.getElementById('pageInput').value = 1;
            document.getElementById('filterForm').submit();
        }
    </script>
</body>

</html>