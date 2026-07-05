<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user/loginUser.php');
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id_order = ? AND id_user = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: katalog.php');
    exit;
}

$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE id_order = ?");
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --soft: #f5f5f0; --border: #e0e0da; }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }
        .wrap { max-width: 640px; margin: 0 auto; padding: 3rem 1rem; }
        .check-badge { width: 64px; height: 64px; border-radius: 50%; background: #e6f7ea; color: #1e7e34; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; }
        .card-order { border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; margin-top: 1.5rem; }
        .row-line { display: flex; justify-content: space-between; font-size: .88rem; padding: 6px 0; }
        .row-line span:first-child { color: #777; }
        hr { border-color: var(--border); }
        .btn-dark-custom { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: .9rem; }
        .btn-dark-custom:hover { background: #333; color: #fff; }
    </style>
</head>
<body>

<?php $base = ''; $activeNav = ''; include 'includes/navbar.php'; ?>

<div class="wrap text-center">
    <div class="check-badge"><i class="bi bi-check-lg"></i></div>
    <h3 class="fw-bold">Pesanan berhasil dibuat!</h3>
    <p class="text-muted" style="font-size:.9rem">
        Kode pesanan <strong><?= htmlspecialchars($order['kode_pesanan']) ?></strong>.
        Kurir akan menghubungi nomor HP-mu sebelum pengiriman.
    </p>

    <div class="card-order text-start">
        <div class="fw-bold mb-2" style="font-size:.9rem"><i class="bi bi-geo-alt"></i> Alamat Pengiriman</div>
        <div style="font-size:.85rem" class="mb-1"><?= htmlspecialchars($order['nama_penerima']) ?> — <?= htmlspecialchars($order['no_hp']) ?></div>
        <div style="font-size:.85rem" class="text-muted"><?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?></div>
        <?php if (!empty($order['catatan'])): ?>
            <div style="font-size:.8rem" class="text-muted mt-1"><i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($order['catatan']) ?></div>
        <?php endif; ?>

        <hr>
        <div class="fw-bold mb-2" style="font-size:.9rem"><i class="bi bi-truck"></i> Pengiriman & Pembayaran</div>
        <div class="row-line"><span>Metode pengiriman</span><span><?= htmlspecialchars($order['label_pengiriman']) ?></span></div>
        <div class="row-line"><span>Metode pembayaran</span><span>Bayar di Tempat (COD)</span></div>
        <div class="row-line"><span>Status</span><span><span class="badge bg-warning-subtle text-warning-emphasis"><?= htmlspecialchars($order['status']) ?></span></span></div>

        <hr>
        <div class="fw-bold mb-2" style="font-size:.9rem"><i class="bi bi-receipt"></i> Rincian Produk</div>
        <?php foreach ($orderItems as $it): ?>
            <div class="row-line"><span><?= htmlspecialchars($it['nama_produk']) ?> × <?= $it['qty'] ?></span><span><?= formatRupiah($it['subtotal']) ?></span></div>
        <?php endforeach; ?>
        <div class="row-line"><span>Subtotal produk</span><span><?= formatRupiah($order['subtotal_produk']) ?></span></div>
        <div class="row-line"><span>Ongkos kirim</span><span><?= formatRupiah($order['ongkir']) ?></span></div>
        <hr>
        <div class="row-line" style="font-size:1rem;font-weight:800"><span>Total bayar (tunai ke kurir)</span><span><?= formatRupiah($order['total_bayar']) ?></span></div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-4">
        <a href="user/riwayat_pesanan.php" class="btn btn-outline-secondary" style="border-radius:8px;font-size:.9rem">Lihat riwayat pesanan</a>
        <a href="katalog.php" class="btn btn-dark-custom">Belanja lagi</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
