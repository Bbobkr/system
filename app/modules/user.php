<?php
declare(strict_types=1);

function roles_all(): array
{
    return db()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
}

function role_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM roles WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function users_all(): array
{
    return db()->query(
        "SELECT u.*, r.name_ar AS role_name_ar, r.name AS role_name, b.name AS branch_name
         FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN branches b ON b.id = u.branch_id
         ORDER BY u.full_name"
    )->fetchAll();
}

function user_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function user_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (username, password_hash, full_name, email, role_id, branch_id, is_active, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['full_name'],
        $data['email'] ?: null,
        $data['role_id'],
        $data['branch_id'] ?: null,
        $data['is_active'] ? 1 : 0,
    ]);
    return (int)db()->lastInsertId();
}

function user_update(int $id, array $data): void
{
    $sql = 'UPDATE users SET full_name=?, email=?, role_id=?, branch_id=?, is_active=?';
    $params = [$data['full_name'], $data['email'] ?: null, $data['role_id'], $data['branch_id'] ?: null, $data['is_active'] ? 1 : 0];

    if (!empty($data['password'])) {
        $sql .= ', password_hash=?';
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    $sql .= ' WHERE id=?';
    $params[] = $id;

    db()->prepare($sql)->execute($params);
}

function user_delete(int $id): bool
{
    if ($id === (current_user()['id'] ?? 0)) {
        return false; // لا يمكن للمستخدم حذف نفسه
    }
    try {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

const PERMISSION_MODULES = ['products', 'categories', 'branches', 'stock', 'customers', 'suppliers', 'sales', 'purchases', 'users', 'reports', 'settings'];

function role_permissions(int $roleId): array
{
    $stmt = db()->prepare('SELECT * FROM permissions WHERE role_id = ?');
    $stmt->execute([$roleId]);
    $perms = [];
    foreach ($stmt->fetchAll() as $row) {
        $perms[$row['module']] = $row;
    }
    return $perms;
}

function role_permissions_save(int $roleId, array $matrix): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_create=VALUES(can_create), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete)'
    );
    foreach (PERMISSION_MODULES as $module) {
        $m = $matrix[$module] ?? [];
        $stmt->execute([
            $roleId,
            $module,
            !empty($m['view']) ? 1 : 0,
            !empty($m['create']) ? 1 : 0,
            !empty($m['edit']) ? 1 : 0,
            !empty($m['delete']) ? 1 : 0,
        ]);
    }
}
