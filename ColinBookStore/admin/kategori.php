<?php
/** Panel kategori: menambah, mengubah, dan menghapus kategori katalog. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $name = trim((string) ($_POST['nama_kategori'] ?? ''));
    $categoryId = (int) ($_POST['id_kategori'] ?? 0);
    try {
        if (in_array($action, ['add', 'edit'], true) && $name === '') {
            throw new RuntimeException('Nama kategori wajib diisi.');
        }
        if ($action === 'add') {
            $statement = $pdo->prepare('INSERT INTO kategori (nama_kategori) VALUES (?)');
            $statement->execute([$name]);
            set_flash('success', 'Kategori ditambahkan.');
        } elseif ($action === 'edit') {
            $statement = $pdo->prepare('UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?');
            $statement->execute([$name, $categoryId]);
            set_flash('success', 'Kategori diperbarui.');
        } elseif ($action === 'delete') {
            $statement = $pdo->prepare('DELETE FROM kategori WHERE id_kategori = ?');
            $statement->execute([$categoryId]);
            set_flash('success', 'Kategori dihapus.');
        }
    } catch (Throwable $exception) {
        set_flash('danger', str_contains($exception->getMessage(), 'Integrity') ? 'Kategori masih dipakai buku.' : $exception->getMessage());
    }
    redirect('admin/kategori.php');
}
$edit = null;
if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM kategori WHERE id_kategori = ?');
    $statement->execute([(int) $_GET['edit']]);
    $edit = $statement->fetch();
}
$categories = $pdo->query('SELECT k.*, COUNT(b.id_buku) AS total_buku FROM kategori k LEFT JOIN buku b ON b.id_kategori = k.id_kategori GROUP BY k.id_kategori ORDER BY k.nama_kategori')->fetchAll();
$page_title = 'Kategori';
$admin_page = 'categories';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-management-grid category-management">
<section class="admin-card">
<span class="eyebrow">
<?= $edit ? 'EDIT KATEGORI' : 'KATEGORI BARU' ?>
</span>
<h2>
<?= $edit ? 'Ubah kategori.' : 'Tambah kategori.' ?>
</h2>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
<?php if ($edit): ?>
<input type="hidden" name="id_kategori" value="<?= $edit['id_kategori'] ?>">
<?php endif; ?>
<div class="form-group">
<label for="nama_kategori">Nama kategori</label>
<input id="nama_kategori" name="nama_kategori" required value="<?= e($edit['nama_kategori'] ?? '') ?>" placeholder="Contoh: Esai">
</div>
<button class="button button-dark" type="submit">
<?= $edit ? 'Simpan perubahan' : 'Tambah kategori' ?>
</button>
<?php if ($edit): ?>
<a class="button button-outline" href="<?= url('admin/kategori.php') ?>">Batal</a>
<?php endif; ?>
</form>
</section>
<section class="admin-card admin-table-wrap">
<span class="eyebrow">TAXONOMY / <?= count($categories) ?>
</span>
<h2>Daftar kategori</h2>
<table class="data-table">
<thead>
<tr>
<th>Kategori</th>
<th>Isi</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($categories as $category): ?>
<tr>
<td>
<strong>
<?= e($category['nama_kategori']) ?>
</strong>
</td>
<td>
<?= (int) $category['total_buku'] ?> buku</td>
<td>
<div class="row-actions">
<a class="button button-small button-outline" href="<?= url('admin/kategori.php?edit=' . $category['id_kategori']) ?>">Ubah</a>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id_kategori" value="<?= $category['id_kategori'] ?>">
<button class="button button-small button-danger" type="submit" data-confirm="Hapus kategori ini?">Hapus</button>
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
