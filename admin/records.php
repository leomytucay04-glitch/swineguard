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

            <!-- Header Panel -->
            <div class="sg-page-header">
                <div>
                    <h1 class="sg-page-title"><i class="fa-solid fa-clock-rotate-left me-2 text-purple"></i>Historical Records</h1>
                    <p class="sg-page-subtitle">Historical collection logs and relay activity (Asia/Manila)</p>
                </div>
            </div>

            <!-- Filter Controls Bar -->
            <form method="POST" id="filterForm" action="">
                <input type="hidden" name="page" id="pageInput" value="<?php echo $page; ?>">

                <div class="sg-card mb-4">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-4">
                            <label for="selected_date" class="sg-form-label">Filter by Date</label>
                            <input type="date" id="selected_date" name="selected_date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="resetPageAndSubmit()">
                        </div>
                    </div>
                </div>
            </form>

            <!-- Data Presentation Area -->
            <div class="sg-card mb-4">
                <div class="sg-card-header">
                    <h5 class="sg-card-title"><i class="fa-solid fa-list-check"></i> Machine State Logs</h5>
                    <span class="sg-badge sg-badge-purple"><?php echo htmlspecialchars($selected_date); ?></span>
                </div>
                <div class="sg-table-wrap">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Cooling Fan</th>
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
                                            <span class="sg-badge <?php echo ($fan_state === 'on') ? 'sg-badge-active' : 'sg-badge-idle'; ?>">
                                                <i class="fa-solid fa-fan me-1 <?php echo ($fan_state === 'on') ? 'spin-slow' : ''; ?>"></i>
                                                <?php echo strtoupper($fan_state); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="sg-badge <?php echo ($exhaust_state === 'on') ? 'sg-badge-active' : 'sg-badge-idle'; ?>">
                                                <i class="fa-solid fa-wind me-1"></i>
                                                <?php echo strtoupper($exhaust_state); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="sg-badge <?php echo ($pump_state === 'on') ? 'sg-badge-cyan' : 'sg-badge-idle'; ?>">
                                                <i class="fa-solid fa-faucet-drip me-1"></i>
                                                <?php echo strtoupper($pump_state); ?>
                                            </span>
                                        </td>
                                        <td class="font-monospace text-sg-muted">
                                            <?php echo htmlspecialchars($display_timestamp); ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-4 text-sg-muted'>No matching activity history logs found for this date.</td></tr>";
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

        </main>
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