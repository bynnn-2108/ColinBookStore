<?php
/** Login admin terpisah: autentikasi role admin sebelum membuka studio. */

require_once __DIR__ . '/../includes/functions.php';
if (is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id_admin, nama, password FROM admin WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $admin = $statement->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id_admin'];
        $_SESSION['admin_nama'] = $admin['nama'];
        redirect('admin/dashboard.php');
    }
    $error = 'Email atau password admin tidak tepat.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login | ColinBookStore</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css?v=20261109-theme-3') ?>">
</head>
<body class="admin-login-body">
<button class="admin-login-theme admin-theme-toggle" type="button" data-dark-mode-toggle aria-pressed="false" aria-label="Aktifkan atau matikan mode gelap admin">
<span aria-hidden="true">◐</span>
<span class="admin-theme-copy">Mode <b data-dark-mode-label>OFF</b></span>
</button>
<main class="admin-login-card">
<a class="brand brand-admin" href="<?= url('index.php') ?>">
<span class="brand-monogram">C</span>
<span>
<b>ColinBookStore</b>
<small>Studio admin</small>
</span>
</a>
<span class="eyebrow">PRIVATE WORKSPACE</span>
<h1>Masuk ke<br>
<em>studio.</em>
</h1>
<p class="helper">Kelola koleksi, pesanan, dan pengalaman pembaca.</p>
<?php if (!empty($error)): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<div class="form-group">
<label for="email">Email admin</label>
<input id="email" type="email" name="email" required autocomplete="username" value="<?= e($email) ?>">
</div>
<div class="form-group">
<label for="password">Password</label>
<input id="password" type="password" name="password" required autocomplete="current-password">
</div>
<button class="button button-dark button-block" type="submit">Masuk ke studio <span>↗</span>
</button>
</form>
<a class="auth-foot" href="<?= url('index.php') ?>">← Kembali ke toko</a>
</main>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
