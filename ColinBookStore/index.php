<?php
/** Beranda customer: mengambil koleksi terbaru dan ringkasan kategori. */

require_once __DIR__ . '/includes/functions.php';

$page_title = 'Beranda';
$active_page = 'home';
// Ambil enam buku terbaru untuk blok rekomendasi di beranda.
$books = $pdo->query(
    'SELECT b.*, k.nama_kategori
     FROM buku b JOIN kategori k ON k.id_kategori = b.id_kategori
     ORDER BY b.created_at DESC, b.id_buku DESC LIMIT 6'
)->fetchAll();
// Hitung jumlah buku per kategori untuk navigasi kategori beranda.
$categories = $pdo->query(
    'SELECT k.id_kategori, k.nama_kategori, COUNT(b.id_buku) AS total_buku
     FROM kategori k LEFT JOIN buku b ON b.id_kategori = k.id_kategori
     GROUP BY k.id_kategori ORDER BY k.nama_kategori LIMIT 5'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="home-mosaic">
  <div class="home-intro panel-blue">
    <span class="eyebrow">COLINBOOKSTORE / 01</span>
    <h1>Buka halaman.<br>
<em>Temukan arah.</em>
</h1>
    <p>Ruang baca digital untuk buku-buku yang membuat rasa ingin tahu tetap menyala.</p>
    <a class="button button-dark" href="<?= url('buku.php') ?>">Jelajahi koleksi <span>↗</span>
</a>
    <div class="intro-note">
<span class="line">
</span>
<span>Dipilih dengan rasa ingin tahu,<br>dikemas untuk perjalananmu.</span>
</div>
  </div>
  <div class="home-stamp panel-cream">
    <span class="stamp-number">25°</span>
    <span class="stamp-copy">cerita<br>untuk dibaca<br>pelan-pelan</span>
    <span class="stamp-ring" aria-hidden="true">✳</span>
  </div>
  <div class="home-feature panel-white">
    <span class="eyebrow">EDISI PILIHAN</span>
    <h2>Belanja buku<br>
<span>tanpa bising.</span>
</h2>
    <p>Filter berdasarkan minat, lihat detail dengan jernih, lalu biarkan kami mengurus sisanya.</p>
    <a class="text-link" href="<?= url('about.php') ?>">Cara kami memilih <span>→</span>
</a>
  </div>
  <div class="home-category panel-ink">
    <span class="eyebrow eyebrow-light">KATEGORI</span>
    <?php foreach ($categories as $category): ?>
      <a href="<?= url('buku.php?kategori=' . $category['id_kategori']) ?>">
<span>
<?= e($category['nama_kategori']) ?>
</span>
<small>
<?= (int) $category['total_buku'] ?>
</small>
</a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section-block section-books">
  <div class="section-headline">
    <div>
<span class="eyebrow">KOLEKSI TERBARU</span>
<h2>Baru di rak kami</h2>
</div>
    <a class="text-link" href="<?= url('buku.php') ?>">Lihat semua <span>→</span>
</a>
  </div>
  <?php if ($books): ?>
    <div class="book-grid">
      <?php foreach ($books as $book): ?>
        <?php $returnTo = 'index.php'; require __DIR__ . '/includes/book_card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
<span class="empty-symbol">∅</span>
<h2>Rak masih disiapkan</h2>
<p>Admin belum menambahkan buku.</p>
</div>
  <?php endif; ?>
</section>

<section class="quote-strip">
  <span class="quote-mark">“</span>
  <p>Buku yang tepat tidak selalu memberi jawaban.<br>Sering kali, ia memberi pertanyaan yang lebih baik.</p>
  <span class="quote-attribution">— ColinBookStore note</span>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
