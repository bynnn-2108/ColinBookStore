<?php
/** Detail buku: memuat informasi lengkap dan alur tambah ke keranjang. */

require_once __DIR__ . '/includes/functions.php';
$bookId = (int) ($_GET['id'] ?? 0);
$statement = $pdo->prepare(
    'SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON k.id_kategori = b.id_kategori WHERE b.id_buku = ?'
);
$statement->execute([$bookId]);
$book = $statement->fetch();

if (!$book) {
    http_response_code(404);
    $page_title = 'Buku tidak ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state">
<h1>Buku tidak ditemukan</h1>
<a class="button button-dark" href="' . url('buku.php') . '">Kembali ke koleksi</a>
</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $book['judul_buku'];
$active_page = 'catalog';
require __DIR__ . '/includes/header.php';
?>
<div class="detail-breadcrumb">
<a href="<?= url('buku.php') ?>">Koleksi</a>
<span>/</span>
<span>
<?= e($book['nama_kategori']) ?>
</span>
</div>
<section class="detail-layout">
  <div class="detail-cover <?= ['cover-sky', 'cover-mist', 'cover-blue', 'cover-ice'][$bookId % 4] ?>">
    <?php if (!empty($book['gambar'])): ?>
<img src="<?= url($book['gambar']) ?>" alt="Sampul <?= e($book['judul_buku']) ?>">
<?php else: ?>
<span class="cover-label">COLIN<br>READS</span>
<strong>
<?= e($book['judul_buku']) ?>
</strong>
<small>
<?= e($book['penulis']) ?>
</small>
<?php endif; ?>
  </div>
  <div class="detail-copy">
    <span class="eyebrow">
<?= e($book['nama_kategori']) ?> · <?= e($book['tahun_terbit']) ?>
</span>
    <h1>
<?= e($book['judul_buku']) ?>
</h1>
    <p class="detail-author">oleh <strong>
<?= e($book['penulis']) ?>
</strong>
</p>
    <p class="detail-description">
<?= nl2br(e($book['deskripsi'])) ?>
</p>
    <div class="detail-data">
      <div>
<span>Penerbit</span>
<strong>
<?= e($book['penerbit']) ?>
</strong>
</div>
      <div>
<span>Tahun terbit</span>
<strong>
<?= e($book['tahun_terbit']) ?>
</strong>
</div>
      <div>
<span>Ketersediaan</span>
<strong>
<?= (int) $book['stok'] > 0 ? (int) $book['stok'] . ' eksemplar' : 'Stok habis' ?>
</strong>
</div>
    </div>
    <div class="detail-purchase">
      <div>
<span class="eyebrow">HARGA</span>
<strong class="detail-price">
<?= rupiah($book['harga']) ?>
</strong>
</div>
      <?php if ((int) $book['stok'] > 0): ?>
        <form method="post" action="<?= url('cart_add.php') ?>" class="add-detail-form">
          <?= csrf_field() ?>
<input type="hidden" name="id_buku" value="<?= $book['id_buku'] ?>">
<input type="hidden" name="return_to" value="detail_buku.php?id=<?= $book['id_buku'] ?>">
          <label for="jumlah">Jumlah</label>
<input id="jumlah" name="jumlah" type="number" min="1" max="<?= $book['stok'] ?>" value="1">
          <button class="button button-dark" type="submit">Masukkan ke keranjang <span>↗</span>
</button>
        </form>
      <?php else: ?>
<span class="stock-empty">Stok sedang habis</span>
<?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
