<?php
require __DIR__ . '/../app/config/config.php';

if (is_logged_in()) {
    redirect(app_path('dashboard.php'));
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $username = input('username');
    $password = input('password');

    if (attempt_login($username, $password)) {
        $redirectTo = $_SESSION['_redirect_after_login'] ?? app_path('dashboard.php');
        unset($_SESSION['_redirect_after_login']);
        redirect($redirectTo ?: app_path('dashboard.php'));
    }
    $error = t('login_failed');
}

$pageTitle = t('login');
require APP_DIR . '/includes/header.php';
?>
<div class="card login-card">
    <div class="card-title"><?= e(t('app_name')) ?></div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label><?= e(t('username')) ?></label>
            <input type="text" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label><?= e(t('password')) ?></label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%"><?= e(t('login')) ?></button>
    </form>
    <div style="text-align:center;margin-top:14px;">
        <form method="post" action="<?= e(app_path('settings/language.php')) ?>" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="lang" value="<?= current_lang() === 'ar' ? 'en' : 'ar' ?>">
            <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
            <button type="submit" class="btn-link"><?= current_lang() === 'ar' ? 'English' : 'العربية' ?></button>
        </form>
    </div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
