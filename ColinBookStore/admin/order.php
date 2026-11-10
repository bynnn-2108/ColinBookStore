<?php
/** Panel order: menampilkan detail customer dan mengubah status pesanan. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();
$statuses = ['Menunggu Konfirmasi', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];

// Update status order dibatasi pada daftar status bisnis yang diizinkan.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int) ($_POST['id_order'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, $statuses, true)) {
        set_flash('danger', 'Status pesanan tidak valid.');
    } else {
        $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE id_order = ?');
        $statement->execute([$status, $orderId]);
        set_flash('success', 'Status pesanan diperbarui.');
    }
    redirect('admin/order.php?detail=' . $orderId);
}

$detailId = (int) ($_GET['detail'] ?? 0);
// Query detail memakai join customer agar admin melihat konteks penerima.
if ($detailId > 0) {
    $statement = $pdo->prepare('SELECT o.*, u.nama, u.email, u.alamat, u.nomor_telepon FROM orders o JOIN `user` u ON u.id_user = o.id_user WHERE o.id_order = ?');
    $statement->execute([$detailId]);
    $order = $statement->fetch();
    if (!$order) {
        set_flash('danger', 'Pesanan tidak ditemukan.');
        redirect('admin/order.php');
    }
    $detail = $pdo->prepare('SELECT od.*, b.judul_buku FROM order_detail od JOIN buku b ON b.id_buku = od.id_buku WHERE od.id_order = ?');
    $detail->execute([$detailId]);
    $details = $detail->fetchAll();
    $buyer = $order;
    $page_title = 'Detail Pesanan';
    $admin_page = 'orders';
    require __DIR__ . '/../includes/admin_header.php';
    ?>
    <div class="admin-detail-grid">
<section class="admin-card">
<div class="admin-card-head">
<div>
<span class="eyebrow">INVOICE / <?= e(invoice_code($detailId)) ?>
</span>
<h2>
<?= e($order['nama']) ?>
</h2>
</div>
<span class="badge badge-<?= order_status_class($order['status']) ?>">
<?= e($order['status']) ?>
</span>
</div>
<div class="customer-facts">
<div>
<span>Email</span>
<strong>
<?= e($order['email']) ?>
</strong>
</div>
<div>
<span>Telepon</span>
<strong>
<?= e($order['nomor_telepon']) ?>
</strong>
</div>
<div>
<span>Alamat</span>
<strong>
<?= nl2br(e($order['alamat'])) ?>
</strong>
</div>
<div>
<span>Metode</span>
<strong>
<?= e($order['metode_pembayaran']) ?>
</strong>
</div>
</div>
<div class="admin-attachment">
<span class="eyebrow">BUKTI PEMBAYARAN</span>
<?php if ($order['bukti_pembayaran']): ?>
<?php $attachmentUrl = url('admin/order_attachment.php?id=' . $detailId); $isPdf = str_ends_with((string) $order['bukti_pembayaran'], '.pdf'); ?>
<?php if ($isPdf): ?>
<a class="pdf-attachment" href="<?= $attachmentUrl ?>" target="_blank" rel="noopener">
<span>PDF</span>
<strong>Buka bukti pembayaran ↗</strong>
</a>
<?php else: ?>
<a href="<?= $attachmentUrl ?>" target="_blank" rel="noopener">
<img class="admin-proof-image" src="<?= $attachmentUrl ?>" alt="Bukti pembayaran <?= e($order['nama']) ?>">
</a>
<?php endif; ?>
<?php elseif ($order['metode_pembayaran'] === 'TRANSFER'): ?>
<p class="helper">Bukti transfer belum diunggah.</p>
<?php else: ?>
<p class="helper">Pesanan COD tidak memerlukan bukti.</p>
<?php endif; ?>
</div>
<form method="post" class="status-form">
<?= csrf_field() ?>
<input type="hidden" name="id_order" value="<?= $detailId ?>">
<div class="form-group">
<label for="status">Status pesanan</label>
<select id="status" name="status">
<?php foreach ($statuses as $status): ?>
<option value="<?= e($status) ?>" <?= $status === $order['status'] ? 'selected' : '' ?>>
<?= e($status) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<button class="button button-dark" type="submit">Simpan status</button>
<a class="button button-outline" href="<?= url('admin/order.php') ?>">Kembali</a>
</form>
</section>
<section>
<div class="admin-receipt-label">
<span class="eyebrow">RINGKASAN CETAK</span>
</div>
<?php require __DIR__ . '/../includes/receipt.php'; ?>
</section>
</div>
    <?php require __DIR__ . '/../includes/admin_footer.php'; exit;
}

$filter = trim((string) ($_GET['status'] ?? ''));
$where = '';
$params = [];
if (in_array($filter, $statuses, true)) {
    $where = ' WHERE o.status = ?';
    $params[] = $filter;
}
$statement = $pdo->prepare('SELECT o.*, u.nama, u.email FROM orders o JOIN `user` u ON u.id_user = o.id_user' . $where . ' ORDER BY o.tanggal_order DESC');
$statement->execute($params);
$orders = $statement->fetchAll();
$page_title = 'Pesanan';
$admin_page = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-toolbar">
<p class="helper">Tinjau pembayaran, customer, dan progres pemenuhan.</p>
<form method="get">
<label class="sr-only" for="status">Filter status</label>
<select id="status" name="status" data-auto-submit>
<option value="">Semua status</option>
<?php foreach ($statuses as $status): ?>
<option value="<?= e($status) ?>" <?= $filter === $status ? 'selected' : '' ?>>
<?= e($status) ?>
</option>
<?php endforeach; ?>
</select>
</form>
</div>
<section class="admin-card admin-table-wrap">
<table class="data-table">
<thead>
<tr>
<th>Invoice</th>
<th>Customer</th>
<th>Tanggal</th>
<th>Bayar</th>
<th>Bukti</th>
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
</td>
<td>
<?= e($order['nama']) ?>
<br>
<small>
<?= e($order['email']) ?>
</small>
</td>
<td>
<?= date('d M Y H:i', strtotime($order['tanggal_order'])) ?>
</td>
<td>
<?= e($order['metode_pembayaran']) ?>
</td>
<td>
<?= $order['bukti_pembayaran'] ? '<span class="table-check">✓ terlampir</span>' : '—' ?>
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
<a class="button button-small button-outline" href="<?= url('admin/order.php?detail=' . $order['id_order']) ?>">Detail</a>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="8" class="helper">Belum ada pesanan.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</section>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
