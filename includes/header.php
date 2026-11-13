<?php
require_once __DIR__ . '/functions.php';
$cart_count = cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' : '' ?>Serenitea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <?= isset($extra_css) ? $extra_css : '' ?>
</head>
<body>

<div class="announcement-bar">Freshly shaken, perfectly yours <span>•</span> Free delivery on orders over ₱500</div>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= app_url('index.php') ?>" class="logo">
            <span class="logo-icon">S</span>
            <span class="logo-text">Serenitea<small>milk tea studio</small></span>
        </a>
        <nav class="main-nav">
            <a href="<?= app_url('index.php') ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Menu</a>
            <a href="<?= app_url('cart.php') ?>" class="nav-cart <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
                Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>
</header>

<main class="site-main">
