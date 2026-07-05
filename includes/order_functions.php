<?php
/**
 * Membuat tabel orders & order_items jika belum ada.
 * Dipanggil di checkout.php & proses_checkout.php supaya fitur checkout
 * langsung jalan tanpa perlu import file .sql manual.
 */
function ensureOrderTables(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id_order INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            kode_pesanan VARCHAR(20) NOT NULL UNIQUE,
            nama_penerima VARCHAR(150) NOT NULL,
            no_hp VARCHAR(25) NOT NULL,
            alamat_pengiriman TEXT NOT NULL,
            metode_pengiriman VARCHAR(30) NOT NULL,
            label_pengiriman VARCHAR(100) NOT NULL,
            ongkir DECIMAL(12,2) NOT NULL DEFAULT 0,
            metode_pembayaran VARCHAR(20) NOT NULL DEFAULT 'COD',
            catatan TEXT NULL,
            subtotal_produk DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_bayar DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'Menunggu Konfirmasi',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id_item INT AUTO_INCREMENT PRIMARY KEY,
            id_order INT NOT NULL,
            id_produk INT NOT NULL,
            nama_produk VARCHAR(200) NOT NULL,
            harga_satuan DECIMAL(12,2) NOT NULL,
            qty INT NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL,
            FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Daftar opsi metode pengiriman.
 * "cargo" ditujukan untuk pesanan dalam jumlah/berat besar (mis. total qty banyak),
 * dengan biaya lebih hemat namun estimasi lebih lama dibanding kurir reguler.
 */
function getShippingMethods($totalQty = 1) {
    $isBulky = $totalQty >= 5; // heuristik sederhana: 5+ item dianggap muatan besar

    return [
        'reguler' => [
            'label'      => 'Kurir Reguler (JNE / J&T / SiCepat)',
            'deskripsi'  => 'Pengiriman standar, cocok untuk paket kecil–sedang.',
            'estimasi'   => '2–4 hari kerja',
            'biaya'      => 15000,
            'icon'       => 'bi-truck',
        ],
        'instan' => [
            'label'      => 'Instan / Same Day',
            'deskripsi'  => 'Khusus area dalam kota, dikirim di hari yang sama.',
            'estimasi'   => '3–6 jam',
            'biaya'      => 25000,
            'icon'       => 'bi-lightning-charge',
        ],
        'kargo' => [
            'label'      => 'Kargo' . ($isBulky ? ' (disarankan)' : ''),
            'deskripsi'  => 'Untuk pesanan dalam jumlah/berat besar. Lebih hemat, waktu tempuh lebih lama.',
            'estimasi'   => '5–8 hari kerja',
            'biaya'      => $isBulky ? 10000 : 12000,
            'icon'       => 'bi-box-seam',
        ],
    ];
}

function generateOrderCode() {
    return 'INV' . date('ymd') . strtoupper(substr(uniqid(), -6));
}
