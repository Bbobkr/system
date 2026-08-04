<?php
require __DIR__ . '/../app/config/config.php';
require_permission('settings', 'view');

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_permission('settings', 'edit');
    csrf_verify();
    setting_set('company_name', input('company_name'));
    setting_set('currency', input('currency', DEFAULT_CURRENCY));
    setting_set('default_language', in_array($_POST['default_language'] ?? '', ['ar', 'en'], true) ? $_POST['default_language'] : 'ar');
    setting_set('invoice_prefix_sale', input('invoice_prefix_sale', INVOICE_PREFIX_SALE));
    setting_set('invoice_prefix_purchase', input('invoice_prefix_purchase', INVOICE_PREFIX_PURCHASE));
    setting_set('default_low_stock_threshold', (string)input_int('default_low_stock_threshold', DEFAULT_LOW_STOCK_THRESHOLD));

    flash_success(t('save') . ' ✓');
    redirect(app_path('settings/index.php'));
}

$pageTitle = t('nav_settings');
require APP_DIR . '/includes/header.php';
?>
<div class="card" style="max-width:600px;">
    <div class="card-title"><?= e(t('invoice_settings')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label><?= e(t('company_name')) ?></label>
            <input type="text" name="company_name" value="<?= e(setting_get('company_name', 'شركتي')) ?>" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('currency')) ?></label>
                <input type="text" name="currency" value="<?= e(setting_get('currency', DEFAULT_CURRENCY)) ?>" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
            </div>
            <div class="form-group">
                <label><?= e(t('language')) ?></label>
                <select name="default_language" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
                    <option value="ar" <?= setting_get('default_language', 'ar') === 'ar' ? 'selected' : '' ?>><?= e(t('arabic')) ?></option>
                    <option value="en" <?= setting_get('default_language', 'ar') === 'en' ? 'selected' : '' ?>><?= e(t('english')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><?= is_rtl() ? 'بادئة فاتورة البيع' : 'Sale Invoice Prefix' ?></label>
                <input type="text" name="invoice_prefix_sale" value="<?= e(setting_get('invoice_prefix_sale', INVOICE_PREFIX_SALE)) ?>" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
            </div>
            <div class="form-group">
                <label><?= is_rtl() ? 'بادئة فاتورة الشراء' : 'Purchase Invoice Prefix' ?></label>
                <input type="text" name="invoice_prefix_purchase" value="<?= e(setting_get('invoice_prefix_purchase', INVOICE_PREFIX_PURCHASE)) ?>" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
            </div>
            <div class="form-group">
                <label><?= e(t('low_stock_threshold')) ?></label>
                <input type="number" name="default_low_stock_threshold" min="0" value="<?= e(setting_get('default_low_stock_threshold', (string)DEFAULT_LOW_STOCK_THRESHOLD)) ?>" <?= has_permission('settings', 'edit') ? '' : 'disabled' ?>>
            </div>
        </div>
        <?php if (has_permission('settings', 'edit')): ?>
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <?php endif; ?>
    </form>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
