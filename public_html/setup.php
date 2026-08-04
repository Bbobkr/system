<?php
require __DIR__ . '/../app/config/config.php';

// حماية: هذه الصفحة تعمل فقط إذا لم يوجد أي مستخدم بعد (أول تشغيل بعد استيراد قاعدة البيانات)
try {
    $userCount = (int)db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
} catch (Throwable $e) {
    require APP_DIR . '/includes/header.php';
    echo '<div class="card login-card"><div class="alert alert-error">تعذر الاتصال بقاعدة البيانات. تأكد من استيراد sql/schema.sql و sql/seed.sql أولًا وضبط بيانات الاتصال في app/config/env.php.</div></div>';
    require APP_DIR . '/includes/footer.php';
    exit;
}

if ($userCount > 0) {
    require APP_DIR . '/includes/header.php';
    echo '<div class="card login-card"><div class="alert alert-error">تم إعداد النظام مسبقًا. لأسباب أمنية يرجى حذف الملف public_html/setup.php من الخادم.</div>'
       . '<a class="btn btn-primary" href="' . e(app_path('login.php')) . '" style="width:100%;text-align:center;display:block;">' . e(t('login')) . '</a></div>';
    require APP_DIR . '/includes/footer.php';
    exit;
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $fullName = input('full_name');
    $username = input('username');
    $password = input('password');
    $confirm = input('password_confirm');

    if ($fullName === '' || $username === '' || $password === '') {
        $errors[] = 'كل الحقول مطلوبة.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.';
    }
    if ($password !== $confirm) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO users (username, password_hash, full_name, role_id, branch_id, is_active, created_at)
             VALUES (?, ?, ?, 1, NULL, 1, NOW())'
        );
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName]);

        flash_success('تم إنشاء حساب المدير بنجاح. يمكنك الآن تسجيل الدخول. لأسباب أمنية يرجى حذف ملف setup.php من الخادم الآن.');
        redirect(app_path('login.php'));
    }
}

$pageTitle = 'إعداد النظام لأول مرة';
require APP_DIR . '/includes/header.php';
?>
<div class="card login-card">
    <div class="card-title">إنشاء حساب المدير الأول</div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label><?= e(t('full_name')) ?></label>
            <input type="text" name="full_name" required autofocus>
        </div>
        <div class="form-group">
            <label><?= e(t('username')) ?></label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label><?= e(t('password')) ?></label>
            <input type="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
            <label>تأكيد كلمة المرور</label>
            <input type="password" name="password_confirm" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">إنشاء الحساب</button>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
