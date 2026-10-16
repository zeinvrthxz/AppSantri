
<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// Pastikan permintaan berasal dari formulir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil ID santri
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['flash'] = 'ID santri tidak valid.';
    header('Location: index.php');
    exit;
}

// Periksa apakah data santri tersedia
$stmt = $pdo->prepare("SELECT id FROM santri WHERE id = :id");
$stmt->execute([':id' => $id]);
$santri = $stmt->fetch();

if (!$santri) {
    $_SESSION['flash'] = 'Data santri tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Hapus data santri
$stmt = $pdo->prepare("DELETE FROM santri WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = 'Data santri berhasil dihapus.';

header('Location: index.php');
exit;
