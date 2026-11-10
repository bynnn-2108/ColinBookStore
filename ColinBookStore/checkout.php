<?php
/** Checkout: memilih pembayaran, mengunggah bukti, mengunci stok, dan membuat order. */

require_once __DIR__ . '/includes/functions.php';
require_user();

function checkout_items(PDO $pdo, int $userId): array
{
    // Baca snapshot cart; stok final tetap dikunci ulang di transaksi checkout.
    $statement = $pdo->prepare(
        'SELECT c.id_cart, c.id_buku, c.jumlah, b.judul_buku, b.harga, b.stok
         FROM cart c JOIN buku b ON b.id_buku = c.id_buku WHERE c.id_user = ? ORDER BY c.id_cart'
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

$userId = (int) $_SESSION['user_id'];
$items = checkout_items($pdo, $userId);
if (!$items) {
    set_flash('warning', 'Keranjang masih kosong.');
    redirect('cart.php');
}
$user = current_user();
$method = 'COD';
$error = '';
$cashPaidInput = '';
$cashPaid = 0.0;
$cashChange = 0.0;

// POST hanya diproses setelah CSRF dan metode pembayaran divalidasi.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $method = (string) ($_POST['metode_pembayaran'] ?? 'COD');
    $cashPaidInput = trim((string) ($_POST['uang_dibayar'] ?? ''));
    $cashPaid = $cashPaidInput !== '' && is_numeric($cashPaidInput) ? (float) $cashPaidInput : 0.0;
    if (!in_array($method, ['COD', 'TRANSFER'], true)) {
        $error = 'Metode pembayaran tidak valid.';
    }

    // Uang pembeli hanya berlaku untuk COD dan harus berupa nominal non-negatif.
    if ($error === '' && $method === 'COD'
        && ($cashPaidInput === '' || !is_numeric($cashPaidInput) || $cashPaid < 0)) {
        $error = 'Masukkan uang pembeli yang valid untuk pembayaran COD.';
    }
    // Transfer tidak menyimpan nominal tunai agar struk tidak menampilkan data yang keliru.
    if ($method === 'TRANSFER') {
        $cashPaid = 0.0;
        $cashChange = 0.0;
    }

    $proof = null;
    // Bukti hanya diwajibkan untuk transfer; COD tidak memakai upload handler.
    if ($error === '' && $method === 'TRANSFER') {
        [$uploaded, $result] = upload_file(
            $_FILES['bukti_pembayaran'] ?? [],
            'uploads/payment',
            ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf']
        );
        if (!$uploaded) {
            $error = $result;
        } else {
            $proof = $result;
        }
    }

    if ($error === '') {
        try {
            // Satu transaksi menjaga stok, order, detail, dan cart tetap konsisten.
            $pdo->beginTransaction();
            $items = checkout_items($pdo, $userId);
            if (!$items) {
                throw new RuntimeException('Keranjang sudah kosong.');
            }
            $total = 0.0;
            $lockedBooks = [];
            foreach ($items as $item) {
                // FOR UPDATE mencegah dua checkout mengurangi stok yang sama bersamaan.
                $lock = $pdo->prepare('SELECT judul_buku, harga, stok FROM buku WHERE id_buku = ? FOR UPDATE');
                $lock->execute([$item['id_buku']]);
                $book = $lock->fetch();
                if (!$book || (int) $book['stok'] < (int) $item['jumlah']) {
                    throw new RuntimeException('Stok untuk "' . ($book['judul_buku'] ?? $item['judul_buku']) . '" tidak mencukupi.');
                }
                $lockedBooks[(int) $item['id_buku']] = $book;
                $total += (float) $book['harga'] * (int) $item['jumlah'];
            }

            // Bandingkan uang pembeli dengan total final setelah harga dan stok dikunci.
            if ($method === 'COD') {
                if ($cashPaid < $total) {
                    throw new RuntimeException('Uang pembeli kurang dari total pembayaran.');
                }
                $cashChange = $cashPaid - $total;
            }

            // Simpan header order setelah seluruh stok lolos verifikasi.
            $order = $pdo->prepare(
                'INSERT INTO orders (id_user, total_harga, metode_pembayaran, bukti_pembayaran, uang_dibayar, uang_kembalian)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $order->execute([$userId, $total, $method, $proof, $cashPaid, $cashChange]);
            $orderId = (int) $pdo->lastInsertId();
            foreach ($items as $item) {
                $book = $lockedBooks[(int) $item['id_buku']];
                $quantity = (int) $item['jumlah'];
                $unitPrice = (float) $book['harga'];
                $detail = $pdo->prepare('INSERT INTO order_detail (id_order, id_buku, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)');
                $detail->execute([$orderId, $item['id_buku'], $quantity, $unitPrice, $unitPrice * $quantity]);
                // Kurangi stok dengan guard agar stok tidak pernah menjadi negatif.
                $stock = $pdo->prepare('UPDATE buku SET stok = stok - ? WHERE id_buku = ? AND stok >= ?');
                $stock->execute([$quantity, $item['id_buku'], $quantity]);
                if ($stock->rowCount() !== 1) {
                    throw new RuntimeException('Stok berubah saat checkout. Silakan coba lagi.');
                }
            }
            // Cart dikosongkan hanya setelah detail order dan pengurangan stok berhasil.
            $clear = $pdo->prepare('DELETE FROM cart WHERE id_user = ?');
            $clear->execute([$userId]);
            $pdo->commit();
            redirect('order_success.php?id=' . $orderId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($proof) {
                delete_upload($proof);
            }
            $error = $exception->getMessage();
        }
    }
}

$total = array_sum(array_map(fn (array $item): float => (float) $item['harga'] * (int) $item['jumlah'], $items));
// Hitung ulang preview kembalian agar form tetap informatif setelah validasi gagal.
if ($method === 'COD' && $cashPaid >= $total) {
    $cashChange = $cashPaid - $total;
}
$page_title = 'Checkout';
$active_page = 'catalog';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">CHECKOUT / LANGKAH TERAKHIR</span>
<h1>Selesaikan pesanan.</h1>
</div>
<a class="text-link" href="<?= url('cart.php') ?>">← Kembali ke keranjang</a>
</section>
<?php if ($error !== ''): ?>
<div class="alert alert-danger" role="alert">
<?= e($error) ?>
</div>
<?php endif; ?>
<div class="checkout-layout">
  <section class="checkout-form-card">
<div class="checkout-section">
<span class="eyebrow">01 / PENGIRIMAN</span>
<h2>Data penerima</h2>
<div class="checkout-recipient">
<strong>
<?= e($user['nama']) ?>
</strong>
<span>
<?= e($user['email']) ?> · <?= e($user['nomor_telepon']) ?>
</span>
<span>
<?= nl2br(e($user['alamat'])) ?>
</span>
<a href="<?= url('kelola_akun.php') ?>">Ubah data profil →</a>
</div>
</div>
<div class="checkout-section">
<span class="eyebrow">02 / PEMBAYARAN</span>
<h2>Pilih cara bayar</h2>
<form method="post" enctype="multipart/form-data" data-payment-form
data-order-total="<?= e(number_format($total, 2, '.', '')) ?>">
<?= csrf_field() ?>
<div class="payment-options">
<label class="payment-choice">
<input type="radio" name="metode_pembayaran" value="COD" <?= $method === 'COD' ? 'checked' : '' ?>>
<span>
<strong>COD</strong>
<small>Bayar saat pesanan tiba.</small>
</span>
<b>01</b>
</label>
<label class="payment-choice">
<input type="radio" name="metode_pembayaran" value="TRANSFER" <?= $method === 'TRANSFER' ? 'checked' : '' ?>>
<span>
<strong>Transfer bank</strong>
<small>Unggah bukti setelah transfer.</small>
</span>
<b>02</b>
</label>
</div>
<div class="cash-payment" data-cash-payment <?= $method === 'TRANSFER' ? 'hidden' : '' ?>>
<div class="form-group">
<label for="uang_dibayar">Uang dari pembeli <span>(wajib untuk COD)</span></label>
<input id="uang_dibayar" type="number" name="uang_dibayar"
min="<?= e(number_format($total, 2, '.', '')) ?>" step="1000" inputmode="numeric"
value="<?= $method === 'COD' ? e($cashPaidInput) : '' ?>" data-cash-paid
<?= $method === 'TRANSFER' ? 'disabled' : 'required' ?>>
<small class="helper">Masukkan nominal uang yang diterima. Nilai minimal sama dengan total pembayaran.</small>
</div>
<div class="cash-change" aria-live="polite">
<span>Uang kembalian</span>
<strong data-cash-change><?= rupiah($cashChange) ?></strong>
</div>
</div>
<div class="transfer-details" data-transfer-details <?= $method === 'TRANSFER' ? '' : 'hidden' ?>>
<div class="account-card">
<span>Nomor rekening transfer</span>
<strong>082263872834</strong>
<small>a.n. ColinBookStore</small>
<button type="button" data-copy-value="082263872834">Salin nomor</button>
</div>
<ol>
<li>Transfer sesuai total pesanan.</li>
<li>Simpan foto atau PDF bukti transfer.</li>
<li>Unggah bukti agar admin dapat memverifikasi.</li>
</ol>
<div class="form-group">
<label for="bukti_pembayaran">Bukti transfer <span>(wajib untuk transfer)</span>
</label>
<input id="bukti_pembayaran" type="file" name="bukti_pembayaran" accept="image/jpeg,image/png,application/pdf" data-payment-proof <?= $method === 'TRANSFER' ? 'required' : 'disabled' ?>>
<small class="helper">JPG, JPEG, PNG, atau PDF · maksimal 2 MB. <span data-upload-file-name aria-live="polite">
</span>
</small>
<img class="upload-preview" data-upload-preview alt="Pratinjau bukti transfer" hidden>
</div>
</div>
<button class="button button-dark" type="submit">Buat pesanan <span>↗</span>
</button>
</form>
</div>
</section>
  <aside class="order-summary">
<span class="eyebrow">RINGKASAN PESANAN</span>
<h2>
<?= count($items) ?> judul<br>di keranjang.</h2>
<?php foreach ($items as $item): ?>
<div class="summary-line">
<span>
<?= e($item['judul_buku']) ?> × <?= $item['jumlah'] ?>
</span>
<strong>
<?= rupiah($item['harga'] * $item['jumlah']) ?>
</strong>
</div>
<?php endforeach; ?>
<div class="summary-line summary-total">
<span>Total pembayaran</span>
<strong>
<?= rupiah($total) ?>
</strong>
</div>
<p class="secure-note">⌁ Pembayaran dan stok diverifikasi secara aman.</p>
</aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
