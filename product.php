<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_available = 1');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_user_logged_in()) {
    redirect('login.php?next=' . rawurlencode('product.php?id=' . $id));
}

$selected_size = $_POST['size'] ?? 'Regular';
$selected_sugar = $_POST['sugar'] ?? '100%';
$selected_ice = $_POST['ice'] ?? 'Regular Ice';
$selected_qty = max(1, min(20, (int) ($_POST['qty'] ?? 1)));
$sizes = [
    ['label' => 'Regular', 'note' => ''],
    ['label' => 'Large', 'note' => '+₱15'],
    ['label' => 'Extra Large', 'note' => '+₱25'],
];
$sugars = ['0%', '25%', '50%', '75%', '100%'];
$ices = ['No Ice', 'Less Ice', 'Regular Ice', 'Extra Ice'];
$show_repeat_modal = false;
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_form_token('product-' . $id, $_POST['form_token'] ?? '')) {
    $form_error = 'This form has expired. Please try adding the drink again.';
}
$form_token = create_form_token('product-' . $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$form_error) {
    if (!in_array($selected_size, ['Regular', 'Large', 'Extra Large'], true)) $selected_size = 'Regular';
    if (!in_array($selected_sugar, $sugars, true)) $selected_sugar = '100%';
    if (!in_array($selected_ice, $ices, true)) $selected_ice = 'Regular Ice';

    $email_stmt = $pdo->prepare('SELECT email FROM customers WHERE id = ? LIMIT 1');
    $email_stmt->execute([(int) $_SESSION['user_id']]);
    $user_email = $email_stmt->fetchColumn();
    $previous_order_stmt = $pdo->prepare(
        "SELECT 1
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         WHERE o.customer_email = ? AND oi.product_id = ? AND o.status <> 'Cancelled'
         LIMIT 1",
    );
    $previous_order_stmt->execute([$user_email, $id]);
    $ordered_before = (bool) $previous_order_stmt->fetchColumn();

    if ($ordered_before && !isset($_POST['confirm_repeat'])) {
        $show_repeat_modal = true;
    } else {
        add_to_cart($product, $selected_size, $selected_sugar, $selected_ice, $selected_qty);
        redirect('cart.php');
    }
}

$page_title = $product['name'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div style="padding:16px 0;">
        <a href="<?= app_url('index.php') ?>" style="font-size:13px;color:var(--text-muted);font-weight:500;">&larr; Back to Menu</a>
    </div>

    <?php if ($form_error): ?><div class="alert alert-error" role="alert"><?= sanitize($form_error) ?></div><?php endif; ?>
    <form method="POST" class="product-detail" id="product-order-form">
        <input type="hidden" name="form_token" value="<?= sanitize($form_token) ?>">
        <div>
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= sanitize($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" class="product-detail-img">
            <?php else: ?>
                <div class="product-detail-img-placeholder" aria-hidden="true">&#9749;</div>
            <?php endif; ?>
        </div>

        <div>
            <p style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--caramel);margin-bottom:6px;"><?= sanitize($product['category']) ?></p>
            <h1 class="product-detail-name"><?= sanitize($product['name']) ?></h1>
            <p class="product-detail-price" id="display-price"><?= format_price($product['price']) ?></p>
            <input type="hidden" id="base-price" value="<?= (float) $product['price'] ?>">
            <p class="product-detail-desc"><?= sanitize($product['description']) ?></p>

            <span class="customize-label">Size</span>
            <div class="option-group" data-input="size">
                <?php foreach ($sizes as $size): ?>
                    <button type="button" class="option-btn <?= $size['label'] === $selected_size ? 'selected' : '' ?>" data-value="<?= sanitize($size['label']) ?>"><?= sanitize($size['label']) ?><?= $size['note'] ? ' <small style="opacity:.7">' . sanitize($size['note']) . '</small>' : '' ?></button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="size" value="<?= sanitize($selected_size) ?>">

            <span class="customize-label">Sugar Level</span>
            <div class="option-group" data-input="sugar">
                <?php foreach ($sugars as $sugar): ?>
                    <button type="button" class="option-btn <?= $sugar === $selected_sugar ? 'selected' : '' ?>" data-value="<?= sanitize($sugar) ?>"><?= sanitize($sugar) ?></button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="sugar" value="<?= sanitize($selected_sugar) ?>">

            <span class="customize-label">Ice Level</span>
            <div class="option-group" data-input="ice">
                <?php foreach ($ices as $ice): ?>
                    <button type="button" class="option-btn <?= $ice === $selected_ice ? 'selected' : '' ?>" data-value="<?= sanitize($ice) ?>"><?= sanitize($ice) ?></button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="ice" value="<?= sanitize($selected_ice) ?>">

            <span class="customize-label">Quantity</span>
            <div class="qty-control" style="margin-bottom:24px;">
                <button type="button" class="qty-btn" id="qty-minus" aria-label="Decrease quantity">&#8722;</button>
                <input type="number" class="qty-input" name="qty" id="qty-input" value="<?= (int) $selected_qty ?>" min="1" max="20" inputmode="numeric" aria-label="Quantity">
                <button type="button" class="qty-btn" id="qty-plus" aria-label="Increase quantity">&#43;</button>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">Add to Cart</button>
        </div>

        <?php if ($show_repeat_modal): ?>
            <div class="repeat-order-backdrop" id="repeat-order-modal" role="presentation">
                <section class="repeat-order-dialog" role="dialog" aria-modal="true" aria-labelledby="repeat-order-title" aria-describedby="repeat-order-copy">
                    <div class="repeat-order-icon" aria-hidden="true">S</div>
                    <p class="cart-eyebrow">A LITTLE REMINDER</p>
                    <h2 id="repeat-order-title">Order this favorite again?</h2>
                    <p id="repeat-order-copy">You&apos;ve ordered <?= sanitize($product['name']) ?> before. Would you like to add it to your cart again?</p>
                    <div class="repeat-order-actions">
                        <button type="button" class="btn btn-secondary" id="repeat-order-cancel">Not now</button>
                        <button type="submit" class="btn btn-primary" name="confirm_repeat" value="1">Yes, add to cart</button>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
