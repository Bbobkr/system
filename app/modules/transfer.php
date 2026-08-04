<?php
declare(strict_types=1);

/**
 * ينشئ تحويل مخزون مباشر بين فرعين (خطوة واحدة، بدون تأكيد استلام).
 * $items: [['product_id'=>int, 'quantity'=>int], ...]
 */
function stock_transfer_create(int $fromBranchId, int $toBranchId, array $items, string $note = ''): int
{
    if ($fromBranchId === $toBranchId) {
        throw new RuntimeException('from_branch and to_branch must differ');
    }
    if (!$items) {
        throw new RuntimeException(t('insufficient_stock'));
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $available = stock_get_quantity((int)$item['product_id'], $fromBranchId);
            if ($available < $item['quantity']) {
                throw new RuntimeException(t('insufficient_stock'));
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO stock_transfers (from_branch_id, to_branch_id, note, created_by, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$fromBranchId, $toBranchId, $note ?: null, current_user()['id'] ?? null]);
        $transferId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO stock_transfer_items (transfer_id, product_id, quantity) VALUES (?, ?, ?)');
        foreach ($items as $item) {
            $itemStmt->execute([$transferId, $item['product_id'], $item['quantity']]);

            stock_record_movement((int)$item['product_id'], $fromBranchId, 'transfer_out', (int)$item['quantity'], 'stock_transfer', $transferId);
            stock_record_movement((int)$item['product_id'], $toBranchId, 'transfer_in', (int)$item['quantity'], 'stock_transfer', $transferId);
        }

        $pdo->commit();
        log_activity('create', 'stock', $transferId, 'transfer');
        return $transferId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function stock_transfers_list(): array
{
    return db()->query(
        "SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.full_name AS user_name,
                (SELECT COUNT(*) FROM stock_transfer_items sti WHERE sti.transfer_id = st.id) AS item_count
         FROM stock_transfers st
         JOIN branches fb ON fb.id = st.from_branch_id
         JOIN branches tb ON tb.id = st.to_branch_id
         LEFT JOIN users u ON u.id = st.created_by
         ORDER BY st.created_at DESC"
    )->fetchAll();
}
