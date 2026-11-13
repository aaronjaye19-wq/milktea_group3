<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Checkout';

$cart = get_cart();
if (empty($cart)) { redirect('/cart.php'); }

$errors = [];
$order_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment = trim($_POST['payment'] ?? '');

    $allowed_payments = ['Cash on Delivery', 'GCash', 'Maya'];

    if (empty($name))    $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (empty($phone))   $errors[] = 'Phone number is required.';
    if (empty($address)) $errors[] = 'Delivery address is required.';
    if (!in_array($payment, $allowed_payments)) $errors[] = 'Please select a payment method.';

    if (empty($errors)) {
        $total = cart_total();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO orders (customer_name, customer_email, customer_phone, delivery_address, payment_method, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->execute([$name, $email, $phone, $address, $payment, $total]);
            $order_id = $pdo->lastInsertId();

            $item_stmt = $pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, size, sugar_level, ice_level, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($cart as $item) {
                $item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['size'],
                    $item['sugar_level'],
                    $item['ice_level'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                ]);
            }

            $pdo->commit();
            clear_cart();
            redirect('/order-confirmation.php?order_id=' . $order_id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$payment_methods = [
    ['value' => 'Cash on Delivery', 'icon' => '&#128176;', 'label' => 'Cash on Delivery'],
    ['value' => 'GCash',            'icon' => '&#128241;', 'label' => 'GCash'],
    ['value' => 'Maya',             'icon' => '&#128179;', 'label' => 'Maya'],
];

$total = cart_total();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1>Checkout</h1>
        <p>Fill in your details to complete your order</p>
    </div>
</section>

<div class="container">
    <div class="checkout-layout">

        <!-- Left: Form -->
        <div>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $e): ?>
                        <div><?= sanitize($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Customer Info -->
                <div class="checkout-card">
                    <h3>Customer Information</h3>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" placeholder="Juan dela Cruz" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="you@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>" placeholder="09XX XXX XXXX" required>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="checkout-card">
                    <h3>Delivery Address</h3>
                    <div class="form-group">
                        <label class="form-label">Full Address</label>
                        <textarea name="address" class="form-control" placeholder="Street, Barangay, City, Province" required><?= sanitize($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-card">
                    <h3>Payment Method</h3>
                    <div class="payment-options">
                        <?php foreach ($payment_methods as $pm): ?>
                        <label class="payment-option">
                            <input type="radio" name="payment" value="<?= sanitize($pm['value']) ?>" <?= ($_POST['payment'] ?? '') === $pm['value'] ? 'checked' : '' ?>>
                            <span class="payment-option-icon"><?= $pm['icon'] ?></span>
                            <span class="payment-option-label"><?= sanitize($pm['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order</button>
            </form>
        </div>

        <!-- Right: Summary -->
        <div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cart as $item): ?>
                <div class="summary-row">
                    <span><?= sanitize($item['product_name']) ?><br>
                        <small style="font-size:11px;color:var(--text-muted);"><?= sanitize($item['size']) ?> &bull; <?= sanitize($item['sugar_level']) ?> &bull; <?= sanitize($item['ice_level']) ?></small>
                        &times; <?= (int)$item['quantity'] ?>
                    </span>
                    <span><?= format_price($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>FREE</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span><?= format_price($total) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
