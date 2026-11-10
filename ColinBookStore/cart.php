<?php
/** Keranjang customer: mengubah jumlah dan menghapus item milik user aktif. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$userId = (int) $_SESSION['user_id'];

// Semua perubahan cart dilakukan melalui POST dan dilindungi CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $cartId = (int) ($_POST['id_cart'] ?? 0);

    // Delete membatasi id_cart dengan id_user agar tidak menghapus cart user lain.
    if ($action === 'delete') {
        $statement = $pdo->prepare('DELETE FROM cart WHERE id_cart = ? AND id_user = ?');
        $statement->execute([$cartId, $userId]);
        set_flash('success', 'Buku dihapus dari keranjang.');
    // Update quantity selalu dibandingkan lagi dengan stok terbaru di database.
    } elseif ($action === 'update') {
        $quantity = max(1, (int) ($_POST['jumlah'] ?? 1));
        $statement = $pdo->prepare(
            'SELECT b.stok FROM cart c JOIN buku b ON b.id_buku = c.id_buku WHERE c.id_cart = ? AND c.id_user = ?'
        );
        $statement->execute([$cartId, $userId]);
        $stock = $statement->fetchColumn();
        if ($stock === false) {
            set_flash('danger', 'Item keranjang tidak ditemukan.');
        } elseif ((int) $stock < 1) {
            $delete = $pdo->prepare('DELETE FROM cart WHERE id_cart = ? AND id_user = ?');
            $delete->execute([$cartId, $userId]);
            set_flash('warning', 'Item dihapus karena stok telah habis.');
        } else {
            $update = $pdo->prepare('UPDATE cart SET jumlah = ? WHERE id_cart = ? AND id_user = ?');
            $update->execute([min($quantity, (int) $stock), $cartId, $userId]);
            set_flash('success', 'Jumlah keranjang diperbarui.');
        }
    }
    redirect('cart.php');
}

$statement = $pdo->prepare(
    'SELECT c.*, b.judul_buku, b.harga, b.stok, b.gambar, k.nama_kategori
     FROM cart c JOIN buku b ON b.id_buku = c.id_buku JOIN kategori k ON k.id_kategori = b.id_kategori
     WHERE c.id_user = ? ORDER BY c.id_cart DESC'
);
$statement->execute([$userId]);
$items = $statement->fetchAll();
$total = array_sum(array_map(fn (array $item): float => (float) $item['harga'] * (int) $item['jumlah'], $items));
$page_title = 'Keranjang';
$active_page = 'catalog';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">KERANJANG / <?= array_sum(array_column($items, 'jumlah')) ?> ITEM</span>
<h1>Rak belanjamu.</h1>
</div>
<a class="text-link" href="<?= url('buku.php') ?>">Tambah bacaan <span>→</span>
</a>
</section>
<?php if (!$items): ?>
  <div class="empty-state empty-cart">
<span class="empty-symbol">＋</span>
<h2>Belum ada buku di sini</h2>
<p>Rak kosong juga bisa menjadi awal yang baik.</p>
<a class="button button-dark" href="<?= url('buku.php') ?>">Cari buku</a>
</div>
<?php else: ?>
  <div class="cart-layout">
    <section class="cart-list">
      <?php foreach ($items as $item): ?>
        <article class="cart-row">
          <a class="cart-cover <?= ['cover-sky', 'cover-mist', 'cover-blue', 'cover-ice'][$item['id_buku'] % 4] ?>" href="<?= url('detail_buku.php?id=' . $item['id_buku']) ?>">
<span>
<?= e(mb_substr($item['judul_buku'], 0, 1)) ?>
</span>
</a>
          <div class="cart-book-info">
<span class="eyebrow">
<?= e($item['nama_kategori']) ?>
</span>
<h2>
<?= e($item['judul_buku']) ?>
</h2>
<p>
<?= rupiah($item['harga']) ?> · stok <?= (int) $item['stok'] ?>
</p>
</div>
          <form class="cart-quantity" method="post">
<label for="qty-<?= $item['id_cart'] ?>">Jumlah</label>
<?= csrf_field() ?>
<input type="hidden" name="action" value="update">
<input type="hidden" name="id_cart" value="<?= $item['id_cart'] ?>">
<input id="qty-<?= $item['id_cart'] ?>" type="number" name="jumlah" min="1" max="<?= $item['stok'] ?>" value="<?= $item['jumlah'] ?>">
<button type="submit">Simpan</button>
</form>
          <strong class="cart-subtotal">
<?= rupiah($item['harga'] * $item['jumlah']) ?>
</strong>
          <form method="post">
<input type="hidden" name="action" value="delete">
<?= csrf_field() ?>
<input type="hidden" name="id_cart" value="<?= $item['id_cart'] ?>">
<button class="remove-button" type="submit" data-confirm="Hapus buku ini dari keranjang?" aria-label="Hapus <?= e($item['judul_buku']) ?>">×</button>
</form>
        </article>
      <?php endforeach; ?>
    </section>
    <aside class="cart-summary">
<span class="eyebrow">RINGKASAN</span>
<h2>Satu langkah<br>lagi.</h2>
<div class="summary-line">
<span>Total item</span>
<span>
<?= array_sum(array_column($items, 'jumlah')) ?> buku</span>
</div>
<div class="summary-line summary-total">
<span>Total</span>
<strong>
<?= rupiah($total) ?>
</strong>
</div>
<a class="button button-dark button-block" href="<?= url('checkout.php') ?>">Lanjut checkout <span>↗</span>
</a>
</aside>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
