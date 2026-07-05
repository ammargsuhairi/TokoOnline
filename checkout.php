<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';
require_once 'includes/order_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user/loginUser.php?redirect=' . urlencode('checkout.php'));
    exit;
}

$stmtUser = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    header('Location: user/loginUser.php');
    exit;
}

// --- "Beli Sekarang" langsung dari katalog (tanpa lewat keranjang) ---
if (isset($_GET['beli'])) {
    $pid = (int)$_GET['beli'];
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $produk = $stmt->fetch();
    if ($produk && (int)$produk['stok'] > 0) {
        $qty = min($qty, (int)$produk['stok']);
        $items = [[
            'id'       => (int)$produk['id'],
            'nama'     => $produk['nama'],
            'harga'    => (float)$produk['harga'],
            'gambar'   => $produk['gambar'],
            'stok'     => (int)$produk['stok'],
            'qty'      => $qty,
            'subtotal' => $qty * (float)$produk['harga'],
        ]];
    } else {
        $items = [];
    }
} else {
    // --- Checkout dari item keranjang yang dipilih ---
    $checkoutIds = $_SESSION['checkout_ids'] ?? null;
    $items = cartGetItems($pdo, $checkoutIds);
}

if (empty($items)) {
    header('Location: keranjang.php');
    exit;
}

