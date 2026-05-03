<?php
session_start();
require_once 'includes/config.php';

$db = getDB();

// Ambil rute untuk dropdown
$rute_query = $db->query("SELECT DISTINCT asal FROM rute ORDER BY asal");
$kota_asal  = [];
while ($r = $rute_query->fetch_assoc()) $kota_asal[] = $r['asal'];

$rute_query2 = $db->query("SELECT DISTINCT tujuan FROM rute ORDER BY tujuan");
$kota_tujuan = [];
while ($r = $rute_query2->fetch_assoc()) $kota_tujuan[] = $r['tujuan'];

// Filter pencarian
$asal    = $_GET['asal'] ?? '';
$tujuan  = $_GET['tujuan'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

$jadwal_list = [];
if ($asal && $tujuan && $tanggal) {
    $stmt = $db->prepare("
        SELECT j.*, k.nama_kapal, k.kode_kapal, r.asal, r.tujuan, r.estimasi_jam
        FROM jadwal j
        JOIN kapal k ON j.kapal_id = k.id
        JOIN rute r ON j.rute_id = r.id
        WHERE r.asal = ? AND r.tujuan = ? AND j.tanggal_berangkat = ? AND j.status = 'aktif'
        ORDER BY j.jam_berangkat
    ");
    $stmt->bind_param("sss", $asal, $tujuan, $tanggal);
    $stmt->execute();
    $jadwal_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Jadwal terbaru jika tidak ada pencarian
$jadwal_terbaru = [];
if (empty($asal)) {
    $res = $db->query("
        SELECT j.*, k.nama_kapal, r.asal, r.tujuan, r.estimasi_jam
        FROM jadwal j
        JOIN kapal k ON j.kapal_id = k.id
        JOIN rute r ON j.rute_id = r.id
        WHERE j.tanggal_berangkat >= CURDATE() AND j.status = 'aktif'
        ORDER BY j.tanggal_berangkat, j.jam_berangkat
        LIMIT 6
    ");
    $jadwal_terbaru = $res->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NusantaraFerry - Pesan Tiket Kapal Online</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="hero">
    <div class="hero-waves"></div>
    <div class="container">
        <h1>⛴️ Jelajahi Nusantara<br>dengan Kapal</h1>
        <p>Pesan tiket kapal antar pulau dengan mudah, cepat, dan aman</p>

        <!-- Search Form -->
        <div class="search-box">
            <form method="GET" action="index.php">
                <div class="search-grid">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">📍 Kota Asal</label>
                        <select name="asal" class="form-control" required>
                            <option value="">Pilih Asal</option>
                            <?php foreach ($kota_asal as $k): ?>
                                <option value="<?= $k ?>" <?= $asal == $k ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0">
                        <label class="form-label">🎯 Kota Tujuan</label>
                        <select name="tujuan" class="form-control" required>
                            <option value="">Pilih Tujuan</option>
                            <?php foreach ($kota_tujuan as $k): ?>
                                <option value="<?= $k ?>" <?= $tujuan == $k ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0">
                        <label class="form-label">📅 Tanggal Berangkat</label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= htmlspecialchars($tanggal) ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg" style="height:48px; margin-bottom:0">
                        🔍 Cari
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container section">

    <?php if ($asal && $tujuan && $tanggal): ?>
        <!-- HASIL PENCARIAN -->
        <h2 style="font-family:'Playfair Display',serif; margin-bottom:6px;">
            Hasil Pencarian
        </h2>
        <p style="color:#8fa3b8; margin-bottom:24px;">
            <?= htmlspecialchars($asal) ?> → <?= htmlspecialchars($tujuan) ?> |
            <?= date('d F Y', strtotime($tanggal)) ?>
        </p>

        <?php if (empty($jadwal_list)): ?>
            <div class="alert alert-warning">
                😔 Tidak ada jadwal tersedia untuk rute dan tanggal yang dipilih.
                <a href="index.php" style="color:inherit; font-weight:600;">← Cari lagi</a>
            </div>
        <?php else: ?>
            <?php foreach ($jadwal_list as $j): ?>
                <?php
                $jam_tiba_dt = strtotime($j['tanggal_berangkat'] . ' ' . $j['jam_berangkat'] . ' +' . $j['estimasi_jam'] . ' hours');
                ?>
                <div class="jadwal-card">
                    <div class="jadwal-header">
                        <div>
                            <div class="jadwal-kapal">⛴️ <?= htmlspecialchars($j['nama_kapal']) ?></div>
                            <div style="font-size:0.8rem; color:#8fa3b8;">
                                <?= date('l, d F Y', strtotime($j['tanggal_berangkat'])) ?>
                            </div>
                        </div>
                        <span class="badge badge-success">● Tersedia</span>
                    </div>

                    <div class="jadwal-route">
                        <div class="route-city">
                            <div class="city-time"><?= substr($j['jam_berangkat'], 0, 5) ?></div>
                            <div class="city-name"><?= htmlspecialchars($j['asal']) ?></div>
                        </div>
                        <div class="route-line">
                            <span class="duration">⏱ <?= $j['estimasi_jam'] ?> jam</span>
                        </div>
                        <div class="route-city">
                            <div class="city-time"><?= date('H:i', $jam_tiba_dt) ?></div>
                            <div class="city-name"><?= htmlspecialchars($j['tujuan']) ?></div>
                        </div>
                    </div>

                    <div class="harga-grid">
                        <div class="harga-item">
                            <div class="kelas">🪑 Ekonomi</div>
                            <div class="harga"><?= formatRupiah($j['harga_ekonomi']) ?></div>
                            <div class="kursi"><?= $j['sisa_kursi_ekonomi'] ?> kursi tersisa</div>
                        </div>
                        <div class="harga-item">
                            <div class="kelas">💺 Bisnis</div>
                            <div class="harga"><?= formatRupiah($j['harga_bisnis']) ?></div>
                            <div class="kursi"><?= $j['sisa_kursi_bisnis'] ?> kursi tersisa</div>
                        </div>
                        <div class="harga-item">
                            <div class="kelas">👑 VIP</div>
                            <div class="harga"><?= formatRupiah($j['harga_vip']) ?></div>
                            <div class="kursi"><?= $j['sisa_kursi_vip'] ?> kursi tersisa</div>
                        </div>
                    </div>

                    <div style="margin-top:16px; text-align:right;">
                        <?php if (isLoggedIn()): ?>
                            <a href="booking.php?jadwal_id=<?= $j['id'] ?>"
                               class="btn btn-primary">Pilih & Pesan →</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline">Login untuk Memesan</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>
        <!-- JADWAL TERBARU -->
        <h2 style="font-family:'Playfair Display',serif; margin-bottom:6px;">
            Jadwal Keberangkatan Terbaru
        </h2>
        <p style="color:#8fa3b8; margin-bottom:24px;">Pilih rute favorit Anda</p>

        <?php foreach ($jadwal_terbaru as $j): ?>
            <div class="jadwal-card">
                <div class="jadwal-header">
                    <div>
                        <div class="jadwal-kapal">⛴️ <?= htmlspecialchars($j['nama_kapal']) ?></div>
                        <div style="font-size:0.8rem; color:#8fa3b8;">
                            <?= date('l, d F Y', strtotime($j['tanggal_berangkat'])) ?>
                        </div>
                    </div>
                    <span class="badge badge-success">● Tersedia</span>
                </div>

                <div class="jadwal-route">
                    <div class="route-city">
                        <div class="city-time"><?= substr($j['jam_berangkat'], 0, 5) ?></div>
                        <div class="city-name"><?= htmlspecialchars($j['asal']) ?></div>
                    </div>
                    <div class="route-line">
                        <span class="duration">⏱ <?= $j['estimasi_jam'] ?> jam</span>
                    </div>
                    <div class="route-city">
                        <div class="city-time"><?= substr($j['jam_tiba'], 0, 5) ?></div>
                        <div class="city-name"><?= htmlspecialchars($j['tujuan']) ?></div>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:16px; border-top:1px solid #e8eef5;">
                    <div style="font-size:0.9rem; color:#8fa3b8;">
                        Mulai dari <strong style="color:#1e6fa8; font-size:1rem;">
                            <?= formatRupiah(min($j['harga_ekonomi'], $j['harga_bisnis'], $j['harga_vip'])) ?>
                        </strong>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <a href="booking.php?jadwal_id=<?= $j['id'] ?>" class="btn btn-primary btn-sm">Pesan →</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- FITUR UNGGULAN -->
    <?php if (!$asal): ?>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:40px;">
        <?php
        $fitur = [
            ['🔒','Aman & Terpercaya','Sistem pembayaran terenkripsi dan data pelanggan terlindungi'],
            ['⚡','Pemesanan Cepat','Proses booking selesai dalam hitungan menit'],
            ['📱','Mudah Digunakan','Interface yang ramah pengguna di semua perangkat'],
        ];
        foreach ($fitur as $f): ?>
            <div style="background:white; border-radius:16px; padding:24px; text-align:center; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
                <div style="font-size:2rem; margin-bottom:12px;"><?= $f[0] ?></div>
                <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin-bottom:8px;"><?= $f[1] ?></h3>
                <p style="font-size:0.85rem; color:#8fa3b8;"><?= $f[2] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Sistem Pemesanan Tiket Kapal | Tugas QA Testing</p>
</div>

</body>
</html>
