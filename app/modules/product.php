<?php
declare(strict_types=1);

function product_all(string $search = '', bool $activeOnly = false): array
{
    $sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (p.name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?)';
        $like = '%' . $search . '%';
        $params = [$like, $like, $like];
    }
    if ($activeOnly) {
        $sql .= ' AND p.is_active = 1';
    }
    $sql .= ' ORDER BY p.name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function product_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_find_by_barcode(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE barcode = ? OR sku = ? LIMIT 1');
    $stmt->execute([$code, $code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO products (sku, barcode, name, category_id, unit, cost_price, sale_price, image_path, low_stock_threshold, is_active, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())'
    );
    $stmt->execute([
        $data['sku'] ?: null,
        $data['barcode'] ?: null,
        $data['name'],
        $data['category_id'] ?: null,
        $data['unit'],
        $data['cost_price'],
        $data['sale_price'],
        $data['image_path'] ?: null,
        $data['low_stock_threshold'],
    ]);
    $productId = (int)db()->lastInsertId();

    // تهيئة صف كمية صفرية في كل فرع نشط ليظهر المنتج في تقارير المخزون فورًا
    $branches = db()->query('SELECT id FROM branches WHERE is_active = 1')->fetchAll();
    $init = db()->prepare('INSERT IGNORE INTO product_stock (product_id, branch_id, quantity) VALUES (?, ?, 0)');
    foreach ($branches as $b) {
        $init->execute([$productId, $b['id']]);
    }

    return $productId;
}

function product_update(int $id, array $data): void
{
    $sql = 'UPDATE products SET sku=?, barcode=?, name=?, category_id=?, unit=?, cost_price=?, sale_price=?, low_stock_threshold=?, is_active=?';
    $params = [
        $data['sku'] ?: null,
        $data['barcode'] ?: null,
        $data['name'],
        $data['category_id'] ?: null,
        $data['unit'],
        $data['cost_price'],
        $data['sale_price'],
        $data['low_stock_threshold'],
        $data['is_active'] ? 1 : 0,
    ];
    if (array_key_exists('image_path', $data) && $data['image_path'] !== null) {
        $sql .= ', image_path=?';
        $params[] = $data['image_path'];
    }
    $sql .= ' WHERE id = ?';
    $params[] = $id;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

/** يحاول حذف المنتج فعليًا؛ يعيد false إذا كان مستخدمًا في حركات/فواتير (قيد مفتاحي) */
function product_delete(int $id): bool
{
    try {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function product_set_active(int $id, bool $active): void
{
    db()->prepare('UPDATE products SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
}

function product_categories_tree(): array
{
    return db()->query('SELECT * FROM categories ORDER BY parent_id IS NULL DESC, name')->fetchAll();
}
