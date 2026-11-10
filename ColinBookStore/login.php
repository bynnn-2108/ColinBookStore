<?php
/** Login customer: memverifikasi kredensial dan membuat session user. */

require_once __DIR__ . '/includes/functions.php';
if (is_user_logged_in()) {
    redirect('index.php');
}

$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id_user, nama, password FROM `user` WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['user_nama'] = $user['nama'];
        set_flash('success', 'Selamat datang kembali, ' . $user['nama'] . '.');
        redirect('index.php');
    }
    $error = 'Email atau password tidak tepat.';
}
$page_title = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
  <div class="auth-side panel-blue">
<span class="eyebrow">COLINBOOKSTORE / MEMBER</span>
<h1>Masuk untuk<br>
<em>melanjutkan.</em>
</h1>
<p>Keranjang, riwayat, dan cerita pilihanmu menunggu di satu ruang.</p>
<span class="auth-side-mark">C</span>
</div>
  <div class="auth-card">
<span class="eyebrow">AKUN PEMBACA</span>
<h2>Selamat datang.</h2>
<p class="helper">Masuk ke akun ColinBookStore.</p>
    <?php if (!empty($error)): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>
    <form method="post" class="stack-form">
<?= csrf_field() ?>
<div class="form-group">
<label for="email">Email</label>
<input id="email" type="email" name="email" autocomplete="email" required value="<?= e($email) ?>">
</div>
<div class="form-group">
<label for="password">Password</label>
<input id="password" type="password" name="password" autocomplete="current-password" required>
</div>
<button class="button button-dark button-block" type="submit">Masuk <span>↗</span>
</button>
</form>
    <p class="auth-foot">Belum punya akun? <a href="<?= url('register.php') ?>">Daftar sekarang</a>
</p>
<p class="auth-foot">
<a href="<?= url('admin/login.php') ?>">Masuk sebagai admin →</a>
</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
