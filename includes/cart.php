<?php
/**
 * Keranjang belanja berbasis session.
 * Struktur: $_SESSION['cart'] = [ product_id => qty, ... ]
 * Cart dipisah per akun (key session berbeda per user_id) supaya tidak
 * tercampur jika ada beberapa akun login bergantian di browser yang sama.
 */

function cartKey() {
    $uid = $_SESSION['user_id'] ?? 'guest';
    return 'cart_' . $uid;
}

function cartRaw() {
    $key = cartKey();
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    return $_SESSION[$key];
}

function cartSaveRaw($cart) {
    $_SESSION[cartKey()] = $cart;
}

function cartAdd($productId, $qty = 1) {
    $cart = cartRaw();
    $productId = (int)$productId;
    $qty = max(1, (int)$qty);
    $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
    cartSaveRaw($cart);
}

function cartSetQty($productId, $qty) {
    $cart = cartRaw();
    $productId = (int)$productId;
    $qty = (int)$qty;
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $qty;
    }
    cartSaveRaw($cart);
}

function cartRemove($productId) {
    $cart = cartRaw();
    unset($cart[(int)$productId]);
    cartSaveRaw($cart);
}

function cartClear() {
    cartSaveRaw([]);
}

function cartCount() {
    $cart = cartRaw();
    return array_sum($cart);
}

/**
 * Ambil detail item keranjang lengkap dengan data produk terbaru dari DB
 * (harga & stok selalu up-to-date, bukan dari cache session).
 */
function cartGetItems(PDO $pdo, $onlyIds = null) {
    $cart = cartRaw();
    if (empty($cart)) return [];

    $ids = array_keys($cart);
    if ($onlyIds !== null) {
        $ids = array_values(array_intersect($ids, array_map('intval', $onlyIds)));
    }
    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $p) {
        $qty = min((int)$cart[$p['id']], max(0, (int)$p['stok'])); // batasi qty maks stok
        if ($qty <= 0) continue;
        $items[] = [
            'id'       => (int)$p['id'],
            'nama'     => $p['nama'],
            'harga'    => (float)$p['harga'],
            'gambar'   => $p['gambar'],
            'stok'     => (int)$p['stok'],
            'qty'      => $qty,
            'subtotal' => $qty * (float)$p['harga'],
        ];
    }
    return $items;
}

function cartTotal(array $items) {
    $total = 0;
    foreach ($items as $it) $total += $it['subtotal'];
    return $total;
}
