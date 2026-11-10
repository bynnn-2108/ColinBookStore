<?php
/** Form bantuan customer: menyimpan pesan tervalidasi ke tabel pesan. */

require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_user();
    verify_csrf();
    $message = trim((string) ($_POST['pesan'] ?? ''));
    if (strlen($message) < 5) {
        set_flash('danger', 'Pesan minimal 5 karakter.');
    } else {
        $statement = $pdo->prepare('INSERT INTO pesan (id_user, pesan) VALUES (?, ?)');
        $statement->execute([(int) $_SESSION['user_id'], $message]);
        set_flash('success', 'Pesanmu sudah dikirim ke admin.');
        redirect('contact.php');
    }
}
$page_title = 'Bantuan';
require __DIR__ . '/includes/header.php';
?>
<section class="contact-layout">
<div>
<span class="eyebrow">BANTUAN / CONTACT</span>
<h1>Ada yang ingin<br>
<em>dibicarakan?</em>
</h1>
<p class="page-lead">Kami siap membantu soal buku, pembayaran, atau pesanan yang sedang berjalan.</p>
<div class="contact-note panel-cream">
<strong>ColinBookStore</strong>
<span>Jl. Buku Impian No. 10, Indonesia</span>
<span>0812-3456-7890</span>
<span>admin@colinbookstore.test</span>
</div>
</div>
<section class="contact-card">
<span class="eyebrow">KIRIM PESAN</span>
<h2>Mulai percakapan.</h2>
<?php if (!is_user_logged_in()): ?>
<p>Silakan <a href="<?= url('login.php') ?>">masuk</a> untuk mengirim pesan kepada admin.</p>
<?php else: ?>
<form method="post" class="stack-form">
<?= csrf_field() ?>
<div class="form-group">
<label for="pesan">Pesanmu</label>
<textarea id="pesan" name="pesan" required minlength="5" placeholder="Tulis pertanyaan atau pesanmu...">
</textarea>
</div>
<button class="button button-dark" type="submit">Kirim pesan <span>↗</span>
</button>
</form>
<?php endif; ?>
</section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
