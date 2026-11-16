<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Checkout';

if (!is_user_logged_in()) {
    redirect('login.php?next=' . rawurlencode('checkout.php'));
}

$cart = get_cart();
if (empty($cart)) { redirect('/cart.php'); }

$errors = [];
$order_id = null;
$order_reference = null;

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
        $order_reference = 'ST-' . strtoupper(bin2hex(random_bytes(5)));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO orders (order_reference, customer_name, customer_email, customer_phone, delivery_address, payment_method, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->execute([$order_reference, $name, $email, $phone, $address, $payment, $total]);
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
            $_SESSION['recent_order_products'] = array_values(array_unique(array_merge(
                $_SESSION['recent_order_products'] ?? [],
                array_map(static fn($item) => (int)$item['product_id'], $cart)
            )));
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

<section class="checkout-heading">
    <div class="container checkout-heading-inner">
        <div><p class="checkout-kicker">SERENITEA · SECURE CHECKOUT</p><h1>Almost yours.</h1><p>Review your order and delivery details below.</p></div>
        <a class="checkout-back" href="<?= app_url('cart.php') ?>">← Back to cart</a>
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

            <form method="POST" class="checkout-form">
                <section class="checkout-card">
                    <p class="checkout-step">01 · YOUR DETAILS</p><h3>Contact information</h3>
                    <div class="form-group"><label class="form-label" for="checkout-name">Full name</label><input id="checkout-name" type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" autocomplete="name" placeholder="Juan dela Cruz" required></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label" for="checkout-email">Email address</label><input id="checkout-email" type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" autocomplete="email" placeholder="you@email.com" required></div>
                        <div class="form-group"><label class="form-label" for="checkout-phone">Phone number</label><input id="checkout-phone" type="tel" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>" autocomplete="tel" placeholder="09XX XXX XXXX" required></div>
                    </div>
                </section>
                <section class="checkout-card">
                    <p class="checkout-step">02 · DELIVERY</p><h3>Where should we bring it?</h3>
                    <div class="form-group"><label class="form-label" for="checkout-address">Delivery address</label><textarea id="checkout-address" name="address" class="form-control" autocomplete="street-address" placeholder="Street, Barangay, City, Province" required><?= sanitize($_POST['address'] ?? '') ?></textarea></div>
                </section>
                <section class="checkout-card checkout-payment-card">
                    <p class="checkout-step">03 · PAYMENT</p><h3>Choose a payment method</h3>
                    <div class="payment-options"><?php foreach ($payment_methods as $pm): ?><label class="payment-option"><input type="radio" name="payment" value="<?= sanitize($pm['value']) ?>" <?= ($_POST['payment'] ?? '') === $pm['value'] ? 'checked' : '' ?> required><span class="payment-option-icon"><?= $pm['icon'] ?></span><span class="payment-option-label"><?= sanitize($pm['label']) ?></span></label><?php endforeach; ?></div>
                </section>
                <button type="submit" class="btn btn-primary btn-lg btn-block checkout-submit">Place order <span aria-hidden="true">→</span></button>
                <p class="checkout-secure">Your order details are submitted securely and used only to fulfill your order.</p>
            </form>
        </div>

        <!-- Right: Summary -->
        <div>
            <div class="order-summary checkout-summary">
                <p class="checkout-step">YOUR ORDER</p><h3>Order summary</h3>
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
