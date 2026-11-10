<?php
/** Riwayat pesanan: membatasi daftar order berdasarkan user yang sedang login. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$statement = $pdo->prepare('SELECT * FROM orders WHERE id_user = ? ORDER BY tanggal_order DESC');
$statement->execute([(int) $_SESSION['user_id']]);
$orders = $statement->fetchAll();
$page_title = 'Riwayat Pesanan';
$active_page = 'orders';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">AKUN / JEJAK TRANSAKSI</span>
<h1>Pesananmu.</h1>
</div>
<a class="text-link" href="<?= url('buku.php') ?>">Belanja lagi <span>→</span>
</a>
</section>
<section class="order-history">
  <?php if ($orders): ?>
    <?php foreach ($orders as $order): ?>
      <article class="order-history-row">
<div class="order-index">#<?= e(invoice_code((int) $order['id_order'])) ?>
</div>
<div>
<span class="eyebrow">
<?= date('d M Y · H:i', strtotime($order['tanggal_order'])) ?>
</span>
<h2>
<?= e($order['metode_pembayaran']) ?> <span>· <?= rupiah($order['total_harga']) ?>
</span>
</h2>
</div>
<span class="badge badge-<?= order_status_class($order['status']) ?>">
<?= e($order['status']) ?>
</span>
<div class="order-actions">
<?php if ($order['bukti_pembayaran']): ?>
<a href="<?= url('order_attachment.php?id=' . $order['id_order']) ?>" target="_blank" rel="noopener">Bukti ↗</a>
<?php endif; ?>
<a href="<?= url('order_success.php?id=' . $order['id_order']) ?>">Lihat detail →</a>
</div>
</article>
    <?php endforeach; ?>
  <?php else: ?>
<div class="empty-state">
<span class="empty-symbol">◫</span>
<h2>Belum ada pesanan</h2>
<p>Pesanan pertamamu akan muncul di sini.</p>
<a class="button button-dark" href="<?= url('buku.php') ?>">Temukan buku</a>
</div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
