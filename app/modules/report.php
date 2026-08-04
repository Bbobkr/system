<?php
declare(strict_types=1);

function dashboard_today_sales(?int $branchId = null): float
{
    $sql = "SELECT COALESCE(SUM(total), 0) v FROM sales_invoices WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
    $params = [];
    if ($branchId !== null) {
        $sql .= ' AND branch_id = ?';
        $params[] = $branchId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetch()['v'];
}

function dashboard_total_products(): int
{
    return (int)db()->query('SELECT COUNT(*) c FROM products WHERE is_active = 1')->fetch()['c'];
}

function dashboard_sales_trend(?int $branchId = null, int $days = 30): array
{
    $sql = "SELECT DATE(created_at) d, COALESCE(SUM(total),0) v FROM sales_invoices
            WHERE status='completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
    $params = [$days];
    if ($branchId !== null) {
        $sql .= ' AND branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' GROUP BY DATE(created_at) ORDER BY d';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // نملأ الأيام بدون مبيعات بصفر لرسم بياني متصل
    $map = [];
    foreach ($rows as $r) {
        $map[$r['d']] = (float)$r['v'];
    }
    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = $d;
        $values[] = $map[$d] ?? 0;
    }
    return ['labels' => $labels, 'values' => $values];
}

function dashboard_top_products(?int $branchId = null, int $limit = 5): array
{
    $sql = "SELECT p.name, SUM(sii.quantity) qty
            FROM sales_invoice_items sii
            JOIN sales_invoices si ON si.id = sii.invoice_id AND si.status='completed'
            JOIN products p ON p.id = sii.product_id
            WHERE 1=1";
    $params = [];
    if ($branchId !== null) {
        $sql .= ' AND si.branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' GROUP BY p.id, p.name ORDER BY qty DESC LIMIT ' . (int)$limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function report_sales(string $fromDate, string $toDate, ?int $branchId = null): array
{
    $sql = "SELECT si.invoice_no, si.created_at, c.name AS customer_name, b.name AS branch_name,
                   si.subtotal, si.discount_amount, si.tax_amount, si.total, si.payment_status
            FROM sales_invoices si
            LEFT JOIN customers c ON c.id = si.customer_id
            JOIN branches b ON b.id = si.branch_id
            WHERE si.status = 'completed' AND DATE(si.created_at) BETWEEN ? AND ?";
    $params = [$fromDate, $toDate];
    if ($branchId !== null) {
        $sql .= ' AND si.branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' ORDER BY si.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function report_inventory(?int $branchId = null): array
{
    $sql = "SELECT p.name, p.barcode, p.unit, p.cost_price, p.sale_price, ps.branch_id, b.name AS branch_name, ps.quantity,
                   (ps.quantity * p.cost_price) AS stock_value
            FROM product_stock ps
            JOIN products p ON p.id = ps.product_id AND p.is_active = 1
            JOIN branches b ON b.id = ps.branch_id
            WHERE 1=1";
    $params = [];
    if ($branchId !== null) {
        $sql .= ' AND ps.branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' ORDER BY p.name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** تقرير الأرباح: الإيرادات مقابل تكلفة البضاعة المباعة (بسعر التكلفة وقت البيع تقريبًا عبر سعر التكلفة الحالي) */
function report_profit(string $fromDate, string $toDate, ?int $branchId = null): array
{
    $sql = "SELECT si.invoice_no, si.created_at, si.branch_id, b.name AS branch_name,
                   si.total AS revenue,
                   COALESCE(SUM(sii.quantity * p.cost_price), 0) AS cost
            FROM sales_invoices si
            JOIN branches b ON b.id = si.branch_id
            JOIN sales_invoice_items sii ON sii.invoice_id = si.id
            JOIN products p ON p.id = sii.product_id
            WHERE si.status = 'completed' AND DATE(si.created_at) BETWEEN ? AND ?";
    $params = [$fromDate, $toDate];
    if ($branchId !== null) {
        $sql .= ' AND si.branch_id = ?';
        $params[] = $branchId;
    }
    $sql .= ' GROUP BY si.id ORDER BY si.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
