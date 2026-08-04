<?php
require __DIR__ . '/../app/config/config.php';
require_permission('products', 'view');

$pageTitle = is_rtl() ? 'بحث بالباركود' : 'Barcode Lookup';
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:480px;">
    <div class="card-title"><?= e($pageTitle) ?></div>
    <div class="form-group">
        <input type="text" id="codeInput" autocomplete="off" placeholder="<?= e(t('scan_or_search')) ?>" autofocus>
    </div>
    <div id="result"></div>
</div>

<script>
var input = document.getElementById('codeInput');
var result = document.getElementById('result');
input.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var code = input.value.trim();
    if (!code) return;
    fetch('<?= e(app_path('api/barcode-lookup.php')) ?>?code=' + encodeURIComponent(code))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.found) {
                result.innerHTML = '<div class="alert alert-error"><?= is_rtl() ? 'لم يتم العثور على منتج بهذا الباركود' : 'No product found for this barcode' ?></div>';
                return;
            }
            var stockHtml = '';
            Object.keys(data.stock).forEach(function (k) {
                var s = data.stock[k];
                if (typeof s === 'object') {
                    stockHtml += '<div>' + s.branch_name + ': <b>' + s.quantity + '</b></div>';
                } else {
                    stockHtml += '<div><?= e(t('current_stock')) ?>: <b>' + s + '</b></div>';
                }
            });
            result.innerHTML =
                '<div class="card" style="background:#fafbfc;">' +
                '<div style="font-weight:600;font-size:16px;">' + data.name + '</div>' +
                '<div class="text-muted">' + (data.sku || '') + ' · ' + (data.barcode || '') + '</div>' +
                '<div style="margin-top:8px;"><?= e(t('sale_price')) ?>: <b>' + data.sale_price.toFixed(2) + '</b></div>' +
                stockHtml +
                '<div style="margin-top:10px;"><a class="btn btn-sm" href="<?= e(app_path('products/edit.php')) ?>?id=' + data.id + '"><?= e(t('edit')) ?></a>' +
                ' <a class="btn btn-sm" href="<?= e(app_path('barcode/generate.php')) ?>?id=' + data.id + '"><?= e(t('nav_barcode')) ?></a></div>' +
                '</div>';
            input.value = '';
        });
});
</script>
<?php require APP_DIR . '/includes/footer.php'; ?>
