<?php
require __DIR__ . '/../app/config/config.php';
require_permission('purchases', 'view');
require APP_DIR . '/modules/purchases.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$filters = [
    'branch_id' => $restrictedBranch ?? (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null),
    'search' => trim($_GET['q'] ?? ''),
];
$invoices = purchase_invoices_list($filters);
$branches = $restrictedBranch === null ? branch_all(true) : [];
$statusBadge = ['paid' => 'badge-success', 'partial' => 'badge-warning', 'unpaid' => 'badge-danger'];

$pageTitle = t('nav_purchases');
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get" class="form-row" style="flex:1;">
        <input type="search" name="q" placeholder="<?= e(t('search')) ?>" value="<?= e($filters['search']) ?>">
        <?php if ($restrictedBranch === null): ?>
        <select name="branch_id">
            <option value=""><?= e(t('all_branches')) ?></option>
            <?php foreach ($branches as $b): ?>
                <option value="<?= (int)$b['id'] ?>" <?= $filters['branch_id'] === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="btn"><?= e(t('search')) ?></button>
    </form>
    <?php if (has_permission('purchases', 'create')): ?>
        <a href="<?= e(app_path('purchases/create.php')) ?>" class="btn btn-primary"><?= e(t('new_purchase')) ?></a>
    <?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead><tr><th><?= e(t('invoice_no')) ?></th><th><?= e(t('date')) ?></th><th><?= e(t('supplier')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('total')) ?></th><th><?= e(t('payment_status')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
<tbody>
<?php if (!$invoices): ?><tr><td colspan="7" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
<?php foreach ($invoices as $inv): ?>
    <tr>
        <td><?= e($inv['invoice_no']) ?></td>
        <td><?= e(format_date($inv['created_at'])) ?></td>
        <td><?= e($inv['supplier_name']) ?></td>
        <td><?= e($inv['branch_name']) ?></td>
        <td><?= e(format_money((float)$inv['total'])) ?></td>
        <td><span class="badge <?= $statusBadge[$inv['payment_status']] ?? 'badge-muted' ?>"><?= e(t($inv['payment_status'])) ?></span></td>
        <td>
            <a href="<?= e(app_path('purchases/view.php?id=' . $inv['id'])) ?>" class="btn btn-sm"><?= e(t('view')) ?></a>
            <a href="<?= e(app_path('purchases/print.php?id=' . $inv['id'])) ?>" class="btn btn-sm"><?= e(t('print')) ?></a>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
