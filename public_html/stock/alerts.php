<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('stock', 'view');
require APP_DIR . '/modules/stock.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$filterBranch = $restrictedBranch ?? (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null);
$items = stock_low_stock_list($filterBranch);
$branches = $restrictedBranch === null ? branch_all(true) : [];

$pageTitle = t('low_stock_alerts');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
<?php if ($restrictedBranch === null && $branches): ?>
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
<thead><tr><th><?= e(t('product_name')) ?></th><th><?= e(t('barcode')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('current_stock')) ?></th><th><?= e(t('low_stock_threshold')) ?></th></tr></thead>
<tbody>
<?php if (!$items): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($items as $row): ?>
    <tr>
        <td><?= e($row['name']) ?></td>
        <td><?= e($row['barcode'] ?? '') ?></td>
        <td><?= e($row['branch_name']) ?></td>
        <td class="text-danger"><?= (int)$row['quantity'] ?></td>
        <td><?= (int)$row['low_stock_threshold'] ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
