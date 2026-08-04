<?php
require __DIR__ . '/../app/config/config.php';
require_permission('products', 'view');
require APP_DIR . '/modules/product.php';
require APP_DIR . '/modules/stock.php';
require APP_DIR . '/modules/branch.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    require_permission('products', 'delete');
    csrf_verify();
    $id = input_int('id');
    if (product_delete($id)) {
        flash_success(t('delete') . ' ✓');
    } else {
        flash_error(is_rtl() ? 'لا يمكن حذف المنتج لوجود حركات مرتبطة به. يمكنك تعطيله بدلاً من ذلك.' : 'Cannot delete: product has related records. Deactivate it instead.');
    }
    redirect(app_path('products/index.php'));
}

$search = trim($_GET['q'] ?? '');
$products = product_all($search);
$branches = branch_all(true);
$branchId = user_branch_id();

$pageTitle = t('products_list');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" style="max-width:320px;flex:1;">
        <input type="search" name="q" placeholder="<?= e(t('search')) ?>" value="<?= e($search) ?>">
    </form>
    <?php if (has_permission('products', 'create')): ?>
        <a href="<?= e(app_path('products/create.php')) ?>" class="btn btn-primary"><?= e(t('add_product')) ?></a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table>
<thead><tr>
    <th><?= e(t('product_name')) ?></th><th><?= e(t('barcode')) ?></th><th><?= e(t('category')) ?></th>
    <th><?= e(t('sale_price')) ?></th><th><?= e(t('current_stock')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('actions')) ?></th>
</tr></thead>
<tbody>
<?php if (!$products): ?>
    <tr><td colspan="7" class="text-muted"><?= e(t('no_records')) ?></td></tr>
<?php endif; ?>
<?php foreach ($products as $p):
    $qty = $branchId ? stock_get_quantity((int)$p['id'], $branchId) : array_sum(stock_get_quantities_all_branches((int)$p['id']));
    $low = $qty <= (int)$p['low_stock_threshold'];
?>
    <tr>
        <td><?= e($p['name']) ?><?php if ($p['sku']): ?><div class="text-muted" style="font-size:11px;">SKU: <?= e($p['sku']) ?></div><?php endif; ?></td>
        <td><?= e($p['barcode'] ?? '') ?></td>
        <td><?= e($p['category_name'] ?? '') ?></td>
        <td><?= e(format_money((float)$p['sale_price'])) ?></td>
        <td class="<?= $low ? 'text-danger' : '' ?>"><?= (int)$qty ?></td>
        <td><span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= e($p['is_active'] ? t('active') : t('inactive')) ?></span></td>
        <td>
            <?php if (has_permission('products', 'edit')): ?>
                <a href="<?= e(app_path('products/edit.php?id=' . $p['id'])) ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
            <?php endif; ?>
            <a href="<?= e(app_path('barcode/generate.php?id=' . $p['id'])) ?>" class="btn btn-sm"><?= e(t('nav_barcode')) ?></a>
            <?php if (has_permission('products', 'delete')): ?>
                <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
