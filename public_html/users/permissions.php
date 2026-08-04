<?php
require __DIR__ . '/../app/config/config.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    require APP_DIR . '/includes/header.php';
    echo '<div class="alert alert-error">' . e(t('no_permission')) . '</div>';
    require APP_DIR . '/includes/footer.php';
    exit;
}
require APP_DIR . '/modules/user.php';

$roles = roles_all();
$roleId = (int)($_GET['role_id'] ?? $roles[0]['id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $roleId = input_int('role_id');
    $matrix = $_POST['perm'] ?? [];
    role_permissions_save($roleId, $matrix);
    flash_success(t('save') . ' ✓');
    redirect(app_path('users/permissions.php?role_id=' . $roleId));
}

$currentPerms = role_permissions($roleId);
$moduleLabels = [
    'products' => t('nav_products'), 'categories' => t('nav_categories'), 'branches' => t('nav_branches'),
    'stock' => t('nav_stock'), 'customers' => t('nav_customers'), 'suppliers' => t('nav_suppliers'),
    'sales' => t('nav_sales'), 'purchases' => t('nav_purchases'), 'users' => t('nav_users'),
    'reports' => t('nav_reports'), 'settings' => t('nav_settings'),
];

$pageTitle = t('permissions');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
    <div class="card-title"><?= e(t('permissions')) ?></div>
    <form method="get" class="toolbar">
        <select name="role_id" onchange="this.form.submit()" style="max-width:260px">
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= $roleId === (int)$r['id'] ? 'selected' : '' ?>><?= e(is_rtl() ? $r['name_ar'] : $r['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($roleId === (int)($roles[0]['id'] ?? 0) && ($roles[0]['slug'] ?? '') === 'admin'): ?>
        <div class="alert alert-success"><?= is_rtl() ? 'حساب المدير (Admin) يملك كل الصلاحيات دائمًا ولا يحتاج تعديلًا.' : 'The Administrator role always has full access and does not need editing.' ?></div>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="role_id" value="<?= (int)$roleId ?>">
        <div class="table-wrap">
        <table>
            <thead><tr><th><?= is_rtl() ? 'الوحدة' : 'Module' ?></th><th><?= e(t('view')) ?></th><th><?= is_rtl() ? 'إضافة' : 'Create' ?></th><th><?= e(t('edit')) ?></th><th><?= e(t('delete')) ?></th></tr></thead>
            <tbody>
            <?php foreach (PERMISSION_MODULES as $module): $p = $currentPerms[$module] ?? []; ?>
                <tr>
                    <td><?= e($moduleLabels[$module] ?? $module) ?></td>
                    <td><input type="checkbox" name="perm[<?= $module ?>][view]" style="width:auto" <?= !empty($p['can_view']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="perm[<?= $module ?>][create]" style="width:auto" <?= !empty($p['can_create']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="perm[<?= $module ?>][edit]" style="width:auto" <?= !empty($p['can_edit']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="perm[<?= $module ?>][delete]" style="width:auto" <?= !empty($p['can_delete']) ? 'checked' : '' ?>></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:14px;"><?= e(t('save')) ?></button>
    </form>
    <?php endif; ?>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
