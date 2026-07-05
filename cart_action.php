<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$action    = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = (int)($_POST['qty'] ?? 1);

if (!$productId && $action !== 'count') {
    echo json_encode(['ok' => false, 'message' => 'Produk tidak valid.']);
    exit;
}

// Validasi stok ke DB langsung supaya tidak bisa menambah melebihi stok
if (in_array($action, ['add', 'update'], true)) {
    $stmt = $pdo->prepare("SELECT stok FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $produk = $stmt->fetch();
    if (!$produk) {
        echo json_encode(['ok' => false, 'message' => 'Produk tidak ditemukan.']);
        exit;
    }
    if ((int)$produk['stok'] <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Stok produk habis.']);
        exit;
    }
}

switch ($action) {
    case 'add':
        cartAdd($productId, $qty > 0 ? $qty : 1);
        break;
    case 'update':
        cartSetQty($productId, $qty);
        break;
    case 'remove':
        cartRemove($productId);
        break;
    case 'clear':
        cartClear();
        break;
    case 'count':
        // no-op, hanya untuk ambil jumlah
        break;
    default:
        echo json_encode(['ok' => false, 'message' => 'Aksi tidak dikenal.']);
        exit;
}

echo json_encode([
    'ok'    => true,
    'count' => cartCount(),
]);
