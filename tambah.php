
<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

$errors = [];

$nama = '';
$jenis_kelamin = '';
$tanggal_lahir = '';
$asal_daerah = '';
$kamar = '';
$angkatan = '';
$no_hp_wali = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $asal_daerah = trim($_POST['asal_daerah'] ?? '');
    $kamar = trim($_POST['kamar'] ?? '');
    $angkatan = trim($_POST['angkatan'] ?? '');
    $no_hp_wali = trim($_POST['no_hp_wali'] ?? '');

    // Validasi data
    if ($nama === '') {
        $errors[] = 'Nama santri wajib diisi.';
    }

    if (!in_array($jenis_kelamin, ['L', 'P'], true)) {
        $errors[] = 'Pilih jenis kelamin yang benar.';
    }

    if ($tanggal_lahir === '') {
        $errors[] = 'Tanggal lahir wajib diisi.';
    }

    if ($asal_daerah === '') {
        $errors[] = 'Asal daerah wajib diisi.';
    }

    if ($kamar === '') {
        $errors[] = 'Kamar wajib diisi.';
    }

    if ($angkatan !== '' && !ctype_digit($angkatan)) {
        $errors[] = 'Angkatan harus berupa angka.';
    }

    if ($no_hp_wali === '') {
        $errors[] = 'Nomor HP wali wajib diisi.';
    }

    // Simpan data jika tidak ada kesalahan
    if (empty($errors)) {
        $sql = "INSERT INTO santri
                (nama, jenis_kelamin, tanggal_lahir, asal_daerah,
                 kamar, angkatan, no_hp_wali)
                VALUES
                (:nama, :jenis_kelamin, :tanggal_lahir, :asal_daerah,
                 :kamar, :angkatan, :no_hp_wali)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nama' => $nama,
            ':jenis_kelamin' => $jenis_kelamin,
            ':tanggal_lahir' => $tanggal_lahir,
            ':asal_daerah' => $asal_daerah,
            ':kamar' => $kamar,
            ':angkatan' => $angkatan !== '' ? (int) $angkatan : null,
            ':no_hp_wali' => $no_hp_wali
        ]);

        $_SESSION['flash'] = 'Data santri berhasil ditambahkan.';
        header('Location: index.php');
        exit;
    }
}

require_once 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Tambah Data Santri</h2>
        <p class="text-muted">Isi formulir berikut dengan benar.</p>
    </div>

    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="card card-body shadow-sm">

    <div class="mb-3">
        <label class="form-label">Nama Santri</label>
        <input
            type="text"
            name="nama"
            class="form-control"
            value="<?= e($nama) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-select" required>
            <option value="">-- Pilih Jenis Kelamin --</option>

            <option value="L"
                <?= $jenis_kelamin === 'L' ? 'selected' : '' ?>>
                Laki-laki
            </option>

            <option value="P"
                <?= $jenis_kelamin === 'P' ? 'selected' : '' ?>>
                Perempuan
            </option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Tanggal Lahir</label>
        <input
            type="date"
            name="tanggal_lahir"
            class="form-control"
            value="<?= e($tanggal_lahir) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Asal Daerah</label>
        <input
            type="text"
            name="asal_daerah"
            class="form-control"
            value="<?= e($asal_daerah) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Kamar</label>
        <input
            type="text"
            name="kamar"
            class="form-control"
            value="<?= e($kamar) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Angkatan</label>
        <input
            type="number"
            name="angkatan"
            class="form-control"
            min="1"
            value="<?= e($angkatan) ?>"
            placeholder="Contoh: 2026"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Nomor HP Wali</label>
        <input
            type="tel"
            name="no_hp_wali"
            class="form-control"
            value="<?= e($no_hp_wali) ?>"
            required
        >
    </div>

    <button type="submit" class="btn btn-primary">
        Simpan Data
    </button>

</form>

<?php require_once 'footer.php'; ?>
