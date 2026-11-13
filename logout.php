<?php
require_once __DIR__ . '/includes/functions.php';
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verify_form_token('logout', $_POST['form_token'] ?? '')
) {
    http_response_code(400);
    exit('Invalid request.');
}
unset($_SESSION['user_id'], $_SESSION['user_name']);
session_regenerate_id(true);
redirect('index.php');
