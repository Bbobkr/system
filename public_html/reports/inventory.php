<?php
require __DIR__ . '/../app/config/config.php';
require_permission('reports', 'view');
require APP_DIR . '/modules/report.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$branchId = $restrictedBranch ?? (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null);
$rows = report_inventory($branchId);
$branches = $restrictedBranch === null ? branch_all(true) : [];
$totalValue = array_sum(array_column($rows, 'stock_value'));

$pageTitle = t('report_inventory');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
    <div class="toolbar">
        <div><a href="<?= e(app_path('reports/sales.php')) ?>" class="btn btn-sm"><?= e(t('report_sales')) ?></a>
             <a href="<?= e(app_path('reports/inventory.php')) ?>" class="btn btn-sm"><?= e(t('report_inventory')) ?></a>
             <a href="<?= e(app_path('reports/profit.php')) ?>" class="btn btn-sm"><?= e(t('report_profit')) ?></a></div>
    </div>
    <?php if ($restrictedBranch === null): ?>
    <form method="get" class="toolbar">
        <select name="branch_id" onchange="this.form.submit()" style="max-width:240px">
            <option value=""><?= e(t('all_branches')) ?></option>
            <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $branchId === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>

    <p><?= e(t('stock_value')) ?>: <b><?= e(format_money($totalValue)) ?></b></p>

    <div class="table-wrap">
    <table>
        <thead><tr><th><?= e(t('product_name')) ?></th><th><?= e(t('barcode')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('current_stock')) ?></th><th><?= e(t('cost_price')) ?></th><th><?= e(t('sale_price')) ?></th><th><?= e(t('stock_value')) ?></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['barcode'] ?? '') ?></td>
                <td><?= e($r['branch_name']) ?></td>
                <td><?= (int)$r['quantity'] ?></td>
                <td><?= e(format_money((float)$r['cost_price'])) ?></td>
                <td><?= e(format_money((float)$r['sale_price'])) ?></td>
                <td><?= e(format_money((float)$r['stock_value'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
