<?php
require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FDFAF5">
    <title><?= isset($page_title)
        ? sanitize($page_title) . ' — '
        : '' ?>Serenitea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/auth.css') ?>">
</head>
<body>
<header class="site-header"><div class="container header-inner"><a href="<?= app_url(
    'index.php',
) ?>" class="logo"><span class="logo-icon">S</span><span class="logo-text">Serenitea<small>milk tea studio</small></span></a><nav class="main-nav" aria-label="Main navigation"><a href="<?= app_url(
    'index.php',
) ?>">Menu</a><a href="<?= app_url('cart.php') ?>">Cart</a></nav></div></header>
<main class="auth-main">
