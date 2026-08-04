<?php
require __DIR__ . '/../app/config/config.php';
require_permission('stock', 'view');
require APP_DIR . '/modules/stock.php';
require APP_DIR . '/modules/product.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_permission('stock', 'create');
    csrf_verify();
    $productId = input_int('product_id');
    $branchId = $restrictedBranch ?? input_int('branch_id');
    $type = in_array($_POST['type'] ?? '', ['in', 'out'], true) ? $_POST['type'] : 'in';
    $qty = input_int('quantity');
    $note = input('note');

    if (!$productId || !$branchId || $qty <= 0) {
        $errors[] = is_rtl() ? 'يرجى اختيار المنتج والفرع وكمية أكبر من صفر.' : 'Please select product, branch and a quantity greater than zero.';
    } elseif ($type === 'out' && stock_get_quantity($productId, $branchId) < $qty) {
        $errors[] = t('insufficient_stock');
    } else {
        stock_record_movement($productId, $branchId, $type, $qty, 'manual', null, $note);
        flash_success(t('save') . ' ✓');
        redirect(app_path('stock/movements.php'));
    }
}

$products = product_all('', true);
$branches = branch_all(true);
$filterBranch = $restrictedBranch ?? (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null);
$movements = stock_movements_list(['branch_id' => $filterBranch]);

$typeLabels = [
    'in' => t('stock_in'), 'out' => t('stock_out'),
    'transfer_in' => t('stock_in') . ' (' . t('stock_transfer') . ')', 'transfer_out' => t('stock_out') . ' (' . t('stock_transfer') . ')',
    'sale' => t('nav_sales'), 'sale_return' => t('sales_returns'),
    'purchase' => t('nav_purchases'), 'purchase_return' => t('purchase_returns'),
];

$pageTitle = t('stock_movements');
require APP_DIR . '/includes/header.php';
?>
<div class="grid grid-2">
<?php if (has_permission('stock', 'create')): ?>
<div class="card">
    <div class="card-title"><?= is_rtl() ? 'تسوية مخزون يدوية' : 'Manual Stock Adjustment' ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label><?= e(t('product_name')) ?></label>
            <select name="product_id" required>
                <option value="">—</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?><?= $p['barcode'] ? ' (' . e($p['barcode']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($restrictedBranch === null): ?>
        <div class="form-group">
            <label><?= e(t('branch')) ?></label>
            <select name="branch_id" required>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('movement_type')) ?></label>
                <select name="type">
                    <option value="in"><?= e(t('stock_in')) ?></option>
                    <option value="out"><?= e(t('stock_out')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('quantity')) ?></label>
                <input type="number" name="quantity" min="1" step="1" required>
            </div>
        </div>
        <div class="form-group">
            <label><?= e(t('note')) ?></label>
            <input type="text" name="note">
        </div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title"><?= e(t('stock_transfer')) ?> / <?= e(t('low_stock_alerts')) ?></div>
    <a href="<?= e(app_path('stock/transfer.php')) ?>" class="btn"><?= e(t('stock_transfer')) ?></a>
    <a href="<?= e(app_path('stock/alerts.php')) ?>" class="btn"><?= e(t('low_stock_alerts')) ?></a>
</div>
</div>

<div class="card">
<?php if ($restrictedBranch === null): ?>
<form method="get" class="toolbar">
    <select name="branch_id" onchange="this.form.submit()" style="max-width:240px">
        <option value=""><?= e(t('all_branches')) ?></option>
        <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('date')) ?></th><th><?= e(t('product_name')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('movement_type')) ?></th><th><?= e(t('quantity')) ?></th><th><?= e(t('note')) ?></th></tr></thead>
<tbody>
<?php if (!$movements): ?><tr><td colspan="6" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($movements as $m):
    $increase = in_array($m['type'], STOCK_INCREASE_TYPES, true);
?>
    <tr>
        <td><?= e(format_date($m['created_at'])) ?></td>
        <td><?= e($m['product_name']) ?></td>
        <td><?= e($m['branch_name']) ?></td>
        <td><span class="badge <?= $increase ? 'badge-success' : 'badge-danger' ?>"><?= e($typeLabels[$m['type']] ?? $m['type']) ?></span></td>
        <td><?= $increase ? '+' : '-' ?><?= (int)$m['quantity'] ?></td>
        <td class="text-muted"><?= e($m['note'] ?? '') ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
