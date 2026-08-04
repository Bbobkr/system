<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('suppliers', 'edit');
require APP_DIR . '/modules/supplier.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$supplier = $id ? supplier_find($id) : null;
if (!$supplier) {
    flash_error(t('no_records'));
    redirect(app_path('suppliers/index.php'));
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = ['name' => input('name'), 'phone' => input('phone'), 'email' => input('email'), 'address' => input('address'), 'is_active' => isset($_POST['is_active'])];
    if ($data['name'] === '') {
        $errors[] = t('supplier_name') . ' *';
    } else {
        supplier_update($id, $data);
        log_activity('update', 'suppliers', $id, $data['name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('suppliers/index.php'));
    }
    $supplier = array_merge($supplier, $data);
}

$pageTitle = t('edit_supplier');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('edit_supplier')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('supplier_name')) ?> *</label><input type="text" name="name" required value="<?= e($supplier['name']) ?>"></div>
        <div class="form-group"><label><?= e(t('phone')) ?></label><input type="text" name="phone" value="<?= e($supplier['phone'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= e($supplier['email'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('address')) ?></label><input type="text" name="address" value="<?= e($supplier['address'] ?? '') ?>"></div>
        <div class="form-group"><label><input type="checkbox" name="is_active" style="width:auto" <?= $supplier['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('suppliers/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
