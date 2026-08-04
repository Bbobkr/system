<?php
require __DIR__ . '/../../app/config/config.php';
require_login();
require APP_DIR . '/modules/stock.php';

$productId = (int)($_GET['product_id'] ?? 0);
$branchId = (int)($_GET['branch_id'] ?? 0) ?: user_branch_id();

if (!$productId || !$branchId) {
    json_response(['quantity' => 0]);
}

json_response(['quantity' => stock_get_quantity($productId, $branchId)]);
