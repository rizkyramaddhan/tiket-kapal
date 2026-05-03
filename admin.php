<?php
session_start();
require_once 'includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$db  = getDB();
$tab = $_GET['tab'] ?? 'dashboard';
$msg = '';
$msg_type = 'success';

// ─────────────────────────────────────────────
// AKSI PEMBAYARAN
// ─────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'konfirmasi_bayar') {
    $pid = (int)$_POST['pembayaran_id'];
    $db->query("UPDATE pembayaran SET status='dikonfirmasi', confirmed_at=NOW() WHERE id=$pid");
    $db->query("UPDATE pemesanan p JOIN pembayaran py ON py.pemesanan_id=p.id SET p.status='dibayar' WHERE py.id=$pid");
    $msg = 'Pembayaran berhasil dikonfirmasi!';
}

if (($_POST['action'] ?? '') === 'tolak_bayar') {
    $pid = (int)$_POST['pembayaran_id'];
    $db->query("UPDATE pembayaran SET status='ditolak' WHERE id=$pid");
    $db->query("UPDATE pemesanan p JOIN pembayaran py ON py.pemesanan_id=p.id SET p.status='pending' WHERE py.id=$pid");
    $msg = 'Pembayaran ditolak.';
    $msg_type = 'warning';
}

// ─────────────────────────────────────────────
// AKSI JADWAL — CREATE
// ─────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'tambah_jadwal') {
    $kapal_id          = (int)$_POST['kapal_id'];
    $rute_id           = (int)$_POST['rute_id'];
    $tanggal_berangkat = $_POST['tanggal_berangkat'];
    $jam_berangkat     = $_POST['jam_berangkat'];
    $jam_tiba          = $_POST['jam_tiba'];
    $harga_ekonomi     = (float)$_POST['harga_ekonomi'];
    $harga_bisnis      = (float)$_POST['harga_bisnis'];
    $harga_vip         = (float)$_POST['harga_vip'];
    $kursi_ekonomi     = (int)$_POST['sisa_kursi_ekonomi'];
    $kursi_bisnis      = (int)$_POST['sisa_kursi_bisnis'];
    $kursi_vip         = (int)$_POST['sisa_kursi_vip'];
    $status_jadwal     = $_POST['status_jadwal'];

    $valid_status = ['aktif', 'penuh', 'dibatalkan'];
    if (!$kapal_id || !$rute_id || !$tanggal_berangkat || !$jam_berangkat || !$jam_tiba) {
        $msg = 'Semua field wajib diisi!';
        $msg_type = 'danger';
    } elseif (!in_array($status_jadwal, $valid_status)) {
        $msg = 'Status tidak valid!';
        $msg_type = 'danger';
    } else {
        $stmt = $db->prepare("
            INSERT INTO jadwal (kapal_id, rute_id, tanggal_berangkat, jam_berangkat, jam_tiba,
                harga_ekonomi, harga_bisnis, harga_vip,
                sisa_kursi_ekonomi, sisa_kursi_bisnis, sisa_kursi_vip, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssdddiiss",
            $kapal_id, $rute_id, $tanggal_berangkat, $jam_berangkat, $jam_tiba,
            $harga_ekonomi, $harga_bisnis, $harga_vip,
            $kursi_ekonomi, $kursi_bisnis, $kursi_vip, $status_jadwal
        );
        if ($stmt->execute()) {
            $msg = 'Jadwal berhasil ditambahkan!';
        } else {
            $msg = 'Gagal menambahkan jadwal: ' . $db->error;
            $msg_type = 'danger';
        }
        $stmt->close();
    }
    $tab = 'jadwal';
}

