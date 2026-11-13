<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_url($path = '') {
    $document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $app_root = realpath(__DIR__ . '/..');
    $base_url = '';

    if ($document_root && $app_root) {
        $document_root = str_replace('\\', '/', rtrim($document_root, '\\/'));
        $app_root = str_replace('\\', '/', $app_root);
        if (stripos($app_root, $document_root) === 0) {
            $base_url = substr($app_root, strlen($document_root));
        }
    }

    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

function get_cart() {
    return $_SESSION['cart'] ?? [];
}

function cart_count() {
    $total = 0;
    foreach (get_cart() as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

function cart_total() {
    $total = 0.0;
    foreach (get_cart() as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

function add_to_cart($product, $size, $sugar, $ice, $qty = 1) {
    $size_prices = ['Regular' => 0, 'Large' => 15, 'Extra Large' => 25];
    $size_add    = $size_prices[$size] ?? 0;
    $unit_price  = $product['price'] + $size_add;
    $key         = md5($product['id'] . $size . $sugar . $ice);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $qty;
        $_SESSION['cart'][$key]['subtotal']  = $_SESSION['cart'][$key]['quantity'] * $unit_price;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id'   => $product['id'],
            'product_name' => $product['name'],
            'image_url'    => $product['image_url'],
            'size'         => $size,
            'sugar_level'  => $sugar,
            'ice_level'    => $ice,
            'quantity'     => $qty,
            'unit_price'   => $unit_price,
            'subtotal'     => $unit_price * $qty,
        ];
    }
}

function remove_from_cart($key) {
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
}

function update_cart_qty($key, $qty) {
    if (isset($_SESSION['cart'][$key])) {
        if ($qty <= 0) {
            remove_from_cart($key);
        } else {
            $_SESSION['cart'][$key]['quantity'] = $qty;
            $_SESSION['cart'][$key]['subtotal']  = $_SESSION['cart'][$key]['unit_price'] * $qty;
        }
    }
}

function clear_cart() {
    $_SESSION['cart'] = [];
}

function format_price($amount) {
    return '&#8369;' . number_format($amount, 2);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . app_url($url));
    exit;
}

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function create_form_token($form) {
    if (empty($_SESSION['form_tokens'][$form])) {
        $_SESSION['form_tokens'][$form] = bin2hex(random_bytes(32));
    }
    return $_SESSION['form_tokens'][$form];
}

function verify_form_token($form, $token) {
    $stored = $_SESSION['form_tokens'][$form] ?? '';
    // Single-use: remove it whether or not it matches
    unset($_SESSION['form_tokens'][$form]);

    return $stored !== '' && is_string($token) && hash_equals($stored, $token);
}