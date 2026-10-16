<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// Penghapusan hanya boleh melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: setoran.php');
    exit;
}

// Ambil ID setoran
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['pesan_setoran'] = 'ID setoran tidak valid.';
    $_SESSION['jenis_pesan_setoran'] = 'danger';

    header('Location: setoran.php');
    exit;
}

try {
    // Hapus setoran berdasarkan ID
    $stmt = $pdo->prepare(
        "DELETE FROM setoran_hafalan WHERE id = :id"
    );

    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['pesan_setoran'] = 'Setoran hafalan berhasil dihapus.';
        $_SESSION['jenis_pesan_setoran'] = 'success';
    } else {
        $_SESSION['pesan_setoran'] = 'Data setoran tidak ditemukan.';
        $_SESSION['jenis_pesan_setoran'] = 'danger';
    }
} catch (PDOException $ex) {
    $_SESSION['pesan_setoran'] = 'Setoran gagal dihapus. Silakan periksa database.';
    $_SESSION['jenis_pesan_setoran'] = 'danger';
}

header('Location: setoran.php');
exit;
?>