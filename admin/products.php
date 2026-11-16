<?php
$page_title = 'Products';
require_once __DIR__ . '/includes/admin-header.php';

$message = '';
$error   = '';

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$del_id]);
    redirect('admin/products.php?deleted=1');
}

if (isset($_GET['deleted'])) {
    $message = 'Product deleted successfully.';
}

$per_page = 8;
$page = max(1, (int)($_GET['page'] ?? 1));
$total_products_count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = max(1, (int)ceil($total_products_count / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY category, name LIMIT ? OFFSET ?");
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<div class="page-actions">
    <h2>Products</h2>
    <a href="<?= app_url('admin/product-form.php') ?>" class="btn btn-primary">+ Add Product</a>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>

<div class="admin-card admin-list-card">
    <div class="admin-table-scroll">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
            <tr><td colspan="6" style="text-align:center;color:#9898B5;padding:32px;">No products yet. <a href="<?= app_url('admin/product-form.php') ?>" style="color:#7C4A2D;">Add one</a></td></tr>
            <?php else: ?>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <?php if (!empty($p['image_url'])): ?>
                        <img src="<?= sanitize($p['image_url']) ?>" class="product-thumb" alt="">
                    <?php else: ?>
                        <div class="product-thumb-placeholder">&#9749;</div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;"><?= sanitize($p['name']) ?></td>
                <td><?= sanitize($p['category']) ?></td>
                <td>&#8369;<?= number_format($p['price'], 2) ?></td>
                <td>
                    <span class="badge <?= $p['is_available'] ? 'badge-available' : 'badge-unavailable' ?>">
                        <?= $p['is_available'] ? 'Available' : 'Hidden' ?>
                    </span>
                </td>
                <td>
                    <div class="action-group">
                        <a href="<?= app_url('admin/product-form.php?id=' . (int)$p['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="<?= app_url('admin/products.php?delete=' . (int)$p['id']) ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this product?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if ($total_pages > 1): ?>
    <nav class="pagination" aria-label="Products pagination">
        <?php if ($page > 1): ?><a href="<?= app_url('admin/products.php?page=' . ($page - 1)) ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?><a class="<?= $p === $page ? 'current' : '' ?>" href="<?= app_url('admin/products.php?page=' . $p) ?>"><?= $p ?></a><?php endfor; ?>
        <?php if ($page < $total_pages): ?><a href="<?= app_url('admin/products.php?page=' . ($page + 1)) ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
    </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