// ─────────────────────────────────────────────
// AKSI JADWAL — UPDATE
// ─────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'edit_jadwal') {
    $jid               = (int)$_POST['jadwal_id'];
    $kapal_id          = (int)$_POST['kapal_id'];
    $rute_id           = (int)$_POST['rute_id'];
    $tanggal_berangkat = $_POST['tanggal_berangkat'];
    $jam_berangkat     = $_POST['jam_berangkat'];
    $jam_tiba          = $_POST['jam_tiba'];
    $harga_ekonomi     = (float)$_POST['harga_ekonomi'];
    $harga_bisnis      = (float)$_POST['harga_bisnis'];
    $harga_vip         = (float)$_POST['harga_vip'];
    $kursi_ekonomi     = (int)$_POST['sisa_kursi_ekonomi'];
    $kursi_bisnis      = (int)$_POST['sisa_kursi_bisnis'];
    $kursi_vip         = (int)$_POST['sisa_kursi_vip'];
    $status_jadwal     = $_POST['status_jadwal'];

    $stmt = $db->prepare("
        UPDATE jadwal SET
            kapal_id=?, rute_id=?, tanggal_berangkat=?, jam_berangkat=?, jam_tiba=?,
            harga_ekonomi=?, harga_bisnis=?, harga_vip=?,
            sisa_kursi_ekonomi=?, sisa_kursi_bisnis=?, sisa_kursi_vip=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("iisssdddiissi",
        $kapal_id, $rute_id, $tanggal_berangkat, $jam_berangkat, $jam_tiba,
        $harga_ekonomi, $harga_bisnis, $harga_vip,
        $kursi_ekonomi, $kursi_bisnis, $kursi_vip, $status_jadwal, $jid
    );
    if ($stmt->execute()) {
        $msg = 'Jadwal berhasil diperbarui!';
    } else {
        $msg = 'Gagal memperbarui jadwal: ' . $db->error;
        $msg_type = 'danger';
    }
    $stmt->close();
    $tab = 'jadwal';
}

// ─────────────────────────────────────────────
// AKSI JADWAL — DELETE
// ─────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'hapus_jadwal') {
    $jid = (int)$_POST['jadwal_id'];
    $cek = $db->query("SELECT COUNT(*) as c FROM pemesanan WHERE jadwal_id=$jid")->fetch_assoc()['c'];
    if ($cek > 0) {
        $msg = "Jadwal tidak dapat dihapus karena sudah memiliki $cek data pemesanan!";
        $msg_type = 'danger';
    } else {
        $db->query("DELETE FROM jadwal WHERE id=$jid");
        $msg = 'Jadwal berhasil dihapus.';
        $msg_type = 'warning';
    }
    $tab = 'jadwal';
}

// ─────────────────────────────────────────────
// DASHBOARD STATS
// ─────────────────────────────────────────────
$stat_user    = $db->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$stat_booking = $db->query("SELECT COUNT(*) as c FROM pemesanan")->fetch_assoc()['c'];
$stat_bayar   = $db->query("SELECT COUNT(*) as c FROM pemesanan WHERE status='dibayar'")->fetch_assoc()['c'];
$stat_revenue = $db->query("SELECT SUM(total_harga) as total FROM pemesanan WHERE status='dibayar'")->fetch_assoc()['total'] ?? 0;
$stat_jadwal  = $db->query("SELECT COUNT(*) as c FROM jadwal WHERE status='aktif'")->fetch_assoc()['c'];

// ─────────────────────────────────────────────
// DATA TIAP TAB
// ─────────────────────────────────────────────
$pemesanan_list  = [];
$pembayaran_list = [];
$jadwal_list     = [];
$kapal_list      = [];
$rute_list       = [];

