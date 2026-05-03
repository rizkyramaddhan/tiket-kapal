<?php
session_start();
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, j.tanggal_berangkat, j.jam_berangkat,
           k.nama_kapal, r.asal, r.tujuan,
           py.status AS status_bayar, py.metode_pembayaran
    FROM pemesanan p
    JOIN jadwal j ON p.jadwal_id = j.id
    JOIN kapal k ON j.kapal_id = k.id
    JOIN rute r ON j.rute_id = r.id
    LEFT JOIN pembayaran py ON py.pemesanan_id = p.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<div class="container section">
    <h2 style="font-family:'Playfair Display',serif; margin-bottom:6px;">📋 Riwayat Pemesanan</h2>
    <p style="color:#8fa3b8; margin-bottom:24px;">Semua tiket yang pernah Anda pesan</p>

    <?php if (empty($riwayat)): ?>
        <div style="text-align:center; padding:60px 20px; background:white; border-radius:16px; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
            <div style="font-size:4rem; margin-bottom:16px;">🎫</div>
            <h3 style="color:#0a1628;">Belum ada pemesanan</h3>
            <p style="color:#8fa3b8; margin:10px 0 24px;">Anda belum pernah memesan tiket kapal.</p>
            <a href="index.php" class="btn btn-primary">Pesan Tiket Sekarang</a>
        </div>
    <?php else: ?>
        <?php foreach ($riwayat as $r):
            $status_bayar = $r['status_bayar'] ?? 'belum_bayar';
            $badge_class = match($r['status']) {
                'dibayar'   => 'badge-success',
                'pending'   => 'badge-warning',
                'dibatalkan'=> 'badge-danger',
                default     => 'badge-info',
            };
            $status_label = match($r['status']) {
                'dibayar'   => '✅ Dibayar',
                'pending'   => '⏳ Menunggu',
                'dibatalkan'=> '❌ Dibatalkan',
                default     => $r['status'],
            };
        ?>
        <div style="background:white; border-radius:16px; padding:24px; margin-bottom:16px; box-shadow:0 4px 20px rgba(10,22,40,0.08); border-left:4px solid <?= $r['status'] == 'dibayar' ? '#2ecc71' : ($r['status'] == 'dibatalkan' ? '#e74c3c' : '#f39c12') ?>;">
            <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="font-size:0.75rem; color:#8fa3b8; margin-bottom:4px;">Kode Booking</div>
                    <div style="font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; color:#0a1628; letter-spacing:1px;">
                        <?= htmlspecialchars($r['kode_booking']) ?>
                    </div>
                    <div style="font-size:0.8rem; color:#8fa3b8; margin-top:4px;">
                        Dipesan: <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <span class="badge <?= $badge_class ?>"><?= $status_label ?></span>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:16px; margin:16px 0; padding:14px; background:#f0f4f9; border-radius:10px;">
                <div style="font-size:1.5rem;">⛴️</div>
                <div style="flex:1;">
                    <div style="font-weight:600; color:#0a1628;"><?= htmlspecialchars($r['nama_kapal']) ?></div>
                    <div style="font-size:0.85rem; color:#8fa3b8;">
                        <?= htmlspecialchars($r['asal']) ?> → <?= htmlspecialchars($r['tujuan']) ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.85rem; color:#8fa3b8;"><?= date('d M Y', strtotime($r['tanggal_berangkat'])) ?></div>
                    <div style="font-weight:600; color:#1e6fa8;"><?= substr($r['jam_berangkat'], 0, 5) ?> WIB</div>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; gap:20px; font-size:0.85rem;">
                    <div>
                        <span style="color:#8fa3b8;">Kelas:</span>
                        <strong style="text-transform:capitalize;"><?= $r['kelas'] ?></strong>
                    </div>
                    <div>
                        <span style="color:#8fa3b8;">Tiket:</span>
                        <strong><?= $r['jumlah_tiket'] ?></strong>
                    </div>
                    <div>
                        <span style="color:#8fa3b8;">Total:</span>
                        <strong style="color:#1e6fa8;"><?= formatRupiah($r['total_harga']) ?></strong>
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <?php if ($r['status'] === 'pending' && !$r['status_bayar']): ?>
                        <a href="pembayaran.php?pemesanan_id=<?= $r['id'] ?>" class="btn btn-gold btn-sm">
                            💳 Bayar Sekarang
                        </a>
                    <?php endif; ?>
                    <a href="konfirmasi.php?pemesanan_id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">
                        Detail
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Sistem Pemesanan Tiket Kapal</p>
</div>

</body>
</html>
