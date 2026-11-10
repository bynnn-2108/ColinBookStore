<?php
/** Halaman sukses: menampilkan invoice dan detail pesanan yang baru dibuat. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$orderId = (int) ($_GET['id'] ?? 0);
$statement = $pdo->prepare('SELECT o.*, u.nama, u.email, u.alamat, u.nomor_telepon FROM orders o JOIN `user` u ON u.id_user = o.id_user WHERE o.id_order = ? AND o.id_user = ?');
$statement->execute([$orderId, (int) $_SESSION['user_id']]);
$order = $statement->fetch();
if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    redirect('pesanan.php');
}
$detail = $pdo->prepare('SELECT od.*, b.judul_buku FROM order_detail od JOIN buku b ON b.id_buku = od.id_buku WHERE od.id_order = ? ORDER BY od.id_detail');
$detail->execute([$orderId]);
$details = $detail->fetchAll();
$buyer = $order;
$page_title = 'Pesanan Berhasil';
$active_page = 'orders';
require __DIR__ . '/includes/header.php';
?>
<section class="success-heading">
<span class="success-check">✓</span>
<div>
<span class="eyebrow">PESANAN BERHASIL DIBUAT</span>
<h1>Terima kasih,<br>
<?= e($order['nama']) ?>.</h1>
<p>Pesananmu sudah tercatat dengan nomor <strong>
<?= e(invoice_code($orderId)) ?>
</strong>.</p>
</div>
<span class="badge badge-<?= order_status_class($order['status']) ?>">
<?= e($order['status']) ?>
</span>
</section>
<?php if ($order['bukti_pembayaran']): ?>
<div class="alert alert-info">Bukti transfer sudah tersimpan dan akan ditinjau admin. <a href="<?= url('order_attachment.php?id=' . $orderId) ?>" target="_blank" rel="noopener">Lihat bukti ↗</a>
</div>
<?php endif; ?>
<div class="receipt-wrap">
<?php require __DIR__ . '/includes/receipt.php'; ?>
<div class="receipt-actions">
<button class="button button-dark" type="button" onclick="window.print()">Cetak struk</button>
<a class="button button-outline" href="<?= url('pesanan.php') ?>">Riwayat pesanan</a>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
