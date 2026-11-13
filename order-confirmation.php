<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Order Confirmed';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) { redirect('/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) { redirect('/index.php'); }

$items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div class="confirmation-page">
        <div class="confirmation-icon">&#10003;</div>
        <h1>Order Placed!</h1>
        <p class="subtitle">Thank you, <?= sanitize($order['customer_name']) ?>! Your order has been received and is now being processed.</p>

        <div class="order-details-box">
            <div class="order-detail-row">
                <span class="order-detail-label">Order Number</span>
                <span class="order-detail-value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="order-detail-row">
                <span class="order-detail-label">Date</span>
                <span class="order-detail-value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="order-detail-row">
                <span class="order-detail-label">Payment Method</span>
                <span class="order-detail-value"><?= sanitize($order['payment_method']) ?></span>
            </div>
            <div class="order-detail-row">
                <span class="order-detail-label">Deliver to</span>
                <span class="order-detail-value"><?= sanitize($order['delivery_address']) ?></span>
            </div>
            <div class="order-detail-row">
                <span class="order-detail-label">Status</span>
                <span class="order-detail-value">
                    <span class="badge badge-pending"><?= sanitize($order['status']) ?></span>
                </span>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-details-box" style="text-align:left;margin-bottom:24px;">
            <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border);">Items Ordered</h4>
            <?php foreach ($items as $item): ?>
            <div class="order-detail-row">
                <span class="order-detail-label">
                    <?= sanitize($item['product_name']) ?><br>
                    <small style="font-size:11px;"><?= sanitize($item['size']) ?> &bull; <?= sanitize($item['sugar_level']) ?> Sugar &bull; <?= sanitize($item['ice_level']) ?></small>
                    &times; <?= (int)$item['quantity'] ?>
                </span>
                <span class="order-detail-value"><?= format_price($item['subtotal']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="order-detail-row" style="font-weight:700;color:var(--brown);">
                <span>Total</span>
                <span><?= format_price($order['total_amount']) ?></span>
            </div>
        </div>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= app_url('index.php') ?>" class="btn btn-primary">Order More</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
