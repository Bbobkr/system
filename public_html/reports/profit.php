<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('reports', 'view');
require APP_DIR . '/modules/report.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$branchId = $restrictedBranch ?? (isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null);

$rows = report_profit($fromDate, $toDate, $branchId);
$branches = $restrictedBranch === null ? branch_all(true) : [];
$totalRevenue = array_sum(array_column($rows, 'revenue'));
$totalCost = array_sum(array_column($rows, 'cost'));
$totalProfit = $totalRevenue - $totalCost;

$pageTitle = t('report_profit');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
    <div class="toolbar">
        <div><a href="<?= e(app_path('reports/sales.php')) ?>" class="btn btn-sm"><?= e(t('report_sales')) ?></a>
             <a href="<?= e(app_path('reports/inventory.php')) ?>" class="btn btn-sm"><?= e(t('report_inventory')) ?></a>
             <a href="<?= e(app_path('reports/profit.php')) ?>" class="btn btn-sm"><?= e(t('report_profit')) ?></a></div>
    </div>
    <form method="get" class="form-row">
        <div class="form-group"><label><?= e(t('from_date')) ?></label><input type="date" name="from_date" value="<?= e($fromDate) ?>"></div>
        <div class="form-group"><label><?= e(t('to_date')) ?></label><input type="date" name="to_date" value="<?= e($toDate) ?>"></div>
        <?php if ($restrictedBranch === null): ?>
        <div class="form-group">
            <label><?= e(t('branch')) ?></label>
            <select name="branch_id">
                <option value=""><?= e(t('all_branches')) ?></option>
                <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $branchId === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-group" style="align-self:flex-end;"><button type="submit" class="btn btn-primary"><?= e(t('search')) ?></button></div>
    </form>

    <div class="grid grid-3">
        <div class="stat-card"><div class="stat-label"><?= e(t('revenue')) ?></div><div class="stat-value"><?= e(format_money($totalRevenue)) ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= e(t('cost')) ?></div><div class="stat-value"><?= e(format_money($totalCost)) ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= e(t('profit')) ?></div><div class="stat-value" style="color:var(--color-success)"><?= e(format_money($totalProfit)) ?></div></div>
    </div>

    <div class="table-wrap" style="margin-top:16px;">
    <table>
        <thead><tr><th><?= e(t('invoice_no')) ?></th><th><?= e(t('date')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('revenue')) ?></th><th><?= e(t('cost')) ?></th><th><?= e(t('profit')) ?></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $profit = (float)$r['revenue'] - (float)$r['cost']; ?>
            <tr>
                <td><?= e($r['invoice_no']) ?></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
                <td><?= e($r['branch_name']) ?></td>
                <td><?= e(format_money((float)$r['revenue'])) ?></td>
                <td><?= e(format_money((float)$r['cost'])) ?></td>
                <td style="color:<?= $profit >= 0 ? 'var(--color-success)' : 'var(--color-danger)' ?>"><?= e(format_money($profit)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
