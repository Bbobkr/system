<?php
declare(strict_types=1);

function supplier_all(string $search = ''): array
{
    $sql = 'SELECT * FROM suppliers WHERE 1=1';
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

function supplier_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function supplier_create(array $data): int
{
    $stmt = db()->prepare('INSERT INTO suppliers (name, phone, email, address, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
    $stmt->execute([$data['name'], $data['phone'] ?: null, $data['email'] ?: null, $data['address'] ?: null]);
    return (int)db()->lastInsertId();
}

function supplier_update(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE suppliers SET name=?, phone=?, email=?, address=?, is_active=? WHERE id=?');
    $stmt->execute([$data['name'], $data['phone'] ?: null, $data['email'] ?: null, $data['address'] ?: null, $data['is_active'] ? 1 : 0, $id]);
}

function supplier_delete(int $id): bool
{
    try {
        db()->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
