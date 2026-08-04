<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('users', 'edit');
require APP_DIR . '/modules/user.php';
require APP_DIR . '/modules/branch.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$user = $id ? user_find($id) : null;
if (!$user) {
    flash_error(t('no_records'));
    redirect(app_path('users/index.php'));
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = [
        'full_name' => input('full_name'),
        'email' => input('email'),
        'role_id' => input_int('role_id'),
        'branch_id' => input_int('branch_id') ?: null,
        'is_active' => isset($_POST['is_active']),
        'password' => input('password'),
    ];

    if ($data['full_name'] === '' || !$data['role_id']) {
        $errors[] = is_rtl() ? 'الحقول المطلوبة: الاسم الكامل، الدور.' : 'Required: full name, role.';
    }
    if ($data['password'] !== '' && strlen($data['password']) < 8) {
        $errors[] = is_rtl() ? 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.' : 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        user_update($id, $data);
        log_activity('update', 'users', $id, $data['full_name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('users/index.php'));
    }
    $user = array_merge($user, $data);
}

$roles = roles_all();
$branches = branch_all(true);
$pageTitle = t('edit_user');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('edit_user')) ?> — <?= e($user['username']) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('full_name')) ?> *</label><input type="text" name="full_name" required value="<?= e($user['full_name']) ?>"></div>
        <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= e($user['email'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('change_password')) ?></label><input type="password" name="password" minlength="8" placeholder="<?= e(t('leave_blank_keep')) ?>"></div>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('role')) ?> *</label>
                <select name="role_id" required>
                    <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e(is_rtl() ? $r['name_ar'] : $r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('assigned_branch')) ?></label>
                <select name="branch_id">
                    <option value=""><?= e(t('all_branches')) ?></option>
                    <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)($user['branch_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label><input type="checkbox" name="is_active" style="width:auto" <?= $user['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('users/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
