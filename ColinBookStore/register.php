<?php
/** Registrasi customer: memvalidasi data dan menyimpan password dengan hash. */

require_once __DIR__ . '/includes/functions.php';
if (is_user_logged_in()) {
    redirect('index.php');
}
$old = ['nama' => '', 'email' => '', 'alamat' => '', 'nomor_telepon' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($old as $key => $value) {
        $old[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['konfirmasi_password'] ?? '');
    if (in_array('', $old, true) || $password === '') {
        $error = 'Semua data wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirmation) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $check = $pdo->prepare('SELECT id_user FROM `user` WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $insert = $pdo->prepare('INSERT INTO `user` (nama, email, password, alamat, nomor_telepon) VALUES (?, ?, ?, ?, ?)');
            $insert->execute([$old['nama'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['alamat'], $old['nomor_telepon']]);
            set_flash('success', 'Registrasi berhasil. Silakan masuk.');
            redirect('login.php');
        }
    }
}
$page_title = 'Daftar';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout auth-layout-register">
<div class="auth-side panel-ink">
<span class="eyebrow eyebrow-light">COLINBOOKSTORE / MEMBER</span>
<h1>Ruang baca<br>
<em>dimulai di sini.</em>
</h1>
<p>Buat akun untuk menyimpan rak pilihan, checkout lebih cepat, dan melihat jejak belanjamu.</p>
<div class="register-list">
<span>01 / koleksi pilihan</span>
<span>02 / checkout aman</span>
<span>03 / riwayat rapi</span>
</div>
</div>
  <div class="auth-card">
<span class="eyebrow">AKUN PEMBACA</span>
<h2>Buat akun baru.</h2>
<p class="helper">Isi data di bawah untuk mulai membaca.</p>
<?php if (!empty($error)): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<div class="form-group">
<label for="nama">Nama lengkap</label>
<input id="nama" name="nama" autocomplete="name" required value="<?= e($old['nama']) ?>">
</div>
<div class="form-row">
<div class="form-group">
<label for="email">Email</label>
<input id="email" type="email" name="email" autocomplete="email" required value="<?= e($old['email']) ?>">
</div>
<div class="form-group">
<label for="nomor_telepon">Nomor telepon</label>
<input id="nomor_telepon" name="nomor_telepon" autocomplete="tel" required value="<?= e($old['nomor_telepon']) ?>">
</div>
</div>
<div class="form-group">
<label for="alamat">Alamat lengkap</label>
<textarea id="alamat" name="alamat" autocomplete="street-address" required>
<?= e($old['alamat']) ?>
</textarea>
</div>
<div class="form-row">
<div class="form-group">
<label for="password">Password</label>
<input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
</div>
<div class="form-group">
<label for="konfirmasi_password">Konfirmasi password</label>
<input id="konfirmasi_password" type="password" name="konfirmasi_password" minlength="8" autocomplete="new-password" required>
</div>
</div>
<button class="button button-dark button-block" type="submit">Daftar sekarang <span>↗</span>
</button>
</form>
<p class="auth-foot">Sudah punya akun? <a href="<?= url('login.php') ?>">Masuk di sini</a>
</p>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
