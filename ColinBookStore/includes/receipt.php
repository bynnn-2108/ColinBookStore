<!-- Template invoice reusable untuk halaman sukses dan riwayat pesanan. -->
<div class="receipt">
  <div class="receipt-head">
    <div class="receipt-mark">C</div>
    <div>
<strong>ColinBookStore</strong>
<span>Ruang baca pilihan</span>
</div>
    <span class="receipt-invoice">#<?= e(invoice_code((int) $order['id_order'])) ?>
</span>
  </div>
  <div class="receipt-contact">Jl. Buku Impian No. 10, Indonesia · 0812-3456-7890</div>
  <hr class="receipt-rule">
  <div class="receipt-row">
<span>
<?= date('d M Y · H:i', strtotime($order['tanggal_order'])) ?>
</span>
<span>
<?= e($buyer['nama']) ?>
</span>
</div>
  <div class="receipt-address">
<?= nl2br(e($buyer['alamat'])) ?>
<br>
<?= e($buyer['nomor_telepon']) ?>
</div>
  <hr class="receipt-rule">
  <?php foreach ($details as $detail): ?>
    <div class="receipt-product">
      <strong>
<?= e($detail['judul_buku']) ?>
</strong>
      <div class="receipt-row">
<span>
<?= (int) $detail['jumlah'] ?> × <?= rupiah($detail['harga_satuan'] ?? ($detail['subtotal'] / max(1, (int) $detail['jumlah']))) ?>
</span>
<span>
<?= rupiah($detail['subtotal']) ?>
</span>
</div>
    </div>
  <?php endforeach; ?>
  <hr class="receipt-rule">
  <div class="receipt-row">
<span>Total item</span>
<span>
<?= array_sum(array_column($details, 'jumlah')) ?>
</span>
</div>
  <div class="receipt-row receipt-total">
<span>Total</span>
<strong>
<?= rupiah($order['total_harga']) ?>
</strong>
</div>
  <div class="receipt-row">
<span>Pembayaran</span>
<span>
<?= e($order['metode_pembayaran']) ?>
</span>
</div>
  <?php // Baris berikut menyimpan jejak pembayaran tunai dan kembalian COD. ?>
  <div class="receipt-row">
<span>Uang dibayar</span>
<span>
<?= $order['metode_pembayaran'] === 'COD' ? rupiah($order['uang_dibayar'] ?? 0) : '—' ?>
</span>
</div>
  <div class="receipt-row">
<span>Uang kembalian</span>
<strong>
<?= $order['metode_pembayaran'] === 'COD' ? rupiah($order['uang_kembalian'] ?? 0) : '—' ?>
</strong>
</div>
  <div class="receipt-row">
<span>Status</span>
<span>
<?= e($order['status']) ?>
</span>
</div>
  <p class="receipt-thanks">Terima kasih telah memberi ruang<br>untuk cerita baru.</p>
</div>
