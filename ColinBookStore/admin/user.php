<?php
/** Panel customer: mencari dan menampilkan data customer terdaftar. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();
$query = trim((string) ($_GET['q'] ?? ''));
$statement = $pdo->prepare('SELECT id_user, nama, email, alamat, nomor_telepon, created_at FROM `user` WHERE nama LIKE ? OR email LIKE ? ORDER BY created_at DESC');
$statement->execute(['%' . $query . '%', '%' . $query . '%']);
$users = $statement->fetchAll();
$page_title = 'Customer';
$admin_page = 'users';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-toolbar">
<p class="helper">Daftar customer terdaftar dan data pengiriman mereka.</p>
<form method="get">
<label class="sr-only" for="user-search">Cari customer</label>
<input id="user-search" name="q" value="<?= e($query) ?>" placeholder="Cari nama atau email">
<button class="button button-dark button-small" type="submit">Cari</button>
</form>
</div>
<section class="admin-card admin-table-wrap">
<table class="data-table">
<thead>
<tr>
<th>Customer</th>
<th>Email</th>
<th>Telepon</th>
<th>Alamat</th>
<th>Bergabung</th>
</tr>
</thead>
<tbody>
<?php if ($users): ?>
<?php foreach ($users as $user): ?>
<tr>
<td>
<strong>
<?= e($user['nama']) ?>
</strong>
</td>
<td>
<?= e($user['email']) ?>
</td>
<td>
<?= e($user['nomor_telepon']) ?>
</td>
<td class="cell-wrap">
<?= nl2br(e($user['alamat'])) ?>
</td>
<td>
<?= date('d M Y', strtotime($user['created_at'])) ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="5" class="helper">Customer tidak ditemukan.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</section>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
