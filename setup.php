<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serenitea — Setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',Arial,sans-serif;background:#FDFAF5;color:#2C1810;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
        .card{background:#fff;border-radius:14px;padding:40px 44px;max-width:600px;width:100%;border:1px solid #DDD0BA;box-shadow:0 4px 20px rgba(44,24,16,.08);}
        h1{font-size:22px;font-weight:700;color:#7C4A2D;margin-bottom:4px;}
        p.sub{font-size:13px;color:#7A5C45;margin-bottom:28px;}
        .step{margin-bottom:12px;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;}
        .step.ok{background:#EBF5EF;color:#3D7A52;border:1px solid #C3E6D1;}
        .step.err{background:#FDEAEA;color:#B83232;border:1px solid #F5C6C6;}
        .step.info{background:#F5ECD7;color:#7C4A2D;border:1px solid #DDD0BA;}
        .btn{display:inline-block;padding:11px 24px;background:#7C4A2D;color:#fff;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;margin-top:20px;transition:.2s;}
        .btn:hover{background:#5E3520;}
        .btn-secondary{background:var(--white,#fff);color:#7C4A2D;border:1.5px solid #DDD0BA;margin-right:8px;}
        pre{font-size:12px;background:#F4F4F7;border-radius:6px;padding:10px 14px;overflow-x:auto;margin-top:8px;white-space:pre-wrap;word-break:break-all;}
    </style>
</head>
<body>
<div class="card">
    <h1>&#9749; Serenitea — Setup</h1>
    <p class="sub">This script creates the database, tables, and sample data for your Serenitea milk tea shop.</p>

<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'milktea_shop';

$steps = [];
$has_error = false;

// 1. Connect without DB
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $steps[] = ['ok', 'Connected to MySQL server successfully.'];
} catch (PDOException $e) {
    $steps[] = ['err', 'Cannot connect to MySQL: ' . $e->getMessage()];
    $has_error = true;
}

if (!$has_error) {
    // 2. Create database
    try {
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        );
        $pdo->exec("USE `$dbname`");
        $steps[] = ['ok', "Database `$dbname` created / already exists."];
    } catch (PDOException $e) {
        $steps[] = ['err', 'Failed to create database: ' . $e->getMessage()];
        $has_error = true;
    }
}

if (!$has_error) {
    // 3. Create tables
    $tables = [
        'customers' => "CREATE TABLE IF NOT EXISTS `customers` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `full_name`  VARCHAR(200) NOT NULL,
            `email`      VARCHAR(254) NOT NULL UNIQUE,
            `password`   VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'password_resets' => "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT NOT NULL,
            `token_hash`  CHAR(64) NOT NULL UNIQUE,
            `expires_at`  DATETIME NOT NULL,
            `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`customer_id`),
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'admins' => "CREATE TABLE IF NOT EXISTS `admins` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `username`   VARCHAR(100) NOT NULL UNIQUE,
            `password`   VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'products' => "CREATE TABLE IF NOT EXISTS `products` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `name`         VARCHAR(200) NOT NULL,
            `description`  TEXT,
            `category`     VARCHAR(100) NOT NULL,
            `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `image_url`    TEXT,
            `is_available` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
            `id`               INT AUTO_INCREMENT PRIMARY KEY,
            `customer_name`    VARCHAR(200) NOT NULL,
            `customer_email`   VARCHAR(200) NOT NULL,
            `customer_phone`   VARCHAR(50)  NOT NULL,
            `delivery_address` TEXT NOT NULL,
            `payment_method`   VARCHAR(100) NOT NULL,
            `total_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `status`           ENUM('Pending','Confirmed','Preparing','Out for Delivery','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
            `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'order_items' => "CREATE TABLE IF NOT EXISTS `order_items` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `order_id`     INT NOT NULL,
            `product_id`   INT,
            `product_name` VARCHAR(200) NOT NULL,
            `size`         VARCHAR(50),
            `sugar_level`  VARCHAR(50),
            `ice_level`    VARCHAR(50),
            `quantity`     INT NOT NULL DEFAULT 1,
            `unit_price`   DECIMAL(10,2) NOT NULL,
            `subtotal`     DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    ];

    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            $steps[] = ['ok', "Table `$name` created / already exists."];
        } catch (PDOException $e) {
            $steps[] = [
                'err',
                "Failed to create table `$name`: " . $e->getMessage(),
            ];
            $has_error = true;
        }
    }
}

if (!$has_error) {
    // 4. Seed admin user
    $admin_count = $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($admin_count == 0) {
        $hashed = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare(
            'INSERT INTO admins (username, password) VALUES (?, ?)',
        )->execute(['admin', $hashed]);
        $steps[] = [
            'ok',
            'Admin user created — username: <strong>admin</strong> / password: <strong>admin123</strong>',
        ];
    } else {
        $steps[] = ['info', 'Admin user already exists. Skipped.'];
    }

    // 5. Seed products
    $product_count = $pdo
        ->query('SELECT COUNT(*) FROM products')
        ->fetchColumn();
    if ($product_count == 0) {
        $products = [
            [
                'Brown Sugar Milk Tea',
                'Our signature drink with rich brown sugar syrup, creamy milk, and chewy tapioca pearls. A crowd favorite!',
                'Milk Tea',
                120.0,
                'https://images.pexels.com/photos/9102653/pexels-photo-9102653.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Classic Milk Tea',
                'Smooth and creamy classic milk tea brewed with premium black tea and fresh milk. Simple and satisfying.',
                'Milk Tea',
                95.0,
                'https://images.pexels.com/photos/15800979/pexels-photo-15800979.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Taro Milk Tea',
                'Velvety taro root blended with milk tea for a naturally sweet and nutty flavor. Beautifully purple.',
                'Milk Tea',
                115.0,
                'https://images.pexels.com/photos/33212319/pexels-photo-33212319.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Matcha Milk Tea',
                'Premium ceremonial-grade matcha blended with fresh milk and sweetened just right. Earthy and smooth.',
                'Milk Tea',
                125.0,
                'https://images.pexels.com/photos/18553404/pexels-photo-18553404.png?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Strawberry Fruit Tea',
                'Refreshing strawberry tea bursting with real fruit flavor. Light, bright, and perfect for a hot day.',
                'Fruit Tea',
                105.0,
                'https://images.pexels.com/photos/28944484/pexels-photo-28944484.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Thai Milk Tea',
                'Bold Thai black tea infused with spices, condensed milk, and evaporated milk for a creamy, exotic taste.',
                'Milk Tea',
                110.0,
                'https://images.pexels.com/photos/19154599/pexels-photo-19154599.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Iced Matcha Latte',
                'Chilled matcha latte with steamed oat milk. Clean, energizing, and lightly sweet.',
                'Coffee',
                130.0,
                'https://images.pexels.com/photos/32158135/pexels-photo-32158135.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            [
                'Mango Fruit Tea',
                'Juicy mango blended with light tea base and popping mango jellies. Tropical and refreshing.',
                'Fruit Tea',
                110.0,
                'https://images.pexels.com/photos/6921330/pexels-photo-6921330.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO products (name, description, category, price, image_url) VALUES (?,?,?,?,?)',
        );
        foreach ($products as $p) {
            $stmt->execute($p);
        }
        $steps[] = [
            'ok',
            count($products) . ' sample products added to the menu.',
        ];
    } else {
        $steps[] = [
            'info',
            "Products already exist ($product_count found). Skipped seeding.",
        ];
    }
}

foreach ($steps as $step):
    [$type, $msg] = $step; ?>
    <div class="step <?= $type ?>"><?= $msg ?></div>
<?php
endforeach;
?>

<?php if (!$has_error): ?>
    <div class="step ok" style="margin-top:20px;font-size:14px;">
        <strong>Setup complete!</strong> Your Serenitea system is ready.
    </div>
    <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="index.php" class="btn">Go to Shop</a>
        <a href="admin/login.php" class="btn" style="background:#2C1810;">Admin Panel</a>
    </div>
    <p style="font-size:12px;color:#B8845A;margin-top:16px;">Tip: Delete or protect this setup.php file before deploying to production.</p>
<?php else: ?>
    <div class="step err" style="margin-top:16px;">Setup could not be completed. Please check your XAMPP MySQL settings.</div>
<?php endif; ?>

</div>
</body>
</html>