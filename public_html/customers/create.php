<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('customers', 'create');
require APP_DIR . '/modules/customer.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = ['name' => input('name'), 'phone' => input('phone'), 'email' => input('email'), 'address' => input('address')];
    if ($data['name'] === '') {
        $errors[] = t('customer_name') . ' *';
    } else {
        $id = customer_create($data);
        log_activity('create', 'customers', $id, $data['name']);
        flash_success(t('save') . ' ✓');
        redirect(app_path('customers/index.php'));
    }
}

$pageTitle = t('add_customer');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:560px;">
    <div class="card-title"><?= e(t('add_customer')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group"><label><?= e(t('customer_name')) ?> *</label><input type="text" name="name" required value="<?= old('name') ?>"></div>
        <div class="form-group"><label><?= e(t('phone')) ?></label><input type="text" name="phone" value="<?= old('phone') ?>"></div>
        <div class="form-group"><label><?= e(t('email')) ?></label><input type="email" name="email" value="<?= old('email') ?>"></div>
        <div class="form-group"><label><?= e(t('address')) ?></label><input type="text" name="address" value="<?= old('address') ?>"></div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('customers/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
