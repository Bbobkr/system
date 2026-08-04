<?php
declare(strict_types=1);

/** الأنواع التي تزيد المخزون مقابل التي تنقصه */
const STOCK_INCREASE_TYPES = ['in', 'transfer_in', 'purchase', 'sale_return'];
const STOCK_DECREASE_TYPES = ['out', 'transfer_out', 'sale', 'purchase_return'];

/**
 * يسجل حركة مخزون ويحدّث الكمية المخزّنة في product_stock.
 * يجب استدعاؤها ضمن معاملة (transaction) عند استخدامها من فواتير متعددة الأصناف.
 */
function stock_record_movement(
    int $productId,
    int $branchId,
    string $type,
    int $quantity,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $note = null
): void {
    $pdo = db();
    $userId = current_user()['id'] ?? null;

    $stmt = $pdo->prepare(
        'INSERT INTO stock_movements (product_id, branch_id, type, quantity, reference_type, reference_id, note, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$productId, $branchId, $type, $quantity, $referenceType, $referenceId, $note, $userId]);

    $direction = in_array($type, STOCK_INCREASE_TYPES, true) ? 1 : -1;
    $delta = $direction * $quantity;

    $upsert = $pdo->prepare(
        'INSERT INTO product_stock (product_id, branch_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );
    $upsert->execute([$productId, $branchId, $delta]);
}

function stock_get_quantity(int $productId, int $branchId): int
{
    $stmt = db()->prepare('SELECT quantity FROM product_stock WHERE product_id = ? AND branch_id = ?');
    $stmt->execute([$productId, $branchId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['quantity'] : 0;
}

/** قائمة كمية كل منتج في كل الفروع {branch_id: quantity} */
function stock_get_quantities_all_branches(int $productId): array
{
    $stmt = db()->prepare('SELECT branch_id, quantity FROM product_stock WHERE product_id = ?');
    $stmt->execute([$productId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['branch_id']] = (int)$row['quantity'];
    }
    return $result;
}

/** أصناف تحت حد التنبيه الأدنى، اختياريًا مقيّدة بفرع */
function stock_low_stock_list(?int $branchId = null): array
{
    $sql = "SELECT p.id, p.name, p.barcode, p.low_stock_threshold, ps.branch_id, ps.quantity, b.name AS branch_name
            FROM product_stock ps
            JOIN products p ON p.id = ps.product_id AND p.is_active = 1
            JOIN branches b ON b.id = ps.branch_id
            WHERE ps.quantity <= p.low_stock_threshold";
    $params = [];
    if ($branchId !== null) {
        $sql .= ' AND ps.branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' ORDER BY ps.quantity ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function stock_low_stock_count(?int $branchId = null): int
{
    return count(stock_low_stock_list($branchId));
}

/** إجمالي قيمة المخزون (بسعر التكلفة) حسب كل فرع */
function stock_value_by_branch(): array
{
    $stmt = db()->query(
        "SELECT b.id, b.name, COALESCE(SUM(ps.quantity * p.cost_price), 0) AS value
         FROM branches b
         LEFT JOIN product_stock ps ON ps.branch_id = b.id
         LEFT JOIN products p ON p.id = ps.product_id
         WHERE b.is_active = 1
         GROUP BY b.id, b.name
         ORDER BY b.name"
    );
    return $stmt->fetchAll();
}

function stock_total_value(?int $branchId = null): float
{
    $sql = 'SELECT COALESCE(SUM(ps.quantity * p.cost_price), 0) AS v FROM product_stock ps JOIN products p ON p.id = ps.product_id';
    $params = [];
    if ($branchId !== null) {
        $sql .= ' WHERE ps.branch_id = ?';
        $params[] = $branchId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetch()['v'];
}

function stock_movements_list(array $filters = [], int $limit = 100): array
{
    $sql = "SELECT sm.*, p.name AS product_name, b.name AS branch_name, u.full_name AS user_name
            FROM stock_movements sm
            JOIN products p ON p.id = sm.product_id
            JOIN branches b ON b.id = sm.branch_id
            LEFT JOIN users u ON u.id = sm.created_by
            WHERE 1=1";
    $params = [];
    if (!empty($filters['branch_id'])) {
        $sql .= ' AND sm.branch_id = ?';
        $params[] = $filters['branch_id'];
    }
    if (!empty($filters['product_id'])) {
        $sql .= ' AND sm.product_id = ?';
        $params[] = $filters['product_id'];
    }
    $sql .= ' ORDER BY sm.created_at DESC, sm.id DESC LIMIT ' . (int)$limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
