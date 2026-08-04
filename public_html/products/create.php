<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('products', 'create');
require APP_DIR . '/modules/product.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $data = [
        'name' => input('name'),
        'sku' => input('sku'),
        'barcode' => input('barcode'),
        'category_id' => input_int('category_id') ?: null,
        'unit' => input('unit', 'قطعة'),
        'cost_price' => input_float('cost_price'),
        'sale_price' => input_float('sale_price'),
        'low_stock_threshold' => input_int('low_stock_threshold', DEFAULT_LOW_STOCK_THRESHOLD),
    ];

    if ($data['name'] === '') {
        $errors[] = t('product_name') . ' *';
    }

    if (!$errors) {
        try {
            $data['image_path'] = handle_product_image_upload('image');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $id = product_create($data);
            log_activity('create', 'products', $id, $data['name']);
            flash_success(t('save') . ' ✓');
            redirect(app_path('products/index.php'));
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'Duplicate')
                ? (is_rtl() ? 'رمز الصنف أو الباركود مستخدم من قبل.' : 'SKU or barcode already in use.')
                : $e->getMessage();
        }
    }
}

$categories = product_categories_tree();
$pageTitle = t('add_product');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:720px;">
    <div class="card-title"><?= e(t('add_product')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <label><?= e(t('product_name')) ?> *</label>
            <input type="text" name="name" required value="<?= old('name') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('sku')) ?></label>
                <input type="text" name="sku" value="<?= old('sku') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('barcode')) ?></label>
                <input type="text" name="barcode" id="barcodeInput" value="<?= old('barcode') ?>">
            </div>
        </div>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('barcodeInput').value = Date.now().toString()"><?= is_rtl() ? 'توليد باركود تلقائي' : 'Auto-generate barcode' ?></button>
        <div class="form-row" style="margin-top:14px;">
            <div class="form-group">
                <label><?= e(t('category')) ?></label>
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('unit')) ?></label>
                <input type="text" name="unit" value="<?= old('unit', 'قطعة') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('cost_price')) ?></label>
                <input type="number" step="0.01" min="0" name="cost_price" value="<?= old('cost_price', '0') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('sale_price')) ?></label>
                <input type="number" step="0.01" min="0" name="sale_price" value="<?= old('sale_price', '0') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('low_stock_threshold')) ?></label>
                <input type="number" step="1" min="0" name="low_stock_threshold" value="<?= old('low_stock_threshold', (string)DEFAULT_LOW_STOCK_THRESHOLD) ?>">
            </div>
        </div>
        <div class="form-group">
            <label><?= e(t('image')) ?></label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
        </div>
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="<?= e(app_path('products/index.php')) ?>" class="btn"><?= e(t('cancel')) ?></a>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
