<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/admin-header.php';

// Default the analytics range to the current month; accept only valid ISO dates.
$today = new DateTimeImmutable('today');
$default_from = $today->modify('first day of this month')->format('Y-m-d');
$default_to = $today->format('Y-m-d');
$parse_date = static function ($value, $fallback) {
    if (!is_string($value) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value)) {
        return $fallback;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return $fallback;
    }

    return $value;
};
$date_from = $parse_date($_GET['from'] ?? null, $default_from);
$date_to = $parse_date($_GET['to'] ?? null, $default_to);
if ($date_from > $date_to) {
    [$date_from, $date_to] = [$date_to, $date_from];
}

$date_condition = 'created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)';
$metrics_stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders,
    COALESCE(SUM(CASE WHEN status <> 'Cancelled' THEN total_amount ELSE 0 END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN status <> 'Cancelled' THEN 1 ELSE 0 END), 0) AS sales_orders
    FROM orders WHERE $date_condition");
$metrics_stmt->execute([$date_from, $date_to]);
$analytics = $metrics_stmt->fetch();
$gross_stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.subtotal), 0)
    FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.status <> 'Cancelled' AND o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)");
$gross_stmt->execute([$date_from, $date_to]);
$gross_sales = (float)$gross_stmt->fetchColumn();
$total_revenue = (float)$analytics['total_revenue'];
// The current order schema has no discounts, refunds, taxes, or delivery charges to deduct.
$net_revenue = $total_revenue;
$sales_orders = (int)$analytics['sales_orders'];
$average_order_value = $sales_orders > 0 ? $total_revenue / $sales_orders : 0;
$total_orders = (int)$analytics['total_orders'];
$sales_stmt = $pdo->prepare("SELECT DATE(created_at) AS sale_date, SUM(total_amount) AS revenue
    FROM orders WHERE status <> 'Cancelled' AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(created_at) ORDER BY sale_date");
$sales_stmt->execute([$date_from, $date_to]);
$sales_by_day = $sales_stmt->fetchAll();
$max_daily_revenue = max(1, ...array_map(static fn($item) => (float)$item['revenue'], $sales_by_day));

// Recent Orders
$recent_per_page = 5;
$recent_page = max(1, (int)($_GET['page'] ?? 1));
$recent_total = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$recent_pages = max(1, (int)ceil($recent_total / $recent_per_page));
$recent_page = min($recent_page, $recent_pages);
$stmt = $pdo->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $recent_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, ($recent_page - 1) * $recent_per_page, PDO::PARAM_INT);
$stmt->execute();
$recent = $stmt->fetchAll();
$status_counts = $pdo->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$status_badge = [
    'Pending'          => 'badge-pending',
    'Confirmed'        => 'badge-confirmed',
    'Preparing'        => 'badge-preparing',
    'Out for Delivery' => 'badge-delivery',
    'Completed'        => 'badge-completed',
    'Cancelled'        => 'badge-cancelled',
];
?>

<section class="analytics-section" aria-labelledby="sales-analytics-title">
    <div class="analytics-toolbar">
        <div>
            <p class="analytics-eyebrow">SALES PERFORMANCE</p>
            <h3 id="sales-analytics-title">Sales analytics</h3>
            <p class="analytics-caption">Sales update automatically as orders are placed. Cancelled orders are excluded from sales totals.</p>
        </div>
        <form class="analytics-date-filter" method="get" action="<?= app_url('admin/index.php') ?>">
            <div class="analytics-date-field"><label for="analytics-from">From</label><input id="analytics-from" type="date" name="from" value="<?= sanitize($date_from) ?>" required></div>
            <div class="analytics-date-field"><label for="analytics-to">To</label><input id="analytics-to" type="date" name="to" value="<?= sanitize($date_to) ?>" required></div>
            <button class="btn btn-primary" type="submit">Apply dates</button>
        </form>
    </div>

    <div class="stat-grid analytics-grid">
        <div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-value analytics-currency"><?= format_price($total_revenue) ?></div><div class="stat-sub">Non-cancelled orders</div></div>
        <div class="stat-card"><div class="stat-label">Gross Sales</div><div class="stat-value analytics-currency"><?= format_price($gross_sales) ?></div><div class="stat-sub">Before deductions</div></div>
        <div class="stat-card"><div class="stat-label">Net Revenue</div><div class="stat-value analytics-currency"><?= format_price($net_revenue) ?></div><div class="stat-sub">No discounts or refunds recorded</div></div>
        <div class="stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?= number_format($total_orders) ?></div><div class="stat-sub">All statuses in range</div></div>
        <div class="stat-card"><div class="stat-label">Average Order Value</div><div class="stat-value analytics-currency"><?= format_price($average_order_value) ?></div><div class="stat-sub">Per non-cancelled order</div></div>
    </div>
</section>

<div class="dashboard-charts">
    <div class="chart-card">
        <h3>Daily net revenue</h3>
        <?php if (empty($sales_by_day)): ?>
            <p class="analytics-empty">No sales in this date range yet.</p>
        <?php else: ?>
        <div class="bar-chart sales-bar-chart" role="img" aria-label="Daily net revenue for the selected date range">
            <?php foreach ($sales_by_day as $item): ?>
                <div class="bar-item"><div class="bar sales-bar" style="height:<?= max(4, (int)round(((float)$item['revenue'] / $max_daily_revenue) * 150)) ?>px" title="<?= sanitize(date('M j, Y', strtotime($item['sale_date']))) ?>: <?= sanitize(number_format((float)$item['revenue'], 2)) ?> PHP"></div><span class="bar-label"><?= sanitize(date('M j', strtotime($item['sale_date']))) ?></span></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="chart-card">
        <h3>Order status · all time</h3>
        <div class="status-list">
            <?php foreach ($status_badge as $status => $class): ?><div class="status-row"><span><?= sanitize($status) ?></span><strong><?= (int)($status_counts[$status] ?? 0) ?></strong></div><?php endforeach; ?>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">Recent Orders</span>
        <a href="<?= app_url('admin/orders.php') ?>" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent)): ?>
            <tr><td colspan="7" style="text-align:center;color:#9898B5;padding:32px;">No orders yet.</td></tr>
            <?php else: ?>
            <?php foreach ($recent as $order): ?>
            <tr>
                <td style="font-weight:600;">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                <td><?= sanitize($order['customer_name']) ?></td>
                <td>&#8369;<?= number_format($order['total_amount'], 2) ?></td>
                <td><?= sanitize($order['payment_method']) ?></td>
                <td><span class="badge <?= $status_badge[$order['status']] ?? 'badge-pending' ?>"><?= sanitize($order['status']) ?></span></td>
                <td style="font-size:12px;color:#9898B5;"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                <td><a href="<?= app_url('admin/order-detail.php?id=' . (int)$order['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($recent_pages > 1): ?>
    <nav class="pagination" aria-label="Recent orders pagination">
        <?php if ($recent_page > 1): ?><a href="<?= app_url('admin/index.php?page=' . ($recent_page - 1)) ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
        <?php for ($p = 1; $p <= $recent_pages; $p++): ?><a class="<?= $p === $recent_page ? 'current' : '' ?>" href="<?= app_url('admin/index.php?page=' . $p) ?>"><?= $p ?></a><?php endfor; ?>
        <?php if ($recent_page < $recent_pages): ?><a href="<?= app_url('admin/index.php?page=' . ($recent_page + 1)) ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
    </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
