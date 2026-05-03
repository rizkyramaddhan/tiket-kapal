# ⛴️ NusantaraFerry - Sistem Pemesanan Tiket Kapal
**Mata Kuliah: Testing QA Perangkat Lunak**

---

## 📁 Struktur File

```
tiket-kapal/
├── index.php           → Halaman utama & pencarian jadwal
├── login.php           → Halaman login
├── register.php        → Halaman registrasi
├── logout.php          → Proses logout
├── booking.php         → Form pemesanan tiket (perlu login)
├── pembayaran.php      → Form pembayaran
├── konfirmasi.php      → Halaman konfirmasi booking
├── riwayat.php         → Riwayat pemesanan user
├── admin.php           → Panel admin (dashboard, booking, verifikasi)
├── database.sql        → Script database MySQL
├── css/
│   └── style.css       → Stylesheet utama
└── includes/
    ├── config.php      → Konfigurasi DB & helper functions
    └── navbar.php      → Komponen navigasi
```

---

## ⚙️ Cara Setup

### 1. Persyaratan
- PHP >= 7.4
- MySQL / MariaDB
- Web Server: Apache (XAMPP/WAMP) atau Nginx

### 2. Instalasi Database
```sql
-- Buka phpMyAdmin atau MySQL CLI, lalu jalankan:
SOURCE /path/to/tiket-kapal/database.sql;
```

### 3. Konfigurasi Koneksi
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');  // host database
define('DB_USER', 'root');       // username MySQL
define('DB_PASS', '');           // password MySQL
define('DB_NAME', 'tiket_kapal'); // nama database
```

### 4. Jalankan Aplikasi
Letakkan folder `tiket-kapal/` di:
- XAMPP: `C:/xampp/htdocs/tiket-kapal/`
- WAMP:  `C:/wamp64/www/tiket-kapal/`

Akses melalui browser: `http://localhost/tiket-kapal/`

---

## 🔐 Akun Demo

| Role  | Email                    | Password  |
|-------|--------------------------|-----------|
| Admin | admin@tiketkapal.com     | password  |
| User  | budi@email.com           | password  |

> **Catatan:** Password pada database.sql menggunakan `password_hash()` PHP.
> Untuk demo, generate ulang hash dengan: `echo password_hash('password', PASSWORD_DEFAULT);`

---

## 🎯 Modul Aplikasi

### 1. Modul Login/Autentikasi
- **login.php** — Form login dengan validasi email & password
- **register.php** — Registrasi pengguna baru
- **logout.php** — Destroy session & redirect
- Session management dengan `$_SESSION`
- Password hashing dengan `password_hash()` & `password_verify()`

### 2. Modul Booking (Pemesanan)
- **index.php** — Pencarian jadwal berdasarkan rute & tanggal
- **booking.php** — Form data penumpang, pilih kelas & jumlah tiket
- Validasi NIK (16 digit), nama, telepon
- Update otomatis sisa kursi setelah booking
- Generate kode booking unik (format: NF-XXXXXXXX)

### 3. Modul Pembayaran
- **pembayaran.php** — Pilih metode (Transfer Bank/Kartu Kredit/Dompet Digital)
- Upload bukti transfer (JPG, PNG, PDF max 2MB)
- **konfirmasi.php** — Halaman konfirmasi setelah pembayaran
- **admin.php** (tab Pembayaran) — Verifikasi/tolak pembayaran oleh admin

---

## 🧪 Test Cases untuk QA

### Login
| Test Case | Input | Expected |
|-----------|-------|----------|
| Login valid | Email & password benar | Redirect ke index.php |
| Email salah | Email tidak terdaftar | Pesan error |
| Password salah | Password tidak cocok | Pesan error |
| Field kosong | Kirim form kosong | Validasi required |

### Booking
| Test Case | Input | Expected |
|-----------|-------|----------|
| Booking valid | Data lengkap & kursi tersedia | Redirect pembayaran |
| NIK tidak valid | NIK < 16 digit | Pesan error |
| Kursi habis | Pesan melebihi sisa kursi | Pesan error |
| Akses tanpa login | Direct URL booking.php | Redirect login |

### Pembayaran
| Test Case | Input | Expected |
|-----------|-------|----------|
| Pembayaran valid | Semua field terisi, jumlah cukup | Redirect konfirmasi |
| Jumlah kurang | Bayar < total tagihan | Pesan error |
| Metode tidak dipilih | Submit tanpa pilih metode | Pesan error |
| File terlalu besar | Upload > 2MB | Pesan error |

---

## 🗄️ Skema Database

- **users** — Data pengguna (id, nama, email, password, telepon, role)
- **kapal** — Data armada kapal
- **rute** — Rute pelayaran (asal, tujuan, jarak, estimasi)
- **jadwal** — Jadwal keberangkatan & harga per kelas
- **pemesanan** — Data booking penumpang
- **pembayaran** — Data & status pembayaran

---

*Dibuat untuk tugas mata kuliah Testing QA Perangkat Lunak*
