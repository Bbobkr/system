<?php
declare(strict_types=1);

/**
 * ينشئ فاتورة شراء كاملة مع أصنافها ضمن معاملة واحدة، ويضيف للمخزون تلقائيًا.
 * $items: [['product_id'=>int, 'quantity'=>int, 'unit_cost'=>float], ...]
 */
function purchase_invoice_create(array $header, array $items): int
{
    if (!$items) {
        throw new RuntimeException(t('insufficient_stock'));
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_cost'];
        }

        $discount = (float)$header['discount_amount'];
        $taxPercent = (float)$header['tax_percent'];
        $taxAmount = round(($subtotal - $discount) * $taxPercent / 100, 2);
        $total = round($subtotal - $discount + $taxAmount, 2);
        $paid = (float)$header['paid_amount'];
        $paymentStatus = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $invoiceNo = generate_invoice_number(setting_get('invoice_prefix_purchase', INVOICE_PREFIX_PURCHASE), 'purchase_invoices');

        $stmt = $pdo->prepare(
            'INSERT INTO purchase_invoices (invoice_no, supplier_id, branch_id, user_id, subtotal, tax_percent, tax_amount, discount_amount, total, paid_amount, payment_status, status, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "completed", ?, NOW())'
        );
        $stmt->execute([
            $invoiceNo,
            $header['supplier_id'],
            $header['branch_id'],
            current_user()['id'] ?? null,
            $subtotal,
            $taxPercent,
            $taxAmount,
            $discount,
            $total,
            $paid,
            $paymentStatus,
            $header['note'] ?: null,
        ]);
        $invoiceId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO purchase_invoice_items (invoice_id, product_id, quantity, unit_cost, total) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $itemStmt->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['unit_cost'], $lineTotal]);
            stock_record_movement(
                (int)$item['product_id'],
                (int)$header['branch_id'],
                'purchase',
                (int)$item['quantity'],
                'purchase_invoice',
                $invoiceId
            );

            // تحديث سعر تكلفة المنتج تلقائيًا على آخر سعر شراء
            db()->prepare('UPDATE products SET cost_price = ? WHERE id = ?')->execute([$item['unit_cost'], $item['product_id']]);
        }

        $pdo->commit();
        log_activity('create', 'purchases', $invoiceId, $invoiceNo);
        return $invoiceId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function purchase_invoices_list(array $filters = []): array
{
    $sql = "SELECT pi.*, s.name AS supplier_name, b.name AS branch_name, u.full_name AS user_name
            FROM purchase_invoices pi
            JOIN suppliers s ON s.id = pi.supplier_id
            JOIN branches b ON b.id = pi.branch_id
            LEFT JOIN users u ON u.id = pi.user_id
            WHERE 1=1";
    $params = [];
    if (!empty($filters['branch_id'])) {
        $sql .= ' AND pi.branch_id = ?';
        $params[] = $filters['branch_id'];
    }
    if (!empty($filters['search'])) {
        $sql .= ' AND (pi.invoice_no LIKE ? OR s.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY pi.created_at DESC, pi.id DESC LIMIT 500';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function purchase_invoice_find(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT pi.*, s.name AS supplier_name, s.phone AS supplier_phone, b.name AS branch_name, u.full_name AS user_name
         FROM purchase_invoices pi
         JOIN suppliers s ON s.id = pi.supplier_id
         JOIN branches b ON b.id = pi.branch_id
         LEFT JOIN users u ON u.id = pi.user_id
         WHERE pi.id = ?"
    );
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    if (!$invoice) {
        return null;
    }
    $itemsStmt = db()->prepare(
        'SELECT pii.*, p.name AS product_name, p.unit FROM purchase_invoice_items pii JOIN products p ON p.id = pii.product_id WHERE invoice_id = ?'
    );
    $itemsStmt->execute([$id]);
    $invoice['items'] = $itemsStmt->fetchAll();
    return $invoice;
}

/** إجمالي ما تم إرجاعه سابقًا لكل صنف في فاتورة شراء معينة */
function purchase_returned_quantities(int $invoiceId): array
{
    $stmt = db()->prepare(
        'SELECT pri.product_id, SUM(pri.quantity) AS qty
         FROM purchase_return_items pri JOIN purchase_returns pr ON pr.id = pri.return_id
         WHERE pr.invoice_id = ? GROUP BY pri.product_id'
    );
    $stmt->execute([$invoiceId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['product_id']] = (int)$row['qty'];
    }
    return $result;
}

/**
 * ينشئ مرتجع شراء: ينقص الكمية من المخزون (تُعاد للمورد) ويسجل حركة purchase_return.
 * $items: [['product_id'=>int,'quantity'=>int,'unit_cost'=>float], ...]
 */
function purchase_return_create(int $invoiceId, array $items, string $note = ''): int
{
    $invoice = purchase_invoice_find($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found');
    }
    if (!$items) {
        throw new RuntimeException(t('insufficient_stock'));
    }

    $purchasedQty = [];
    foreach ($invoice['items'] as $line) {
        $purchasedQty[(int)$line['product_id']] = (int)$line['quantity'];
    }
    $alreadyReturned = purchase_returned_quantities($invoiceId);
    foreach ($items as $item) {
        $pid = (int)$item['product_id'];
        $maxReturnable = ($purchasedQty[$pid] ?? 0) - ($alreadyReturned[$pid] ?? 0);
        if ($item['quantity'] > $maxReturnable) {
            throw new RuntimeException(t('insufficient_stock'));
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $available = stock_get_quantity((int)$item['product_id'], (int)$invoice['branch_id']);
            if ($available < $item['quantity']) {
                throw new RuntimeException(t('insufficient_stock'));
            }
        }

        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_cost'];
        }
        $returnNo = generate_invoice_number('PR-', 'purchase_returns');

        $stmt = $pdo->prepare(
            'INSERT INTO purchase_returns (return_no, invoice_id, branch_id, user_id, total, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$returnNo, $invoiceId, $invoice['branch_id'], current_user()['id'] ?? null, $total, $note ?: null]);
        $returnId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO purchase_return_items (return_id, product_id, quantity, unit_cost, total) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $itemStmt->execute([$returnId, $item['product_id'], $item['quantity'], $item['unit_cost'], $lineTotal]);
            stock_record_movement(
                (int)$item['product_id'],
                (int)$invoice['branch_id'],
                'purchase_return',
                (int)$item['quantity'],
                'purchase_return',
                $returnId
            );
        }

        $pdo->commit();
        log_activity('create', 'purchase_returns', $returnId, $returnNo);
        return $returnId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function purchase_returns_list(): array
{
    return db()->query(
        "SELECT pr.*, pi.invoice_no, b.name AS branch_name, u.full_name AS user_name
         FROM purchase_returns pr
         JOIN purchase_invoices pi ON pi.id = pr.invoice_id
         JOIN branches b ON b.id = pr.branch_id
         LEFT JOIN users u ON u.id = pr.user_id
         ORDER BY pr.created_at DESC"
    )->fetchAll();
}
