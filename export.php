
<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// Membaca filter kamar dari URL
$filterKamar = trim((string) ($_GET['kamar'] ?? ''));

// Hanya izinkan kamar 1, 2, dan 3 atau semua kamar
if (!in_array($filterKamar, ['', '1', '2', '3'], true)) {
    $filterKamar = '';
}

// Mengambil data berdasarkan filter
if ($filterKamar !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, nama, jenis_kelamin, tanggal_lahir,
                asal_daerah, kamar, angkatan, no_hp_wali, created_at
         FROM santri
         WHERE kamar = :kamar
         ORDER BY id DESC"
    );

    $stmt->execute(['kamar' => $filterKamar]);
} else {
    $stmt = $pdo->query(
        "SELECT id, nama, jenis_kelamin, tanggal_lahir,
                asal_daerah, kamar, angkatan, no_hp_wali, created_at
         FROM santri
         ORDER BY id DESC"
    );
}

// Menyiapkan unduhan CSV
$namaFile = 'data_santri_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');

// Membuat keluaran CSV
$output = fopen('php://output', 'w');

// BOM agar karakter Indonesia terbaca baik di Excel
fwrite($output, "\xEF\xBB\xBF");

// Judul kolom
fputcsv($output, [
    'ID',
    'Nama',
    'Jenis Kelamin',
    'Tanggal Lahir',
    'Asal Daerah',
    'Kamar',
    'Angkatan',
    'No. HP Wali',
    'Tanggal Ditambahkan'
], ';');

// Menulis data santri
while ($santri = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Mengubah kode L/P menjadi tulisan
    if ($santri['jenis_kelamin'] === 'L') {
        $jenisKelamin = 'Laki-laki';
    } elseif ($santri['jenis_kelamin'] === 'P') {
        $jenisKelamin = 'Perempuan';
    } else {
        $jenisKelamin = '-';
    }

    fputcsv($output, [
        $santri['id'],
        $santri['nama'],
        $jenisKelamin,
        $santri['tanggal_lahir'] ?? '',
        $santri['asal_daerah'] ?? '',
        $santri['kamar'] ?? '',
        $santri['angkatan'] ?? '',
        $santri['no_hp_wali'] ?? '',
        $santri['created_at'] ?? ''
    ], ';');
}

fclose($output);
exit;
