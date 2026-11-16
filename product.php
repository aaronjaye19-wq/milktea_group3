<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { redirect('/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_available = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { redirect('/index.php'); }

// Require an account before accepting any cart changes.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_user_logged_in()) {
    redirect('login.php?next=' . rawurlencode('product.php?id=' . $id));
}

// Confirm repeat purchases before adding products bought after the previous checkout.
$repeat_confirmation = false;
$repeat_selection = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $size  = in_array($_POST['size'] ?? '', ['Regular','Large','Extra Large'], true) ? $_POST['size'] : 'Regular';
    $sugar = in_array($_POST['sugar'] ?? '', ['0%','25%','50%','75%','100%'], true) ? $_POST['sugar'] : '100%';
    $ice   = in_array($_POST['ice'] ?? '', ['No Ice','Less Ice','Regular Ice','Extra Ice'], true) ? $_POST['ice'] : 'Regular Ice';
    $qty   = max(1, min(20, (int)($_POST['qty'] ?? 1)));

    $repeat_selection = compact('size', 'sugar', 'ice', 'qty');
    $was_ordered = in_array($id, $_SESSION['recent_order_products'] ?? [], true);
    if ($was_ordered && ($_POST['confirm_repeat'] ?? '') !== 'yes') {
        $repeat_confirmation = true;
    } else {
        add_to_cart($product, $size, $sugar, $ice, $qty);
        redirect('/cart.php');
    }
}

$page_title = $product['name'];
$sizes = [
    ['label' => 'Regular', 'note' => ''],
    ['label' => 'Large',   'note' => '+₱15'],
    ['label' => 'Extra Large', 'note' => '+₱25'],
];
$sugars = ['0%', '25%', '50%', '75%', '100%'];
$ices   = ['No Ice', 'Less Ice', 'Regular Ice', 'Extra Ice'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div style="padding:16px 0;">
        <a href="<?= app_url('index.php') ?>" style="font-size:13px;color:var(--text-muted);font-weight:500;">&larr; Back to Menu</a>
    </div>

    <form method="POST" class="product-detail">
        <!-- Image -->
        <div>
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= sanitize($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" class="product-detail-img">
            <?php else: ?>
                <div class="product-detail-img-placeholder">&#9749;</div>
            <?php endif; ?>
        </div>

        <!-- Info & Customize -->
        <div>
            <p style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--caramel);margin-bottom:6px;"><?= sanitize($product['category']) ?></p>
            <h1 class="product-detail-name"><?= sanitize($product['name']) ?></h1>
            <p class="product-detail-price" id="display-price"><?= format_price($product['price']) ?></p>
            <input type="hidden" id="base-price" value="<?= (float)$product['price'] ?>">
            <p class="product-detail-desc"><?= sanitize($product['description']) ?></p>

            <!-- Size -->
            <span class="customize-label">Size</span>
            <div class="option-group" data-input="size">
                <?php foreach ($sizes as $s): ?>
                    <button type="button" class="option-btn <?= $s['label'] === 'Regular' ? 'selected' : '' ?>" data-value="<?= $s['label'] ?>">
                        <?= $s['label'] ?><?= $s['note'] ? ' <small style="opacity:.7">' . $s['note'] . '</small>' : '' ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="size" value="Regular">

            <!-- Sugar Level -->
            <span class="customize-label">Sugar Level</span>
            <div class="option-group" data-input="sugar">
                <?php foreach ($sugars as $sg): ?>
                    <button type="button" class="option-btn <?= $sg === '100%' ? 'selected' : '' ?>" data-value="<?= $sg ?>"><?= $sg ?></button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="sugar" value="100%">

            <!-- Ice Level -->
            <span class="customize-label">Ice Level</span>
            <div class="option-group" data-input="ice">
                <?php foreach ($ices as $ic): ?>
                    <button type="button" class="option-btn <?= $ic === 'Regular Ice' ? 'selected' : '' ?>" data-value="<?= $ic ?>"><?= $ic ?></button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="ice" value="Regular Ice">

            <!-- Quantity -->
            <span class="customize-label">Quantity</span>
            <div class="qty-control" style="margin-bottom:24px;">
                <button type="button" class="qty-btn" id="qty-minus">&#8722;</button>
                <input type="number" class="qty-input" name="qty" id="qty-input" value="1" min="1" max="20">
                <button type="button" class="qty-btn" id="qty-plus">&#43;</button>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">Add to Cart</button>
        </div>
    </form>

    <?php if ($repeat_confirmation && $repeat_selection): ?>
    <div class="repeat-order-backdrop">
        <section class="repeat-order-dialog" role="dialog" aria-modal="true" aria-labelledby="repeat-order-title" aria-describedby="repeat-order-description">
            <p class="checkout-kicker">A LITTLE MORE BOBA?</p>
            <h2 id="repeat-order-title">Order this again?</h2>
            <p id="repeat-order-description">You ordered <strong><?= sanitize($product['name']) ?></strong> after your last checkout. Would you like to add it to your cart again?</p>
            <div class="repeat-order-actions">
                <a class="btn btn-secondary" href="<?= app_url('product.php?id=' . (int)$product['id']) ?>">Not now</a>
                <form method="POST">
                    <input type="hidden" name="size" value="<?= sanitize($repeat_selection['size']) ?>">
                    <input type="hidden" name="sugar" value="<?= sanitize($repeat_selection['sugar']) ?>">
                    <input type="hidden" name="ice" value="<?= sanitize($repeat_selection['ice']) ?>">
                    <input type="hidden" name="qty" value="<?= (int)$repeat_selection['qty'] ?>">
                    <input type="hidden" name="confirm_repeat" value="yes">
                    <button type="submit" class="btn btn-primary">Yes, add to cart</button>
                </form>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
