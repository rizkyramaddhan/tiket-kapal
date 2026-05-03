<?php
session_start();
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telepon  = trim($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO users (nama, email, password, telepon) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $nama, $email, $hash, $telepon);
            if ($ins->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = 'Terjadi kesalahan. Coba lagi!';
            }
            $ins->close();
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - NusantaraFerry</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-bg-circles">
        <span></span><span></span><span></span>
    </div>

    <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-logo">⛴️</div>
            <h1>Buat Akun Baru</h1>
            <p>Daftar untuk memesan tiket kapal</p>
        </div>

        <div class="auth-card-body">
            <div class="auth-tabs">
                <a href="login.php" class="auth-tab">Masuk</a>
                <a href="register.php" class="auth-tab active">Daftar</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control"
                           placeholder="Nama sesuai KTP"
                           value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="contoh@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="telepon" class="form-control"
                           placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Minimal 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" class="form-control"
                           placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="btn btn-gold btn-full btn-lg">
                    ✨ Daftar Sekarang
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
