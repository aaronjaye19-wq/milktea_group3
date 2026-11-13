<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Cart';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        foreach ($_POST['qty'] as $key => $qty) {
            update_cart_qty($key, (int)$qty);
        }
    } elseif ($action === 'remove') {
        remove_from_cart($_POST['key'] ?? '');
    }
    redirect('/cart.php');
}

$cart  = get_cart();
$total = cart_total();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Your Cart</h1>
        <p><?= count($cart) ?> item(s) in your cart</p>
    </div>
</section>

<div class="container">
    <?php if (empty($cart)): ?>
    <div class="cart-empty">
        <div class="cart-empty-icon">&#9749;</div>
        <h2>Your cart is empty</h2>
        <p>Looks like you haven't added anything yet.</p>
                <a href="<?= app_url('index.php') ?>" class="btn btn-primary">Browse Menu</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">

        <!-- Cart Items -->
        <div>
            <form method="POST" id="cart-form">
                <input type="hidden" name="action" value="update">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customization</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $key => $item): ?>
                        <tr>
                            <td>
                                <div class="cart-item-info">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= sanitize($item['image_url']) ?>" class="cart-item-img" alt="<?= sanitize($item['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="cart-item-img-placeholder">&#9749;</div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="cart-item-name"><?= sanitize($item['product_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cart-item-meta">
                                    <?= sanitize($item['size']) ?> &bull; <?= sanitize($item['sugar_level']) ?> Sugar &bull; <?= sanitize($item['ice_level']) ?>
                                </div>
                            </td>
                            <td style="white-space:nowrap;"><?= format_price($item['unit_price']) ?></td>
                            <td>
                                <input type="number" class="cart-qty-input" name="qty[<?= sanitize($key) ?>]" value="<?= (int)$item['quantity'] ?>" min="1" max="20"
                                    onchange="document.getElementById('cart-form').submit()">
                            </td>
                            <td style="font-weight:600;white-space:nowrap;"><?= format_price($item['subtotal']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="key" value="<?= sanitize($key) ?>">
                                    <button type="submit" class="cart-remove-btn" title="Remove">&#10005;</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <div style="margin-top:20px;">
                <a href="<?= app_url('index.php') ?>" class="btn btn-secondary btn-sm">&larr; Continue Shopping</a>
            </div>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cart as $item): ?>
                <div class="summary-row">
                    <span><?= sanitize($item['product_name']) ?> &times; <?= (int)$item['quantity'] ?></span>
                    <span><?= format_price($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row" style="margin-top:6px;">
                    <span>Delivery Fee</span>
                    <span>FREE</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span><?= format_price($total) ?></span>
                </div>
                <a href="<?= app_url('checkout.php') ?>" class="btn btn-primary btn-block btn-lg" style="margin-top:18px;">Proceed to Checkout</a>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
