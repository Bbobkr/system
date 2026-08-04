<?php
require __DIR__ . '/../../app/config/config.php';
require_permission('stock', 'create');
require APP_DIR . '/modules/transfer.php';
require APP_DIR . '/modules/product.php';
require APP_DIR . '/modules/branch.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $fromBranch = input_int('from_branch_id');
    $toBranch = input_int('to_branch_id');
    $note = input('note');
    $items = [];
    foreach ($_POST['items'] ?? [] as $row) {
        $pid = (int)($row['product_id'] ?? 0);
        $qty = (int)($row['quantity'] ?? 0);
        if ($pid && $qty > 0) {
            $items[] = ['product_id' => $pid, 'quantity' => $qty];
        }
    }

    if (!$fromBranch || !$toBranch || $fromBranch === $toBranch) {
        $errors[] = is_rtl() ? 'اختر فرعين مختلفين.' : 'Select two different branches.';
    } elseif (!$items) {
        $errors[] = is_rtl() ? 'أضف صنفًا واحدًا على الأقل.' : 'Add at least one item.';
    } else {
        try {
            stock_transfer_create($fromBranch, $toBranch, $items, $note);
            flash_success(t('save') . ' ✓');
            redirect(app_path('stock/movements.php'));
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$products = product_all('', true);
$branches = branch_all(true);
$transfers = stock_transfers_list();

$pageTitle = t('stock_transfer');
require APP_DIR . '/includes/header.php';
?>
<div class="card">
    <div class="card-title"><?= e(t('stock_transfer')) ?></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label><?= e(t('from_branch')) ?></label>
                <select name="from_branch_id" required>
                    <option value="">—</option>
                    <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('to_branch')) ?></label>
                <select name="to_branch_id" required>
                    <option value="">—</option>
                    <?php foreach ($branches as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= e(t('note')) ?></label>
                <input type="text" name="note">
            </div>
        </div>

        <table class="invoice-items-table">
            <thead><tr><th><?= e(t('product_name')) ?></th><th style="width:120px"><?= e(t('quantity')) ?></th><th></th></tr></thead>
            <tbody id="rows">
                <tr>
                    <td>
                        <select name="items[0][product_id]" required>
                            <option value="">—</option>
                            <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[0][quantity]" min="1" step="1" required></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">×</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-sm" id="addRow"><?= e(t('add_item')) ?></button>
        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title"><?= e(t('stock_transfer')) ?> — <?= is_rtl() ? 'السجل' : 'History' ?></div>
    <div class="table-wrap">
    <table>
        <thead><tr><th><?= e(t('date')) ?></th><th><?= e(t('from_branch')) ?></th><th><?= e(t('to_branch')) ?></th><th><?= is_rtl() ? 'عدد الأصناف' : 'Items' ?></th><th><?= is_rtl() ? 'بواسطة' : 'By' ?></th></tr></thead>
        <tbody>
        <?php if (!$transfers): ?><tr><td colspan="5" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
        <?php foreach ($transfers as $t): ?>
            <tr>
                <td><?= e(format_date($t['created_at'])) ?></td>
                <td><?= e($t['from_branch_name']) ?></td>
                <td><?= e($t['to_branch_name']) ?></td>
                <td><?= (int)$t['item_count'] ?></td>
                <td><?= e($t['user_name'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
(function () {
    var i = 1;
    var productOptions = document.querySelector('#rows select').innerHTML;
    document.getElementById('addRow').addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="items[' + i + '][product_id]" required>' + productOptions + '</select></td>' +
            '<td><input type="number" name="items[' + i + '][quantity]" min="1" step="1" required></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'tr\').remove()">×</button></td>';
        document.getElementById('rows').appendChild(tr);
        i++;
    });
})();
</script>
<?php require APP_DIR . '/includes/footer.php'; ?>
