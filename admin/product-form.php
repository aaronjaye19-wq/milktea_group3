<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = $id ? 'Edit Product' : 'Add Product';
require_once __DIR__ . '/includes/admin-header.php';

$product = [
    'name'         => '',
    'description'  => '',
    'category'     => '',
    'price'        => '',
    'image_url'    => '',
    'is_available' => 1,
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) $product = $existing;
    else { redirect('admin/products.php'); }
}

$errors  = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = $_POST['price'] ?? '';
    $image_url   = $product['image_url'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!empty($_FILES['product_image']['name'])) {
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $file_type = mime_content_type($_FILES['product_image']['tmp_name']);
        if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK || !isset($allowed_types[$file_type]) || $_FILES['product_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Please upload a JPG, PNG, WEBP, or GIF image up to 5MB.';
        } else {
            $filename = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowed_types[$file_type];
            $upload_dir = dirname(__DIR__) . '/assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = app_url('assets/uploads/' . $filename);
            } else {
                $errors[] = 'The image could not be saved. Please try again.';
            }
        }
    }

    if (empty($name))     $errors[] = 'Product name is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Valid price is required.';

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE products SET name=?, description=?, category=?, price=?, image_url=?, is_available=? WHERE id=?"
            );
            $stmt->execute([$name, $description, $category, $price, $image_url, $is_available, $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO products (name, description, category, price, image_url, is_available) VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$name, $description, $category, $price, $image_url, $is_available]);
        }
        redirect('admin/products.php');
    }

    $product = compact('name','description','category','price','image_url','is_available');
}

$categories_preset = ['Milk Tea','Fruit Tea','Coffee','Smoothies','Specials'];
?>

<div class="page-actions">
    <h2><?= $id ? 'Edit Product' : 'Add Product' ?></h2>
    <a href="<?= app_url('admin/products.php') ?>" class="btn btn-secondary">Back to Products</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="admin-form-card">
    <h3><?= $id ? 'Edit Product Details' : 'New Product' ?></h3>
    <form method="POST" class="admin-form" enctype="multipart/form-data">

        <div class="form-group">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" value="<?= sanitize($product['name']) ?>" placeholder="e.g. Brown Sugar Milk Tea" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" placeholder="Describe the flavor, ingredients..."><?= sanitize($product['description']) ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" list="cat-list" value="<?= sanitize($product['category']) ?>" placeholder="Milk Tea, Fruit Tea..." required>
                <datalist id="cat-list">
                    <?php foreach ($categories_preset as $c): ?>
                        <option value="<?= sanitize($c) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label">Base Price (PHP)</label>
                <input type="number" name="price" class="form-control" value="<?= sanitize($product['price']) ?>" placeholder="0.00" step="0.01" min="0" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Product Image <span style="font-weight:400;color:#9898B5;">(optional)</span></label>
            <div class="image-upload-control">
                <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>
            <div class="image-upload-hint">Upload a JPG, PNG, WEBP, or GIF image up to 5MB.</div>
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= sanitize($product['image_url']) ?>" class="image-preview" alt="Current product image">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_available" value="1" <?= $product['is_available'] ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:#7C4A2D;">
                <span class="form-label" style="margin:0;">Available on menu</span>
            </label>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><?= $id ? 'Save Changes' : 'Add Product' ?></button>
            <a href="<?= app_url('admin/products.php') ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
