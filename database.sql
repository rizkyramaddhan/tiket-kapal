-- ============================================
-- DATABASE: tiket_kapal
-- Sistem Pemesanan Tiket Kapal
-- ============================================

CREATE DATABASE IF NOT EXISTS tiket_kapal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tiket_kapal;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telepon VARCHAR(20),
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kapal
CREATE TABLE IF NOT EXISTS kapal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kapal VARCHAR(100) NOT NULL,
    kode_kapal VARCHAR(20) UNIQUE NOT NULL,
    kapasitas INT NOT NULL,
    deskripsi TEXT
);

-- Tabel Rute
CREATE TABLE IF NOT EXISTS rute (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asal VARCHAR(100) NOT NULL,
    tujuan VARCHAR(100) NOT NULL,
    jarak_km INT,
    estimasi_jam INT
);

-- Tabel Jadwal
CREATE TABLE IF NOT EXISTS jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kapal_id INT NOT NULL,
    rute_id INT NOT NULL,
    tanggal_berangkat DATE NOT NULL,
    jam_berangkat TIME NOT NULL,
    jam_tiba TIME NOT NULL,
    harga_ekonomi DECIMAL(12,2) NOT NULL,
    harga_bisnis DECIMAL(12,2) NOT NULL,
    harga_vip DECIMAL(12,2) NOT NULL,
    sisa_kursi_ekonomi INT NOT NULL,
    sisa_kursi_bisnis INT NOT NULL,
    sisa_kursi_vip INT NOT NULL,
    status ENUM('aktif','penuh','dibatalkan') DEFAULT 'aktif',
    FOREIGN KEY (kapal_id) REFERENCES kapal(id),
    FOREIGN KEY (rute_id) REFERENCES rute(id)
);

-- Tabel Pemesanan
CREATE TABLE IF NOT EXISTS pemesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_booking VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    jadwal_id INT NOT NULL,
    nama_penumpang VARCHAR(100) NOT NULL,
    nik VARCHAR(20) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    kelas ENUM('ekonomi','bisnis','vip') NOT NULL,
    jumlah_tiket INT NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL,
    status ENUM('pending','dibayar','dibatalkan') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)
);

-- Tabel Pembayaran
CREATE TABLE IF NOT EXISTS pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pemesanan_id INT NOT NULL,
    metode_pembayaran ENUM('transfer_bank','kartu_kredit','dompet_digital') NOT NULL,
    bank_tujuan VARCHAR(50),
    nomor_rekening VARCHAR(50),
    nama_pemilik VARCHAR(100),
    jumlah_bayar DECIMAL(12,2) NOT NULL,
    bukti_transfer VARCHAR(255),
    status ENUM('menunggu','dikonfirmasi','ditolak') DEFAULT 'menunggu',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (pemesanan_id) REFERENCES pemesanan(id)
);

-- ============================================
-- DATA SAMPLE
-- ============================================

-- User Admin (password: admin123)
INSERT INTO users (nama, email, password, telepon, role) VALUES 
('Administrator', 'admin@tiketkapal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin');

-- User Biasa (password: user123)
INSERT INTO users (nama, email, password, telepon, role) VALUES 
('Budi Santoso', 'budi@email.com', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', '082345678901', 'user'),
('Siti Rahayu', 'siti@email.com', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', '083456789012', 'user');

-- Kapal
INSERT INTO kapal (nama_kapal, kode_kapal, kapasitas, deskripsi) VALUES
('KM Nusantara Jaya', 'NJ-001', 500, 'Kapal modern dengan fasilitas lengkap'),
('KM Bahari Indah', 'BI-002', 350, 'Kapal cepat rute antar pulau'),
('KM Samudra Raya', 'SR-003', 450, 'Kapal mewah dengan berbagai kelas');

-- Rute
INSERT INTO rute (asal, tujuan, jarak_km, estimasi_jam) VALUES
('Jakarta (Tanjung Priok)', 'Surabaya (Tanjung Perak)', 735, 18),
('Surabaya (Tanjung Perak)', 'Makassar (Soekarno-Hatta)', 850, 20),
('Jakarta (Tanjung Priok)', 'Semarang (Tanjung Emas)', 440, 12),
('Makassar (Soekarno-Hatta)', 'Balikpapan', 650, 16),
('Surabaya (Tanjung Perak)', 'Lombok (Lembar)', 280, 8);

-- Jadwal
INSERT INTO jadwal (kapal_id, rute_id, tanggal_berangkat, jam_berangkat, jam_tiba, harga_ekonomi, harga_bisnis, harga_vip, sisa_kursi_ekonomi, sisa_kursi_bisnis, sisa_kursi_vip) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '02:00:00', 250000, 450000, 750000, 200, 80, 20),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '20:00:00', '14:00:00', 250000, 450000, 750000, 150, 60, 15),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '06:00:00', 300000, 500000, 850000, 180, 70, 18),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '07:00:00', '19:00:00', 180000, 320000, 550000, 220, 90, 25),
(2, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 150000, 280000, 480000, 100, 40, 10),
(3, 4, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '15:00:00', '07:00:00', 350000, 600000, 950000, 160, 65, 16);
