<?php
require __DIR__ . '/../app/config/config.php';
require_permission('customers', 'edit');
require APP_DIR . '/modules/customer.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$customer = $id ? customer_find($id) : null;
if (!$customer) {
    flash_error(t('no_records'));
    redirect(app_path('customers/index.php'));
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = ['name' => input('name'), 'phone' => input('phone'), 'email' => input('email'), 'address' => input('address'), 'is_active' => isset($_POST['is_active'])];
    if ($data['name'] === '') {
        $errors[] = t('customer_name') . ' *';
    } else {
        customer_update($id, $data);
        log_activity('update', 'customers', $id, $data['name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('customers/index.php'));
    }
    $customer = array_merge($customer, $data);
}

$pageTitle = t('edit_customer');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('edit_customer')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('customer_name')) ?> *</label><input type="text" name="name" required value="<?= e($customer['name']) ?>"></div>
        <div class="form-group"><label><?= e(t('phone')) ?></label><input type="text" name="phone" value="<?= e($customer['phone'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= e($customer['email'] ?? '') ?>"></div>
        <div class="form-group"><label><?= e(t('address')) ?></label><input type="text" name="address" value="<?= e($customer['address'] ?? '') ?>"></div>
        <div class="form-group"><label><input type="checkbox" name="is_active" style="width:auto" <?= $customer['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('customers/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
