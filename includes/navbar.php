<?php
// includes/navbar.php
$current = basename($_SERVER['PHP_SELF']);
$user_initial = isset($_SESSION['nama']) ? strtoupper(substr($_SESSION['nama'], 0, 1)) : 'U';
?>
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <div class="logo-icon">⛴️</div>
        <div>
            <div class="brand-text">NusantaraFerry</div>
            <div class="brand-sub">Sistem Tiket Kapal</div>
        </div>
    </a>

    <ul class="navbar-nav">
        <?php if (isLoggedIn()): ?>
            <li><a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">🏠 Beranda</a></li>
            <li><a href="booking.php" class="<?= $current=='booking.php'?'active':'' ?>">🎫 Pesan Tiket</a></li>
            <li><a href="riwayat.php" class="<?= $current=='riwayat.php'?'active':'' ?>">📋 Riwayat</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin.php" class="<?= $current=='admin.php'?'active':'' ?>">⚙️ Admin</a></li>
            <?php endif; ?>
            <li>
                <div class="user-badge">
                    <div class="user-avatar"><?= $user_initial ?></div>
                    <span><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
                </div>
            </li>
            <li><a href="logout.php" class="btn-logout">Keluar</a></li>
        <?php else: ?>
            <li><a href="index.php">🏠 Beranda</a></li>
            <li><a href="login.php">Masuk</a></li>
            <li><a href="register.php">Daftar</a></li>
        <?php endif; ?>
    </ul>
</nav>
