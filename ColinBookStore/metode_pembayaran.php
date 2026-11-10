<?php
/** Halaman referensi metode pembayaran dan nomor rekening transfer. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$page_title = 'Metode Pembayaran';
$active_page = 'settings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">AKUN / PEMBAYARAN</span>
<h1>Metode bayar.</h1>
</div>
<a class="text-link" href="<?= url('pengaturan.php') ?>">← Pengaturan</a>
</section>
<div class="method-grid">
<article class="method-card panel-blue">
<span class="method-number">01</span>
<span class="eyebrow">TRANSFER MANUAL</span>
<h2>Transfer lalu<br>unggah bukti.</h2>
<p>Gunakan nomor rekening berikut saat checkout. Admin akan memverifikasi sebelum pesanan diproses.</p>
<div class="method-account">
<span>Nomor rekening</span>
<strong>082263872834</strong>
<small>a.n. ColinBookStore</small>
<button type="button" data-copy-value="082263872834">Salin nomor</button>
</div>
</article>
<article class="method-card panel-cream">
<span class="method-number">02</span>
<span class="eyebrow">CASH ON DELIVERY</span>
<h2>Bayar saat<br>buku tiba.</h2>
<p>Pilih COD untuk membayar kepada kurir saat pesanan sampai. Tidak ada bukti transfer yang diperlukan.</p>
<div class="method-icon-large">▣</div>
</article>
</div>
<div class="alert alert-info">Jangan kirim bukti pembayaran melalui chat. Gunakan kolom upload di halaman checkout agar tercatat aman pada pesananmu.</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