if ($tab === 'pemesanan') {
    $res = $db->query("
        SELECT p.*, u.nama as nama_user, j.tanggal_berangkat, j.jam_berangkat,
               k.nama_kapal, r.asal, r.tujuan,
               py.status AS status_bayar
        FROM pemesanan p
        JOIN users u ON p.user_id = u.id
        JOIN jadwal j ON p.jadwal_id = j.id
        JOIN kapal k ON j.kapal_id = k.id
        JOIN rute r ON j.rute_id = r.id
        LEFT JOIN pembayaran py ON py.pemesanan_id = p.id
        ORDER BY p.created_at DESC
    ");
    $pemesanan_list = $res->fetch_all(MYSQLI_ASSOC);
}

if ($tab === 'pembayaran') {
    $res = $db->query("
        SELECT py.*, p.kode_booking, p.total_harga, p.kelas, p.jumlah_tiket,
               u.nama as nama_user, r.asal, r.tujuan
        FROM pembayaran py
        JOIN pemesanan p ON py.pemesanan_id = p.id
        JOIN users u ON p.user_id = u.id
        JOIN jadwal j ON p.jadwal_id = j.id
        JOIN rute r ON j.rute_id = r.id
        ORDER BY py.created_at DESC
    ");
    $pembayaran_list = $res->fetch_all(MYSQLI_ASSOC);
}

if ($tab === 'jadwal') {
    $res = $db->query("
        SELECT j.*, k.nama_kapal, k.kode_kapal, r.asal, r.tujuan,
               (SELECT COUNT(*) FROM pemesanan pm WHERE pm.jadwal_id = j.id) as jumlah_pemesanan
        FROM jadwal j
        JOIN kapal k ON j.kapal_id = k.id
        JOIN rute r ON j.rute_id = r.id
        ORDER BY j.tanggal_berangkat DESC, j.jam_berangkat ASC
    ");
    $jadwal_list = $res->fetch_all(MYSQLI_ASSOC);
    $kapal_list  = $db->query("SELECT id, nama_kapal, kode_kapal FROM kapal ORDER BY nama_kapal")->fetch_all(MYSQLI_ASSOC);
    $rute_list   = $db->query("SELECT id, asal, tujuan FROM rute ORDER BY asal")->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(10,22,40,0.6); z-index: 1000;
            align-items: center; justify-content: center; padding: 16px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: white; border-radius: 16px;
            width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(10,22,40,0.3);
            animation: slideUp 0.25s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 24px; border-bottom: 2px solid #e8eef5;
            position: sticky; top: 0; background: white; z-index: 1;
        }
        .modal-header h3 { font-size: 1.1rem; font-weight: 700; color: #0a1628; margin: 0; }
        .modal-body { padding: 24px; }
        .modal-close { background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #8fa3b8; line-height: 1; }
        .modal-close:hover { color: #0a1628; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .section-label { font-size: 0.85rem; font-weight: 700; color: #0a1628; margin-bottom: 10px; border-top: 2px solid #e8eef5; padding-top: 14px; }
        .harga-kelas { display: flex; gap: 6px; flex-wrap: wrap; }
        .harga-tag { background: #f0f6ff; color: #1e6fa8; border-radius: 8px; padding: 3px 10px; font-size: 0.75rem; font-weight: 600; }
        .harga-tag.bisnis { background: #fff4e8; color: #c47f17; }
        .harga-tag.vip    { background: #fdf0ff; color: #8b3a9c; }
        .kursi-info { display: flex; gap: 6px; flex-wrap: wrap; }
        .kursi-dot { background: #e8f5e9; color: #2e7d32; border-radius: 6px; padding: 2px 8px; font-weight: 600; font-size: 0.75rem; }
        .kursi-dot.bisnis { background: #fff3e0; color: #e65100; }
        .kursi-dot.vip    { background: #f3e5f5; color: #6a1b9a; }
        .alert-warning { background: #fff8e1; color: #b26a00; border-left: 4px solid #f5a623; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; }
        .alert-danger  { background: #fff0f0; color: #c0392b; border-left: 4px solid #e74c3c; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<div class="container section">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-family:'Playfair Display',serif;">⚙️ Panel Administrator</h2>
            <p style="color:#8fa3b8;">Kelola sistem pemesanan tiket kapal</p>
        </div>
        <span class="badge badge-danger" style="font-size:0.9rem; padding:8px 16px;">👑 ADMIN</span>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <?= $msg_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div style="display:flex; gap:8px; margin-bottom:24px; background:white; padding:6px; border-radius:12px; box-shadow:0 2px 10px rgba(10,22,40,0.08);">
        <?php
        $tabs = [
            'dashboard'  => '📊 Dashboard',
            'jadwal'     => '🚢 Jadwal',
            'pemesanan'  => '🎫 Pemesanan',
            'pembayaran' => '💳 Pembayaran',
        ];
        foreach ($tabs as $k => $v): ?>
            <a href="?tab=<?= $k ?>"
               style="flex:1; text-align:center; padding:10px; border-radius:8px; font-size:0.9rem; font-weight:600; text-decoration:none; transition:all 0.2s;
                      <?= $tab == $k ? 'background:linear-gradient(135deg,#1e6fa8,#3ba3d4); color:white;' : 'color:#8fa3b8;' ?>">
                <?= $v ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ══════════════════════════════════════════
         DASHBOARD TAB
    ══════════════════════════════════════════ -->
    <?php if ($tab === 'dashboard'): ?>

        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:20px; margin-bottom:32px;">
            <?php
            $stats = [
                ['👥', 'Total Pengguna',   $stat_user,                 '#3ba3d4'],
                ['🚢', 'Jadwal Aktif',     $stat_jadwal,               '#8b3a9c'],
                ['🎫', 'Total Booking',    $stat_booking,              '#2ecc71'],
                ['✅', 'Booking Dibayar',  $stat_bayar,                '#e8a820'],
                ['💰', 'Total Pendapatan', formatRupiah($stat_revenue), '#e74c3c'],
            ];
            foreach ($stats as $s): ?>
                <div style="background:white; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
                    <div style="font-size:2rem; margin-bottom:8px;"><?= $s[0] ?></div>
                    <div style="font-size:0.75rem; color:#8fa3b8; font-weight:600; text-transform:uppercase;"><?= $s[1] ?></div>
                    <div style="font-size:1.3rem; font-weight:700; color:<?= $s[3] ?>; margin-top:6px;"><?= $s[2] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="background:white; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(10,22,40,0.08);">
            <h3 style="font-size:1rem; font-weight:700; color:#0a1628; margin-bottom:16px;">📌 Aksi Cepat</h3>
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px;">
                <a href="?tab=jadwal" class="btn btn-primary">🚢 Kelola Jadwal</a>
                <a href="?tab=pembayaran" class="btn btn-primary">💳 Verifikasi Pembayaran</a>
                <a href="?tab=pemesanan" class="btn btn-outline">🎫 Lihat Semua Booking</a>
                <a href="index.php" class="btn btn-outline">🏠 Kembali ke Beranda</a>
            </div>
        </div>

    <!-- ══════════════════════════════════════════
         JADWAL TAB
    ══════════════════════════════════════════ -->
    <?php elseif ($tab === 'jadwal'): ?>

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <div>
                <h3 style="font-size:1.1rem; font-weight:700; color:#0a1628; margin:0;">🚢 Manajemen Jadwal Tiket</h3>
                <p style="color:#8fa3b8; font-size:0.85rem; margin:4px 0 0;">Total <?= count($jadwal_list) ?> jadwal ditemukan</p>
            </div>
            <button onclick="bukaModalTambah()" class="btn btn-gold">＋ Tambah Jadwal</button>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kapal</th>
                                <th>Rute</th>
                                <th>Tanggal & Jam</th>
                                <th>Harga Tiket</th>
                                <th>Sisa Kursi</th>
                                <th>Booking</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jadwal_list)): ?>
                                <tr><td colspan="8" style="text-align:center; padding:40px; color:#8fa3b8;">
                                    Belum ada data jadwal. Klik "Tambah Jadwal" untuk mulai.
                                </td></tr>
                            <?php endif; ?>
                            <?php foreach ($jadwal_list as $j):
                                $badge_status = match($j['status']) {
                                    'aktif'      => 'badge-success',
                                    'penuh'      => 'badge-warning',
                                    'dibatalkan' => 'badge-danger',
                                    default      => 'badge-warning',
                                };
                            ?>
                            <tr>
                                <td>
                                    <strong style="font-size:0.9rem;"><?= htmlspecialchars($j['nama_kapal']) ?></strong><br>
                                    <span style="font-size:0.75rem; color:#8fa3b8;"><?= htmlspecialchars($j['kode_kapal']) ?></span>
                                </td>
                                <td style="font-size:0.85rem;">
                                    <strong><?= htmlspecialchars($j['asal']) ?></strong><br>
                                    <span style="color:#3ba3d4;">→ <?= htmlspecialchars($j['tujuan']) ?></span>
                                </td>
                                <td style="font-size:0.85rem;">
                                    <strong><?= date('d M Y', strtotime($j['tanggal_berangkat'])) ?></strong><br>
                                    <span style="color:#8fa3b8; font-size:0.78rem;">
                                        🛫 <?= substr($j['jam_berangkat'],0,5) ?> → 🛬 <?= substr($j['jam_tiba'],0,5) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="harga-kelas">
                                        <span class="harga-tag">Eko <?= formatRupiah($j['harga_ekonomi']) ?></span>
                                        <span class="harga-tag bisnis">Bis <?= formatRupiah($j['harga_bisnis']) ?></span>
                                        <span class="harga-tag vip">VIP <?= formatRupiah($j['harga_vip']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="kursi-info">
                                        <span class="kursi-dot"><?= $j['sisa_kursi_ekonomi'] ?> Eko</span>
                                        <span class="kursi-dot bisnis"><?= $j['sisa_kursi_bisnis'] ?> Bis</span>
                                        <span class="kursi-dot vip"><?= $j['sisa_kursi_vip'] ?> VIP</span>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <strong style="color:#1e6fa8; font-size:1rem;"><?= $j['jumlah_pemesanan'] ?></strong>
                                    <div style="font-size:0.72rem; color:#8fa3b8;">pemesanan</div>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_status ?>" style="text-transform:capitalize;">
                                        <?= $j['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <button onclick='bukaModalEdit(<?= htmlspecialchars(json_encode($j), ENT_QUOTES) ?>)'
                                                class="btn btn-primary btn-sm" title="Edit Jadwal">✏️</button>
                                        <?php if ($j['jumlah_pemesanan'] == 0): ?>
                                            <form method="POST" style="display:inline;"
                                                  onsubmit="return confirm('Hapus jadwal ini? Tindakan tidak dapat dibatalkan.')">
                                                <input type="hidden" name="action" value="hapus_jadwal">
                                                <input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus Jadwal">🗑️</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm" disabled
                                                    title="Tidak bisa dihapus — ada <?= $j['jumlah_pemesanan'] ?> pemesanan"
                                                    style="background:#f0f0f0; color:#bbb; cursor:not-allowed;">🗑️</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── MODAL TAMBAH ── -->
        <div class="modal-overlay" id="modalTambah">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>🚢 Tambah Jadwal Baru</h3>
                    <button class="modal-close" onclick="tutupModal('modalTambah')">✕</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="?tab=jadwal">
                        <input type="hidden" name="action" value="tambah_jadwal">

                        <div class="form-grid-2" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">🚢 Kapal</label>
                                <select name="kapal_id" class="form-control" required>
                                    <option value="">— Pilih Kapal —</option>
                                    <?php foreach ($kapal_list as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kapal']) ?> (<?= $k['kode_kapal'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🗺️ Rute</label>
                                <select name="rute_id" class="form-control" required>
                                    <option value="">— Pilih Rute —</option>
                                    <?php foreach ($rute_list as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['asal']) ?> → <?= htmlspecialchars($r['tujuan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">📅 Tanggal Berangkat</label>
                                <input type="date" name="tanggal_berangkat" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🕐 Jam Berangkat</label>
                                <input type="time" name="jam_berangkat" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🕐 Jam Tiba</label>
                                <input type="time" name="jam_tiba" class="form-control" required>
                            </div>
                        </div>

                        <p class="section-label">💰 Harga per Tiket (Rp)</p>
                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">Ekonomi</label>
                                <input type="number" name="harga_ekonomi" class="form-control" min="0" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bisnis</label>
                                <input type="number" name="harga_bisnis" class="form-control" min="0" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">VIP</label>
                                <input type="number" name="harga_vip" class="form-control" min="0" placeholder="0" required>
                            </div>
                        </div>

                        <p class="section-label">💺 Kapasitas Kursi</p>
                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">Ekonomi</label>
                                <input type="number" name="sisa_kursi_ekonomi" class="form-control" min="0" value="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bisnis</label>
                                <input type="number" name="sisa_kursi_bisnis" class="form-control" min="0" value="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">VIP</label>
                                <input type="number" name="sisa_kursi_vip" class="form-control" min="0" value="0" required>
                            </div>
                        </div>

                        <div class="form-group" style="border-top:2px solid #e8eef5; padding-top:14px;">
                            <label class="form-label">Status Jadwal</label>
                            <select name="status_jadwal" class="form-control">
                                <option value="aktif">✅ Aktif</option>
                                <option value="penuh">⚠️ Penuh</option>
                                <option value="dibatalkan">❌ Dibatalkan</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:12px; margin-top:20px;">
                            <button type="submit" class="btn btn-gold" style="flex:1;">🚢 Simpan Jadwal</button>
                            <button type="button" onclick="tutupModal('modalTambah')" class="btn btn-outline" style="flex:1;">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── MODAL EDIT ── -->
        <div class="modal-overlay" id="modalEdit">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>✏️ Edit Jadwal</h3>
                    <button class="modal-close" onclick="tutupModal('modalEdit')">✕</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="?tab=jadwal">
                        <input type="hidden" name="action" value="edit_jadwal">
                        <input type="hidden" name="jadwal_id" id="edit_jadwal_id">

                        <div class="form-grid-2" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">🚢 Kapal</label>
                                <select name="kapal_id" id="edit_kapal_id" class="form-control" required>
                                    <option value="">— Pilih Kapal —</option>
                                    <?php foreach ($kapal_list as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kapal']) ?> (<?= $k['kode_kapal'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🗺️ Rute</label>
                                <select name="rute_id" id="edit_rute_id" class="form-control" required>
                                    <option value="">— Pilih Rute —</option>
                                    <?php foreach ($rute_list as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['asal']) ?> → <?= htmlspecialchars($r['tujuan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">📅 Tanggal Berangkat</label>
                                <input type="date" name="tanggal_berangkat" id="edit_tanggal" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🕐 Jam Berangkat</label>
                                <input type="time" name="jam_berangkat" id="edit_jam_berangkat" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🕐 Jam Tiba</label>
                                <input type="time" name="jam_tiba" id="edit_jam_tiba" class="form-control" required>
                            </div>
                        </div>

                        <p class="section-label">💰 Harga per Tiket (Rp)</p>
                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">Ekonomi</label>
                                <input type="number" name="harga_ekonomi" id="edit_harga_ekonomi" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bisnis</label>
                                <input type="number" name="harga_bisnis" id="edit_harga_bisnis" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">VIP</label>
                                <input type="number" name="harga_vip" id="edit_harga_vip" class="form-control" min="0" required>
                            </div>
                        </div>

                        <p class="section-label">💺 Sisa Kursi</p>
                        <div class="form-grid-3" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label class="form-label">Ekonomi</label>
                                <input type="number" name="sisa_kursi_ekonomi" id="edit_kursi_ekonomi" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bisnis</label>
                                <input type="number" name="sisa_kursi_bisnis" id="edit_kursi_bisnis" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">VIP</label>
                                <input type="number" name="sisa_kursi_vip" id="edit_kursi_vip" class="form-control" min="0" required>
                            </div>
                        </div>

                        <div class="form-group" style="border-top:2px solid #e8eef5; padding-top:14px;">
                            <label class="form-label">Status Jadwal</label>
                            <select name="status_jadwal" id="edit_status" class="form-control">
                                <option value="aktif">✅ Aktif</option>
                                <option value="penuh">⚠️ Penuh</option>
                                <option value="dibatalkan">❌ Dibatalkan</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:12px; margin-top:20px;">
                            <button type="submit" class="btn btn-gold" style="flex:1;">💾 Simpan Perubahan</button>
                            <button type="button" onclick="tutupModal('modalEdit')" class="btn btn-outline" style="flex:1;">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <!-- ══════════════════════════════════════════
         PEMESANAN TAB
    ══════════════════════════════════════════ -->
    <?php elseif ($tab === 'pemesanan'): ?>

        <div class="card">
            <div class="card-header"><h2>🎫 Daftar Semua Pemesanan</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Booking</th><th>Penumpang</th><th>User</th>
                                <th>Rute</th><th>Tanggal</th><th>Kelas</th><th>Total</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pemesanan_list)): ?>
                                <tr><td colspan="8" style="text-align:center; padding:30px; color:#8fa3b8;">Belum ada data pemesanan</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pemesanan_list as $p):
                                $badge = match($p['status']) {
                                    'dibayar'    => 'badge-success',
                                    'dibatalkan' => 'badge-danger',
                                    default      => 'badge-warning',
                                }; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['kode_booking']) ?></strong></td>
                                <td><?= htmlspecialchars($p['nama_penumpang']) ?></td>
                                <td style="font-size:0.85rem; color:#8fa3b8;"><?= htmlspecialchars($p['nama_user']) ?></td>
                                <td style="font-size:0.82rem;"><?= htmlspecialchars($p['asal']) ?> → <?= htmlspecialchars($p['tujuan']) ?></td>
                                <td style="font-size:0.85rem;"><?= date('d M Y', strtotime($p['tanggal_berangkat'])) ?></td>
                                <td style="text-transform:capitalize;"><?= $p['kelas'] ?></td>
                                <td><strong><?= formatRupiah($p['total_harga']) ?></strong></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ══════════════════════════════════════════
         PEMBAYARAN TAB
    ══════════════════════════════════════════ -->
    <?php elseif ($tab === 'pembayaran'): ?>

        <div class="card">
            <div class="card-header"><h2>💳 Verifikasi Pembayaran</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Booking</th><th>Pelanggan</th><th>Rute</th>
                                <th>Metode</th><th>Bank</th><th>Jumlah Bayar</th>
                                <th>Tanggal</th><th>Status</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pembayaran_list)): ?>
                                <tr><td colspan="9" style="text-align:center; padding:30px; color:#8fa3b8;">Belum ada data pembayaran</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pembayaran_list as $p):
                                $badge = match($p['status']) {
                                    'dikonfirmasi' => 'badge-success',
                                    'ditolak'      => 'badge-danger',
                                    default        => 'badge-warning',
                                };
                                $metode_lbl = ['transfer_bank'=>'Transfer Bank','kartu_kredit'=>'Kartu Kredit','dompet_digital'=>'Dompet Digital'];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['kode_booking']) ?></strong></td>
                                <td><?= htmlspecialchars($p['nama_user']) ?></td>
                                <td style="font-size:0.82rem;"><?= htmlspecialchars($p['asal']) ?> → <?= htmlspecialchars($p['tujuan']) ?></td>
                                <td style="font-size:0.85rem;"><?= $metode_lbl[$p['metode_pembayaran']] ?? '-' ?></td>
                                <td><?= htmlspecialchars($p['bank_tujuan'] ?? '-') ?></td>
                                <td><strong><?= formatRupiah($p['jumlah_bayar']) ?></strong></td>
                                <td style="font-size:0.82rem;"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span></td>
                                <td>
                                    <?php if ($p['status'] === 'menunggu'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="pembayaran_id" value="<?= $p['id'] ?>">
                                            <button name="action" value="konfirmasi_bayar" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Konfirmasi pembayaran ini?')">✓</button>
                                            <button name="action" value="tolak_bayar" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Tolak pembayaran ini?')">✗</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:#8fa3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<div class="footer">
    <p>© 2024 <strong>NusantaraFerry</strong> — Panel Administrasi</p>
</div>

<script>
function bukaModalTambah() {
    document.getElementById('modalTambah').classList.add('open');
}

function bukaModalEdit(data) {
    document.getElementById('edit_jadwal_id').value     = data.id;
    document.getElementById('edit_kapal_id').value      = data.kapal_id;
    document.getElementById('edit_rute_id').value       = data.rute_id;
    document.getElementById('edit_tanggal').value       = data.tanggal_berangkat;
    document.getElementById('edit_jam_berangkat').value = data.jam_berangkat.substring(0,5);
    document.getElementById('edit_jam_tiba').value      = data.jam_tiba.substring(0,5);
    document.getElementById('edit_harga_ekonomi').value = parseFloat(data.harga_ekonomi);
    document.getElementById('edit_harga_bisnis').value  = parseFloat(data.harga_bisnis);
    document.getElementById('edit_harga_vip').value     = parseFloat(data.harga_vip);
    document.getElementById('edit_kursi_ekonomi').value = data.sisa_kursi_ekonomi;
    document.getElementById('edit_kursi_bisnis').value  = data.sisa_kursi_bisnis;
    document.getElementById('edit_kursi_vip').value     = data.sisa_kursi_vip;
    document.getElementById('edit_status').value        = data.status;
    document.getElementById('modalEdit').classList.add('open');
}

function tutupModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Tutup modal saat klik di luar kotak
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>

</body>
</html>
