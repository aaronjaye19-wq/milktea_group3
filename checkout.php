<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Secure Checkout';

if (!is_user_logged_in()) {
    redirect('login.php?next=' . rawurlencode('checkout.php'));
}

$cart = get_cart();
if (empty($cart)) {
    redirect('cart.php');
}

$errors = [];
$form_token = create_form_token('checkout');
$customer_stmt = $pdo->prepare('SELECT full_name, email FROM customers WHERE id = ? LIMIT 1');
$customer_stmt->execute([(int) $_SESSION['user_id']]);
$customer = $customer_stmt->fetch() ?: ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_form_token('checkout', $_POST['form_token'] ?? '')) {
        $errors[] = 'This checkout form has expired. Please review your order and try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment = trim($_POST['payment'] ?? '');
    $allowed_payments = ['Cash on Delivery', 'GCash', 'Maya'];

    if ($name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($address === '') $errors[] = 'Delivery address is required.';
    if (!in_array($payment, $allowed_payments, true)) $errors[] = 'Please select a payment method.';

    if (!$errors) {
        $total = cart_total();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO orders (customer_name, customer_email, customer_phone, delivery_address, payment_method, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')",
            );
            $stmt->execute([$name, $email, $phone, $address, $payment, $total]);
            $order_id = (int) $pdo->lastInsertId();

            $item_stmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, size, sugar_level, ice_level, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
            redirect('order-confirmation.php?order_id=' . $order_id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }

    $form_token = create_form_token('checkout');
}

$total = cart_total();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container checkout-page">
    <a class="cart-back-link" href="<?= app_url('cart.php') ?>">&larr; Back to cart</a>
    <header class="checkout-heading">
        <div>
            <p class="cart-eyebrow">ALMOST THERE</p>
            <h1>Complete your order</h1>
            <p>Review your details and choose how you&apos;d like to pay.</p>
        </div>
        <div class="checkout-step" aria-label="Checkout step 2 of 2"><span>02</span><span>of 02</span><strong>Checkout</strong></div>
    </header>

    <div class="checkout-layout checkout-layout-modern">
        <div>
            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert"><?php foreach ($errors as $error): ?><div><?= sanitize($error) ?></div><?php endforeach; ?></div>
            <?php endif; ?>

            <form method="POST" class="checkout-form-modern">
                <input type="hidden" name="form_token" value="<?= sanitize($form_token) ?>">
                <section class="checkout-card checkout-section-card">
                    <div class="checkout-section-heading"><span class="checkout-section-number">01</span><div><h2>Contact information</h2><p>Where should we send your order updates?</p></div></div>
                    <div class="form-group"><label class="form-label" for="checkout-name">Full name</label><input type="text" id="checkout-name" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? $customer['full_name']) ?>" autocomplete="name" placeholder="Your full name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label" for="checkout-email">Email address</label><input type="email" id="checkout-email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? $customer['email']) ?>" autocomplete="email" placeholder="you@example.com" required></div>
                        <div class="form-group"><label class="form-label" for="checkout-phone">Phone number</label><input type="tel" id="checkout-phone" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>" autocomplete="tel" placeholder="09XX XXX XXXX" required></div>
                    </div>
                </section>

                <section class="checkout-card checkout-section-card">
                    <div class="checkout-section-heading"><span class="checkout-section-number">02</span><div><h2>Delivery address</h2><p>Tell us where your tea should arrive.</p></div></div>
                    <div class="form-group"><label class="form-label" for="checkout-address">Full address</label><textarea id="checkout-address" name="address" class="form-control" autocomplete="street-address" placeholder="Street, barangay, city, province" required><?= sanitize($_POST['address'] ?? '') ?></textarea></div>
                </section>

                <section class="checkout-card checkout-section-card">
                    <div class="checkout-section-heading"><span class="checkout-section-number">03</span><div><h2>Payment method</h2><p>Select your preferred way to pay.</p></div></div>
                    <div class="payment-options payment-options-modern">
                        <?php foreach ([['Cash on Delivery', 'Pay when your order arrives'], ['GCash', 'Pay with GCash'], ['Maya', 'Pay with Maya']] as [$method, $description]): ?>
                            <label class="payment-option payment-option-modern <?= ($_POST['payment'] ?? 'Cash on Delivery') === $method ? 'selected' : '' ?>">
                                <input type="radio" name="payment" value="<?= sanitize($method) ?>" <?= ($_POST['payment'] ?? 'Cash on Delivery') === $method ? 'checked' : '' ?> required>
                                <span class="payment-option-content"><span class="payment-option-label"><?= sanitize($method) ?></span><small><?= sanitize($description) ?></small></span>
                                <span class="payment-radio-indicator" aria-hidden="true"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
                <button type="submit" class="btn btn-primary btn-lg btn-block checkout-submit">Place order <span aria-hidden="true">&rarr;</span></button>
                <p class="checkout-secure-note"><span aria-hidden="true">✓</span> Your order details are securely recorded.</p>
            </form>
        </div>

        <aside class="order-summary checkout-summary">
            <p class="cart-eyebrow">YOUR ORDER</p>
            <h2>Order summary</h2>
            <div class="checkout-summary-items">
                <?php foreach ($cart as $item): ?>
                    <div class="checkout-summary-item">
                        <div class="checkout-summary-item-copy"><strong><?= sanitize($item['product_name']) ?> <span>&times; <?= (int)$item['quantity'] ?></span></strong><small><?= sanitize($item['size']) ?> &middot; <?= sanitize($item['sugar_level']) ?> sugar &middot; <?= sanitize($item['ice_level']) ?></small></div>
                        <span><?= format_price($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary-row"><span>Subtotal</span><span><?= format_price($total) ?></span></div>
            <div class="summary-row"><span>Delivery</span><span class="cart-free-shipping">Free</span></div>
            <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
            <a class="checkout-edit-cart" href="<?= app_url('cart.php') ?>">Edit your cart</a>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
