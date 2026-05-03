<?php
session_start();
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi!';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NusantaraFerry</title>
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
            <h1>NusantaraFerry</h1>
            <p>Sistem Pemesanan Tiket Kapal Online</p>
        </div>

        <div class="auth-card-body">
            <div class="auth-tabs">
                <a href="login.php" class="auth-tab active">Masuk</a>
                <a href="register.php" class="auth-tab">Daftar</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">✅ Registrasi berhasil! Silakan login.</div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-info">👋 Anda telah berhasil keluar.</div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="contoh@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    🚀 Masuk Sekarang
                </button>
            </form>

            <div style="margin-top:20px; padding:16px; background:#f0f4f9; border-radius:10px; font-size:0.82rem; color:#666;">
                <strong>Demo Akun:</strong><br>
                👤 Admin: admin@tiketkapal.com / <em>password</em><br>
                👤 User: budi@email.com / <em>password</em>
            </div>
        </div>
    </div>
</div>
</body>
</html>
