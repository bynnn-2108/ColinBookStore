<?php
/** Kelola akun: memperbarui profil, password, atau menghapus akun customer. */

require_once __DIR__ . '/includes/functions.php';
require_user();
$userId = (int) $_SESSION['user_id'];
$user = current_user();
if (!$user) {
    unset($_SESSION['user_id'], $_SESSION['user_nama']);
    set_flash('warning', 'Akun tidak ditemukan. Silakan masuk kembali.');
    redirect('login.php');
}
$old = $user;
$error = '';
$deleteError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $old = ['nama' => trim((string) ($_POST['nama'] ?? '')), 'email' => trim((string) ($_POST['email'] ?? '')), 'alamat' => trim((string) ($_POST['alamat'] ?? '')), 'nomor_telepon' => trim((string) ($_POST['nomor_telepon'] ?? ''))];
        $newPassword = (string) ($_POST['password_baru'] ?? '');
        $confirmation = (string) ($_POST['konfirmasi_password'] ?? '');
        if (in_array('', $old, true)) {
            $error = 'Nama, email, alamat, dan nomor telepon wajib diisi.';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($newPassword !== $confirmation) {
            $error = 'Konfirmasi password baru tidak sama.';
        } else {
            $check = $pdo->prepare('SELECT id_user FROM `user` WHERE email = ? AND id_user != ?');
            $check->execute([$old['email'], $userId]);
            if ($check->fetch()) {
                $error = 'Email tersebut sudah digunakan akun lain.';
            } else {
                if ($newPassword !== '') {
                    $statement = $pdo->prepare('UPDATE `user` SET nama = ?, email = ?, alamat = ?, nomor_telepon = ?, password = ? WHERE id_user = ?');
                    $statement->execute([$old['nama'], $old['email'], $old['alamat'], $old['nomor_telepon'], password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                } else {
                    $statement = $pdo->prepare('UPDATE `user` SET nama = ?, email = ?, alamat = ?, nomor_telepon = ? WHERE id_user = ?');
                    $statement->execute([$old['nama'], $old['email'], $old['alamat'], $old['nomor_telepon'], $userId]);
                }
                $_SESSION['user_nama'] = $old['nama'];
                set_flash('success', 'Data akun berhasil diperbarui.');
                redirect('kelola_akun.php');
            }
        }
    } elseif ($action === 'delete_account') {
        $confirmation = trim((string) ($_POST['konfirmasi_hapus'] ?? ''));
        $password = (string) ($_POST['password_hapus'] ?? '');
        $statement = $pdo->prepare('SELECT password FROM `user` WHERE id_user = ?');
        $statement->execute([$userId]);
        $hash = $statement->fetchColumn();
        if ($confirmation !== 'HAPUS AKUN') {
            $deleteError = 'Ketik HAPUS AKUN sebagai konfirmasi.';
        } elseif (!is_string($hash) || !password_verify($password, $hash)) {
            $deleteError = 'Password saat ini tidak sesuai.';
        } else {
            try {
                $pdo->beginTransaction();
                $cart = $pdo->prepare('DELETE FROM cart WHERE id_user = ?');
                $cart->execute([$userId]);
                $messages = $pdo->prepare('DELETE FROM pesan WHERE id_user = ?');
                $messages->execute([$userId]);
                $anonymize = $pdo->prepare('UPDATE `user` SET nama = ?, email = ?, password = ?, alamat = ?, nomor_telepon = ? WHERE id_user = ?');
                $anonymize->execute(['Akun Dihapus', 'deleted-' . $userId . '-' . bin2hex(random_bytes(5)) . '@example.invalid', password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), '-', '-', $userId]);
                $pdo->commit();
                $_SESSION = [];
                set_flash('success', 'Akun dihapus. Riwayat pesanan disimpan anonim untuk pencatatan transaksi.');
                redirect('login.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $deleteError = 'Akun tidak dapat dihapus saat ini.';
            }
        }
    }
}
$page_title = 'Kelola Akun';
$active_page = 'settings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading-row">
<div>
<span class="eyebrow">AKUN / IDENTITAS</span>
<h1>Profil saya.</h1>
</div>
<a class="text-link" href="<?= url('pengaturan.php') ?>">← Pengaturan</a>
</section>
<section class="profile-banner panel-blue">
<span class="avatar avatar-large">
<?= e(strtoupper(mb_substr($user['nama'], 0, 1))) ?>
</span>
<div>
<span class="eyebrow">PEMBACA COLINBOOKSTORE</span>
<h2>
<?= e($user['nama']) ?>
</h2>
<p>
<?= e($user['email']) ?>
</p>
</div>
<a class="button button-light" href="<?= url('pesanan.php') ?>">Lihat pesanan</a>
</section>
<div class="account-layout">
<section class="account-form">
<span class="eyebrow">DATA PRIBADI</span>
<h2>Informasi pengiriman</h2>
<p class="helper">Data ini digunakan saat checkout.</p>
<?php if ($error !== ''): ?>
<div class="alert alert-danger">
<?= e($error) ?>
</div>
<?php endif; ?>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="update_profile">
<div class="form-group">
<label for="nama">Nama lengkap</label>
<input id="nama" name="nama" required value="<?= e($old['nama']) ?>">
</div>
<div class="form-row">
<div class="form-group">
<label for="email">Email</label>
<input id="email" type="email" name="email" required value="<?= e($old['email']) ?>">
</div>
<div class="form-group">
<label for="nomor_telepon">Nomor telepon</label>
<input id="nomor_telepon" name="nomor_telepon" required value="<?= e($old['nomor_telepon']) ?>">
</div>
</div>
<div class="form-group">
<label for="alamat">Alamat lengkap</label>
<textarea id="alamat" name="alamat" required>
<?= e($old['alamat']) ?>
</textarea>
</div>
<hr class="receipt-rule">
<span class="eyebrow">UBAH PASSWORD / OPSIONAL</span>
<div class="form-row">
<div class="form-group">
<label for="password_baru">Password baru</label>
<input id="password_baru" type="password" name="password_baru" minlength="8">
</div>
<div class="form-group">
<label for="konfirmasi_password">Konfirmasi password</label>
<input id="konfirmasi_password" type="password" name="konfirmasi_password" minlength="8">
</div>
</div>
<button class="button button-dark" type="submit">Simpan perubahan</button>
</form>
</section>
<aside class="danger-card">
<span class="eyebrow">ZONA BERBAHAYA</span>
<h2>Hapus akun.</h2>
<p>Tindakan ini tidak dapat dibatalkan. Profil, keranjang, dan pesan dihapus; riwayat pesanan dianonimkan.</p>
<?php if ($deleteError !== ''): ?>
<div class="alert alert-danger">
<?= e($deleteError) ?>
</div>
<?php endif; ?>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<input type="hidden" name="action" value="delete_account">
<div class="form-group">
<label for="password_hapus">Password saat ini</label>
<input id="password_hapus" type="password" name="password_hapus" required>
</div>
<div class="form-group">
<label for="konfirmasi_hapus">Ketik HAPUS AKUN</label>
<input id="konfirmasi_hapus" name="konfirmasi_hapus" autocomplete="off" required>
</div>
<button class="button button-danger" type="submit" data-confirm="Akun akan dihapus permanen. Lanjutkan?">Hapus akun saya</button>
</form>
</aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
