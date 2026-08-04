<?php
require __DIR__ . '/../app/config/config.php';
require_permission('suppliers', 'view');
require APP_DIR . '/modules/supplier.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    require_permission('suppliers', 'delete');
    csrf_verify();
    if (supplier_delete(input_int('id'))) {
        flash_success(t('delete') . ' ✓');
    } else {
        flash_error(is_rtl() ? 'لا يمكن حذف مورد مرتبط بفواتير.' : 'Cannot delete: supplier has invoices.');
    }
    redirect(app_path('suppliers/index.php'));
}

$search = trim($_GET['q'] ?? '');
$suppliers = supplier_all($search);
$pageTitle = t('nav_suppliers');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" style="max-width:320px;flex:1;">
        <input type="search" name="q" placeholder="<?= e(t('search')) ?>" value="<?= e($search) ?>">
    </form>
    <?php if (has_permission('suppliers', 'create')): ?>
        <a href="<?= e(app_path('suppliers/create.php')) ?>" class="btn btn-primary"><?= e(t('add_supplier')) ?></a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('supplier_name')) ?></th><th><?= e(t('phone')) ?></th><th><?= e(t('email')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
<tbody>
<?php if (!$suppliers): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($suppliers as $s): ?>
    <tr>
        <td><?= e($s['name']) ?></td>
        <td><?= e($s['phone'] ?? '') ?></td>
        <td><?= e($s['email'] ?? '') ?></td>
        <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= e($s['is_active'] ? t('active') : t('inactive')) ?></span></td>
        <td>
            <?php if (has_permission('suppliers', 'edit')): ?>
                <a href="<?= e(app_path('suppliers/edit.php?id=' . $s['id'])) ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
            <?php endif; ?>
            <?php if (has_permission('suppliers', 'delete')): ?>
            <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
