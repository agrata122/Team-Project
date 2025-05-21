<?php
require_once 'includes/report_header.php';

// Get the start and end of the current week
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

// Query to get weekly sales statistics
$salesQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as average_order_value
    FROM orders 
    WHERE TRUNC(order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
";

$salesStmt = oci_parse($conn, $salesQuery);
oci_bind_by_name($salesStmt, ":start_date", $startOfWeek);
oci_bind_by_name($salesStmt, ":end_date", $endOfWeek);
oci_execute($salesStmt);
$salesStats = oci_fetch_assoc($salesStmt);

// Query to get daily sales data for the chart
$dailyQuery = "
    SELECT 
        TO_CHAR(order_date, 'DY') as day,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders 
    WHERE TRUNC(order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
    GROUP BY TO_CHAR(order_date, 'DY')
    ORDER BY MIN(order_date)
";

$dailyStmt = oci_parse($conn, $dailyQuery);
oci_bind_by_name($dailyStmt, ":start_date", $startOfWeek);
oci_bind_by_name($dailyStmt, ":end_date", $endOfWeek);
oci_execute($dailyStmt);

$dailyData = [];
while ($row = oci_fetch_assoc($dailyStmt)) {
    $dailyData[] = $row;
}
?>

<div class="stats-container">
    <div class="stat-card">
        <h3>Total Orders This Week</h3>
        <div class="value"><?= number_format($salesStats['TOTAL_ORDERS'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Sales This Week</h3>
        <div class="value">$<?= number_format($salesStats['TOTAL_SALES'] ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
        <h3>Average Order Value</h3>
        <div class="value">$<?= number_format($salesStats['AVERAGE_ORDER_VALUE'] ?? 0, 2) ?></div>
    </div>
</div>

<div class="chart-container">
    <h3 class="chart-title">Daily Sales Overview</h3>
    <canvas id="dailySalesChart"></canvas>
</div>

<script>
// Prepare data for the chart
const days = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
const orderCounts = Array(7).fill(0);
const salesAmounts = Array(7).fill(0);

<?php foreach ($dailyData as $data): ?>
const dayIndex = days.indexOf('<?= $data['DAY'] ?>');
if (dayIndex !== -1) {
    orderCounts[dayIndex] = <?= $data['ORDER_COUNT'] ?>;
    salesAmounts[dayIndex] = <?= $data['TOTAL_SALES'] ?>;
}
<?php endforeach; ?>

// Create the chart
const ctx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: days,
        datasets: [{
            label: 'Number of Orders',
            data: orderCounts,
            backgroundColor: 'rgba(50, 95, 49, 0.7)',
            yAxisID: 'y'
        }, {
            label: 'Sales Amount ($)',
            data: salesAmounts,
            backgroundColor: 'rgba(52, 152, 219, 0.7)',
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Orders'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Sales Amount ($)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

</body>
</html> 