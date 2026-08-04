<?php
declare(strict_types=1);

function category_all(): array
{
    return db()->query(
        'SELECT c.*, p.name AS parent_name FROM categories c LEFT JOIN categories p ON p.id = c.parent_id ORDER BY c.name'
    )->fetchAll();
}

function category_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_create(string $name, ?int $parentId): int
{
    $stmt = db()->prepare('INSERT INTO categories (name, parent_id, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$name, $parentId ?: null]);
    return (int)db()->lastInsertId();
}

function category_update(int $id, string $name, ?int $parentId): void
{
    $stmt = db()->prepare('UPDATE categories SET name = ?, parent_id = ? WHERE id = ?');
    $stmt->execute([$name, $parentId ?: null, $id]);
}

function category_delete(int $id): bool
{
    try {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
