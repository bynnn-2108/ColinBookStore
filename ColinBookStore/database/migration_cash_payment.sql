-- Migrasi ColinBookStore: menyimpan uang COD dan kembalian pada order lama.
USE colinbookstore;

-- Jalankan sekali pada database yang dibuat sebelum fitur uang COD ditambahkan.
ALTER TABLE orders
  ADD COLUMN uang_dibayar DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Nominal uang pembeli untuk COD' AFTER bukti_pembayaran,
  ADD COLUMN uang_kembalian DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Nominal kembalian dari pembayaran COD' AFTER uang_dibayar;
