<?php
require __DIR__ . '/../app/config/config.php';
require_permission('products', 'view');
require APP_DIR . '/modules/product.php';

$id = (int)($_GET['id'] ?? 0);
$product = $id ? product_find($id) : null;
$products = product_all('', true);

$pageTitle = t('nav_barcode');
require APP_DIR . '/includes/header.php';
?>
<div class="card no-print">
    <div class="card-title"><?= e(t('nav_barcode')) ?></div>
    <form method="get" class="toolbar">
        <select name="id" onchange="this.form.submit()" style="max-width:320px">
            <option value="">—</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <a href="<?= e(app_path('barcode/lookup.php')) ?>" class="btn"><?= is_rtl() ? 'بحث بمسح الباركود' : 'Scan / Lookup' ?></a>
    </form>
</div>

<?php if ($product): ?>
<div class="card" style="max-width:360px;">
    <div style="text-align:center;">
        <div style="font-weight:600;margin-bottom:8px;"><?= e($product['name']) ?></div>
        <?php if ($product['barcode']): ?>
            <svg id="barcodeSvg"></svg>
            <div class="no-print" style="margin-top:14px;">
                <button class="btn btn-primary" onclick="window.print()"><?= e(t('print')) ?></button>
            </div>
        <?php else: ?>
            <p class="text-muted"><?= is_rtl() ? 'لا يوجد باركود لهذا المنتج. أضف باركود من صفحة تعديل المنتج.' : 'This product has no barcode. Add one from the edit page.' ?></p>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
JsBarcode('#barcodeSvg', <?= json_encode($product['barcode']) ?>, { format: 'CODE128', displayValue: true, height: 60 });
</script>
<?php endif; ?>
<?php require APP_DIR . '/includes/footer.php'; ?>
