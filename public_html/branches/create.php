<?php
require __DIR__ . '/../app/config/config.php';
require_permission('branches', 'create');
require APP_DIR . '/modules/branch.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = ['name' => input('name'), 'address' => input('address'), 'phone' => input('phone')];
    if ($data['name'] === '') {
        $errors[] = t('branch_name') . ' *';
    } else {
        $id = branch_create($data);
        log_activity('create', 'branches', $id, $data['name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('branches/index.php'));
    }
}

$pageTitle = t('add_branch');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('add_branch')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('branch_name')) ?> *</label><input type="text" name="name" required value="<?= old('name') ?>"></div>
        <div class="form-group"><label><?= e(t('address')) ?></label><input type="text" name="address" value="<?= old('address') ?>"></div>
        <div class="form-group"><label><?= e(t('phone')) ?></label><input type="text" name="phone" value="<?= old('phone') ?>"></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('branches/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
