<?php
require __DIR__ . '/../app/config/config.php';
require_permission('sales', 'create');
require APP_DIR . '/modules/sales.php';

$errors = [];
$invoiceId = (int)($_GET['invoice_id'] ?? $_POST['invoice_id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $note = input('note');
    $items = [];
    foreach ($_POST['items'] ?? [] as $row) {
        $qty = (int)($row['quantity'] ?? 0);
        if ($qty > 0) {
            $items[] = ['product_id' => (int)$row['product_id'], 'quantity' => $qty, 'unit_price' => (float)$row['unit_price']];
        }
    }
    if (!$items) {
        $errors[] = is_rtl() ? 'أدخل كمية إرجاع لصنف واحد على الأقل.' : 'Enter a return quantity for at least one item.';
    } else {
        try {
            sales_return_create($invoiceId, $items, $note);
            flash_success(t('save') . ' ✓');
            redirect(app_path('sales/view.php?id=' . $invoiceId));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$invoice = $invoiceId ? sales_invoice_find($invoiceId) : null;
$returnedQty = $invoiceId ? sales_returned_quantities($invoiceId) : [];
$returns = sales_returns_list();

$pageTitle = t('sales_returns');
require APP_DIR . '/includes/header.php';
?>
<?php if ($invoice): ?>
<div class="card">
    <div class="card-title"><?= e(t('new_return')) ?> — <?= e($invoice['invoice_no']) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="invoice_id" value="<?= (int)$invoiceId ?>">
        <div class="table-wrap">
        <table>
            <thead><tr><th><?= e(t('product_name')) ?></th><th><?= is_rtl() ? 'الكمية المباعة' : 'Sold Qty' ?></th><th><?= is_rtl() ? 'أُرجع سابقًا' : 'Already Returned' ?></th><th style="width:120px"><?= is_rtl() ? 'كمية الإرجاع' : 'Return Qty' ?></th></tr></thead>
            <tbody>
            <?php foreach ($invoice['items'] as $item):
                $already = $returnedQty[$item['product_id']] ?? 0;
                $remaining = (int)$item['quantity'] - $already;
                if ($remaining <= 0) continue;
            ?>
                <tr>
                    <td><?= e($item['product_name']) ?>
                        <input type="hidden" name="items[<?= $item['product_id'] ?>][product_id]" value="<?= (int)$item['product_id'] ?>">
                        <input type="hidden" name="items[<?= $item['product_id'] ?>][unit_price]" value="<?= e((string)$item['unit_price']) ?>">
                    </td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= (int)$already ?></td>
                    <td><input type="number" name="items[<?= $item['product_id'] ?>][quantity]" min="0" max="<?= $remaining ?>" value="0"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label><?= e(t('note')) ?></label>
            <input type="text" name="note">
        </div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('sales/view.php?id=' . $invoiceId)) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title"><?= e(t('sales_returns')) ?></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th><?= is_rtl() ? 'رقم المرتجع' : 'Return No' ?></th><th><?= e(t('invoice_no')) ?></th><th><?= e(t('date')) ?></th><th><?= e(t('branch')) ?></th><th><?= e(t('total')) ?></th></tr></thead>
        <tbody>
        <?php if (!$returns): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
        <?php foreach ($returns as $r): ?>
            <tr>
                <td><?= e($r['return_no']) ?></td>
                <td><a href="<?= e(app_path('sales/view.php?id=' . $r['invoice_id'])) ?>"><?= e($r['invoice_no']) ?></a></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
                <td><?= e($r['branch_name']) ?></td>
                <td><?= e(format_money((float)$r['total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
