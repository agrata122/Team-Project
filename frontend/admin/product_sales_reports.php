<?php
require_once 'includes/report_header.php';

// Get date range from query parameters or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Query to get product sales statistics
$salesQuery = "
    SELECT 
        p.product_id,
        p.product_name,
        p.product_category_name as category,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(pc.quantity) as total_quantity,
        SUM(pc.quantity * p.price) as total_sales,
        AVG(p.price) as average_price
    FROM product p
    LEFT JOIN product_cart pc ON p.product_id = pc.product_id
    LEFT JOIN cart c ON pc.cart_id = c.cart_id
    LEFT JOIN orders o ON c.cart_id = o.cart_id
    WHERE TRUNC(o.order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
    GROUP BY p.product_id, p.product_name, p.product_category_name
    ORDER BY total_sales DESC
";

$salesStmt = oci_parse($conn, $salesQuery);
oci_bind_by_name($salesStmt, ":start_date", $startDate);
oci_bind_by_name($salesStmt, ":end_date", $endDate);
oci_execute($salesStmt);

$productSales = [];
while ($row = oci_fetch_assoc($salesStmt)) {
    $productSales[] = $row;
}

// Query to get category-wise sales
$categoryQuery = "
    SELECT 
        p.product_category_name as category,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(pc.quantity) as total_quantity,
        SUM(pc.quantity * p.price) as total_sales
    FROM product p
    LEFT JOIN product_cart pc ON p.product_id = pc.product_id
    LEFT JOIN cart c ON pc.cart_id = c.cart_id
    LEFT JOIN orders o ON c.cart_id = o.cart_id
    WHERE TRUNC(o.order_date) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') AND TO_DATE(:end_date, 'YYYY-MM-DD')
    GROUP BY p.product_category_name
    ORDER BY total_sales DESC
";

$categoryStmt = oci_parse($conn, $categoryQuery);
oci_bind_by_name($categoryStmt, ":start_date", $startDate);
oci_bind_by_name($categoryStmt, ":end_date", $endDate);
oci_execute($categoryStmt);

$categorySales = [];
while ($row = oci_fetch_assoc($categoryStmt)) {
    $categorySales[] = $row;
}
?>

<div class="card">
    <h3 class="card-title"><i class="fas fa-calendar"></i> Date Range</h3>
    <form method="GET" class="date-range-form" style="display: flex; gap: 10px; align-items: center;">
        <div>
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" required>
        </div>
        <div>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Update
        </button>
    </form>
</div>

<div class="chart-container">
    <h3 class="chart-title">Category-wise Sales Distribution</h3>
    <canvas id="categoryChart"></canvas>
</div>

<div class="card">
    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Product Sales Details</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Total Orders</th>
                    <th>Total Quantity</th>
                    <th>Total Sales</th>
                    <th>Average Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productSales as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['PRODUCT_NAME']) ?></td>
                    <td><?= htmlspecialchars($product['CATEGORY']) ?></td>
                    <td><?= number_format($product['TOTAL_ORDERS']) ?></td>
                    <td><?= number_format($product['TOTAL_QUANTITY']) ?></td>
                    <td>$<?= number_format($product['TOTAL_SALES'], 2) ?></td>
                    <td>$<?= number_format($product['AVERAGE_PRICE'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($productSales)): ?>
                <tr>
                    <td colspan="6" class="no-data">No sales data found for the selected period</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Prepare data for the category chart
const categories = <?= json_encode(array_column($categorySales, 'CATEGORY')) ?>;
const categorySales = <?= json_encode(array_column($categorySales, 'TOTAL_SALES')) ?>;
const categoryQuantities = <?= json_encode(array_column($categorySales, 'TOTAL_QUANTITY')) ?>;

// Create the category chart
const ctx = document.getElementById('categoryChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: categories,
        datasets: [{
            label: 'Sales Amount ($)',
            data: categorySales,
            backgroundColor: 'rgba(50, 95, 49, 0.7)',
            yAxisID: 'y'
        }, {
            label: 'Quantity Sold',
            data: categoryQuantities,
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
                    text: 'Sales Amount ($)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Quantity Sold'
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