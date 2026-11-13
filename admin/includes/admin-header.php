<?php
require_once dirname(__DIR__) . '/../includes/db.php';
require_once dirname(__DIR__) . '/../includes/functions.php';
require_admin();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' : '' ?>Serenitea Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-shell">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo">
            <span class="logo-icon">&#9749;</span>
            <span>Serenitea</span>
        </div>
        <div class="sidebar-label">Main Menu</div>
        <nav class="sidebar-nav">
            <a href="<?= app_url('admin/index.php') ?>" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon">&#9783;</span>
                <span>Dashboard</span>
            </a>
            <a href="<?= app_url('admin/products.php') ?>" class="<?= in_array($current_page, ['products.php','product-form.php']) ? 'active' : '' ?>">
                <span class="nav-icon">&#9749;</span>
                <span>Products</span>
            </a>
            <a href="<?= app_url('admin/orders.php') ?>" class="<?= in_array($current_page, ['orders.php','order-detail.php']) ? 'active' : '' ?>">
                <span class="nav-icon">&#9741;</span>
                <span>Orders</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= app_url('admin/logout.php') ?>">
                <span class="nav-icon">&#10148;</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Content -->
    <div class="admin-content">
        <header class="admin-topbar">
            <h2><?= isset($page_title) ? sanitize($page_title) : 'Dashboard' ?></h2>
            <div class="admin-topbar-right">
                <span>Logged in as <strong><?= sanitize($_SESSION['admin_name']) ?></strong></span>
            </div>
        </header>
        <div class="admin-page">
