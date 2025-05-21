<?php
require_once 'includes/report_header.php';

// Get the start and end of the current month
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

// Query to get monthly sales statistics
$salesQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as average_order_value
    FROM orders 
    WHERE TRUNC(order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
";

$salesStmt = oci_parse($conn, $salesQuery);
oci_bind_by_name($salesStmt, ":start_date", $startOfMonth);
oci_bind_by_name($salesStmt, ":end_date", $endOfMonth);
oci_execute($salesStmt);
$salesStats = oci_fetch_assoc($salesStmt);

// Query to get daily sales data for the chart
$dailyQuery = "
    SELECT 
        TO_CHAR(order_date, 'DD') as day,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders 
    WHERE TRUNC(order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
    GROUP BY TO_CHAR(order_date, 'DD')
    ORDER BY TO_CHAR(order_date, 'DD')
";

$dailyStmt = oci_parse($conn, $dailyQuery);
oci_bind_by_name($dailyStmt, ":start_date", $startOfMonth);
oci_bind_by_name($dailyStmt, ":end_date", $endOfMonth);
oci_execute($dailyStmt);

$dailyData = [];
while ($row = oci_fetch_assoc($dailyStmt)) {
    $dailyData[] = $row;
}

// Query to get top selling products
$topProductsQuery = "
    SELECT 
        p.product_name AS PRODUCT_NAME,
        SUM(pc.quantity) AS TOTAL_QUANTITY,
        SUM(pc.quantity * p.price) AS TOTAL_SALES
    FROM orders o
    JOIN cart c ON o.cart_id = c.cart_id
    JOIN product_cart pc ON c.cart_id = pc.cart_id
    JOIN product p ON pc.product_id = p.product_id
    WHERE TRUNC(o.order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
    GROUP BY p.product_name
    ORDER BY TOTAL_SALES DESC
    FETCH FIRST 5 ROWS ONLY
";

$topProductsStmt = oci_parse($conn, $topProductsQuery);
oci_bind_by_name($topProductsStmt, ":start_date", $startOfMonth);
oci_bind_by_name($topProductsStmt, ":end_date", $endOfMonth);
oci_execute($topProductsStmt);

$topProducts = [];
while ($row = oci_fetch_assoc($topProductsStmt)) {
    $topProducts[] = $row;
}
oci_free_statement($topProductsStmt);
?>

<div class="stats-container">
    <div class="stat-card">
        <h3>Total Orders This Month</h3>
        <div class="value"><?= number_format($salesStats['TOTAL_ORDERS'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Sales This Month</h3>
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

<div class="chart-container">
    <h3 class="chart-title">Top Selling Products</h3>
    <canvas id="topProductsChart"></canvas>
</div>

<script>
// Prepare data for the daily sales chart
const days = Array.from({length: 31}, (_, i) => String(i + 1).padStart(2, '0'));
const orderCounts = Array(31).fill(0);
const salesAmounts = Array(31).fill(0);

<?php foreach ($dailyData as $data): ?>
const dayIndex = parseInt('<?= $data['DAY'] ?>') - 1;
if (dayIndex >= 0 && dayIndex < 31) {
    orderCounts[dayIndex] = <?= $data['ORDER_COUNT'] ?>;
    salesAmounts[dayIndex] = <?= $data['TOTAL_SALES'] ?>;
}
<?php endforeach; ?>

// Create the daily sales chart
const dailyCtx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: days,
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

// Prepare data for the top products chart
const productNames = <?= json_encode(array_column($topProducts, 'PRODUCT_NAME')) ?>;
const productSales = <?= json_encode(array_column($topProducts, 'TOTAL_SALES')) ?>;

// Create the top products chart
const productsCtx = document.getElementById('topProductsChart').getContext('2d');
new Chart(productsCtx, {
    type: 'bar',
    data: {
        labels: productNames,
        datasets: [{
            label: 'Sales Amount ($)',
            data: productSales,
            backgroundColor: 'rgba(50, 95, 49, 0.7)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales Amount ($)'
                }
            }
        }
    }
});
</script>

</body>
</html> 