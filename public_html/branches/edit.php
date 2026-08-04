<?php
require __DIR__ . '/../app/config/config.php';
require_permission('branches', 'edit');
require APP_DIR . '/modules/branch.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$branch = $id ? branch_find($id) : null;
if (!$branch) {
    flash_error(t('no_records'));
    redirect(app_path('branches/index.php'));
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = ['name' => input('name'), 'address' => input('address'), 'phone' => input('phone'), 'is_active' => isset($_POST['is_active'])];
    if ($data['name'] === '') {
        $errors[] = t('branch_name') . ' *';
    } else {
        branch_update($id, $data);
        log_activity('update', 'branches', $id, $data['name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('branches/index.php'));
    }
    $branch = array_merge($branch, $data);
}

$pageTitle = t('edit_branch');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('edit_branch')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('branch_name')) ?> *</label><input type="text" name="name" required value="<?= e($branch['name']) ?>"></div>
        <div class="form-group"><label><?= e(t('address')) ?></label><input type="text" name="address" value="<?= e($branch['address'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('phone')) ?></label><input type="text" name="phone" value="<?= e($branch['phone'] ?? '') ?>"></div>
        <div class="form-group"><label><input type="checkbox" name="is_active" style="width:auto" <?= $branch['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('branches/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
