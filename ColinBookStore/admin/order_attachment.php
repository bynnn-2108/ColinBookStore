<?php
/** Endpoint bukti transfer admin: hanya admin terautentikasi yang dapat melihat file. */

require_once __DIR__ . '/../includes/functions.php';
require_admin();
$orderId = (int) ($_GET['id'] ?? 0);
$statement = $pdo->prepare('SELECT bukti_pembayaran FROM orders WHERE id_order = ?');
$statement->execute([$orderId]);
$path = $statement->fetchColumn();
if (!is_string($path) || !preg_match('#^uploads/payment/[a-f0-9]{32}\.(jpg|png|pdf)$#i', $path, $matches)) {
    http_response_code(404);
    exit('Lampiran tidak ditemukan.');
}
$directory = realpath(__DIR__ . '/../uploads/payment');
$file = realpath(__DIR__ . '/../' . $path);
if (!$directory || !$file || !str_starts_with($file, $directory . DIRECTORY_SEPARATOR) || !is_file($file)) {
    http_response_code(404);
    exit('Lampiran tidak ditemukan.');
}
$extension = strtolower($matches[1]);
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
header('Content-Type: ' . $types[$extension]);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="bukti-' . $orderId . '.' . $extension . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;
