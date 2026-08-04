<?php
require __DIR__ . '/../app/config/config.php';
require_permission('users', 'view');
require APP_DIR . '/modules/user.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    require_permission('users', 'delete');
    csrf_verify();
    if (user_delete(input_int('id'))) {
        flash_success(t('delete') . ' ✓');
    } else {
        flash_error(is_rtl() ? 'تعذر حذف هذا المستخدم.' : 'Could not delete this user.');
    }
    redirect(app_path('users/index.php'));
}

$users = users_all();
$pageTitle = t('nav_users');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <div>
        <?php if (is_admin()): ?><a href="<?= e(app_path('users/permissions.php')) ?>" class="btn"><?= e(t('permissions')) ?></a><?php endif; ?>
    </div>
    <?php if (has_permission('users', 'create')): ?>
        <a href="<?= e(app_path('users/create.php')) ?>" class="btn btn-primary"><?= e(t('add_user')) ?></a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('full_name')) ?></th><th><?= e(t('username')) ?></th><th><?= e(t('role')) ?></th><th><?= e(t('assigned_branch')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
<tbody>
<?php if (!$users): ?><tr><td colspan="6" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($users as $u): ?>
    <tr>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['username']) ?></td>
        <td><?= e(is_rtl() ? $u['role_name_ar'] : $u['role_name']) ?></td>
        <td><?= e($u['branch_name'] ?? t('all_branches')) ?></td>
        <td><span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= e($u['is_active'] ? t('active') : t('inactive')) ?></span></td>
        <td>
            <?php if (has_permission('users', 'edit')): ?>
                <a href="<?= e(app_path('users/edit.php?id=' . $u['id'])) ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
            <?php endif; ?>
            <?php if (has_permission('users', 'delete') && (int)$u['id'] !== (int)current_user()['id']): ?>
            <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
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
