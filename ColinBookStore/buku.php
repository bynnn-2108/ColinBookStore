<?php
/** Katalog utama: mengambil buku, pencarian, dan filter kategori. */

require_once __DIR__ . '/includes/functions.php';

// Normalisasi input pencarian dan kategori sebelum dipakai sebagai parameter query.
$query = trim((string) ($_GET['q'] ?? ''));
$category = (int) ($_GET['kategori'] ?? 0);
$categories = $pdo->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$where = [];
$params = [];

// Tambahkan pencarian lintas judul, penulis, dan penerbit bila query diisi.
if ($query !== '') {
    $where[] = '(b.judul_buku LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)';
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
}
// Tambahkan filter kategori secara kondisional agar URL tanpa filter tetap valid.
if ($category > 0) {
    $where[] = 'b.id_kategori = ?';
    $params[] = $category;
}

$sql = 'SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON k.id_kategori = b.id_kategori';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY b.judul_buku ASC';
// Prepared statement menjaga parameter pencarian tidak menjadi SQL injection.
$statement = $pdo->prepare($sql);
$statement->execute($params);
$books = $statement->fetchAll();
$page_title = 'Koleksi Buku';
$active_page = 'catalog';

require __DIR__ . '/includes/header.php';
?>
<section class="catalog-heading">
  <div>
<span class="eyebrow">RAK TERBUKA / <?= count($books) ?>
</span>
<h1>Koleksi buku<br>
<em>untuk dibaca.</em>
</h1>
</div>
  <p>Telusuri fiksi, ide, teknologi, dan cerita kecil yang layak mendapat tempat di harimu.</p>
</section>

<section class="catalog-toolbar">
  <form class="catalog-search" method="get" role="search">
    <label class="sr-only" for="q">Cari judul, penulis, atau penerbit</label>
    <span aria-hidden="true">⌕</span>
<input id="q" name="q" value="<?= e($query) ?>" placeholder="Cari judul, penulis, penerbit...">
<button type="submit">Cari</button>
  </form>
  <div class="filter-links" aria-label="Filter kategori">
    <a class="<?= $category === 0 ? 'is-selected' : '' ?>" href="<?= url('buku.php' . ($query ? '?q=' . urlencode($query) : '')) ?>">Semua</a>
    <?php foreach ($categories as $item): ?>
      <a class="<?= $category === (int) $item['id_kategori'] ? 'is-selected' : '' ?>" href="<?= url('buku.php?kategori=' . $item['id_kategori'] . ($query ? '&q=' . urlencode($query) : '')) ?>">
<?= e($item['nama_kategori']) ?>
</a>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($books): ?>
  <div class="book-grid book-grid-catalog">
    <?php foreach ($books as $book): ?>
      <?php $returnTo = 'buku.php' . ($query ? '?q=' . urlencode($query) : ''); require __DIR__ . '/includes/book_card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-state">
<span class="empty-symbol">⌕</span>
<h2>Tidak ada hasil yang cocok</h2>
<p>Coba kata kunci atau kategori lain.</p>
<a class="button button-dark" href="<?= url('buku.php') ?>">Reset pencarian</a>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
