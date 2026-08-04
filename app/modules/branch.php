<?php
declare(strict_types=1);

function branch_all(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM branches';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name';
    return db()->query($sql)->fetchAll();
}

function branch_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM branches WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function branch_create(array $data): int
{
    $stmt = db()->prepare('INSERT INTO branches (name, address, phone, is_active, created_at) VALUES (?, ?, ?, 1, NOW())');
    $stmt->execute([$data['name'], $data['address'] ?: null, $data['phone'] ?: null]);
    $branchId = (int)db()->lastInsertId();

    // تهيئة كمية صفرية لكل المنتجات النشطة في الفرع الجديد
    $products = db()->query('SELECT id FROM products WHERE is_active = 1')->fetchAll();
    $init = db()->prepare('INSERT IGNORE INTO product_stock (product_id, branch_id, quantity) VALUES (?, ?, 0)');
    foreach ($products as $p) {
        $init->execute([$p['id'], $branchId]);
    }

    return $branchId;
}

function branch_update(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE branches SET name=?, address=?, phone=?, is_active=? WHERE id=?');
    $stmt->execute([$data['name'], $data['address'] ?: null, $data['phone'] ?: null, $data['is_active'] ? 1 : 0, $id]);
}

function branch_delete(int $id): bool
{
    try {
        db()->prepare('DELETE FROM branches WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
