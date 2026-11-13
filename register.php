<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_user_logged_in()) {
    redirect('index.php');
}
$page_title = 'Create Account';
$errors = [];
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!verify_form_token('register', $_POST['form_token'] ?? '')) {
        $errors[] = 'Unable to submit this form. Please try again.';
    } else {
        if ($full_name === '') {
            $errors[] = 'This field is required.';
        } elseif (!preg_match("/^[\p{L} ]+$/u", $full_name)) {
            $errors[] = 'Full name must contain letters and spaces only.';
        }
        if ($email === '') {
            $errors[] = 'This field is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email.';
        }
        if ($password === '' || $confirm_password === '') {
            $errors[] = 'This field is required.';
        } elseif (
            strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            $errors[] = 'Password does not meet requirements.';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        if (empty($_POST['terms'])) {
            $errors[] = 'You must accept the Terms & Conditions.';
        }

        if (!$errors) {
            try {
                $check = $pdo->prepare(
                    'SELECT id FROM customers WHERE email = ? LIMIT 1',
                );
                $check->execute([$email]);
                if ($check->fetch()) {
                    $errors[] = 'Email is already registered.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO customers (full_name, email, password) VALUES (?, ?, ?)',
                    );
                    $insert->execute([
                        $full_name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                    ]);
                    $new_user_id = (int) $pdo->lastInsertId();
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_name'] = $full_name;
                    redirect('index.php');
                }
            } catch (PDOException $e) {
                $errors[] =
                    $e->getCode() === '23000'
                        ? 'Email is already registered.'
                        : 'Unable to connect. Please try again.';
            }
        }
    }
}
$form_token = create_form_token('register');
require __DIR__ . '/includes/auth-page-top.php';
?>
<section class="auth-card" aria-labelledby="auth-title">
    <p class="auth-eyebrow">A little more lovely</p>
    <h1 id="auth-title">Create your account</h1>
    <p class="auth-intro">Join Serenitea for a smoother, sweeter checkout.</p>
    <?php if (
        $errors
    ): ?><div class="alert alert-error" role="alert"><ul><?php foreach (
    array_unique($errors)
    as $error
): ?><li><?= sanitize(
    $error,
) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" data-single-submit>
        <input type="hidden" name="form_token" value="<?= sanitize(
            $form_token,
        ) ?>">
        <div class="form-group"><label class="form-label" for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" autocomplete="name" value="<?= sanitize(
            $full_name,
        ) ?>" required></div>
        <div class="form-group"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= sanitize(
            $email,
        ) ?>" required></div>
        <div class="form-group"><label class="form-label" for="password">Password</label><div class="password-field"><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button></div><small class="auth-hint">At least 8 characters with uppercase, lowercase, and a number.</small></div>
        <div class="form-group"><label class="form-label" for="confirm_password">Confirm password</label><div class="password-field"><input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Show confirm password">Show</button></div></div>
        <label class="auth-check"><input type="checkbox" name="terms" value="1" required <?= isset(
            $_POST['terms'],
        )
            ? 'checked'
            : '' ?>><span>I accept the Terms &amp; Conditions.</span></label>
        <button class="btn btn-primary btn-lg btn-block auth-submit" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="<?= app_url(
        'login.php',
    ) ?>">Sign in</a></p>
</section>
<?php require __DIR__ . '/includes/auth-page-bottom.php'; ?>