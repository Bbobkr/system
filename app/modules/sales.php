<?php
declare(strict_types=1);

/**
 * ينشئ فاتورة بيع كاملة مع أصنافها ضمن معاملة واحدة، ويخصم المخزون تلقائيًا.
 * $items: [['product_id'=>int, 'quantity'=>int, 'unit_price'=>float], ...]
 * يرمي RuntimeException برسالة مترجمة إذا كانت الكمية غير كافية لأي صنف.
 */
function sales_invoice_create(array $header, array $items): int
{
    if (!$items) {
        throw new RuntimeException(t('insufficient_stock'));
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];

            $available = stock_get_quantity((int)$item['product_id'], (int)$header['branch_id']);
            if ($available < $item['quantity']) {
                throw new RuntimeException(t('insufficient_stock'));
            }
        }

        $discount = (float)$header['discount_amount'];
        $taxPercent = (float)$header['tax_percent'];
        $taxAmount = round(($subtotal - $discount) * $taxPercent / 100, 2);
        $total = round($subtotal - $discount + $taxAmount, 2);
        $paid = (float)$header['paid_amount'];
        $paymentStatus = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $invoiceNo = generate_invoice_number(setting_get('invoice_prefix_sale', INVOICE_PREFIX_SALE), 'sales_invoices');

        $stmt = $pdo->prepare(
            'INSERT INTO sales_invoices (invoice_no, customer_id, branch_id, user_id, subtotal, tax_percent, tax_amount, discount_amount, total, paid_amount, payment_status, status, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "completed", ?, NOW())'
        );
        $stmt->execute([
            $invoiceNo,
            $header['customer_id'] ?: null,
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
            'INSERT INTO sales_invoice_items (invoice_id, product_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemStmt->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['unit_price'], $lineTotal]);
            stock_record_movement(
                (int)$item['product_id'],
                (int)$header['branch_id'],
                'sale',
                (int)$item['quantity'],
                'sales_invoice',
                $invoiceId
            );
        }

        $pdo->commit();
        log_activity('create', 'sales', $invoiceId, $invoiceNo);
        return $invoiceId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function sales_invoices_list(array $filters = []): array
{
    $sql = "SELECT si.*, c.name AS customer_name, b.name AS branch_name, u.full_name AS user_name
            FROM sales_invoices si
            LEFT JOIN customers c ON c.id = si.customer_id
            JOIN branches b ON b.id = si.branch_id
            LEFT JOIN users u ON u.id = si.user_id
            WHERE 1=1";
    $params = [];
    if (!empty($filters['branch_id'])) {
        $sql .= ' AND si.branch_id = ?';
        $params[] = $filters['branch_id'];
    }
    if (!empty($filters['search'])) {
        $sql .= ' AND (si.invoice_no LIKE ? OR c.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['from_date'])) {
        $sql .= ' AND DATE(si.created_at) >= ?';
        $params[] = $filters['from_date'];
    }
    if (!empty($filters['to_date'])) {
        $sql .= ' AND DATE(si.created_at) <= ?';
        $params[] = $filters['to_date'];
    }
    $sql .= ' ORDER BY si.created_at DESC, si.id DESC LIMIT 500';
    $stmt = $pdo = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function sales_invoice_find(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT si.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name, u.full_name AS user_name
         FROM sales_invoices si
         LEFT JOIN customers c ON c.id = si.customer_id
         JOIN branches b ON b.id = si.branch_id
         LEFT JOIN users u ON u.id = si.user_id
         WHERE si.id = ?"
    );
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    if (!$invoice) {
        return null;
    }
    $itemsStmt = db()->prepare(
        'SELECT sii.*, p.name AS product_name, p.unit FROM sales_invoice_items sii JOIN products p ON p.id = sii.product_id WHERE invoice_id = ?'
    );
    $itemsStmt->execute([$id]);
    $invoice['items'] = $itemsStmt->fetchAll();
    return $invoice;
}

/** إجمالي ما تم إرجاعه سابقًا لكل صنف في فاتورة معينة (لمنع إرجاع أكثر من الكمية المباعة) */
function sales_returned_quantities(int $invoiceId): array
{
    $stmt = db()->prepare(
        'SELECT sri.product_id, SUM(sri.quantity) AS qty
         FROM sales_return_items sri JOIN sales_returns sr ON sr.id = sri.return_id
         WHERE sr.invoice_id = ? GROUP BY sri.product_id'
    );
    $stmt->execute([$invoiceId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['product_id']] = (int)$row['qty'];
    }
    return $result;
}

/**
 * ينشئ مرتجع بيع: يعيد الكمية للمخزون ويسجل حركة sale_return.
 * $items: [['product_id'=>int,'quantity'=>int,'unit_price'=>float], ...]
 */
function sales_return_create(int $invoiceId, array $items, string $note = ''): int
{
    $invoice = sales_invoice_find($invoiceId);
    if (!$invoice) {
        throw new RuntimeException('Invoice not found');
    }
    if (!$items) {
        throw new RuntimeException(t('insufficient_stock'));
    }

    $soldQty = [];
    foreach ($invoice['items'] as $line) {
        $soldQty[(int)$line['product_id']] = (int)$line['quantity'];
    }
    $alreadyReturned = sales_returned_quantities($invoiceId);
    foreach ($items as $item) {
        $pid = (int)$item['product_id'];
        $maxReturnable = ($soldQty[$pid] ?? 0) - ($alreadyReturned[$pid] ?? 0);
        if ($item['quantity'] > $maxReturnable) {
            throw new RuntimeException(t('insufficient_stock'));
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }
        $returnNo = generate_invoice_number('SR-', 'sales_returns');

        $stmt = $pdo->prepare(
            'INSERT INTO sales_returns (return_no, invoice_id, branch_id, user_id, total, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$returnNo, $invoiceId, $invoice['branch_id'], current_user()['id'] ?? null, $total, $note ?: null]);
        $returnId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO sales_return_items (return_id, product_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $itemStmt->execute([$returnId, $item['product_id'], $item['quantity'], $item['unit_price'], $lineTotal]);
            stock_record_movement(
                (int)$item['product_id'],
                (int)$invoice['branch_id'],
                'sale_return',
                (int)$item['quantity'],
                'sales_return',
                $returnId
            );
        }

        $pdo->commit();
        log_activity('create', 'sales_returns', $returnId, $returnNo);
        return $returnId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function sales_returns_list(): array
{
    return db()->query(
        "SELECT sr.*, si.invoice_no, b.name AS branch_name, u.full_name AS user_name
         FROM sales_returns sr
         JOIN sales_invoices si ON si.id = sr.invoice_id
         JOIN branches b ON b.id = sr.branch_id
         LEFT JOIN users u ON u.id = sr.user_id
         ORDER BY sr.created_at DESC"
    )->fetchAll();
}
