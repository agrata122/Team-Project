<?php
require_once 'includes/report_header.php';

// Get today's date
$today = date('Y-m-d');

// Query to get daily sales statistics
$salesQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as average_order_value
    FROM orders 
    WHERE TRUNC(order_date) = TO_DATE(:today, 'YYYY-MM-DD')
";

$salesStmt = oci_parse($conn, $salesQuery);
oci_bind_by_name($salesStmt, ":today", $today);
oci_execute($salesStmt);
$salesStats = oci_fetch_assoc($salesStmt);

// Query to get hourly sales data for the chart
$hourlyQuery = "
    SELECT 
        EXTRACT(HOUR FROM order_date) as hour,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders 
    WHERE TRUNC(order_date) = TO_DATE(:today, 'YYYY-MM-DD')
    GROUP BY EXTRACT(HOUR FROM order_date)
    ORDER BY hour
";

$hourlyStmt = oci_parse($conn, $hourlyQuery);
oci_bind_by_name($hourlyStmt, ":today", $today);
oci_execute($hourlyStmt);

$hourlyData = [];
while ($row = oci_fetch_assoc($hourlyStmt)) {
    $hourlyData[] = $row;
}
?>

<div class="stats-container">
    <div class="stat-card">
        <h3>Total Orders Today</h3>
        <div class="value"><?= number_format($salesStats['TOTAL_ORDERS'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Sales Today</h3>
        <div class="value">$<?= number_format($salesStats['TOTAL_SALES'] ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
        <h3>Average Order Value</h3>
        <div class="value">$<?= number_format($salesStats['AVERAGE_ORDER_VALUE'] ?? 0, 2) ?></div>
    </div>
</div>

<div class="chart-container">
    <h3 class="chart-title">Hourly Sales Overview</h3>
    <canvas id="hourlySalesChart"></canvas>
</div>

<script>
// Prepare data for the chart
const hours = Array.from({length: 24}, (_, i) => i);
const orderCounts = Array(24).fill(0);
const salesAmounts = Array(24).fill(0);

<?php foreach ($hourlyData as $data): ?>
orderCounts[<?= $data['HOUR'] ?>] = <?= $data['ORDER_COUNT'] ?>;
salesAmounts[<?= $data['HOUR'] ?>] = <?= $data['TOTAL_SALES'] ?>;
<?php endforeach; ?>

// Create the chart
const ctx = document.getElementById('hourlySalesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: hours.map(h => `${h}:00`),
        datasets: [{
            label: 'Number of Orders',
            data: orderCounts,
            borderColor: 'rgb(50, 95, 49)',
            backgroundColor: 'rgba(50, 95, 49, 0.1)',
            yAxisID: 'y',
            fill: true
        }, {
            label: 'Sales Amount ($)',
            data: salesAmounts,
            borderColor: 'rgb(52, 152, 219)',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            yAxisID: 'y1',
            fill: true
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