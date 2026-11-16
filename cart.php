<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Your Cart';

$size_prices = ['Regular' => 0, 'Large' => 15, 'Extra Large' => 25];
$sugar_levels = ['0%', '25%', '50%', '75%', '100%'];
$ice_levels = ['No Ice', 'Less Ice', 'Regular Ice', 'Extra Ice'];
$cart_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_form_token('cart', $_POST['form_token'] ?? '')) {
        $cart_error = 'Your cart could not be updated. Please refresh and try again.';
    } elseif (isset($_POST['remove_key'])) {
        remove_from_cart($_POST['remove_key']);
        redirect('cart.php');
    } elseif (($_POST['action'] ?? '') === 'clear') {
        clear_cart();
        redirect('cart.php');
    } elseif (in_array($_POST['action'] ?? '', ['update', 'update_checkout'], true)) {
        $continue_to_checkout = ($_POST['action'] ?? '') === 'update_checkout';
        foreach (get_cart() as $key => $item) {
            if (!isset($_POST['qty'][$key])) {
                continue;
            }

            $size = $_POST['size'][$key] ?? $item['size'];
            $sugar = $_POST['sugar'][$key] ?? $item['sugar_level'];
            $ice = $_POST['ice'][$key] ?? $item['ice_level'];
            $quantity = (int) $_POST['qty'][$key];

            if (!isset($size_prices[$size]) || !in_array($sugar, $sugar_levels, true) || !in_array($ice, $ice_levels, true)) {
                $cart_error = 'Please select valid product options.';
                break;
            }
            if ($quantity < 1 || $quantity > 20) {
                $cart_error = 'Quantity must be between 1 and 20.';
                break;
            }

            $product_stmt = $pdo->prepare('SELECT price, is_available FROM products WHERE id = ? LIMIT 1');
            $product_stmt->execute([(int) $item['product_id']]);
            $product = $product_stmt->fetch();
            if (!$product || !(int) $product['is_available']) {
                remove_from_cart($key);
                $cart_error = 'An unavailable item was removed from your cart.';
                continue;
            }

            $unit_price = (float) $product['price'] + $size_prices[$size];
            $_SESSION['cart'][$key]['size'] = $size;
            $_SESSION['cart'][$key]['sugar_level'] = $sugar;
            $_SESSION['cart'][$key]['ice_level'] = $ice;
            $_SESSION['cart'][$key]['quantity'] = $quantity;
            $_SESSION['cart'][$key]['unit_price'] = $unit_price;
            $_SESSION['cart'][$key]['subtotal'] = $unit_price * $quantity;
        }
        if ($continue_to_checkout && !$cart_error) {
            redirect('checkout.php');
        }
    }
}

