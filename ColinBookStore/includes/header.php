<?php
/** Shell customer: rail desktop, topbar, skip link, dan pembuka main content. */

require_once __DIR__ . '/functions.php';
$page_title = $page_title ?? 'Ruang Baca';
$active_page = $active_page ?? '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="ColinBookStore — toko buku online dengan koleksi terkurasi.">
  <title>
<?= e($page_title) ?> | ColinBookStore</title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css?v=20261109-theme-3') ?>">
</head>
<body>
  <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
  <div class="app-frame">
    <aside class="rail" aria-label="Navigasi utama">
      <a class="brand brand-rail" href="<?= url('index.php') ?>" aria-label="ColinBookStore, beranda">
        <span class="brand-monogram">C</span>
        <span class="brand-wordmark">Colin<br>BookStore</span>
      </a>

      <nav class="rail-nav">
        <a class="rail-link <?= $active_page === 'home' ? 'is-active' : '' ?>" href="<?= url('index.php') ?>">
          <span aria-hidden="true">⌂</span>
<span>Beranda</span>
        </a>
        <a class="rail-link <?= $active_page === 'catalog' ? 'is-active' : '' ?>" href="<?= url('buku.php') ?>">
          <span aria-hidden="true">▦</span>
<span>Koleksi</span>
        </a>
        <a class="rail-link <?= $active_page === 'orders' ? 'is-active' : '' ?>" href="<?= url('pesanan.php') ?>">
          <span aria-hidden="true">◫</span>
<span>Pesanan</span>
        </a>
        <a class="rail-link <?= $active_page === 'settings' ? 'is-active' : '' ?>" href="<?= url('pengaturan.php') ?>">
          <span aria-hidden="true">◌</span>
<span>Pengaturan</span>
        </a>
      </nav>

      <div class="rail-bottom">
        <a class="rail-cart" href="<?= url('cart.php') ?>" aria-label="Keranjang, <?= cart_count() ?> item">
          <span aria-hidden="true">🛒</span>
          <span>Keranjang</span>
          <?php if (is_user_logged_in()): ?>
<b>
<?= cart_count() ?>
</b>
<?php endif; ?>
        </a>

        <?php if (is_user_logged_in()): ?>
          <details class="profile-menu">
            <summary aria-label="Buka menu profil">
              <span class="avatar" aria-hidden="true">
<?= e(strtoupper(mb_substr($_SESSION['user_nama'] ?? 'C', 0, 1))) ?>
</span>
              <span class="profile-menu-name">
<?= e($_SESSION['user_nama'] ?? 'Profil') ?>
</span>
              <span aria-hidden="true">⌄</span>
            </summary>
            <div class="profile-popover">
              <a href="<?= url('kelola_akun.php') ?>">Profil saya</a>
              <a href="<?= url('pengaturan.php') ?>">Pengaturan</a>
              <a href="<?= url('logout.php') ?>">Keluar</a>
            </div>
          </details>
        <?php else: ?>
          <a class="rail-register" href="<?= url('register.php') ?>">Daftar</a>
          <a class="rail-login" href="<?= url('login.php') ?>">Masuk</a>
        <?php endif; ?>
      </div>
    </aside>

    <div class="content-column">
      <header class="topbar">
        <div class="topbar-context">
          <span class="topbar-dot" aria-hidden="true">
</span>
          <span>Ruang baca pilihan</span>
        </div>
        <div class="topbar-tools">
          <a href="<?= url('about.php') ?>">Tentang kami</a>
          <a href="<?= url('contact.php') ?>">Bantuan</a>
        </div>
      </header>
      <main id="main-content" class="page-content page-transition">
        <?php display_flash(); ?>
