<?php
/** Helper bersama: keamanan, autentikasi, format data, upload, dan keranjang. */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    // Escape output HTML untuk mencegah injection dari data database atau input user.
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    // Satukan BASE_URL dengan path relatif agar link bekerja di subfolder XAMPP.
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    // Redirect terpusat menjaga semua perpindahan halaman memakai BASE_URL.
    header('Location: ' . url($path));
    exit;
}

function rupiah(float|int|string $amount): string
{
    // Format nominal database ke tampilan mata uang Indonesia.
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function set_flash(string $type, string $message): void
{
    // Simpan notifikasi satu kali untuk ditampilkan setelah redirect.
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function display_flash(): void
{
    // Render lalu hapus flash message agar tidak muncul berulang.
    if (empty($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert alert-' . e($flash['type']) . '" role="status">'
        . e($flash['message']) . '</div>';
}

function csrf_token(): string
{
    // Buat token acak session untuk melindungi form POST penting.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    // Tolak request POST jika token form tidak sama dengan token session.
    $given = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($given)
        || !is_string($known) || $known === '' || !hash_equals($known, $given)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan kembali dan coba lagi.');
    }
}

function is_user_logged_in(): bool
{
    // Cek apakah session memiliki identitas customer yang valid.
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function is_admin_logged_in(): bool
{
    // Cek session admin sebelum halaman studio diproses.
    return isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] > 0;
}

function require_user(): void
{
    // Guard customer: arahkan visitor anonim ke halaman login.
    if (!is_user_logged_in()) {
        set_flash('warning', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    // Guard admin: cegah akses panel tanpa session admin.
    if (!is_admin_logged_in()) {
        set_flash('warning', 'Silakan masuk sebagai admin.');
        redirect('admin/login.php');
    }
}

function current_user(): ?array
{
    // Ambil data customer terbaru berdasarkan ID di session.
    global $pdo;

    if (!is_user_logged_in()) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id_user, nama, email, alamat, nomor_telepon
         FROM `user` WHERE id_user = ?'
    );
    $statement->execute([(int) $_SESSION['user_id']]);
    return $statement->fetch() ?: null;
}

function cart_count(): int
{
    // Hitung total kuantitas item keranjang untuk badge navigasi.
    global $pdo;

    if (!is_user_logged_in()) {
        return 0;
    }

    $statement = $pdo->prepare('SELECT COALESCE(SUM(jumlah), 0) FROM cart WHERE id_user = ?');
    $statement->execute([(int) $_SESSION['user_id']]);
    return (int) $statement->fetchColumn();
}

function invoice_code(int $orderId): string
{
    // Bentuk kode invoice yang konsisten dan mudah dibaca customer.
    return 'CBS-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
}

function order_status_class(string $status): string
{
    // Petakan status order ke kelas warna badge UI.
    return match ($status) {
        'Selesai' => 'success',
        'Dibatalkan' => 'danger',
        'Diproses', 'Dikirim' => 'info',
        default => 'warning',
    };
}

function upload_error_message(int $error): string
{
    // Ubah kode error upload PHP menjadi pesan yang ramah pengguna.
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file maksimal 2 MB.',
        UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'Server tidak dapat menyimpan file.',
        UPLOAD_ERR_EXTENSION => 'Unggahan file dihentikan oleh server.',
        default => 'File gagal diunggah.',
    };
}

function upload_file(array $file, string $directory, array $allowed, int $maxBytes = 2097152): array
{
    // Validasi file berdasarkan error, ukuran, MIME, ekstensi, dan folder tujuan.
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return [false, 'File wajib dipilih.'];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return [false, upload_error_message($error)];
    }

    $temporaryFile = $file['tmp_name'] ?? '';
    $size = (int) ($file['size'] ?? 0);

    if (!is_string($temporaryFile) || !is_uploaded_file($temporaryFile)) {
        return [false, 'File unggahan tidak valid.'];
    }

    if ($size < 1 || $size > $maxBytes) {
        return [false, 'Ukuran file maksimal 2 MB.'];
    }

    if (!class_exists('finfo')) {
        return [false, 'Ekstensi fileinfo harus aktif di PHP.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
    if (!is_string($mime) || !isset($allowed[$mime])) {
        return [false, 'Format file tidak diizinkan.'];
    }

    $originalExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $extensionsByMime = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
    ];
    if (!in_array($originalExtension, $extensionsByMime[$mime] ?? [], true)) {
        return [false, 'Ekstensi file tidak sesuai dengan format file.'];
    }

    $relativeDirectory = trim($directory, '/');
    $targetDirectory = __DIR__ . '/../' . $relativeDirectory . '/';

    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true)
        && !is_dir($targetDirectory)) {
        return [false, 'Folder upload tidak dapat dibuat.'];
    }

    if (!is_writable($targetDirectory)) {
        // XAMPP sering menjalankan Apache dengan user berbeda dari pemilik
        // folder proyek. Coba pulihkan permission folder upload terlebih dahulu.
        @chmod($targetDirectory, 0777);
    }

    $probePath = $targetDirectory . '.write-test-' . bin2hex(random_bytes(8));
    $probeHandle = @fopen($probePath, 'wb');
    if (!is_resource($probeHandle)) {
        return [false, 'Apache belum dapat menulis folder upload. Beri izin Modify/Tulis pada folder uploads, uploads/payment, dan uploads/books.'];
    }
    fclose($probeHandle);
    @unlink($probePath);

    $randomName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($temporaryFile, $targetDirectory . $randomName)) {
        return [false, 'File gagal disimpan.'];
    }

    return [true, $relativeDirectory . '/' . $randomName];
}

function delete_upload(?string $path): void
{
    // Hapus file upload hanya jika path berada di dalam folder uploads yang aman.
    if (!$path || !str_starts_with($path, 'uploads/')) {
        return;
    }

    $root = realpath(__DIR__ . '/../uploads');
    $file = realpath(__DIR__ . '/../' . $path);

    if ($root && $file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
        unlink($file);
    }
}

function add_to_cart(PDO $pdo, int $userId, int $bookId, int $quantity): string
{
    // Tambah item secara atomik sambil mengunci stok buku dengan transaksi database.
    $quantity = max(1, $quantity);
    $pdo->beginTransaction();

    try {
        $bookStatement = $pdo->prepare('SELECT judul_buku, stok FROM buku WHERE id_buku = ? FOR UPDATE');
        $bookStatement->execute([$bookId]);
        $book = $bookStatement->fetch();

        if (!$book) {
            throw new RuntimeException('Buku tidak ditemukan.');
        }
        if ((int) $book['stok'] < 1) {
            throw new RuntimeException('Stok buku sedang habis.');
        }

        $cartStatement = $pdo->prepare('SELECT jumlah FROM cart WHERE id_user = ? AND id_buku = ? FOR UPDATE');
        $cartStatement->execute([$userId, $bookId]);
        $existing = (int) ($cartStatement->fetchColumn() ?: 0);
        $newQuantity = min((int) $book['stok'], $existing + $quantity);

        if ($existing > 0) {
            $update = $pdo->prepare('UPDATE cart SET jumlah = ? WHERE id_user = ? AND id_buku = ?');
            $update->execute([$newQuantity, $userId, $bookId]);
        } else {
            $insert = $pdo->prepare('INSERT INTO cart (id_user, id_buku, jumlah) VALUES (?, ?, ?)');
            $insert->execute([$userId, $bookId, $newQuantity]);
        }

        $pdo->commit();
        return 'Buku ditambahkan ke keranjang.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
