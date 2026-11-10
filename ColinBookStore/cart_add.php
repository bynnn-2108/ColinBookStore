<?php
/** Endpoint POST: memvalidasi tujuan dan menambahkan buku ke keranjang. */

require_once __DIR__ . '/includes/functions.php';
require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('buku.php');
}

verify_csrf();
$bookId = (int) ($_POST['id_buku'] ?? 0);
$quantity = (int) ($_POST['jumlah'] ?? 1);
$returnTo = trim((string) ($_POST['return_to'] ?? 'buku.php'));

if (!preg_match('/^[a-zA-Z0-9_\-]+\.php(?:\?[^#]*)?$/', $returnTo)) {
    $returnTo = 'buku.php';
}

try {
    set_flash('success', add_to_cart($pdo, (int) $_SESSION['user_id'], $bookId, $quantity));
} catch (Throwable $exception) {
    set_flash('danger', $exception->getMessage());
}

redirect($returnTo);
