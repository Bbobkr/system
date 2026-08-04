<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('customers', 'view');
require APP_DIR . '/modules/customer.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    require_permission('customers', 'delete');
    csrf_verify();
    if (customer_delete(input_int('id'))) {
        flash_success(t('delete') . ' ✓');
    } else {
        flash_error(is_rtl() ? 'لا يمكن حذف عميل مرتبط بفواتير.' : 'Cannot delete: customer has invoices.');
    }
    redirect(app_path('customers/index.php'));
}

$search = trim($_GET['q'] ?? '');
$customers = customer_all($search);
$pageTitle = t('nav_customers');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" style="max-width:320px;flex:1;">
        <input type="search" name="q" placeholder="<?= e(t('search')) ?>" value="<?= e($search) ?>">
    </form>
    <?php if (has_permission('customers', 'create')): ?>
        <a href="<?= e(app_path('customers/create.php')) ?>" class="btn btn-primary"><?= e(t('add_customer')) ?></a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('customer_name')) ?></th><th><?= e(t('phone')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
<tbody>
<?php if (!$customers): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($customers as $c): ?>
    <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= e($c['phone'] ?? '') ?></td>
        <td><?= e($c['email'] ?? '') ?></td>
        <td><span class="badge <?= $c['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= e($c['is_active'] ? t('active') : t('inactive')) ?></span></td>
        <td>
            <?php if (has_permission('customers', 'edit')): ?>
                <a href="<?= e(app_path('customers/edit.php?id=' . $c['id'])) ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
            <?php endif; ?>
            <?php if (has_permission('customers', 'delete')): ?>
            <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><?= e(t('delete')) ?></button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
