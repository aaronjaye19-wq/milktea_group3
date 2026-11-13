<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Order Details';
require_once __DIR__ . '/includes/admin-header.php';

if (!$id) { redirect('admin/orders.php'); }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { redirect('admin/orders.php'); }

$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();

$message = '';
$all_statuses = ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Completed', 'Cancelled'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    if (in_array($new_status, $all_statuses)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $id]);
        $order['status'] = $new_status;
        $message = 'Order status updated to ' . $new_status . '.';
    }
}

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
    <h2>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h2>
    <a href="<?= app_url('admin/orders.php') ?>" class="btn btn-secondary">Back to Orders</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= sanitize($message) ?></div>
<?php endif; ?>

<div class="order-detail-grid">
    <!-- Left: Items + Customer Info -->
    <div>
        <!-- Items -->
        <div class="info-card">
            <h4>Items Ordered</h4>
            <table class="admin-table" style="margin:-4px 0;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Customization</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="font-weight:600;"><?= sanitize($item['product_name']) ?></td>
                        <td style="font-size:12px;color:#9898B5;">
                            <?= sanitize($item['size']) ?> &bull; <?= sanitize($item['sugar_level']) ?> Sugar &bull; <?= sanitize($item['ice_level']) ?>
                        </td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td>&#8369;<?= number_format($item['unit_price'], 2) ?></td>
                        <td style="font-weight:600;">&#8369;<?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:14px 0 0;text-align:right;font-weight:700;font-size:15px;color:#1E1E2D;border-top:1px solid #E8E8EF;margin-top:8px;">
                Total: &#8369;<?= number_format($order['total_amount'], 2) ?>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="info-card">
            <h4>Customer Information</h4>
            <div class="info-row">
                <span class="label">Name</span>
                <span class="value"><?= sanitize($order['customer_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Email</span>
                <span class="value"><?= sanitize($order['customer_email']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Phone</span>
                <span class="value"><?= sanitize($order['customer_phone']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Delivery Address</span>
                <span class="value"><?= sanitize($order['delivery_address']) ?></span>
            </div>
        </div>
    </div>

    <!-- Right: Order Info + Status Update -->
    <div>
        <!-- Order Meta -->
        <div class="info-card">
            <h4>Order Information</h4>
            <div class="info-row">
                <span class="label">Order #</span>
                <span class="value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Date Placed</span>
                <span class="value"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment</span>
                <span class="value"><?= sanitize($order['payment_method']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Current Status</span>
                <span class="value"><span class="badge <?= $status_badge[$order['status']] ?? '' ?>"><?= sanitize($order['status']) ?></span></span>
            </div>
        </div>

        <!-- Update Status -->
        <div class="info-card">
            <h4>Update Order Status</h4>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" style="color:#1E1E2D;">New Status</label>
                    <select name="status" class="form-control status-select" style="width:100%;margin-bottom:12px;">
                        <?php foreach ($all_statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Status</button>
            </form>

            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #E8E8EF;">
                <div style="font-size:12px;color:#9898B5;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Status Flow</div>
                <?php
                $flow = ['Pending','Confirmed','Preparing','Out for Delivery','Completed'];
                foreach ($flow as $i => $step): ?>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;">
                    <span style="width:20px;height:20px;border-radius:50%;background:<?= $order['status'] === $step ? '#7C4A2D' : '#E8E8EF' ?>;display:flex;align-items:center;justify-content:center;font-size:10px;color:<?= $order['status'] === $step ? '#fff' : '#9898B5' ?>;flex-shrink:0;font-weight:700;"><?= $i+1 ?></span>
                    <span style="color:<?= $order['status'] === $step ? '#1E1E2D' : '#9898B5' ?>;font-weight:<?= $order['status'] === $step ? '600' : '400' ?>;"><?= $step ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
