<?php
require __DIR__ . '/../../app/config/config.php';
require_login();
require APP_DIR . '/modules/product.php';
require APP_DIR . '/modules/stock.php';
require APP_DIR . '/modules/branch.php';

$code = trim($_GET['code'] ?? '');
if ($code === '') {
    json_response(['found' => false], 404);
}

$product = product_find_by_barcode($code);
if (!$product) {
    json_response(['found' => false], 404);
}

$branchId = user_branch_id();
$stockByBranch = [];
if ($branchId !== null) {
    $stockByBranch[$branchId] = stock_get_quantity((int)$product['id'], $branchId);
} else {
    foreach (branch_all(true) as $b) {
        $stockByBranch[(int)$b['id']] = [
            'branch_name' => $b['name'],
            'quantity' => stock_get_quantity((int)$product['id'], (int)$b['id']),
        ];
    }
}

json_response([
    'found' => true,
    'id' => (int)$product['id'],
    'name' => $product['name'],
    'sku' => $product['sku'],
    'barcode' => $product['barcode'],
    'sale_price' => (float)$product['sale_price'],
    'cost_price' => (float)$product['cost_price'],
    'stock' => $stockByBranch,
]);
