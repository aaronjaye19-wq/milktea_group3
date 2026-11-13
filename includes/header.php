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
            <?php if (is_user_logged_in()): ?>
                <span class="nav-account">Hi, <?= sanitize(explode(' ', $_SESSION['user_name'] ?? 'there')[0]) ?></span>
                <form class="nav-logout" method="post" action="<?= app_url('logout.php') ?>"><input type="hidden" name="form_token" value="<?= sanitize(create_form_token('logout')) ?>"><button type="submit">Sign out</button></form>
            <?php else: ?>
                <a href="<?= app_url('login.php') ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>">Sign in</a>
                <a href="<?= app_url('register.php') ?>" class="<?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>">Join</a>
            <?php endif; ?>
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
