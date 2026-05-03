<?php
session_start();
require_once 'includes/config.php';
requireLogin();

$db           = getDB();
$pemesanan_id = (int)($_GET['pemesanan_id'] ?? 0);
$error        = '';
$success      = '';

if (!$pemesanan_id) {
    header("Location: index.php");
    exit();
}

// Ambil data pemesanan
$stmt = $db->prepare("
    SELECT p.*, j.tanggal_berangkat, j.jam_berangkat, k.nama_kapal, r.asal, r.tujuan
    FROM pemesanan p
    JOIN jadwal j ON p.jadwal_id = j.id
    JOIN kapal k ON j.kapal_id = k.id
    JOIN rute r ON j.rute_id = r.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $pemesanan_id, $_SESSION['user_id']);
$stmt->execute();
$pemesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pemesanan) {
    header("Location: riwayat.php");
    exit();
}

// Cek apakah sudah ada pembayaran
$cek = $db->prepare("SELECT id, status FROM pembayaran WHERE pemesanan_id = ? LIMIT 1");
$cek->bind_param("i", $pemesanan_id);
$cek->execute();
$bayar_exist = $cek->get_result()->fetch_assoc();
$cek->close();

// Proses form pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bayar_exist) {
    $metode      = $_POST['metode_pembayaran'] ?? '';
    $bank        = trim($_POST['bank_tujuan'] ?? '');
    $no_rek      = trim($_POST['nomor_rekening'] ?? '');
    $nama_pemilik= trim($_POST['nama_pemilik'] ?? '');
    $jumlah_bayar= (float)($_POST['jumlah_bayar'] ?? 0);
    $catatan     = trim($_POST['catatan'] ?? '');

    $valid_metode = ['transfer_bank', 'kartu_kredit', 'dompet_digital'];

    if (empty($metode) || !in_array($metode, $valid_metode)) {
        $error = 'Pilih metode pembayaran!';
    } elseif (empty($bank) || empty($no_rek) || empty($nama_pemilik)) {
        $error = 'Lengkapi informasi pembayaran!';
    } elseif ($jumlah_bayar < $pemesanan['total_harga']) {
        $error = 'Jumlah pembayaran kurang dari total tagihan!';
    } else {
        // Handle file upload bukti transfer
        $bukti = null;
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $ext_allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext         = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_allowed)) {
                $error = 'Format file tidak didukung (jpg, jpeg, png, pdf)!';
            } elseif ($_FILES['bukti_transfer']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran file maksimal 2MB!';
            } else {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $bukti = 'bukti_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $bukti);
            }
        }

        if (!$error) {
            $ins = $db->prepare("
                INSERT INTO pembayaran (pemesanan_id, metode_pembayaran, bank_tujuan, nomor_rekening, nama_pemilik, jumlah_bayar, bukti_transfer, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param("issssdss", $pemesanan_id, $metode, $bank, $no_rek, $nama_pemilik, $jumlah_bayar, $bukti, $catatan);

            if ($ins->execute()) {
                // Update status pemesanan
                $upd = $db->prepare("UPDATE pemesanan SET status = 'dibayar' WHERE id = ?");
                $upd->bind_param("i", $pemesanan_id);
                $upd->execute();
                $upd->close();

                $ins->close();
                $db->close();
                header("Location: konfirmasi.php?pemesanan_id=$pemesanan_id");
                exit();
            } else {
                $error = 'Gagal menyimpan pembayaran!';
            }
            $ins->close();
        }
    }
}

$db->close();

