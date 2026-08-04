<?php
require __DIR__ . '/../app/config/config.php';
require_permission('branches', 'view');
require APP_DIR . '/modules/branch.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    require_permission('branches', 'delete');
    csrf_verify();
    if (branch_delete(input_int('id'))) {
        flash_success(t('delete') . ' ✓');
    } else {
        flash_error(is_rtl() ? 'لا يمكن حذف فرع مرتبط بمخزون أو فواتير.' : 'Cannot delete: branch is in use.');
    }
    redirect(app_path('branches/index.php'));
}

$branches = branch_all();
$pageTitle = t('nav_branches');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <div></div>
    <?php if (has_permission('branches', 'create')): ?>
        <a href="<?= e(app_path('branches/create.php')) ?>" class="btn btn-primary"><?= e(t('add_branch')) ?></a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('branch_name')) ?></th><th><?= e(t('address')) ?></th><th><?= e(t('phone')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
<tbody>
<?php if (!$branches): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($branches as $b): ?>
    <tr>
        <td><?= e($b['name']) ?></td>
        <td><?= e($b['address'] ?? '') ?></td>
        <td><?= e($b['phone'] ?? '') ?></td>
        <td><span class="badge <?= $b['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= e($b['is_active'] ? t('active') : t('inactive')) ?></span></td>
        <td>
            <?php if (has_permission('branches', 'edit')): ?>
                <a href="<?= e(app_path('branches/edit.php?id=' . $b['id'])) ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
            <?php endif; ?>
            <?php if (has_permission('branches', 'delete')): ?>
            <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
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
