-- Schema ColinBookStore: tabel admin, customer, katalog, cart, order, dan pesan.
CREATE DATABASE IF NOT EXISTS colinbookstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE colinbookstore;

-- Reset terkontrol agar file SQL dapat di-import ulang pada database development.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_detail, orders, cart, pesan, buku, kategori, `user`, admin;
SET FOREIGN_KEY_CHECKS = 1;

-- Kredensial admin memakai hash password, bukan plaintext.
CREATE TABLE admin (
  id_admin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Data customer menyimpan kontak pengiriman dan index waktu pendaftaran.
CREATE TABLE `user` (
  id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  alamat TEXT NOT NULL,
  nomor_telepon VARCHAR(25) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_created (created_at)
) ENGINE=InnoDB;

-- Kategori memiliki nama unik agar filter katalog konsisten.
CREATE TABLE kategori (
  id_kategori INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Buku menyimpan metadata katalog, harga, stok, dan path gambar acak.
CREATE TABLE buku (
  id_buku INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_kategori INT UNSIGNED NOT NULL,
  judul_buku VARCHAR(200) NOT NULL,
  penulis VARCHAR(150) NOT NULL,
  penerbit VARCHAR(150) NOT NULL,
  tahun_terbit YEAR NOT NULL,
  harga DECIMAL(12,2) UNSIGNED NOT NULL,
  stok INT UNSIGNED NOT NULL DEFAULT 0,
  deskripsi TEXT NOT NULL,
  gambar VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_book_category (id_kategori),
  INDEX idx_book_title (judul_buku),
  CONSTRAINT fk_book_category FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Satu user hanya memiliki satu baris cart untuk setiap buku.
CREATE TABLE cart (
  id_cart INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user INT UNSIGNED NOT NULL,
  id_buku INT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_user_book (id_user, id_buku),
  CONSTRAINT fk_cart_user FOREIGN KEY (id_user) REFERENCES `user`(id_user) ON DELETE CASCADE,
  CONSTRAINT fk_cart_book FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Pesan customer masuk ke admin melalui halaman bantuan.
CREATE TABLE pesan (
  id_pesan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user INT UNSIGNED NOT NULL,
  pesan TEXT NOT NULL,
  tanggal TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_message_date (tanggal),
  CONSTRAINT fk_message_user FOREIGN KEY (id_user) REFERENCES `user`(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order menyimpan metode pembayaran, bukti transfer, uang COD, status, dan total.
CREATE TABLE orders (
  id_order INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user INT UNSIGNED NOT NULL,
  tanggal_order DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_harga DECIMAL(12,2) UNSIGNED NOT NULL,
  metode_pembayaran ENUM('COD','TRANSFER') NOT NULL DEFAULT 'COD',
  bukti_pembayaran VARCHAR(255) DEFAULT NULL,
  -- Nominal uang yang diterima kasir untuk menghitung kembalian COD.
  uang_dibayar DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
  -- Kembalian disimpan sebagai snapshot agar selalu sama dengan struk.
  uang_kembalian DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('Menunggu Konfirmasi','Diproses','Dikirim','Selesai','Dibatalkan')
    NOT NULL DEFAULT 'Menunggu Konfirmasi',
  INDEX idx_order_user_date (id_user, tanggal_order),
  INDEX idx_order_status (status),
  CONSTRAINT fk_order_user FOREIGN KEY (id_user) REFERENCES `user`(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Detail order menyimpan snapshot harga dan kuantitas setiap buku.
CREATE TABLE order_detail (
  id_detail INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_order INT UNSIGNED NOT NULL,
  id_buku INT UNSIGNED NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  harga_satuan DECIMAL(12,2) UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
  INDEX idx_detail_order (id_order),
  CONSTRAINT fk_detail_order FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
  CONSTRAINT fk_detail_book FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Seed admin development: password di database sudah berupa bcrypt hash.
INSERT INTO admin (nama, email, password) VALUES
('Colin Administrator', 'admin@colinbookstore.test', '$2y$10$9jP8AjRi7vmKkfkcaAciLunsUpAnMC1SctBEEYqqnHLg1ybgS6T8y');

-- Seed kategori awal untuk menguji filter katalog.
INSERT INTO kategori (nama_kategori) VALUES
('Fiksi'), ('Pengembangan Diri'), ('Teknologi'), ('Anak & Remaja'), ('Bisnis');

-- Seed buku awal agar beranda dan katalog langsung memiliki isi.
INSERT INTO buku (id_kategori, judul_buku, penulis, penerbit, tahun_terbit, harga, stok, deskripsi) VALUES
(1, 'Laut Bercerita', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', 2017, 115000, 18,
 'Novel tentang persahabatan, keluarga, dan perjuangan kemanusiaan yang tinggal lama setelah halaman terakhir.'),
(2, 'Atomic Habits', 'James Clear', 'Gramedia Pustaka Utama', 2019, 108000, 25,
 'Panduan praktis membangun kebiasaan kecil untuk menghasilkan perubahan besar.'),
(3, 'Dasar-Dasar Pemrograman Web', 'Budi Raharjo', 'Informatika', 2023, 95000, 14,
 'Pengantar HTML, CSS, JavaScript, PHP, dan basis data untuk pembaca pemula.'),
(5, 'The Psychology of Money', 'Morgan Housel', 'Baca', 2021, 99000, 20,
 'Cerita tentang cara manusia berpikir, bertindak, dan mengambil keputusan mengenai uang.'),
(4, 'Petualangan Kancil Cerdas', 'Nadia Pratiwi', 'Pelangi Anak', 2022, 58000, 30,
 'Kumpulan cerita anak penuh warna dengan pesan baik untuk dibaca bersama keluarga.');