$metode_labels = [
    'transfer_bank'  => 'Transfer Bank',
    'kartu_kredit'   => 'Kartu Kredit/Debit',
    'dompet_digital' => 'Dompet Digital',
];
$bank_options = [
    'BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB Niaga', 'Permata', 'BSI',
    'GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
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
            <div class="step <?= $bayar_exist ? 'done' : 'active' ?>">
                <div class="step-num"><?= $bayar_exist ? '✓' : '2' ?></div>
                <div class="step-label">Pembayaran</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-label">Konfirmasi</div>
            </div>
        </div>
    </div>

    <?php if ($bayar_exist): ?>
        <div class="alert alert-warning">
            ⚠️ Pembayaran sudah dikirim dan menunggu konfirmasi admin.
            <a href="riwayat.php" class="btn btn-primary btn-sm" style="margin-left:12px;">Lihat Riwayat</a>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start;">

        <!-- Form Pembayaran -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h2>💳 Informasi Pembayaran</h2>
                </div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (!$bayar_exist): ?>
                    <form method="POST" action="" enctype="multipart/form-data">

                        <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin-bottom:16px;">
                            💳 Pilih Metode Pembayaran
                        </h3>

                        <div onclick="selectMetode('transfer_bank')" id="m-transfer_bank"
                             class="payment-method <?= ($_POST['metode_pembayaran']??'') == 'transfer_bank' ? 'selected':'' ?>">
                            <div class="payment-icon" style="background:#e8f4fd;">🏦</div>
                            <div>
                                <h4>Transfer Bank</h4>
                                <p>BCA, BNI, BRI, Mandiri, dan bank lainnya</p>
                            </div>
                        </div>

                        <div onclick="selectMetode('kartu_kredit')" id="m-kartu_kredit"
                             class="payment-method <?= ($_POST['metode_pembayaran']??'') == 'kartu_kredit' ? 'selected':'' ?>">
                            <div class="payment-icon" style="background:#fff4e8;">💳</div>
                            <div>
                                <h4>Kartu Kredit / Debit</h4>
                                <p>Visa, Mastercard, semua kartu bank</p>
                            </div>
                        </div>

                        <div onclick="selectMetode('dompet_digital')" id="m-dompet_digital"
                             class="payment-method <?= ($_POST['metode_pembayaran']??'') == 'dompet_digital' ? 'selected':'' ?>">
                            <div class="payment-icon" style="background:#e8f8e8;">📱</div>
                            <div>
                                <h4>Dompet Digital</h4>
                                <p>GoPay, OVO, DANA, ShopeePay, LinkAja</p>
                            </div>
                        </div>

                        <input type="hidden" name="metode_pembayaran" id="metode_val"
                               value="<?= htmlspecialchars($_POST['metode_pembayaran'] ?? '') ?>">

                        <div id="detail-form" style="margin-top:24px; <?= empty($_POST['metode_pembayaran']) ? 'display:none' : '' ?>">

                            <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin-bottom:16px; padding-top:16px; border-top:2px solid #e8eef5;">
                                📝 Detail Pembayaran
                            </h3>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                <div class="form-group">
                                    <label class="form-label" id="label-bank">Bank / Platform</label>
                                    <select name="bank_tujuan" class="form-control">
                                        <option value="">Pilih Bank</option>
                                        <?php foreach ($bank_options as $b): ?>
                                            <option value="<?= $b ?>" <?= ($_POST['bank_tujuan'] ?? '') == $b ? 'selected' : '' ?>>
                                                <?= $b ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" id="label-norek">No. Rekening / Akun</label>
                                    <input type="text" name="nomor_rekening" class="form-control"
                                           placeholder="Nomor rekening"
                                           value="<?= htmlspecialchars($_POST['nomor_rekening'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nama Pemilik Rekening</label>
                                <input type="text" name="nama_pemilik" class="form-control"
                                       placeholder="Sesuai identitas rekening"
                                       value="<?= htmlspecialchars($_POST['nama_pemilik'] ?? $_SESSION['nama']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Jumlah yang Dibayar (Rp)</label>
                                <input type="number" name="jumlah_bayar" class="form-control"
                                       placeholder="<?= $pemesanan['total_harga'] ?>"
                                       value="<?= htmlspecialchars($_POST['jumlah_bayar'] ?? $pemesanan['total_harga']) ?>"
                                       min="<?= $pemesanan['total_harga'] ?>" required>
                                <small style="color:#8fa3b8; font-size:0.8rem;">
                                    Total tagihan: <?= formatRupiah($pemesanan['total_harga']) ?>
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Bukti Transfer (Opsional)</label>
                                <input type="file" name="bukti_transfer" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf"
                                       style="padding:10px;">
                                <small style="color:#8fa3b8; font-size:0.8rem;">Format: JPG, PNG, PDF. Maks 2MB</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Catatan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2"
                                          placeholder="Informasi tambahan..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                            </div>

                            <!-- Info Rekening Tujuan -->
                            <div style="background:linear-gradient(135deg,#f5f8ff,#e8f4fd); border-radius:12px; padding:16px; margin-bottom:20px; border-left:4px solid #1e6fa8;">
                                <strong style="color:#0a1628; font-size:0.9rem;">📌 Rekening Tujuan Pembayaran:</strong>
                                <div style="margin-top:8px; font-size:0.85rem; color:#1a3a5c;">
                                    <div><strong>Bank BCA</strong> — No. 1234-5678-9012</div>
                                    <div><strong>Bank BNI</strong> — No. 0987-6543-2100</div>
                                    <div style="margin-top:4px; color:#8fa3b8;">a.n. PT Nusantara Ferry Indonesia</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gold btn-full btn-lg">
                                💳 Konfirmasi Pembayaran →
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px 20px;">
                            <div style="font-size:3rem; margin-bottom:16px;">⏳</div>
                            <h3 style="color:#0a1628;">Menunggu Konfirmasi</h3>
                            <p style="color:#8fa3b8; margin-top:8px;">
                                Pembayaran Anda sedang diverifikasi oleh admin.<br>
                                Proses verifikasi maksimal 1x24 jam.
                            </p>
                            <a href="riwayat.php" class="btn btn-primary" style="margin-top:20px;">
                                📋 Lihat Riwayat Pemesanan
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ringkasan Pemesanan -->
        <div>
            <div class="summary-box">
                <h3 style="font-family:'Playfair Display',serif; margin-bottom:20px; font-size:1.1rem;">
                    🎫 Ringkasan Pemesanan
                </h3>
                <div class="summary-row">
                    <span>Kode Booking</span>
                    <strong style="color:#4dd4e7;"><?= htmlspecialchars($pemesanan['kode_booking']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Penumpang</span>
                    <span><?= htmlspecialchars($pemesanan['nama_penumpang']) ?></span>
                </div>
                <div class="summary-row">
                    <span>Rute</span>
                    <span style="text-align:right; font-size:0.85rem;">
                        <?= htmlspecialchars($pemesanan['asal']) ?><br>→ <?= htmlspecialchars($pemesanan['tujuan']) ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Kapal</span>
                    <span><?= htmlspecialchars($pemesanan['nama_kapal']) ?></span>
                </div>
                <div class="summary-row">
                    <span>Tanggal</span>
                    <span><?= date('d M Y', strtotime($pemesanan['tanggal_berangkat'])) ?></span>
                </div>
                <div class="summary-row">
                    <span>Jam</span>
                    <span><?= substr($pemesanan['jam_berangkat'], 0, 5) ?> WIB</span>
                </div>
                <div class="summary-row">
                    <span>Kelas</span>
                    <span style="text-transform:capitalize;"><?= $pemesanan['kelas'] ?></span>
                </div>
                <div class="summary-row">
                    <span>Jumlah Tiket</span>
                    <span><?= $pemesanan['jumlah_tiket'] ?> tiket</span>
                </div>
                <div class="summary-total">
                    <span>TOTAL</span>
                    <span><?= formatRupiah($pemesanan['total_harga']) ?></span>
                </div>
            </div>

            <div style="background:white; border-radius:16px; padding:16px; margin-top:16px; font-size:0.82rem; color:#8fa3b8;">
                🔒 Transaksi Anda dilindungi enkripsi SSL 256-bit
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Sistem Pemesanan Tiket Kapal</p>
</div>

<script>
function selectMetode(m) {
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
    document.getElementById('m-' + m).classList.add('selected');
    document.getElementById('metode_val').value = m;
    document.getElementById('detail-form').style.display = 'block';
}
</script>

</body>
</html>
