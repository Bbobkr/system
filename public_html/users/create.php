<?php
require __DIR__ . '/../app/config/config.php';
require_permission('users', 'create');
require APP_DIR . '/modules/user.php';
require APP_DIR . '/modules/branch.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = [
        'username' => input('username'),
        'password' => input('password'),
        'full_name' => input('full_name'),
        'email' => input('email'),
        'role_id' => input_int('role_id'),
        'branch_id' => input_int('branch_id') ?: null,
        'is_active' => isset($_POST['is_active']),
    ];

    if ($data['username'] === '' || $data['full_name'] === '' || !$data['role_id']) {
        $errors[] = is_rtl() ? 'الحقول المطلوبة: اسم المستخدم، الاسم الكامل، الدور.' : 'Required: username, full name, role.';
    }
    if (strlen($data['password']) < 8) {
        $errors[] = is_rtl() ? 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.' : 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        try {
            $id = user_create($data);
            log_activity('create', 'users', $id, $data['username']);
            flash_success(t('save') . ' ✓');
            redirect(app_path('users/index.php'));
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'Duplicate')
                ? (is_rtl() ? 'اسم المستخدم مستخدم من قبل.' : 'Username already taken.')
                : $e->getMessage();
        }
    }
}

$roles = roles_all();
$branches = branch_all(true);
$pageTitle = t('add_user');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('add_user')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('full_name')) ?> *</label><input type="text" name="full_name" required value="<?= old('full_name') ?>"></div>
        <div class="form-row">
            <div class="form-group"><label><?= e(t('username')) ?> *</label><input type="text" name="username" required value="<?= old('username') ?>"></div>
            <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= old('email') ?>"></div>
        </div>
        <div class="form-group"><label><?= e(t('password')) ?> *</label><input type="password" name="password" required minlength="8"></div>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('role')) ?> *</label>
                <select name="role_id" required>
                    <option value="">—</option>
                    <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e(is_rtl() ? $r['name_ar'] : $r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('assigned_branch')) ?></label>
                <select name="branch_id">
                    <option value=""><?= e(t('all_branches')) ?></option>
                    <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label><input type="checkbox" name="is_active" style="width:auto" checked> <?= e(t('active')) ?></label></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('users/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
