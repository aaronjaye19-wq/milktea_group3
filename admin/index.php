<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/admin-header.php';

// Stats
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_available = 1")->fetchColumn();

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
$monthly = $pdo->query("SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%b') ORDER BY YEAR(created_at), MONTH(created_at)")->fetchAll();
$max_monthly = max(1, ...array_map(fn($item) => (int)$item['total'], $monthly));

$status_badge = [
    'Pending'          => 'badge-pending',
    'Confirmed'        => 'badge-confirmed',
    'Preparing'        => 'badge-preparing',
    'Out for Delivery' => 'badge-delivery',
    'Completed'        => 'badge-completed',
    'Cancelled'        => 'badge-cancelled',
];
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= (int)$total_orders ?></div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending Orders</div>
        <div class="stat-value"><?= (int)$pending_orders ?></div>
        <div class="stat-sub">Needs attention</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value" style="font-size:20px;">&#8369;<?= number_format($total_revenue, 2) ?></div>
        <div class="stat-sub">Exc. cancelled</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?= (int)$total_products ?></div>
        <div class="stat-sub">Available on menu</div>
    </div>
</div>

<div class="dashboard-charts">
    <div class="chart-card">
        <h3>Orders over time</h3>
        <div class="bar-chart">
            <?php foreach ($monthly as $item): ?>
                <div class="bar-item"><div class="bar" style="height:<?= max(4, round(((int)$item['total'] / $max_monthly) * 150)) ?>px"></div><span class="bar-label"><?= sanitize($item['month']) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="chart-card">
        <h3>Order status</h3>
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