$subtotal = cartTotal($items);
$totalQty = array_sum(array_column($items, 'qty'));
$shippingMethods = getShippingMethods($totalQty);
$defaultShipping = array_key_first($shippingMethods);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPenerima = trim($_POST['nama_penerima'] ?? '');
    $noHp         = trim($_POST['no_hp'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');
    $metodeKirim  = $_POST['metode_pengiriman'] ?? '';
    $catatan      = trim($_POST['catatan'] ?? '');
    $metodeBayar  = $_POST['metode_pembayaran'] ?? 'COD';

    if (!$namaPenerima) $errors[] = 'Nama penerima wajib diisi.';
    if (!$noHp || !preg_match('/^[0-9+\-\s]{9,15}$/', $noHp)) $errors[] = 'Nomor HP tidak valid.';
    if (!$alamat) $errors[] = 'Alamat pengiriman wajib diisi.';
    if (!isset($shippingMethods[$metodeKirim])) $errors[] = 'Metode pengiriman tidak valid.';
    if ($metodeBayar !== 'COD') $errors[] = 'Saat ini hanya metode pembayaran COD yang tersedia.';

    if (empty($errors)) {
        ensureOrderTables($pdo);

        $ongkir = $shippingMethods[$metodeKirim]['biaya'];
        $label  = $shippingMethods[$metodeKirim]['label'];
        $total  = $subtotal + $ongkir;
        $kode   = generateOrderCode();

        try {
            $pdo->beginTransaction();

            // Cek ulang stok sebelum insert (mencegah race condition sederhana)
            foreach ($items as $it) {
                $cek = $pdo->prepare("SELECT stok FROM products WHERE id = ? FOR UPDATE");
                $cek->execute([$it['id']]);
                $stokSekarang = (int)$cek->fetchColumn();
                if ($stokSekarang < $it['qty']) {
                    throw new Exception('Stok "' . $it['nama'] . '" tidak mencukupi lagi.');
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO orders
                    (id_user, kode_pesanan, nama_penerima, no_hp, alamat_pengiriman,
                    metode_pengiriman, label_pengiriman, ongkir, metode_pembayaran,
                    catatan, subtotal_produk, total_bayar, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], $kode, $namaPenerima, $noHp, $alamat,
                $metodeKirim, $label, $ongkir, $metodeBayar,
                $catatan, $subtotal, $total, 'Menunggu Konfirmasi',
            ]);
            $orderId = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO order_items (id_order, id_produk, nama_produk, harga_satuan, qty, subtotal)
                VALUES (?,?,?,?,?,?)
            ");
            $stmtStok = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");

            foreach ($items as $it) {
                $stmtItem->execute([$orderId, $it['id'], $it['nama'], $it['harga'], $it['qty'], $it['subtotal']]);
                $stmtStok->execute([$it['qty'], $it['id']]);

                // Hapus dari keranjang jika item ini berasal dari keranjang
                cartRemove($it['id']);
            }

            $pdo->commit();
            unset($_SESSION['checkout_ids']);

            header('Location: pesanan_sukses.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage() ?: 'Gagal memproses pesanan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --accent: #e8ff00; --soft: #f5f5f0; --border: #e0e0da; }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }
        .page-header { padding: 2rem 0 1rem; }
        .page-header h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        .section-card { border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; margin-bottom: 1.25rem; }
        .section-title { font-size: .95rem; font-weight: 700; margin-bottom: 1rem; display:flex; align-items:center; gap:8px; }
        .section-title i { color: #666; }
        .form-label { font-size: .78rem; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .form-control, .form-select { border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; padding: 10px 14px; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 .15rem rgba(0,0,0,.08); }

        .ship-option { border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background .15s; }
        .ship-option:hover { border-color: #999; }
        .ship-option:has(input:checked) { border-color: var(--primary); background: var(--soft); }
        .ship-option .biaya { font-weight: 700; }

        .pay-option { border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; }
        .pay-option.active { border-color: var(--primary); background: var(--soft); }
        .pay-option.disabled { opacity: .55; }

        .summary-card { border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; position: sticky; top: 90px; }
        .item-row { display: flex; justify-content: space-between; font-size: .85rem; margin-bottom: 8px; }
        .item-row .nama { color: #444; }
        .total-row { display: flex; justify-content: space-between; font-size: 1rem; font-weight: 800; padding-top: 12px; border-top: 1px solid var(--border); margin-top: 8px; }
        .btn-dark-custom { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: .95rem; font-weight: 600; }
        .btn-dark-custom:hover { background: #333; color: #fff; }
        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<?php $base = ''; $activeNav = 'keranjang'; include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Checkout</h1>
        <p class="text-muted mb-0" style="font-size:.9rem">Lengkapi alamat, pilih metode pengiriman, lalu selesaikan pesanan.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger py-2 px-3" style="font-size:.85rem;border-radius:8px">
            <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-8">

                <!-- ALAMAT PENGIRIMAN -->
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-geo-alt"></i> Alamat Pengiriman</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" name="nama_penerima" class="form-control"
                                   value="<?= htmlspecialchars($_POST['nama_penerima'] ?? $currentUser['nama']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor HP</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx"
                                   value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Nama jalan, no rumah, RT/RW, kelurahan, kecamatan, kota, kode pos" required><?= htmlspecialchars($_POST['alamat'] ?? $currentUser['alamat']) ?></textarea>
                            <div class="form-text" style="font-size:.75rem">Alamat ini diambil dari profil akunmu. Kamu bisa mengubahnya khusus untuk pesanan ini.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan untuk kurir (opsional)</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Contoh: rumah pagar hijau, titip di security"
                                   value="<?= htmlspecialchars($_POST['catatan'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- METODE PENGIRIMAN -->
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-truck"></i> Metode Pengiriman</div>
                    <?php foreach ($shippingMethods as $key => $m): ?>
                        <label class="ship-option d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="metode_pengiriman" value="<?= $key ?>"
                                       <?= (($_POST['metode_pengiriman'] ?? $defaultShipping) === $key) ? 'checked' : '' ?> required>
                                <div>
                                    <div class="fw-semibold" style="font-size:.9rem"><i class="bi <?= $m['icon'] ?>"></i> <?= htmlspecialchars($m['label']) ?></div>
                                    <div class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($m['deskripsi']) ?> · Estimasi <?= htmlspecialchars($m['estimasi']) ?></div>
                                </div>
                            </div>
                            <div class="biaya"><?= formatRupiah($m['biaya']) ?></div>
                        </label>
                    <?php endforeach; ?>
                    <?php if ($totalQty >= 5): ?>
                        <div class="text-muted" style="font-size:.78rem"><i class="bi bi-info-circle"></i> Pesananmu berjumlah <?= $totalQty ?> item — Kargo bisa jadi pilihan paling hemat untuk muatan besar.</div>
                    <?php endif; ?>
                </div>

                <!-- METODE PEMBAYARAN -->
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-wallet2"></i> Metode Pembayaran</div>
                    <label class="pay-option active d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="metode_pembayaran" value="COD" checked required>
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem"><i class="bi bi-cash-coin"></i> Bayar di Tempat (COD)</div>
                                <div class="text-muted" style="font-size:.78rem">Bayar tunai ke kurir saat paket sampai di alamatmu.</div>
                            </div>
                        </div>
                    </label>
                    <div class="pay-option disabled d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" disabled>
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem"><i class="bi bi-credit-card"></i> Transfer Bank / E-Wallet</div>
                                <div class="text-muted" style="font-size:.78rem">Segera hadir.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 class="fw-bold mb-3" style="font-size:1rem">Ringkasan Pesanan</h5>
                    <?php foreach ($items as $it): ?>
                        <div class="item-row">
                            <span class="nama"><?= htmlspecialchars($it['nama']) ?> × <?= $it['qty'] ?></span>
                            <span><?= formatRupiah($it['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="item-row">
                        <span class="nama">Subtotal produk</span>
                        <span><?= formatRupiah($subtotal) ?></span>
                    </div>
                    <div class="item-row">
                        <span class="nama">Ongkos kirim</span>
                        <span id="ongkirDisplay"><?= formatRupiah($shippingMethods[$_POST['metode_pengiriman'] ?? $defaultShipping]['biaya']) ?></span>
                    </div>
                    <div class="total-row">
                        <span>Total Bayar</span>
                        <span id="totalDisplay"><?= formatRupiah($subtotal + $shippingMethods[$_POST['metode_pengiriman'] ?? $defaultShipping]['biaya']) ?></span>
                    </div>
                    <button type="submit" class="btn btn-dark-custom w-100 mt-3">
                        <i class="bi bi-check2-circle"></i> Buat Pesanan (COD)
                    </button>
                    <a href="keranjang.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:8px;font-size:.85rem">← Kembali ke keranjang</a>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ongkirData = <?= json_encode(array_map(fn($m) => $m['biaya'], $shippingMethods)) ?>;
const subtotal = <?= (int)$subtotal ?>;

function formatRupiah(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

document.querySelectorAll('input[name="metode_pengiriman"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const ongkir = ongkirData[this.value] || 0;
        document.getElementById('ongkirDisplay').textContent = formatRupiah(ongkir);
        document.getElementById('totalDisplay').textContent = formatRupiah(subtotal + ongkir);
    });
});
</script>
</body>
</html>
