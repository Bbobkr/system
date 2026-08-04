<?php
require __DIR__ . '/../app/config/config.php';
require_permission('categories', 'view');
require APP_DIR . '/modules/category.php';

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
    $action = $_POST['_action'] ?? '';

    if ($action === 'delete') {
        require_permission('categories', 'delete');
        if (category_delete(input_int('id'))) {
            flash_success(t('delete') . ' ✓');
        } else {
            flash_error(is_rtl() ? 'لا يمكن حذف تصنيف مرتبط بمنتجات أو تصنيفات فرعية.' : 'Cannot delete: category is in use.');
        }
        redirect(app_path('products/categories.php'));
    }

    $name = input('name');
    $parentId = input_int('parent_id') ?: null;
    $editId = input_int('id');

    if ($name === '') {
        $errors[] = t('category_name') . ' *';
    } else {
        if ($editId) {
            require_permission('categories', 'edit');
            category_update($editId, $name, $parentId);
        } else {
            require_permission('categories', 'create');
            category_create($name, $parentId);
        }
        flash_success(t('save') . ' ✓');
        redirect(app_path('products/categories.php'));
    }
}

$categories = category_all();
$editing = null;
if (!empty($_GET['edit'])) {
    $editing = category_find((int)$_GET['edit']);
}

$pageTitle = t('nav_categories');
require APP_DIR . '/includes/header.php';
?>
<div class="grid grid-2">
    <?php if (has_permission('categories', $editing ? 'edit' : 'create')): ?>
    <div class="card">
        <div class="card-title"><?= $editing ? e(t('edit')) : e(t('add_new')) ?></div>
        <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label><?= e(t('category_name')) ?> *</label>
                <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('parent_category')) ?></label>
                <select name="parent_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($editing && (int)$c['id'] === (int)$editing['id']) continue; ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($editing['parent_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
            <?php if ($editing): ?><a href="<?= e(app_path('products/categories.php')) ?>" class="btn"><?= e(t('cancel')) ?></a><?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title"><?= e(t('nav_categories')) ?></div>
        <div class="table-wrap">
        <table>
            <thead><tr><th><?= e(t('category_name')) ?></th><th><?= e(t('parent_category')) ?></th><th><?= e(t('actions')) ?></th></tr></thead>
            <tbody>
            <?php if (!$categories): ?><tr><td colspan="3" class="text-muted"><?= e(t('no_records')) ?></td></tr><?php endif; ?>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['parent_name'] ?? '') ?></td>
                    <td>
                        <?php if (has_permission('categories', 'edit')): ?>
                            <a href="?edit=<?= (int)$c['id'] ?>" class="btn btn-sm"><?= e(t('edit')) ?></a>
                        <?php endif; ?>
                        <?php if (has_permission('categories', 'delete')): ?>
                        <form method="post" style="display:inline" data-confirm="<?= e(t('confirm_delete')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><?= e(t('delete')) ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require APP_DIR . '/includes/footer.php'; ?>
