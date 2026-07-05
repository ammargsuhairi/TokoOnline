<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

// Daftar status yang valid, urut sesuai alur pesanan
$statusOptions = ['Menunggu Konfirmasi', 'Dikemas', 'Dikirim', 'Selesai', 'Dibatalkan'];

// Aksi: Konfirmasi pembayaran (Menunggu Konfirmasi -> Dikemas)
if (isset($_POST['confirm_payment'])) {
    $id_order = (int) $_POST['id_order'];
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id_order = ?");
    $stmt->execute([$id_order]);
    $current = $stmt->fetchColumn();

    if ($current === 'Menunggu Konfirmasi') {
        $pdo->prepare("UPDATE orders SET status = 'Dikemas' WHERE id_order = ?")->execute([$id_order]);
        header('Location: pesanan.php?msg=confirmed');
        exit;
    }
    header('Location: pesanan.php?msg=invalid');
    exit;
}

// Aksi: Ubah status manual (Dikirim, Selesai, Dibatalkan, dst)
if (isset($_POST['update_status'])) {
    $id_order = (int) $_POST['id_order'];
    $status_baru = $_POST['status_baru'] ?? '';

    if (in_array($status_baru, $statusOptions, true)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id_order = ?")->execute([$status_baru, $id_order]);
        header('Location: pesanan.php?msg=updated');
        exit;
    }
    header('Location: pesanan.php?msg=invalid');
    exit;
}

