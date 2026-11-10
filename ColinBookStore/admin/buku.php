<?php
/** Panel buku: CRUD katalog dan validasi upload gambar sampul. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();

function book_form_values(): array
{
    // Cast seluruh field buku agar format data seragam sebelum validasi.
    return [
        (int) ($_POST['id_kategori'] ?? 0),
        trim((string) ($_POST['judul_buku'] ?? '')),
        trim((string) ($_POST['penulis'] ?? '')),
        trim((string) ($_POST['penerbit'] ?? '')),
        (int) ($_POST['tahun_terbit'] ?? 0),
        (float) ($_POST['harga'] ?? -1),
        (int) ($_POST['stok'] ?? -1),
        trim((string) ($_POST['deskripsi'] ?? '')),
    ];
}

// Semua aksi CRUD buku hanya menerima POST dengan CSRF token valid.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $bookId = (int) ($_POST['id_buku'] ?? 0);
    $uploadedImage = null;
    try {
        // Delete menghapus record buku lalu membersihkan file gambar lama.
        if ($action === 'delete') {
            $find = $pdo->prepare('SELECT gambar FROM buku WHERE id_buku = ?');
            $find->execute([$bookId]);
            $old = $find->fetch();
            $delete = $pdo->prepare('DELETE FROM buku WHERE id_buku = ?');
            $delete->execute([$bookId]);
            if (!$delete->rowCount()) {
                throw new RuntimeException('Buku tidak ditemukan.');
            }
            delete_upload($old['gambar'] ?? null);
            set_flash('success', 'Buku dihapus dari katalog.');
            redirect('admin/buku.php');
        }
        if (!in_array($action, ['add', 'edit'], true)) {
            throw new RuntimeException('Aksi buku tidak valid.');
        }
        [$category, $title, $author, $publisher, $year, $price, $stock, $description] = book_form_values();
        if ($category < 1 || $title === '' || $author === '' || $publisher === '' || $year < 1000 || $year > ((int) date('Y') + 1) || $price < 0 || $stock < 0 || $description === '') {
            throw new RuntimeException('Mohon isi seluruh data buku dengan benar.');
        }
        $image = $_FILES['gambar'] ?? [];
        if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$uploaded, $result] = upload_file($image, 'uploads/books', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']);
            if (!$uploaded) {
                throw new RuntimeException($result);
            }
            $uploadedImage = $result;
        }
        // Add membuat record baru setelah kategori dan field wajib lolos validasi.
        if ($action === 'add') {
            $insert = $pdo->prepare('INSERT INTO buku (id_kategori, judul_buku, penulis, penerbit, tahun_terbit, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$category, $title, $author, $publisher, $year, $price, $stock, $description, $uploadedImage]);
            set_flash('success', 'Buku baru ditambahkan.');
        } else {
            // Edit memperbarui metadata dan mengganti gambar jika upload baru valid.
            $find = $pdo->prepare('SELECT gambar FROM buku WHERE id_buku = ?');
            $find->execute([$bookId]);
            $old = $find->fetch();
            if (!$old) {
                throw new RuntimeException('Data buku tidak ditemukan.');
            }
            $finalImage = $uploadedImage ?: $old['gambar'];
            $update = $pdo->prepare('UPDATE buku SET id_kategori = ?, judul_buku = ?, penulis = ?, penerbit = ?, tahun_terbit = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ? WHERE id_buku = ?');
            $update->execute([$category, $title, $author, $publisher, $year, $price, $stock, $description, $finalImage, $bookId]);
            if ($uploadedImage) {
                delete_upload($old['gambar']);
            }
            set_flash('success', 'Data buku diperbarui.');
        }
    } catch (Throwable $exception) {
        if ($uploadedImage) {
            delete_upload($uploadedImage);
        }
        set_flash('danger', $exception->getMessage());
    }
    redirect('admin/buku.php');
}

$categories = $pdo->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM buku WHERE id_buku = ?');
    $statement->execute([(int) $_GET['edit']]);
    $edit = $statement->fetch();
}
$query = trim((string) ($_GET['q'] ?? ''));
$statement = $pdo->prepare('SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON k.id_kategori = b.id_kategori WHERE b.judul_buku LIKE ? OR b.penulis LIKE ? ORDER BY b.created_at DESC');
$statement->execute(['%' . $query . '%', '%' . $query . '%']);
$books = $statement->fetchAll();
$page_title = 'Buku';
$admin_page = 'books';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-toolbar">
<p class="helper">Kelola koleksi, stok, metadata, dan sampul buku.</p>
<form method="get">
<label class="sr-only" for="book-search">Cari buku</label>
<input id="book-search" name="q" value="<?= e($query) ?>" placeholder="Cari judul atau penulis">
<button class="button button-dark button-small" type="submit">Cari</button>
</form>
</div>
<div class="admin-management-grid">
<section class="admin-card">
<span class="eyebrow">
<?= $edit ? 'EDIT JUDUL' : 'TAMBAH JUDUL' ?>
</span>
<h2>
<?= $edit ? 'Ubah buku.' : 'Buku baru.' ?>
</h2>
<form method="post" enctype="multipart/form-data" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
<?php if ($edit): ?>
<input type="hidden" name="id_buku" value="<?= $edit['id_buku'] ?>">
<?php endif; ?>
<div class="form-group">
<label for="id_kategori">Kategori</label>
<select id="id_kategori" name="id_kategori" required>
<option value="">Pilih kategori</option>
<?php foreach ($categories as $category): ?>
<option value="<?= $category['id_kategori'] ?>" <?= (int) ($edit['id_kategori'] ?? 0) === (int) $category['id_kategori'] ? 'selected' : '' ?>>
<?= e($category['nama_kategori']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label for="judul_buku">Judul buku</label>
<input id="judul_buku" name="judul_buku" required value="<?= e($edit['judul_buku'] ?? '') ?>">
</div>
<div class="form-row">
<div class="form-group">
<label for="penulis">Penulis</label>
<input id="penulis" name="penulis" required value="<?= e($edit['penulis'] ?? '') ?>">
</div>
<div class="form-group">
<label for="penerbit">Penerbit</label>
<input id="penerbit" name="penerbit" required value="<?= e($edit['penerbit'] ?? '') ?>">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label for="tahun_terbit">Tahun</label>
<input id="tahun_terbit" type="number" name="tahun_terbit" min="1000" max="<?= date('Y') + 1 ?>" required value="<?= e($edit['tahun_terbit'] ?? date('Y')) ?>">
</div>
<div class="form-group">
<label for="harga">Harga</label>
<input id="harga" type="number" name="harga" min="0" step="1000" required value="<?= e($edit['harga'] ?? '') ?>">
</div>
<div class="form-group">
<label for="stok">Stok</label>
<input id="stok" type="number" name="stok" min="0" required value="<?= e($edit['stok'] ?? 0) ?>">
</div>
</div>
<div class="form-group">
<label for="deskripsi">Deskripsi</label>
<textarea id="deskripsi" name="deskripsi" required>
<?= e($edit['deskripsi'] ?? '') ?>
</textarea>
</div>
<div class="form-group">
<label for="gambar">Sampul buku <span>(JPG, PNG, WEBP · maksimal 2 MB)</span>
</label>
<input id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
</div>
<button class="button button-dark" type="submit">
<?= $edit ? 'Simpan perubahan' : 'Tambah buku' ?>
</button>
<?php if ($edit): ?>
<a class="button button-outline" href="<?= url('admin/buku.php') ?>">Batal</a>
<?php endif; ?>
</form>
</section>
<section class="admin-card admin-table-wrap">
<div class="admin-card-head">
<div>
<span class="eyebrow">CATALOG / <?= count($books) ?>
</span>
<h2>Daftar buku</h2>
</div>
</div>
<table class="data-table">
<thead>
<tr>
<th>Judul</th>
<th>Kategori</th>
<th>Harga</th>
<th>Stok</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($books as $book): ?>
<tr>
<td>
<strong>
<?= e($book['judul_buku']) ?>
</strong>
<br>
<small>
<?= e($book['penulis']) ?>
</small>
</td>
<td>
<?= e($book['nama_kategori']) ?>
</td>
<td>
<?= rupiah($book['harga']) ?>
</td>
<td>
<?= (int) $book['stok'] ?>
</td>
<td>
<div class="row-actions">
<a class="button button-small button-outline" href="<?= url('admin/buku.php?edit=' . $book['id_buku']) ?>">Ubah</a>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id_buku" value="<?= $book['id_buku'] ?>">
<button class="button button-small button-danger" type="submit" data-confirm="Hapus buku ini?">Hapus</button>
</form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
