<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Menu';

$stmt = $pdo->query("SELECT * FROM products WHERE is_available = 1 ORDER BY category, name");
$products = $stmt->fetchAll();

$categories = [];
foreach ($products as $p) {
    if (!in_array($p['category'], $categories)) {
        $categories[] = $p['category'];
    }
}

// Hero image
$hero_img = 'https://images.pexels.com/photos/9102653/pexels-photo-9102653.jpeg?auto=compress&cs=tinysrgb&w=800';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-text">
                <p class="hero-label">Handcrafted with love</p>
                <h1 class="hero-title">Sip the <span>Warmth</span> of Serenitea</h1>
                <p class="hero-subtitle">Freshly brewed milk teas crafted to your taste. Choose your blend, customize your cup, and enjoy every sip.</p>
                <a href="#menu" class="btn btn-primary btn-lg">Browse Our Menu</a>
            </div>
            <div>
                <img src="<?= htmlspecialchars($hero_img) ?>" alt="Milk Tea" class="hero-img">
            </div>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section class="section menu-section" id="menu">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your next favorite sip</p>
                <h2 class="section-title">Find your happy cup</h2>
            </div>
            <p class="section-note">Made fresh. Served with joy.</p>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="category-filter">
            <button class="category-btn active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-btn" data-category="<?= sanitize($cat) ?>"><?= sanitize($cat) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div style="text-align:center;padding:60px 0;color:var(--text-muted);">
                <p style="font-size:15px;">No products available yet. <a href="<?= app_url('setup.php') ?>" style="color:var(--brown);font-weight:600;">Run setup</a> to add sample products.</p>
            </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card-wrapper" data-category="<?= sanitize($p['category']) ?>">
                <div class="product-card">
                    <a href="<?= app_url('product.php?id=' . (int)$p['id']) ?>">
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= sanitize($p['image_url']) ?>" alt="<?= sanitize($p['name']) ?>" class="product-card-img">
                        <?php else: ?>
                            <div class="product-card-img-placeholder">&#9749;</div>
                        <?php endif; ?>
                    </a>
                    <div class="product-card-body">
                        <h3 class="product-card-name"><?= sanitize($p['name']) ?></h3>
                        <p class="product-card-desc"><?= sanitize($p['description']) ?></p>
                        <div class="product-card-footer">
                            <div>
                                <span class="product-price"><?= format_price($p['price']) ?></span>
                                <span class="product-price-note"> / reg.</span>
                            </div>
                            <a href="<?= app_url('product.php?id=' . (int)$p['id']) ?>" class="btn btn-primary btn-sm">Order</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
