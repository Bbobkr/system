<?php
require __DIR__ . '/../app/config/config.php';
require_permission('purchases', 'create');
require APP_DIR . '/modules/purchases.php';
require APP_DIR . '/modules/product.php';
require APP_DIR . '/modules/branch.php';
require APP_DIR . '/modules/supplier.php';

$restrictedBranch = user_branch_id();
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $header = [
        'supplier_id' => input_int('supplier_id'),
        'branch_id' => $restrictedBranch ?? input_int('branch_id'),
        'tax_percent' => input_float('tax_percent'),
        'discount_amount' => input_float('discount_amount'),
        'paid_amount' => input_float('paid_amount'),
        'note' => input('note'),
    ];
    $items = [];
    foreach ($_POST['items'] ?? [] as $row) {
        $pid = (int)($row['product_id'] ?? 0);
        $qty = (int)($row['quantity'] ?? 0);
        $cost = (float)($row['unit_price'] ?? 0);
        if ($pid && $qty > 0) {
            $items[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_cost' => $cost];
        }
    }

    if (!$header['supplier_id'] || !$header['branch_id']) {
        $errors[] = t('supplier') . ' / ' . t('branch') . ' *';
    } elseif (!$items) {
        $errors[] = is_rtl() ? 'أضف صنفًا واحدًا على الأقل.' : 'Add at least one item.';
    } else {
        try {
            $invoiceId = purchase_invoice_create($header, $items);
            flash_success(t('save') . ' ✓');
            redirect(app_path('purchases/view.php?id=' . $invoiceId));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$products = product_all('', true);
$productsJson = array_map(fn($p) => [
    'id' => (int)$p['id'], 'name' => $p['name'], 'barcode' => $p['barcode'], 'sku' => $p['sku'], 'price' => (float)$p['cost_price'],
], $products);
$branches = branch_all(true);
$suppliers = supplier_all();

$pageTitle = t('new_purchase');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
    <div class="card-title"><?= e(t('new_purchase')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" id="invoiceForm">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('supplier')) ?> *</label>
                <select name="supplier_id" required>
                    <option value="">—</option>
                    <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php if ($restrictedBranch === null): ?>
            <div class="form-group">
                <label><?= e(t('branch')) ?> *</label>
                <select name="branch_id" required>
                    <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label><?= e(t('scan_or_search')) ?></label>
            <input type="text" id="searchInput" autocomplete="off" placeholder="<?= e(t('scan_or_search')) ?>">
        </div>

        <div class="table-wrap">
        <table class="invoice-items-table">
            <thead><tr><th><?= e(t('product_name')) ?></th><th style="width:110px"><?= e(t('quantity')) ?></th><th style="width:130px"><?= e(t('unit_cost')) ?></th><th style="width:100px"><?= e(t('total')) ?></th><th></th></tr></thead>
            <tbody id="itemsBody"></tbody>
        </table>
        </div>

        <div class="grid grid-2" style="margin-top:16px;">
            <div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?= e(t('tax')) ?> %</label>
                        <input type="number" id="taxPercent" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label><?= e(t('discount')) ?></label>
                        <input type="number" id="discount" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label><?= e(t('paid_amount')) ?></label>
                        <input type="number" name="paid_amount" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= e(t('note')) ?></label>
                    <input type="text" name="note">
                </div>
            </div>
            <div class="card" style="background:#fafbfc;">
                <div style="display:flex;justify-content:space-between;"><span><?= e(t('subtotal')) ?></span><b id="subtotalOut">0.00</b></div>
                <div style="display:flex;justify-content:space-between;"><span><?= e(t('tax')) ?></span><b id="taxOut">0.00</b></div>
                <div style="display:flex;justify-content:space-between;font-size:18px;margin-top:8px;"><span><?= e(t('total')) ?></span><b id="totalOut">0.00</b></div>
                <input type="hidden" name="total_display" id="totalHidden">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:16px;"><?= e(t('save_invoice')) ?></button>
        <a href="<?= e(app_path('purchases/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>

<script>
initInvoiceForm({
    products: <?= json_encode($productsJson, JSON_UNESCAPED_UNICODE) ?>,
    searchInputId: 'searchInput',
    tableBodyId: 'itemsBody',
    subtotalId: 'subtotalOut',
    taxPercentId: 'taxPercent',
    taxAmountId: 'taxOut',
    discountId: 'discount',
    totalId: 'totalOut',
    totalHiddenId: 'totalHidden',
    rowNamePrefix: 'items',
    emptyItemsMessage: '<?= is_rtl() ? 'أضف صنفًا واحدًا على الأقل' : 'Add at least one item' ?>'
});
document.getElementById('searchInput').focus();
</script>
<?php require APP_DIR . '/includes/footer.php'; ?>
