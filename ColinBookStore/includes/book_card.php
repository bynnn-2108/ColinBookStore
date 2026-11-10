<?php
/** Komponen reusable kartu buku untuk beranda dan halaman koleksi. */
// Pilih aksen sampul deterministik agar setiap buku memiliki identitas visual.
$coverColor = ['cover-sky', 'cover-mist', 'cover-blue', 'cover-ice'][((int) $book['id_buku']) % 4];
?>
<article class="book-card">
  <!-- Cover menjadi fokus utama agar kartu terasa seperti katalog bookstore. -->
  <a class="book-cover <?= $coverColor ?><?= !empty($book['gambar']) ? ' has-image' : '' ?>" href="<?= url('detail_buku.php?id=' . $book['id_buku']) ?>">
    <span class="book-cover-topline">COLINBOOKSTORE / EDITION</span>
    <?php if (!empty($book['gambar'])): ?>
      <!-- Gambar sampul berasal dari path upload yang sudah divalidasi server. -->
      <img src="<?= url($book['gambar']) ?>" alt="Sampul <?= e($book['judul_buku']) ?>">
    <?php else: ?>
      <!-- Fallback cover typographic dipakai ketika buku belum memiliki gambar. -->
      <span class="cover-label">COLIN<br>READS</span>
      <strong>
<?= e($book['judul_buku']) ?>
</strong>
      <small>
<?= e($book['penulis']) ?>
</small>
    <?php endif; ?>
    <span class="book-cover-mark" aria-hidden="true">C</span>
  </a>
  <div class="book-card-copy">
    <!-- Metadata membantu customer mengenali kategori dan ketersediaan stok. -->
    <div class="book-card-meta">
<span class="book-card-category">
<?= e($book['nama_kategori'] ?? 'Bacaan') ?>
</span>
<span class="book-card-stock">
<?= (int) $book['stok'] ?> stok</span>
</div>
    <h3>
<a href="<?= url('detail_buku.php?id=' . $book['id_buku']) ?>">
<?= e($book['judul_buku']) ?>
</a>
</h3>
    <p>
<?= e($book['penulis']) ?>
</p>
    <div class="book-card-bottom">
      <strong>
<?= rupiah($book['harga']) ?>
</strong>
      <?php if ((int) $book['stok'] > 0): ?>
        <!-- Form mini ini memakai endpoint cart_add dan CSRF token yang sama. -->
        <form method="post" action="<?= url('cart_add.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id_buku" value="<?= $book['id_buku'] ?>">
          <input type="hidden" name="jumlah" value="1">
          <input type="hidden" name="return_to" value="<?= e($returnTo ?? 'buku.php') ?>">
          <button class="book-add-button" type="submit" aria-label="Tambah <?= e($book['judul_buku']) ?> ke keranjang">
            <span class="book-add-icon" aria-hidden="true">+</span>
            <span>Tambah</span>
          </button>
        </form>
      <?php else: ?>
        <span class="out-label">Habis</span>
      <?php endif; ?>
    </div>
  </div>
</article>
