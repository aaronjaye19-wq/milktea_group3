<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Choose a New Password';
$token = trim($_POST['token'] ?? ($_GET['token'] ?? ''));
$error = '';
$complete = false;
$valid = false;
if (preg_match('/^[a-f0-9]{64}$/', $token) && !$pdo) {
    $error = 'Unable to connect. Please try again.';
} elseif (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, customer_id FROM password_resets WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP() LIMIT 1',
        );
        $stmt->execute([hash('sha256', $token)]);
        $valid = (bool) $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Unable to connect. Please try again.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!verify_form_token('reset', $_POST['form_token'] ?? '')) {
        $error = 'Unable to submit this form. Please try again.';
    } elseif (!$valid) {
        $error = 'This password reset link is invalid or has expired.';
    } elseif ($password === '' || $confirm_password === '') {
        $error = 'This field is required.';
    } elseif (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        $error = 'Password does not meet requirements.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();
            $lookup = $pdo->prepare(
                'SELECT customer_id FROM password_resets WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP() FOR UPDATE',
            );
            $lookup->execute([hash('sha256', $token)]);
            $reset = $lookup->fetch();
            if (!$reset) {
                throw new RuntimeException('expired');
            }
            $update = $pdo->prepare(
                'UPDATE customers SET password = ? WHERE id = ?',
            );
            $update->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $reset['customer_id'],
            ]);
            $delete = $pdo->prepare(
                'DELETE FROM password_resets WHERE customer_id = ?',
            );
            $delete->execute([$reset['customer_id']]);
            $pdo->commit();
            $complete = true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error =
                $e instanceof RuntimeException
                    ? 'This password reset link is invalid or has expired.'
                    : 'Unable to connect. Please try again.';
        }
    }
}
$form_token = create_form_token('reset');
require __DIR__ . '/includes/auth-page-top.php';
?>
<section class="auth-card" aria-labelledby="auth-title"><p class="auth-eyebrow">A fresh start</p><h1 id="auth-title">Choose a new password</h1>
    <?php if (
        $complete
    ): ?><div class="alert alert-success" role="status">Your password has been updated. <a href="<?= app_url(
    'login.php',
) ?>">Sign in</a></div>
    <?php elseif (
        !$valid &&
        !$error
    ): ?><div class="alert alert-error" role="alert">This password reset link is invalid or has expired.</div>
    <?php else: ?>
        <?php if (
            $error
        ): ?><div class="alert alert-error" role="alert"><?= sanitize(
    $error,
) ?></div><?php endif; ?>
        <p class="auth-intro">Use at least 8 characters with uppercase, lowercase, and a number.</p>
        <form method="post" data-single-submit><input type="hidden" name="form_token" value="<?= sanitize(
            $form_token,
        ) ?>"><input type="hidden" name="token" value="<?= sanitize(
    $token,
) ?>"><div class="form-group"><label class="form-label" for="password">New password</label><div class="password-field"><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button></div></div><div class="form-group"><label class="form-label" for="confirm_password">Confirm new password</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required></div><button class="btn btn-primary btn-lg btn-block auth-submit" type="submit">Update password</button></form>
    <?php endif; ?>
    <p class="auth-switch"><a href="<?= app_url(
        'login.php',
    ) ?>">Return to sign in</a></p>
</section>
<?php require __DIR__ . '/includes/auth-page-bottom.php'; ?>
