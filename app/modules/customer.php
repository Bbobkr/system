<?php
declare(strict_types=1);

function customer_all(string $search = ''): array
{
    $sql = 'SELECT * FROM customers WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (name LIKE ? OR phone LIKE ?)';
        $params = ['%' . $search . '%', '%' . $search . '%'];
    }
    $sql .= ' ORDER BY name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function customer_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function customer_create(array $data): int
{
    $stmt = db()->prepare('INSERT INTO customers (name, phone, email, address, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
    $stmt->execute([$data['name'], $data['phone'] ?: null, $data['email'] ?: null, $data['address'] ?: null]);
    return (int)db()->lastInsertId();
}

function customer_update(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE customers SET name=?, phone=?, email=?, address=?, is_active=? WHERE id=?');
    $stmt->execute([$data['name'], $data['phone'] ?: null, $data['email'] ?: null, $data['address'] ?: null, $data['is_active'] ? 1 : 0, $id]);
}

function customer_delete(int $id): bool
{
    try {
        db()->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
