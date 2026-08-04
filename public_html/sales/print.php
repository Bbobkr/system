<?php
require __DIR__ . '/../app/config/config.php';
require_permission('sales', 'view');
require APP_DIR . '/modules/sales.php';

$id = (int)($_GET['id'] ?? 0);
$invoice = $id ? sales_invoice_find($id) : null;
if (!$invoice) {
    die(t('no_records'));
}
$lang = current_lang();
$dir = is_rtl() ? 'rtl' : 'ltr';
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= $dir ?>">
<head>
<meta charset="utf-8">
<title><?= e($invoice['invoice_no']) ?></title>
<link rel="stylesheet" href="<?= e(app_path('assets/css/app.css')) ?>">
<?php if (is_rtl()): ?><link rel="stylesheet" href="<?= e(app_path('assets/css/rtl.css')) ?>"><?php endif; ?>
<style>
body { background:#fff; padding: 30px; max-width: 720px; margin: 0 auto; }
table { width:100%; }
</style>
</head>
<body>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <h2 class="mt-0"><?= e(t('app_name')) ?></h2>
            <div class="text-muted"><?= e($invoice['branch_name']) ?></div>
        </div>
        <div style="text-align:end;">
            <div><b><?= e(t('invoice')) ?> #<?= e($invoice['invoice_no']) ?></b></div>
            <div class="text-muted"><?= e(format_date($invoice['created_at'])) ?></div>
        </div>
    </div>
    <hr>
    <p><?= e(t('customer')) ?>: <b><?= e($invoice['customer_name'] ?? t('walk_in_customer')) ?></b><?= $invoice['customer_phone'] ? ' — ' . e($invoice['customer_phone']) : '' ?></p>

    <table>
        <thead><tr><th><?= e(t('product_name')) ?></th><th><?= e(t('quantity')) ?></th><th><?= e(t('unit_price')) ?></th><th><?= e(t('total')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($invoice['items'] as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= e(format_money((float)$item['unit_price'])) ?></td>
                <td><?= e(format_money((float)$item['total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="max-width:280px;margin-inline-start:auto;margin-top:16px;">
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('subtotal')) ?></span><span><?= e(format_money((float)$invoice['subtotal'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('discount')) ?></span><span>-<?= e(format_money((float)$invoice['discount_amount'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;"><span><?= e(t('tax')) ?></span><span><?= e(format_money((float)$invoice['tax_amount'])) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;border-top:1px solid #000;padding-top:6px;"><span><?= e(t('total')) ?></span><span><?= e(format_money((float)$invoice['total'])) ?></span></div>
    </div>

    <p class="no-print" style="margin-top:30px;">
        <button class="btn btn-primary" onclick="window.print()"><?= e(t('print')) ?></button>
    </p>
</body>
</html>