$cart = get_cart();
$total = cart_total();
$form_token = create_form_token('cart');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container cart-page">
    <a class="cart-back-link" href="<?= app_url('index.php') ?>">&larr; Back to menu</a>
    <header class="cart-page-heading">
        <div>
            <p class="cart-eyebrow">YOUR ORDER</p>
            <h1>Shopping cart</h1>
            <p class="cart-heading-copy"><?= count($cart) ?> <?= count($cart) === 1 ? 'item' : 'items' ?> ready for a fresh pour.</p>
        </div>
        <a class="cart-continue-link" href="<?= app_url('index.php') ?>">Continue shopping <span aria-hidden="true">&rarr;</span></a>
    </header>

    <?php if ($cart_error): ?><div class="alert alert-error" role="alert"><?= sanitize($cart_error) ?></div><?php endif; ?>

    <?php if (empty($cart)): ?>
        <section class="cart-empty cart-empty-modern">
            <div class="cart-empty-mark" aria-hidden="true">S</div>
            <h2>Your cart is taking a tea break</h2>
            <p>Explore the menu and add your favorites when you&apos;re ready.</p>
            <a href="<?= app_url('index.php') ?>" class="btn btn-primary">Browse the menu</a>
        </section>
    <?php else: ?>
        <form method="POST" class="cart-form" id="cart-form">
            <input type="hidden" name="form_token" value="<?= sanitize($form_token) ?>">
            <div class="cart-items-panel">
                <div class="cart-table-scroll">
                    <table class="cart-table cart-table-modern">
                        <thead><tr><th scope="col">Product</th><th scope="col">Size</th><th scope="col">Quantity</th><th scope="col">Total price</th><th scope="col"><span class="sr-only">Remove</span></th></tr></thead>
                        <tbody>
                        <?php foreach ($cart as $key => $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-item-info">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= sanitize($item['image_url']) ?>" class="cart-item-img" alt="<?= sanitize($item['product_name']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="cart-item-img-placeholder" aria-hidden="true">S</div>
                                        <?php endif; ?>
                                        <div class="cart-product-copy">
                                            <div class="cart-item-name"><?= sanitize($item['product_name']) ?></div>
                                            <div class="cart-item-meta">
                                                <label class="sr-only" for="sugar-<?= sanitize($key) ?>">Sugar level for <?= sanitize($item['product_name']) ?></label>
                                                <select id="sugar-<?= sanitize($key) ?>" name="sugar[<?= sanitize($key) ?>]" aria-label="Sugar level">
                                                    <?php foreach ($sugar_levels as $level): ?><option value="<?= sanitize($level) ?>" <?= $item['sugar_level'] === $level ? 'selected' : '' ?>><?= sanitize($level) ?> sugar</option><?php endforeach; ?>
                                                </select>
                                                <label class="sr-only" for="ice-<?= sanitize($key) ?>">Ice level for <?= sanitize($item['product_name']) ?></label>
                                                <select id="ice-<?= sanitize($key) ?>" name="ice[<?= sanitize($key) ?>]" aria-label="Ice level">
                                                    <?php foreach ($ice_levels as $level): ?><option value="<?= sanitize($level) ?>" <?= $item['ice_level'] === $level ? 'selected' : '' ?>><?= sanitize($level) ?></option><?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <label class="sr-only" for="size-<?= sanitize($key) ?>">Size for <?= sanitize($item['product_name']) ?></label>
                                    <select class="cart-size-select" id="size-<?= sanitize($key) ?>" name="size[<?= sanitize($key) ?>]">
                                        <?php foreach ($size_prices as $size => $addition): ?><option value="<?= sanitize($size) ?>" <?= $item['size'] === $size ? 'selected' : '' ?>><?= sanitize($size) ?><?= $addition ? ' (+ ' . format_price($addition) . ')' : '' ?></option><?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <label class="sr-only" for="qty-<?= sanitize($key) ?>">Quantity for <?= sanitize($item['product_name']) ?></label>
                                    <input class="cart-qty-input" id="qty-<?= sanitize($key) ?>" type="number" name="qty[<?= sanitize($key) ?>]" value="<?= (int)$item['quantity'] ?>" min="1" max="20" inputmode="numeric">
                                </td>
                                <td class="cart-line-total"><?= format_price($item['subtotal']) ?></td>
                                <td><button type="submit" name="remove_key" value="<?= sanitize($key) ?>" class="cart-remove-btn" aria-label="Remove <?= sanitize($item['product_name']) ?> from cart">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cart-actions-row">
                    <a class="cart-continue-link" href="<?= app_url('index.php') ?>">&larr; Continue shopping</a>
                    <div class="cart-action-buttons">
                        <button type="submit" class="btn btn-secondary" name="action" value="clear">Clear all</button>
                        <button type="submit" class="btn btn-secondary" name="action" value="update">Update cart</button>
                    </div>
                </div>
            </div>

            <aside class="order-summary cart-summary">
                <p class="cart-eyebrow">ORDER SUMMARY</p>
                <h2>Your total</h2>
                <div class="summary-row"><span>Subtotal</span><span><?= format_price($total) ?></span></div>
                <div class="summary-row"><span>Delivery</span><span class="cart-free-shipping">Free</span></div>
                <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
                <p class="cart-summary-note">Your drink selections can be adjusted before checkout.</p>
                <button type="submit" class="btn btn-primary btn-block btn-lg" name="action" value="update_checkout">Save changes &amp; checkout <span aria-hidden="true">&rarr;</span></button>
            </aside>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
