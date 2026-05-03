<?php
session_start();
require_once 'includes/config.php';
requireLogin();

$db = getDB();
$jadwal_id = (int)($_GET['jadwal_id'] ?? 0);
$error     = '';

if (!$jadwal_id) {
    header("Location: index.php");
    exit();
}

// Ambil data jadwal
$stmt = $db->prepare("
    SELECT j.*, k.nama_kapal, k.kode_kapal, r.asal, r.tujuan, r.estimasi_jam
    FROM jadwal j
    JOIN kapal k ON j.kapal_id = k.id
    JOIN rute r ON j.rute_id = r.id
    WHERE j.id = ? AND j.status = 'aktif'
");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$jadwal) {
    header("Location: index.php?error=jadwal_tidak_ditemukan");
    exit();
}

// Proses form booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penumpang = trim($_POST['nama_penumpang'] ?? '');
    $nik            = trim($_POST['nik'] ?? '');
    $telepon        = trim($_POST['telepon'] ?? '');
    $kelas          = $_POST['kelas'] ?? '';
    $jumlah_tiket   = (int)($_POST['jumlah_tiket'] ?? 1);

    // Validasi
    if (empty($nama_penumpang) || empty($nik) || empty($telepon) || empty($kelas)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!in_array($kelas, ['ekonomi', 'bisnis', 'vip'])) {
        $error = 'Kelas tidak valid!';
    } elseif (strlen($nik) !== 16 || !ctype_digit($nik)) {
        $error = 'NIK harus 16 digit angka!';
    } elseif ($jumlah_tiket < 1 || $jumlah_tiket > 10) {
        $error = 'Jumlah tiket antara 1-10!';
    } else {
        // Cek ketersediaan kursi
        $field_sisa = "sisa_kursi_$kelas";
        if ($jadwal[$field_sisa] < $jumlah_tiket) {
            $error = "Kursi $kelas tidak mencukupi! Tersisa: " . $jadwal[$field_sisa];
        } else {
            // Hitung total
            $field_harga = "harga_$kelas";
            $total_harga = $jadwal[$field_harga] * $jumlah_tiket;

            // Generate kode booking
            $kode_booking = 'NF-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

            // Simpan pemesanan
            $ins = $db->prepare("
                INSERT INTO pemesanan (kode_booking, user_id, jadwal_id, nama_penumpang, nik, telepon, kelas, jumlah_tiket, total_harga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $user_id = $_SESSION['user_id'];
            $ins->bind_param("siissssid", $kode_booking, $user_id, $jadwal_id, $nama_penumpang, $nik, $telepon, $kelas, $jumlah_tiket, $total_harga);

            if ($ins->execute()) {
                $pemesanan_id = $db->insert_id;

                // Kurangi sisa kursi
                $upd = $db->prepare("UPDATE jadwal SET $field_sisa = $field_sisa - ? WHERE id = ?");
                $upd->bind_param("ii", $jumlah_tiket, $jadwal_id);
                $upd->execute();
                $upd->close();

                $ins->close();
                $db->close();

                header("Location: pembayaran.php?pemesanan_id=$pemesanan_id");
                exit();
            } else {
                $error = 'Gagal menyimpan pemesanan. Coba lagi!';
            }
            $ins->close();
        }
    }
}

$db->close();

// Hitung harga berdasarkan kelas terpilih
$kelas_sel = $_POST['kelas'] ?? 'ekonomi';
$harga_map = [
    'ekonomi' => $jadwal['harga_ekonomi'],
    'bisnis'  => $jadwal['harga_bisnis'],
    'vip'     => $jadwal['harga_vip'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Tiket - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<div class="container section">

    <!-- Steps -->
    <div style="background:white; border-radius:16px; padding:24px; margin-bottom:24px; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
        <div class="steps">
            <div class="step active">
                <div class="step-num">1</div>
                <div class="step-label">Data Penumpang</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-label">Pembayaran</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-label">Konfirmasi</div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start;">

        <!-- Form Booking -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h2>🎫 Data Pemesanan Tiket</h2>
                </div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="formBooking">
                        <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #e8eef5;">
                            👤 Data Penumpang
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap Penumpang</label>
                            <input type="text" name="nama_penumpang" class="form-control"
                                   placeholder="Sesuai KTP/identitas"
                                   value="<?= htmlspecialchars($_POST['nama_penumpang'] ?? $_SESSION['nama']) ?>" required>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">NIK (16 Digit)</label>
                                <input type="text" name="nik" class="form-control"
                                       placeholder="1234567890123456"
                                       maxlength="16"
                                       value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="telepon" class="form-control"
                                       placeholder="08xxxxxxxxxx"
                                       value="<?= htmlspecialchars($_POST['telepon'] ?? $_SESSION['email']) ?>" required>
                            </div>
                        </div>

                        <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin:20px 0 16px; padding-bottom:10px; border-bottom:2px solid #e8eef5;">
                            🪑 Pilih Kelas & Tiket
                        </h3>

                        <!-- Pilih Kelas -->
                        <div class="form-group">
                            <label class="form-label">Kelas Penumpang</label>
                            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">
                                <?php
                                $kelas_info = [
                                    'ekonomi' => ['🪑', 'Ekonomi', 'Fasilitas standar'],
                                    'bisnis'  => ['💺', 'Bisnis',  'Kursi reclining, AC'],
                                    'vip'     => ['👑', 'VIP',    'Kabin privat, makan'],
                                ];
                                foreach ($kelas_info as $k => $info):
                                    $sel = ($kelas_sel == $k) ? 'selected' : '';
                                ?>
                                    <label style="cursor:pointer;">
                                        <input type="radio" name="kelas" value="<?= $k ?>"
                                               <?= $kelas_sel == $k ? 'checked' : '' ?>
                                               onchange="updateHarga()" style="display:none;">
                                        <div class="harga-item <?= $sel ?>" onclick="selectKelas('<?= $k ?>')">
                                            <div style="font-size:1.5rem;"><?= $info[0] ?></div>
                                            <div class="kelas"><?= $info[1] ?></div>
                                            <div class="harga"><?= formatRupiah($harga_map[$k]) ?></div>
                                            <div class="kursi"><?= $jadwal["sisa_kursi_$k"] ?> tersisa</div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jumlah Tiket</label>
                            <select name="jumlah_tiket" class="form-control" onchange="updateHarga()">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"
                                            <?= (($_POST['jumlah_tiket'] ?? 1) == $i) ? 'selected' : '' ?>>
                                        <?= $i ?> Tiket
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div style="background:linear-gradient(135deg,#e8f4fd,#d4edff); border-radius:12px; padding:16px; margin-bottom:20px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:#1a3a5c;">
                                <span>Harga per tiket:</span>
                                <span id="harga-per-tiket"><?= formatRupiah($harga_map[$kelas_sel]) ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1.1rem; color:#1e6fa8; margin-top:8px;">
                                <span>Total Harga:</span>
                                <span id="total-harga"><?= formatRupiah($harga_map[$kelas_sel]) ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full btn-lg">
                            Lanjut ke Pembayaran →
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Jadwal -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3>📋 Detail Perjalanan</h3>
                </div>
                <div class="card-body">
                    <div style="text-align:center; padding:16px 0; border-bottom:1px solid #e8eef5;">
                        <div style="font-size:1.8rem;">⛴️</div>
                        <div style="font-weight:700; color:#0a1628; font-size:1.05rem;"><?= htmlspecialchars($jadwal['nama_kapal']) ?></div>
                        <div style="font-size:0.8rem; color:#8fa3b8;"><?= htmlspecialchars($jadwal['kode_kapal']) ?></div>
                    </div>

                    <div style="padding:16px 0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e8eef5; font-size:0.9rem;">
                            <span style="color:#8fa3b8;">Rute</span>
                            <span style="font-weight:600; text-align:right; font-size:0.85rem;">
                                <?= htmlspecialchars($jadwal['asal']) ?> →<br>
                                <?= htmlspecialchars($jadwal['tujuan']) ?>
                            </span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e8eef5; font-size:0.9rem;">
                            <span style="color:#8fa3b8;">Tanggal</span>
                            <span style="font-weight:600;"><?= date('d M Y', strtotime($jadwal['tanggal_berangkat'])) ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e8eef5; font-size:0.9rem;">
                            <span style="color:#8fa3b8;">Berangkat</span>
                            <span style="font-weight:600; color:#1e6fa8;"><?= substr($jadwal['jam_berangkat'], 0, 5) ?> WIB</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:0.9rem;">
                            <span style="color:#8fa3b8;">Estimasi</span>
                            <span style="font-weight:600;"><?= $jadwal['estimasi_jam'] ?> Jam</span>
                        </div>
                    </div>

                    <div style="background:#f0f4f9; border-radius:10px; padding:12px; font-size:0.82rem; color:#8fa3b8;">
                        ℹ️ Harap tiba di pelabuhan minimal 2 jam sebelum keberangkatan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Sistem Pemesanan Tiket Kapal</p>
</div>

<script>
const hargaData = {
    ekonomi: <?= $jadwal['harga_ekonomi'] ?>,
    bisnis:  <?= $jadwal['harga_bisnis'] ?>,
    vip:     <?= $jadwal['harga_vip'] ?>
};

let selectedKelas = '<?= $kelas_sel ?>';

function selectKelas(kelas) {
    selectedKelas = kelas;
    document.querySelectorAll('input[name="kelas"]').forEach(r => {
        r.checked = (r.value === kelas);
    });
    document.querySelectorAll('.harga-item').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.querySelector('.harga-item').classList.add('selected');
    updateHarga();
}

function updateHarga() {
    const jumlah = parseInt(document.querySelector('select[name="jumlah_tiket"]').value);
    const harga  = hargaData[selectedKelas] || 0;
    const total  = harga * jumlah;

    document.getElementById('harga-per-tiket').textContent = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('total-harga').textContent     = 'Rp ' + total.toLocaleString('id-ID');
}
</script>

</body>
</html>
