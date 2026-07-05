<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: loginUser.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id_user = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE id_order = ?");

function statusBadge($status) {
    $map = [
        'Menunggu Konfirmasi' => 'bg-warning-subtle text-warning-emphasis',
        'Dikemas'             => 'bg-info-subtle text-info-emphasis',
        'Dikirim'             => 'bg-primary-subtle text-primary-emphasis',
        'Selesai'             => 'bg-success-subtle text-success-emphasis',
        'Dibatalkan'          => 'bg-danger-subtle text-danger-emphasis',
    ];
    $class = $map[$status] ?? 'bg-secondary-subtle text-secondary-emphasis';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan — TokoKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --soft: #f5f5f0; --border: #e0e0da; }
        body { font-family: 'Segoe UI', sans-serif; background: #fff; color: var(--primary); }
        .page-header { padding: 2rem 0 1rem; }
        .page-header h1 { font-size: 1.6rem; font-weight: 800; letter-spacing: -1px; }
        .order-card { border: 1px solid var(--border); border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 1rem; }
        .order-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .order-item-line { font-size: .85rem; color: #555; }
        footer { border-top: 1px solid var(--border); padding: 2rem 0; color: #999; font-size: 0.8rem; }
    </style>
</head>
<body>

<?php $base = '../'; $activeNav = 'riwayat'; include '../includes/navbar.php'; ?>

<div class="container" style="max-width:800px">
    <div class="page-header">
        <h1>Riwayat Pesanan</h1>
        <p class="text-muted mb-0" style="font-size:.9rem">Semua pesanan COD yang pernah kamu buat.</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-receipt" style="font-size:2.5rem;"></i>
            <p class="mt-3 mb-3">Belum ada pesanan.</p>
            <a href="../katalog.php" class="btn btn-dark" style="background:var(--primary);border-radius:8px">Mulai belanja →</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <?php
                $stmtItems->execute([$o['id_order']]);
                $its = $stmtItems->fetchAll();
            ?>
            <div class="order-card">
                <div class="order-head">
                    <div>
                        <div class="fw-bold" style="font-size:.92rem"><?= htmlspecialchars($o['kode_pesanan']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></div>
                    </div>
                    <?= statusBadge($o['status']) ?>
                </div>
                <?php foreach ($its as $it): ?>
                    <div class="order-item-line">• <?= htmlspecialchars($it['nama_produk']) ?> × <?= $it['qty'] ?> — <?= formatRupiah($it['subtotal']) ?></div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between flex-wrap gap-2" style="font-size:.82rem">
                    <span class="text-muted"><i class="bi bi-truck"></i> <?= htmlspecialchars($o['label_pengiriman']) ?></span>
                    <span class="text-muted"><i class="bi bi-cash-coin"></i> COD</span>
                    <span class="fw-bold">Total: <?= formatRupiah($o['total_bayar']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
