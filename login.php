<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}
$page_title = 'Sign In';
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!verify_form_token('login', $_POST['form_token'] ?? '')) {
        $error = 'Unable to submit this form. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'This field is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password FROM customers WHERE email = ? LIMIT 1',
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                redirect('index.php');
            }
            $error = 'Invalid email or password.';
        } catch (PDOException $e) {
            $error = 'Unable to connect. Please try again.';
        }
    }
}
$form_token = create_form_token('login');
require __DIR__ . '/includes/auth-page-top.php';
?>
<section class="auth-card" aria-labelledby="auth-title">
    <p class="auth-eyebrow">Welcome back</p><h1 id="auth-title">Sign in to Serenitea</h1><p class="auth-intro">Your favorite tea is just a few sips away.</p>
    <?php if (
        $error
    ): ?><div class="alert alert-error" role="alert"><?= sanitize(
    $error,
) ?></div><?php endif; ?>
    <form method="post" data-single-submit>
        <input type="hidden" name="form_token" value="<?= sanitize(
            $form_token,
        ) ?>">
        <div class="form-group"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= sanitize(
            $email,
        ) ?>" required autofocus></div>
        <div class="form-group"><div class="auth-label-row"><label class="form-label" for="password">Password</label><a href="<?= app_url(
            'forgot-password.php',
        ) ?>">Forgot password?</a></div><div class="password-field"><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button></div></div>
        <button class="btn btn-primary btn-lg btn-block auth-submit" type="submit">Sign in</button>
    </form>
    <p class="auth-switch">New to Serenitea? <a href="<?= app_url(
        'register.php',
    ) ?>">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/auth-page-bottom.php'; ?>
