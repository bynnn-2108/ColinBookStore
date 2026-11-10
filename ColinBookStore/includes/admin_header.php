<?php
/** Shell admin: autentikasi role, rail studio, topbar, dan toggle tema admin. */

require_once __DIR__ . '/functions.php';
require_admin();
$page_title = $page_title ?? 'Admin';
$admin_page = $admin_page ?? '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
<?= e($page_title) ?> | ColinBookStore Studio</title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css?v=20261109-theme-3') ?>">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-rail">
      <a class="brand brand-admin" href="<?= url('admin/dashboard.php') ?>">
        <span class="brand-monogram">C</span>
        <span>
<b>ColinBookStore</b>
<small>Studio admin</small>
</span>
      </a>
      <p class="admin-kicker">WORKSPACE</p>
      <nav class="admin-nav" aria-label="Navigasi admin">
        <a class="<?= $admin_page === 'dashboard' ? 'is-active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
<span>◈</span>Ringkasan</a>
        <a class="<?= $admin_page === 'orders' ? 'is-active' : '' ?>" href="<?= url('admin/order.php') ?>">
<span>◫</span>Pesanan</a>
        <a class="<?= $admin_page === 'books' ? 'is-active' : '' ?>" href="<?= url('admin/buku.php') ?>">
<span>▦</span>Buku</a>
        <a class="<?= $admin_page === 'categories' ? 'is-active' : '' ?>" href="<?= url('admin/kategori.php') ?>">
<span>◇</span>Kategori</a>
        <a class="<?= $admin_page === 'users' ? 'is-active' : '' ?>" href="<?= url('admin/user.php') ?>">
<span>♙</span>Customer</a>
      </nav>
      <div class="admin-rail-foot">
        <span class="admin-user-dot" aria-hidden="true">
</span>
        <span>
<?= e($_SESSION['admin_nama'] ?? 'Administrator') ?>
</span>
        <a href="<?= url('admin/logout.php') ?>" aria-label="Keluar dari panel admin">↗</a>
      </div>
    </aside>
    <main class="admin-main">
      <header class="admin-topbar">
        <div>
<span class="admin-mobile-brand">COLINBOOKSTORE / </span>STUDIO</div>
        <div class="admin-topbar-actions">
          <button class="admin-theme-toggle" type="button" data-dark-mode-toggle aria-pressed="false" aria-label="Aktifkan atau matikan mode gelap admin">
            <span aria-hidden="true">◐</span>
            <span class="admin-theme-copy">Mode <b data-dark-mode-label>OFF</b></span>
          </button>
          <a href="<?= url('index.php') ?>" target="_blank" rel="noopener">Lihat toko ↗</a>
        </div>
      </header>
      <div class="admin-content">
        <div class="admin-heading">
          <div>
<span class="eyebrow">ColinBookStore Studio</span>
<h1>
<?= e($page_title) ?>
</h1>
</div>
          <span class="admin-date">
<?= date('d M Y') ?>
</span>
        </div>
        <?php display_flash(); ?>
