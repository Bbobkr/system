<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('sales', 'view');
require APP_DIR . '/modules/sales.php';

$id = (int)($_GET['id'] ?? 0);
$invoice = $id ? sales_invoice_find($id) : null;
if (!$invoice) {
    flash_error(t('no_records'));
    redirect(app_path('sales/index.php'));
}
$returned = sales_returned_quantities($id);

$statusBadge = ['paid' => 'badge-success', 'partial' => 'badge-warning', 'unpaid' => 'badge-danger'];
$pageTitle = t('invoice') . ' ' . $invoice['invoice_no'];
require APP_DIR . '/includes/header.php';
?>
<div class="toolbar">
    <div>
        <a href="<?= e(app_path('sales/print.php?id=' . $id)) ?>" class="btn"><?= e(t('print')) ?></a>
        <?php if (has_permission('sales', 'create')): ?>
            <a href="<?= e(app_path('sales/returns.php?invoice_id=' . $id)) ?>" class="btn"><?= e(t('new_return')) ?></a>
        <?php endif; ?>
    </div>
    <a href="<?= e(app_path('sales/index.php')) ?>" class="btn"><?= e(t('back')) ?></a>
</div>

<div class="card">
    <div class="grid grid-3" style="margin-bottom:10px;">
        <div><span class="text-muted"><?= e(t('invoice_no')) ?>:</span> <b><?= e($invoice['invoice_no']) ?></b></div>
        <div><span class="text-muted"><?= e(t('date')) ?>:</span> <?= e(format_date($invoice['created_at'])) ?></div>
        <div><span class="text-muted"><?= e(t('branch')) ?>:</span> <?= e($invoice['branch_name']) ?></div>
        <div><span class="text-muted"><?= e(t('customer')) ?>:</span> <?= e($invoice['customer_name'] ?? t('walk_in_customer')) ?></div>
        <div><span class="text-muted"><?= e(t('payment_status')) ?>:</span> <span class="badge <?= $statusBadge[$invoice['payment_status']] ?? '' ?>"><?= e(t($invoice['payment_status'])) ?></span></div>
        <div><span class="text-muted"><?= is_rtl() ? 'بواسطة' : 'By' ?>:</span> <?= e($invoice['user_name'] ?? '') ?></div>
    </div>

    <div class="table-wrap">
    <table>
        <thead><tr><th><?= e(t('product_name')) ?></th><th><?= e(t('quantity')) ?></th><th><?= e(t('unit_price')) ?></th><th><?= e(t('total')) ?></th><th><?= is_rtl() ? 'مرتجع' : 'Returned' ?></th></tr></thead>
        <tbody>
        <?php foreach ($invoice['items'] as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= e(format_money((float)$item['unit_price'])) ?></td>
                <td><?= e(format_money((float)$item['total'])) ?></td>
                <td><?= (int)($returned[$item['product_id']] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div style="max-width:280px;margin-inline-start:auto;margin-top:16px;">
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('subtotal')) ?></span><span><?= e(format_money((float)$invoice['subtotal'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('discount')) ?></span><span>-<?= e(format_money((float)$invoice['discount_amount'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('tax')) ?> (<?= e((string)$invoice['tax_percent']) ?>%)</span><span><?= e(format_money((float)$invoice['tax_amount'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;border-top:1px solid var(--color-border);padding-top:6px;"><span><?= e(t('total')) ?></span><span><?= e(format_money((float)$invoice['total'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('paid_amount')) ?></span><span><?= e(format_money((float)$invoice['paid_amount'])) ?></span></div>
    </div>
    <?php if ($invoice['note']): ?><p class="text-muted"><?= e(t('note')) ?>: <?= e($invoice['note']) ?></p><?php endif; ?>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
