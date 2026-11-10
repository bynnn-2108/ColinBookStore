<?php
/** Dashboard admin: menghitung statistik, order terbaru, dan pesan customer. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();
$stats = [
    ['label' => 'Buku aktif', 'value' => $pdo->query('SELECT COUNT(*) FROM buku')->fetchColumn(), 'note' => 'judul di katalog'],
    ['label' => 'Customer', 'value' => $pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn(), 'note' => 'akun terdaftar'],
    ['label' => 'Pesanan', 'value' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(), 'note' => 'sepanjang waktu'],
    ['label' => 'Menunggu', 'value' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Menunggu Konfirmasi'")->fetchColumn(), 'note' => 'perlu perhatian'],
];
$orders = $pdo->query('SELECT o.*, u.nama FROM orders o JOIN `user` u ON u.id_user = o.id_user ORDER BY o.tanggal_order DESC LIMIT 6')->fetchAll();
$messages = $pdo->query('SELECT p.*, u.nama FROM pesan p JOIN `user` u ON u.id_user = p.id_user ORDER BY p.tanggal DESC LIMIT 4')->fetchAll();
$page_title = 'Ringkasan';
$admin_page = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>
<section class="stat-grid">
  <?php foreach ($stats as $index => $stat): ?>
<article class="stat-card stat-<?= $index + 1 ?>">
<span class="stat-index">0<?= $index + 1 ?>
</span>
<span class="eyebrow">
<?= e($stat['label']) ?>
</span>
<strong>
<?= number_format((int) $stat['value'], 0, ',', '.') ?>
</strong>
<small>
<?= e($stat['note']) ?>
</small>
</article>
<?php endforeach; ?>
</section>
<div class="admin-dashboard-grid">
<section class="admin-card admin-orders-card">
<div class="admin-card-head">
<div>
<span class="eyebrow">LIVE / PESANAN TERBARU</span>
<h2>Aktivitas terkini</h2>
</div>
<a href="<?= url('admin/order.php') ?>">Semua pesanan →</a>
</div>
<div class="admin-table-wrap">
<table class="data-table">
<thead>
<tr>
<th>Invoice</th>
<th>Customer</th>
<th>Total</th>
<th>Status</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php if ($orders): ?>
<?php foreach ($orders as $order): ?>
<tr>
<td>
<strong>
<?= e(invoice_code((int) $order['id_order'])) ?>
</strong>
<br>
<small>
<?= date('d M Y H:i', strtotime($order['tanggal_order'])) ?>
</small>
</td>
<td>
<?= e($order['nama']) ?>
</td>
<td>
<?= rupiah($order['total_harga']) ?>
</td>
<td>
<span class="badge badge-<?= order_status_class($order['status']) ?>">
<?= e($order['status']) ?>
</span>
</td>
<td>
<a href="<?= url('admin/order.php?detail=' . $order['id_order']) ?>">Detail ↗</a>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="5" class="helper">Belum ada pesanan.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
<aside class="admin-card admin-note-card">
<span class="eyebrow">CATATAN HARI INI</span>
<h2>Jaga rak tetap<br>hidup.</h2>
<p>Perbarui stok dan pastikan setiap bukti transfer ditinjau sebelum pesanan diproses.</p>
<a class="button button-dark" href="<?= url('admin/buku.php') ?>">Kelola katalog <span>↗</span>
</a>
</aside>
</div>
<section class="admin-card admin-message-card">
<div class="admin-card-head">
<div>
<span class="eyebrow">CUSTOMER VOICE</span>
<h2>Pesan terbaru</h2>
</div>
<a href="<?= url('admin/user.php') ?>">Lihat customer →</a>
</div>
<?php if ($messages): ?>
<div class="message-grid">
<?php foreach ($messages as $message): ?>
<article>
<strong>
<?= e($message['nama']) ?>
</strong>
<span>
<?= date('d M Y', strtotime($message['tanggal'])) ?>
</span>
<p>
<?= e($message['pesan']) ?>
</p>
</article>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="helper">Belum ada pesan dari customer.</p>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
