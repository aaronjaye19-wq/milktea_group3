<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        redirect('admin/index.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Serenitea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-login-page">
    <div class="admin-login-card">
        <div class="admin-login-logo">
            <span class="logo-icon">&#9749;</span>
            <h1>Serenitea Admin</h1>
            <p>Sign in to manage your store</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= sanitize($_POST['username'] ?? '') ?>" placeholder="admin" required autofocus>
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Sign In</button>
        </form>

        <p style="font-size:12px;color:#9898B5;text-align:center;margin-top:20px;">Default: admin / admin123</p>
    </div>
</div>

</body>
</html>
