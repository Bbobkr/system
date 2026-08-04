<?php
require __DIR__ . '/../app/config/config.php';
require_login();
require APP_DIR . '/modules/stock.php';
require APP_DIR . '/modules/report.php';
require APP_DIR . '/modules/branch.php';

$restrictedBranch = user_branch_id();
$selectedBranch = $restrictedBranch;
if ($restrictedBranch === null && isset($_GET['branch_id']) && $_GET['branch_id'] !== '') {
    $selectedBranch = (int)$_GET['branch_id'];
}

$todaySales = dashboard_today_sales($selectedBranch);
$lowStockCount = stock_low_stock_count($selectedBranch);
$stockValue = stock_total_value($selectedBranch);
$totalProducts = dashboard_total_products();
$trend = dashboard_sales_trend($selectedBranch);
$topProducts = dashboard_top_products($selectedBranch);
$stockByBranch = $restrictedBranch === null ? stock_value_by_branch() : [];
$branches = $restrictedBranch === null ? branch_all(true) : [];

$pageTitle = t('nav_dashboard');
require APP_DIR . '/includes/header.php';
?>

<?php if ($restrictedBranch === null && $branches): ?>
<form method="get" class="toolbar">
    <select name="branch_id" onchange="this.form.submit()" style="max-width:240px">
        <option value=""><?= e(t('all_branches')) ?></option>
        <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $selectedBranch === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php endif; ?>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-label"><?= e(t('today_sales')) ?></div>
        <div class="stat-value"><?= e(format_money($todaySales)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?= e(t('low_stock_items')) ?></div>
        <div class="stat-value" style="<?= $lowStockCount > 0 ? 'color:var(--color-danger)' : '' ?>"><?= (int)$lowStockCount ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?= e(t('stock_value')) ?></div>
        <div class="stat-value"><?= e(format_money($stockValue)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><?= e(t('total_products')) ?></div>
        <div class="stat-value"><?= (int)$totalProducts ?></div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-title"><?= e(t('sales_trend')) ?></div>
        <canvas id="chartTrend" height="200"></canvas>
    </div>
    <div class="card">
        <div class="card-title"><?= e(t('top_products')) ?></div>
        <canvas id="chartTop" height="200"></canvas>
    </div>
</div>

<?php if ($stockByBranch): ?>
<div class="card">
    <div class="card-title"><?= e(t('stock_by_branch')) ?></div>
    <canvas id="chartBranch" height="120"></canvas>
</div>
<?php endif; ?>

<?php if ($lowStockCount > 0 && has_permission('stock')): ?>
<div class="card">
    <div class="card-title"><?= e(t('low_stock_alerts')) ?></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th><?= e(t('product_name')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('current_stock')) ?></th><th><?= e(t('low_stock_threshold')) ?></th></tr></thead>
        <tbody>
        <?php foreach (array_slice(stock_low_stock_list($selectedBranch), 0, 10) as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['branch_name']) ?></td>
                <td class="text-danger"><?= (int)$row['quantity'] ?></td>
                <td><?= (int)$row['low_stock_threshold'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend['labels']) ?>,
        datasets: [{ label: '<?= e(t('today_sales')) ?>', data: <?= json_encode($trend['values']) ?>, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.1)', tension: .3, fill: true }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('chartTop'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topProducts, 'name')) ?>,
        datasets: [{ label: '<?= e(t('quantity')) ?>', data: <?= json_encode(array_map('intval', array_column($topProducts, 'qty'))) ?>, backgroundColor: '#16a34a' }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

<?php if ($stockByBranch): ?>
new Chart(document.getElementById('chartBranch'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($stockByBranch, 'name')) ?>,
        datasets: [{ label: '<?= e(t('stock_value')) ?>', data: <?= json_encode(array_map('floatval', array_column($stockByBranch, 'value'))) ?>, backgroundColor: '#d97706' }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

<?php require APP_DIR . '/includes/footer.php'; ?>