// Filter
$search = $_GET['q'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.nama AS nama_user, u.username
        FROM orders o
        JOIN user u ON o.id_user = u.id_user
        WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND (o.kode_pesanan LIKE ? OR o.nama_penerima LIKE ? OR u.nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE id_order = ?");

$total_pesanan = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_menunggu = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Menunggu Konfirmasi'")->fetchColumn();
$total_diproses = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Dikemas','Dikirim')")->fetchColumn();

function statusBadgeAdmin($status) {
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
    <title>Pesanan — TokoKu Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #1a1a1a; --soft: #f5f5f0; --border: #e0e0da; }
        body { background: var(--soft); font-family: 'Segoe UI', sans-serif; }

        .sidebar { width: 220px; min-height: 100vh; background: #fff; border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; padding: 1.5rem 1rem; display: flex; flex-direction: column; }
        .sidebar-logo { font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
        .nav-item a { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 8px; color: #666; font-size: .875rem; text-decoration: none; margin-bottom: 2px; }
        .nav-item a:hover, .nav-item a.active { background: var(--soft); color: var(--primary); font-weight: 600; }
        .nav-item a i { font-size: 1rem; }
        .sidebar-footer { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border); }

        .main { margin-left: 220px; padding: 2rem; }
        .page-title { font-size: 1.2rem; font-weight: 700; letter-spacing: -.5px; margin-bottom: .25rem; }

        .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.25rem; }
        .stat-label { font-size: .75rem; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
        .stat-val { font-size: 1.75rem; font-weight: 800; letter-spacing: -1px; }

        .search-input { border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: .85rem; }
        .search-input:focus { outline: none; border-color: #888; }
        .alert-success-sm { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; border-radius: 8px; padding: 8px 14px; font-size: .85rem; }
        .alert-warn-sm { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; border-radius: 8px; padding: 8px 14px; font-size: .85rem; }

        .order-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 1rem; }
        .order-head { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
        .order-item-line { font-size: .85rem; color: #555; }
        .btn-confirm { background: #16a34a; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: .8rem; display: inline-flex; align-items: center; gap: 5px; }
        .btn-confirm:hover { background: #15803d; color: #fff; }
        .btn-confirm:disabled { background: #cbd5e1; cursor: not-allowed; }
        .status-select { border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; font-size: .8rem; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">⚙ TokoKu Admin</div>
    <nav>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="index.php">
                    <i class="bi bi-box-seam"></i> Produk
                </a>
            </li>
            <li class="nav-item">
                <a href="kategori.php">
                    <i class="bi bi-tags"></i> Kategori
                </a>
            </li>
            <li class="nav-item">
                <a href="pesanan.php" class="active">
                    <i class="bi bi-receipt"></i> Pesanan
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="d-flex align-items-center gap-2 text-decoration-none" style="color:#999;font-size:.8rem">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="page-title">Pesanan</h1>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-receipt"></i> Total pesanan</div>
                <div class="stat-val"><?= $total_pesanan ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-hourglass-split"></i> Menunggu konfirmasi</div>
                <div class="stat-val" style="color:#d97706"><?= $total_menunggu ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-truck"></i> Sedang diproses</div>
                <div class="stat-val" style="color:#2563eb"><?= $total_diproses ?></div>
            </div>
        </div>
    </div>

    <!-- NOTIF -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'confirmed'): ?>
            <div class="alert-success-sm mb-3">✅ Pembayaran berhasil dikonfirmasi. Status pesanan diperbarui menjadi <strong>Dikemas</strong>.</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert-success-sm mb-3">✅ Status pesanan berhasil diperbarui.</div>
        <?php elseif ($_GET['msg'] === 'invalid'): ?>
            <div class="alert-warn-sm mb-3">⚠️ Aksi tidak valid atau pesanan sudah diproses sebelumnya.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- FILTER -->
    <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
        <input type="text" name="q" class="search-input" style="width:260px" placeholder="🔍 Cari kode / nama pemesan..." value="<?= htmlspecialchars($search) ?>">
        <select name="status" class="search-input" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <?php foreach ($statusOptions as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-dark" style="border-radius:8px">Terapkan</button>
        <?php if ($search || $status_filter): ?>
            <a href="pesanan.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">Reset</a>
        <?php endif; ?>
    </form>

    <!-- LIST PESANAN -->
    <?php if (empty($orders)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-receipt" style="font-size:2.5rem;"></i>
            <p class="mt-3">Tidak ada pesanan ditemukan.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $o): ?>
        <?php
            $stmtItems->execute([$o['id_order']]);
            $its = $stmtItems->fetchAll();
        ?>
        <div class="order-card">
            <div class="order-head">
                <div>
                    <div class="fw-bold" style="font-size:.95rem"><?= htmlspecialchars($o['kode_pesanan']) ?></div>
                    <div class="text-muted" style="font-size:.78rem">
                        <i class="bi bi-person"></i> <?= htmlspecialchars($o['nama_user']) ?> (<?= htmlspecialchars($o['username']) ?>)
                        &middot; <?= date('d M Y, H:i', strtotime($o['created_at'])) ?>
                    </div>
                </div>
                <?= statusBadgeAdmin($o['status']) ?>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <?php foreach ($its as $it): ?>
                        <div class="order-item-line">• <?= htmlspecialchars($it['nama_produk']) ?> × <?= $it['qty'] ?> — <?= formatRupiah($it['subtotal']) ?></div>
                    <?php endforeach; ?>
                    <div class="text-muted mt-2" style="font-size:.78rem">
                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($o['nama_penerima']) ?>, <?= htmlspecialchars($o['no_hp']) ?><br>
                        <?= htmlspecialchars($o['alamat_pengiriman']) ?><br>
                        <i class="bi bi-truck"></i> <?= htmlspecialchars($o['label_pengiriman']) ?>
                        &middot; <i class="bi bi-cash-coin"></i> <?= htmlspecialchars($o['metode_pembayaran']) ?>
                        <?php if (!empty($o['catatan'])): ?>
                            <br><i class="bi bi-chat-left-text"></i> Catatan: <?= htmlspecialchars($o['catatan']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div style="font-size:.8rem" class="text-muted">Subtotal: <?= formatRupiah($o['subtotal_produk']) ?></div>
                    <div style="font-size:.8rem" class="text-muted">Ongkir: <?= formatRupiah($o['ongkir']) ?></div>
                    <div class="fw-bold mb-3" style="font-size:1rem">Total: <?= formatRupiah($o['total_bayar']) ?></div>

                    <div class="d-flex flex-column gap-2 align-items-md-end">
                        <?php if ($o['status'] === 'Menunggu Konfirmasi'): ?>
                            <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran untuk pesanan <?= htmlspecialchars($o['kode_pesanan']) ?>?');">
                                <input type="hidden" name="id_order" value="<?= $o['id_order'] ?>">
                                <button type="submit" name="confirm_payment" class="btn-confirm">
                                    <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="id_order" value="<?= $o['id_order'] ?>">
                                <select name="status_baru" class="status-select">
                                    <?php foreach ($statusOptions as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-dark" style="border-radius:8px">Simpan</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
