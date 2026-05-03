<?php
session_start();
require_once 'includes/config.php';
requireLogin();

$db           = getDB();
$pemesanan_id = (int)($_GET['pemesanan_id'] ?? 0);

if (!$pemesanan_id) {
    header("Location: index.php");
    exit();
}

$stmt = $db->prepare("
    SELECT p.*, j.tanggal_berangkat, j.jam_berangkat, j.jam_tiba,
           k.nama_kapal, k.kode_kapal, r.asal, r.tujuan, r.estimasi_jam,
           py.metode_pembayaran, py.bank_tujuan, py.nomor_rekening, py.jumlah_bayar, py.status AS status_bayar
    FROM pemesanan p
    JOIN jadwal j ON p.jadwal_id = j.id
    JOIN kapal k ON j.kapal_id = k.id
    JOIN rute r ON j.rute_id = r.id
    LEFT JOIN pembayaran py ON py.pemesanan_id = p.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $pemesanan_id, $_SESSION['user_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$db->close();

if (!$data) {
    header("Location: riwayat.php");
    exit();
}

$metode_labels = [
    'transfer_bank'  => 'Transfer Bank',
    'kartu_kredit'   => 'Kartu Kredit/Debit',
    'dompet_digital' => 'Dompet Digital',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pemesanan - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            70%  { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-icon { animation: popIn 0.5s ease forwards; }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<div class="container section">

    <!-- Steps -->
    <div style="background:white; border-radius:16px; padding:24px; margin-bottom:24px; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
        <div class="steps">
            <div class="step done">
                <div class="step-num">✓</div>
                <div class="step-label">Data Penumpang</div>
            </div>
            <div class="step done">
                <div class="step-num">✓</div>
                <div class="step-label">Pembayaran</div>
            </div>
            <div class="step active">
                <div class="step-num">3</div>
                <div class="step-label">Konfirmasi</div>
            </div>
        </div>
    </div>

    <!-- Success Banner -->
    <div style="background:linear-gradient(135deg,#0a1628,#1a3a5c); border-radius:20px; padding:40px; text-align:center; margin-bottom:24px; color:white;">
        <div class="success-icon" style="font-size:5rem; margin-bottom:16px;">🎉</div>
        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; margin-bottom:8px;">
            Pemesanan Berhasil!
        </h1>
        <p style="color:#8fa3b8; margin-bottom:20px;">
            Tiket Anda telah dipesan. Pembayaran sedang diverifikasi.
        </p>
        <div style="background:rgba(77,212,231,0.15); border:1px solid rgba(77,212,231,0.3); border-radius:12px; padding:14px 24px; display:inline-block;">
            <div style="font-size:0.8rem; color:#8fa3b8; margin-bottom:4px;">Kode Booking Anda</div>
            <div style="font-size:1.8rem; font-weight:700; color:#4dd4e7; letter-spacing:3px; font-family:'Playfair Display',serif;">
                <?= htmlspecialchars($data['kode_booking']) ?>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

        <!-- Detail Tiket -->
        <div class="card">
            <div class="card-header">
                <h3>🎫 Detail Tiket</h3>
            </div>
            <div class="card-body">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid #e8eef5;">
                    <div style="font-size:2.5rem;">⛴️</div>
                    <div>
                        <div style="font-weight:700; color:#0a1628; font-size:1rem;"><?= htmlspecialchars($data['nama_kapal']) ?></div>
                        <div style="font-size:0.8rem; color:#8fa3b8;"><?= htmlspecialchars($data['kode_kapal']) ?></div>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                    <div style="text-align:center;">
                        <div style="font-size:1.6rem; font-weight:700; color:#1e6fa8; font-family:'Playfair Display',serif;">
                            <?= substr($data['jam_berangkat'], 0, 5) ?>
                        </div>
                        <div style="font-size:0.85rem; font-weight:600; color:#0a1628;"><?= htmlspecialchars($data['asal']) ?></div>
                    </div>
                    <div style="text-align:center; flex:1; color:#8fa3b8; font-size:0.8rem;">
                        ──── <?= $data['estimasi_jam'] ?> jam ────
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:1.6rem; font-weight:700; color:#1e6fa8; font-family:'Playfair Display',serif;">
                            <?= substr($data['jam_tiba'], 0, 5) ?>
                        </div>
                        <div style="font-size:0.85rem; font-weight:600; color:#0a1628;"><?= htmlspecialchars($data['tujuan']) ?></div>
                    </div>
                </div>

                <?php
                $rows = [
                    ['Tanggal', date('l, d F Y', strtotime($data['tanggal_berangkat']))],
                    ['Penumpang', htmlspecialchars($data['nama_penumpang'])],
                    ['NIK', htmlspecialchars($data['nik'])],
                    ['Kelas', '<span style="text-transform:capitalize; font-weight:700;">' . $data['kelas'] . '</span>'],
                    ['Jumlah Tiket', $data['jumlah_tiket'] . ' tiket'],
                    ['Total Harga', '<strong style="color:#1e6fa8;">' . formatRupiah($data['total_harga']) . '</strong>'],
                ];
                foreach ($rows as $r):
                ?>
                <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #e8eef5; font-size:0.9rem;">
                    <span style="color:#8fa3b8;"><?= $r[0] ?></span>
                    <span><?= $r[1] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status Pembayaran -->
        <div>
            <div class="card" style="margin-bottom:16px;">
                <div class="card-header">
                    <h3>💳 Status Pembayaran</h3>
                </div>
                <div class="card-body">
                    <?php
                    $status_icon  = ['menunggu' => '⏳', 'dikonfirmasi' => '✅', 'ditolak' => '❌'];
                    $status_color = ['menunggu' => '#f39c12', 'dikonfirmasi' => '#2ecc71', 'ditolak' => '#e74c3c'];
                    $status_label = ['menunggu' => 'Menunggu Verifikasi', 'dikonfirmasi' => 'Dikonfirmasi', 'ditolak' => 'Ditolak'];
                    $sb = $data['status_bayar'] ?? 'menunggu';
                    ?>

                    <div style="text-align:center; padding:20px 0;">
                        <div style="font-size:3rem;"><?= $status_icon[$sb] ?></div>
                        <div style="font-size:1rem; font-weight:700; color:<?= $status_color[$sb] ?>; margin-top:8px;">
                            <?= $status_label[$sb] ?>
                        </div>
                    </div>

                    <?php if ($data['metode_pembayaran']): ?>
                    <div style="background:#f0f4f9; border-radius:10px; padding:14px;">
                        <div style="font-size:0.85rem; color:#1a2940;">
                            <strong>Metode:</strong> <?= $metode_labels[$data['metode_pembayaran']] ?? '-' ?><br>
                            <strong>Bank/Platform:</strong> <?= htmlspecialchars($data['bank_tujuan'] ?? '-') ?><br>
                            <strong>No. Rekening:</strong> <?= htmlspecialchars($data['nomor_rekening'] ?? '-') ?><br>
                            <strong>Jumlah Dibayar:</strong> <?= formatRupiah($data['jumlah_bayar'] ?? 0) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:16px; background:#fff8e8; border-radius:10px; padding:12px; font-size:0.82rem; color:#9a6904; border-left:3px solid #f39c12;">
                        ℹ️ Verifikasi pembayaran dilakukan dalam 1x24 jam. Anda akan mendapat notifikasi melalui email.
                    </div>
                </div>
            </div>

            <!-- Aksi -->
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="riwayat.php" class="btn btn-primary btn-full">
                    📋 Lihat Riwayat Pemesanan
                </a>
                <a href="index.php" class="btn btn-outline btn-full">
                    🏠 Kembali ke Beranda
                </a>
                <button onclick="window.print()" class="btn btn-outline btn-full">
                    🖨️ Cetak Tiket
                </button>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Sistem Pemesanan Tiket Kapal</p>
</div>

</body>
</html>
