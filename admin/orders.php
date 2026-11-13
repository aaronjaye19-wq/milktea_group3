<?php
$page_title = 'Orders';
require_once __DIR__ . '/includes/admin-header.php';

// Status filter
$allowed_statuses = ['All', 'Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Completed', 'Cancelled'];
$filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses) ? $_GET['status'] : 'All';

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$count_sql = $filter === 'All' ? "SELECT COUNT(*) FROM orders" : "SELECT COUNT(*) FROM orders WHERE status = ?";
$count_stmt = $pdo->prepare($count_sql);
if ($filter !== 'All') $count_stmt->execute([$filter]);
else $count_stmt->execute();
$total_pages = max(1, (int)ceil(((int)$count_stmt->fetchColumn()) / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$sql = $filter === 'All' ? "SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?" : "SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$bind = $filter === 'All' ? [$per_page, $offset] : [$filter, $per_page, $offset];
foreach ($bind as $i => $value) $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
$stmt->execute();
$orders = $stmt->fetchAll();

$status_badge = [
    'Pending'          => 'badge-pending',
    'Confirmed'        => 'badge-confirmed',
    'Preparing'        => 'badge-preparing',
    'Out for Delivery' => 'badge-delivery',
    'Completed'        => 'badge-completed',
    'Cancelled'        => 'badge-cancelled',
];
?>

<div class="page-actions">
    <h2>Orders</h2>
</div>

<!-- Status Filter Tabs -->
<div class="category-filter" style="margin-bottom:20px;">
    <?php foreach ($allowed_statuses as $s): ?>
        <a href="<?= app_url('admin/orders.php?status=' . urlencode($s)) ?>"
           class="category-btn <?= $filter === $s ? 'active' : '' ?>"
           style="text-decoration:none;"><?= sanitize($s) ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="text-align:center;color:#9898B5;padding:32px;">No orders found.</td></tr>
            <?php else: ?>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td style="font-weight:600;">#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></td>
                <td><?= sanitize($o['customer_name']) ?></td>
                <td style="font-size:13px;color:#9898B5;"><?= sanitize($o['customer_phone']) ?></td>
                <td style="font-weight:600;">&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                <td><?= sanitize($o['payment_method']) ?></td>
                <td><span class="badge <?= $status_badge[$o['status']] ?? 'badge-pending' ?>"><?= sanitize($o['status']) ?></span></td>
                <td style="font-size:12px;color:#9898B5;"><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
                <td><a href="<?= app_url('admin/order-detail.php?id=' . (int)$o['id']) ?>" class="btn btn-secondary btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($total_pages > 1): ?>
    <nav class="pagination" aria-label="Orders pagination">
        <?php if ($page > 1): ?><a href="<?= app_url('admin/orders.php?status=' . urlencode($filter) . '&page=' . ($page - 1)) ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?><a class="<?= $p === $page ? 'current' : '' ?>" href="<?= app_url('admin/orders.php?status=' . urlencode($filter) . '&page=' . $p) ?>"><?= $p ?></a><?php endfor; ?>
        <?php if ($page < $total_pages): ?><a href="<?= app_url('admin/orders.php?status=' . urlencode($filter) . '&page=' . ($page + 1)) ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
    </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
