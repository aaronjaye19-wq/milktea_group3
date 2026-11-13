<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Forgot Password';
$error = '';
$sent = false;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!verify_form_token('forgot', $_POST['form_token'] ?? '')) {
        $error = 'Unable to submit this form. Please try again.';
    } elseif ($email === '') {
        $error = 'This field is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM customers WHERE email = ? LIMIT 1',
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $raw_token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $raw_token);
                $save = $pdo->prepare(
                    'INSERT INTO password_resets (customer_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))',
                );
                $save->execute([$user['id'], $token_hash]);
                $scheme =
                    !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                        ? 'https'
                        : 'http';
                $host = preg_replace(
                    '/[^A-Za-z0-9.:-]/',
                    '',
                    $_SERVER['SERVER_NAME'] ?? 'localhost',
                );
                $url =
                    $scheme .
                    '://' .
                    $host .
                    app_url(
                        'reset-password.php?token=' . rawurlencode($raw_token),
                    );
                $subject = 'Reset your Serenitea password';
                $message =
                    "To reset your password, open this link within one hour:\n\n" .
                    $url .
                    "\n\nIf you did not request this, you can ignore this email.";
                $headers =
                    'From: Serenitea <no-reply@' .
                    (strpos($host, ':') === false ? $host : 'localhost') .
                    ">\r\nContent-Type: text/plain; charset=UTF-8";
                if (!@mail($email, $subject, $message, $headers)) {
                    $pdo->prepare(
                        'DELETE FROM password_resets WHERE token_hash = ?',
                    )->execute([$token_hash]);
                    $error = 'Unable to connect. Please try again.';
                }
            }
            if (!$error) {
                $sent = true;
            }
        } catch (Throwable $e) {
            $error = 'Unable to connect. Please try again.';
        }
    }
}
$form_token = create_form_token('forgot');
require __DIR__ . '/includes/auth-page-top.php';
?>
<section class="auth-card" aria-labelledby="auth-title">
    <p class="auth-eyebrow">No worries</p><h1 id="auth-title">Reset your password</h1><p class="auth-intro">Enter your account email and we&apos;ll send a secure reset link.</p>
    <?php if (
        $sent
    ): ?><div class="alert alert-success" role="status">If an account exists for that email, a reset link has been sent.</div><?php endif; ?>
    <?php if (
        $error
    ): ?><div class="alert alert-error" role="alert"><?= sanitize(
    $error,
) ?></div><?php endif; ?>
    <?php if (
        !$sent
    ): ?><form method="post" data-single-submit><input type="hidden" name="form_token" value="<?= sanitize(
    $form_token,
) ?>"><div class="form-group"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= sanitize(
    $email,
) ?>" required></div><button class="btn btn-primary btn-lg btn-block auth-submit" type="submit">Send reset link</button></form><?php endif; ?>
    <p class="auth-switch"><a href="<?= app_url(
        'login.php',
    ) ?>">Return to sign in</a></p>
</section>
<?php require __DIR__ . '/includes/auth-page-bottom.php'; ?>
