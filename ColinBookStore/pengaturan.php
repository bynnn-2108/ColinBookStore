<?php
/** Pengaturan customer: menyediakan preferensi tema dan tautan keamanan akun. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$page_title = 'Pengaturan';
$active_page = 'settings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">AKUN / PREFERENSI</span>
<h1>Pengaturan.</h1>
<p class="page-lead">Atur cara ColinBookStore hadir di perangkatmu.</p>
</div>
<span class="settings-orbit" aria-hidden="true">◌</span>
</section>
<div class="settings-layout">
<aside class="settings-aside panel-blue">
<span class="eyebrow">PUSAT PREFERENSI</span>
<h2>Ruang yang terasa<br>seperti milikmu.</h2>
<p>Kelola tampilan, transaksi, dan keamanan dari satu tempat.</p>
<span class="settings-aside-number">03</span>
</aside>
<section class="settings-panel">
  <div class="settings-section">
<div>
<span class="eyebrow">01 / TAMPILAN</span>
<h2>Pengalaman aplikasi</h2>
</div>
<div class="settings-line">
<span class="settings-icon">◐</span>
<div>
<strong>Mode gelap</strong>
<p>Kurangi silau saat membaca di malam hari.</p>
</div>
<button class="theme-toggle" type="button" data-dark-mode-toggle aria-label="Aktifkan atau matikan mode gelap" aria-pressed="false">
<span class="theme-track">
<span>
</span>
</span>
<b data-dark-mode-label>OFF</b>
</button>
</div>
</div>
  <div class="settings-section">
<div>
<span class="eyebrow">02 / TRANSAKSI</span>
<h2>Pesanan dan pembayaran</h2>
</div>
<a class="settings-link" href="<?= url('pesanan.php') ?>">
<span class="settings-icon">◫</span>
<div>
<strong>Log transaksi</strong>
<p>Lihat status, total, dan bukti pesanan.</p>
</div>
<span>→</span>
</a>
<a class="settings-link" href="<?= url('metode_pembayaran.php') ?>">
<span class="settings-icon">⇄</span>
<div>
<strong>Metode pembayaran</strong>
<p>COD dan transfer manual.</p>
</div>
<span>→</span>
</a>
</div>
  <div class="settings-section">
<div>
<span class="eyebrow">03 / KEAMANAN</span>
<h2>Profil dan akses</h2>
</div>
<a class="settings-link" href="<?= url('kelola_akun.php') ?>">
<span class="settings-icon">♙</span>
<div>
<strong>Kelola akun</strong>
<p>Ubah data pribadi, password, atau hapus akun.</p>
</div>
<span>→</span>
</a>
</div>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
