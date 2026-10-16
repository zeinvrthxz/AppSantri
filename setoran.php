<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// ==========================================
// 1. PESAN NOTIFIKASI
// ==========================================
$pesan = '';
$jenisPesan = '';

if (isset($_SESSION['pesan_setoran'])) {
    $pesan = $_SESSION['pesan_setoran'];
    $jenisPesan = $_SESSION['jenis_pesan_setoran'] ?? 'success';

    unset($_SESSION['pesan_setoran'], $_SESSION['jenis_pesan_setoran']);
}

// ==========================================
// 2. PROSES TAMBAH SETORAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $santriId = filter_input(INPUT_POST, 'santri_id', FILTER_VALIDATE_INT);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $namaSurah = trim($_POST['nama_surah'] ?? '');
    $ayatAwal = filter_input(INPUT_POST, 'ayat_awal', FILTER_VALIDATE_INT);
    $ayatAkhir = filter_input(INPUT_POST, 'ayat_akhir', FILTER_VALIDATE_INT);
    $nilaiInput = trim($_POST['nilai'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $nilai = null;

    if ($nilaiInput !== '' && is_numeric($nilaiInput)) {
        $nilai = (float) $nilaiInput;
    }

    $objTanggal = $tanggal !== ''
        ? DateTime::createFromFormat('!Y-m-d', $tanggal)
        : false;

    $tanggalValid = $objTanggal
        && $objTanggal->format('Y-m-d') === $tanggal;

    if (
        !$santriId ||
        !$tanggalValid ||
        $namaSurah === '' ||
        !$ayatAwal ||
        !$ayatAkhir ||
        $ayatAwal < 1 ||
        $ayatAkhir < $ayatAwal
    ) {
        $pesan = 'Periksa kembali data. Pastikan kolom wajib terisi dan rentang ayat benar.';
        $jenisPesan = 'danger';
    } elseif (
        $nilaiInput !== '' &&
        (!is_numeric($nilaiInput) || $nilai === null || $nilai < 0 || $nilai > 100)
    ) {
        $pesan = 'Nilai harus berupa angka antara 0 sampai 100.';
        $jenisPesan = 'danger';
    } elseif (strlen($namaSurah) > 100 || strlen($keterangan) > 255) {
        $pesan = 'Nama surah maksimal 100 karakter dan keterangan maksimal 255 karakter.';
        $jenisPesan = 'danger';
    } else {
        try {
            $cekSantri = $pdo->prepare(
                "SELECT id FROM santri WHERE id = :id"
            );
            $cekSantri->execute(['id' => $santriId]);

            if (!$cekSantri->fetch()) {
                $pesan = 'Santri yang dipilih tidak ditemukan.';
                $jenisPesan = 'danger';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO setoran_hafalan
                    (santri_id, tanggal, nama_surah, ayat_awal,
                     ayat_akhir, nilai, keterangan)
                    VALUES
                    (:santri_id, :tanggal, :nama_surah, :ayat_awal,
                     :ayat_akhir, :nilai, :keterangan)"
                );

                $stmt->execute([
                    'santri_id' => $santriId,
                    'tanggal' => $tanggal,
                    'nama_surah' => $namaSurah,
                    'ayat_awal' => $ayatAwal,
                    'ayat_akhir' => $ayatAkhir,
                    'nilai' => $nilai,
                    'keterangan' => $keterangan !== '' ? $keterangan : null
                ]);

                $_SESSION['pesan_setoran'] = 'Setoran hafalan berhasil ditambahkan!';
                $_SESSION['jenis_pesan_setoran'] = 'success';

                header('Location: setoran.php');
                exit;
            }
        } catch (PDOException $ex) {
            $pesan = 'Setoran gagal disimpan. Periksa tabel dan koneksi database.';
            $jenisPesan = 'danger';
        }
    }
}

// ==========================================
// 3. AMBIL DAFTAR SANTRI
// ==========================================
$stmtSantri = $pdo->query(
    "SELECT id, nama, kamar FROM santri ORDER BY nama ASC"
);
$daftarSantri = $stmtSantri->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 4. PENCARIAN DAN FILTER KAMAR
// ==========================================
$cari = trim($_GET['cari'] ?? '');
$filterKamar = trim($_GET['kamar'] ?? '');

$kondisi = [];
$parameter = [];

if ($cari !== '') {
    $kondisi[] = "(s.nama LIKE :cari_nama OR sh.nama_surah LIKE :cari_surah)";
    $parameter['cari_nama'] = '%' . $cari . '%';
    $parameter['cari_surah'] = '%' . $cari . '%';
}

if (in_array($filterKamar, ['1', '2', '3'], true)) {
    $kondisi[] = "s.kamar = :kamar";
    $parameter['kamar'] = $filterKamar;
}

$whereSQL = count($kondisi) > 0
    ? 'WHERE ' . implode(' AND ', $kondisi)
    : '';

// ==========================================
// 5. RINGKASAN HAFALAN
// ==========================================
$stmtRingkasan = $pdo->query(
    "SELECT
        COUNT(*) AS total_setoran,
        COUNT(DISTINCT santri_id) AS total_santri,
        AVG(nilai) AS rata_nilai
     FROM setoran_hafalan"
);
$ringkasan = $stmtRingkasan->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 6. AMBIL DAFTAR SETORAN SESUAI FILTER
// ==========================================
$sqlSetoran = "
    SELECT
        sh.id,
        sh.tanggal,
        sh.nama_surah,
        sh.ayat_awal,
        sh.ayat_akhir,
        sh.nilai,
        sh.keterangan,
        s.nama AS nama_santri,
        s.kamar
    FROM setoran_hafalan sh
    INNER JOIN santri s ON sh.santri_id = s.id
    $whereSQL
    ORDER BY sh.tanggal DESC, sh.id DESC
";

$stmtSetoran = $pdo->prepare($sqlSetoran);
$stmtSetoran->execute($parameter);
$daftarSetoran = $stmtSetoran->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .setoran-page {
        padding-top: 24px;
        padding-bottom: 32px;
    }

    .setoran-heading {
        margin-bottom: 24px;
    }

    .setoran-heading h4 {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .setoran-heading p {
        color: #6c757d;
        margin-bottom: 0;
    }

    .setoran-stat {
        height: 100%;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .04);
    }

    .setoran-stat-label {
        color: #6c757d;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .setoran-stat-number {
        color: #198754;
        font-size: 27px;
        font-weight: 700;
        margin: 0;
    }

    .setoran-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .045);
        margin-bottom: 24px;
    }

    .setoran-card-title {
        background: #d1e7dd;
        color: #234b39;
        padding: 15px 20px;
        font-weight: 700;
        border-bottom: 1px solid #badbcc;
    }

    .setoran-card-body {
        padding: 20px;
    }

    .setoran-table {
        min-width: 950px;
        margin-bottom: 0;
        vertical-align: middle;
    }

    .setoran-table th {
        background: #d1e7dd;
        color: #234b39;
        white-space: nowrap;
        font-size: 13px;
        padding: 13px;
    }

    .setoran-table td {
        padding: 12px;
        font-size: 13px;
    }

    .aksi-buttons {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    @media (max-width: 576px) {
        .setoran-page {
            padding-top: 16px;
        }

        .setoran-card-body {
            padding: 15px;
        }

        .setoran-stat-number {
            font-size: 23px;
        }
    }
</style>

<div class="setoran-page">

    <!-- JUDUL -->
    <div class="setoran-heading">
        <h4>Setoran Hafalan</h4>
        <p>Catat dan pantau perkembangan hafalan santri.</p>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($pesan !== ''): ?>
        <div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show"
             role="alert">
            <?= e($pesan) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>

    <!-- RINGKASAN -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-4">
            <div class="setoran-stat">
                <div class="setoran-stat-label">Total Setoran</div>
                <p class="setoran-stat-number">
                    <?= (int) $ringkasan['total_setoran'] ?>
                </p>
                <small class="text-muted">Seluruh catatan hafalan</small>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="setoran-stat">
                <div class="setoran-stat-label">Santri yang Sudah Menyetor</div>
                <p class="setoran-stat-number">
                    <?= (int) $ringkasan['total_santri'] ?>
                </p>
                <small class="text-muted">Santri unik yang memiliki setoran</small>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="setoran-stat">
                <div class="setoran-stat-label">Rata-rata Nilai</div>
                <p class="setoran-stat-number">
                    <?= $ringkasan['rata_nilai'] !== null
                        ? number_format((float) $ringkasan['rata_nilai'], 2, ',', '.')
                        : '-' ?>
                </p>
                <small class="text-muted">Dari setoran yang memiliki nilai</small>
            </div>
        </div>

    </div>

    <!-- FORM TAMBAH SETORAN -->
    <div class="setoran-card">
        <div class="setoran-card-title">
            + Tambah Setoran Hafalan
        </div>

        <div class="setoran-card-body">

            <?php if (empty($daftarSantri)): ?>
                <div class="alert alert-warning mb-0">
                    Belum ada data santri. Tambahkan data santri terlebih dahulu.
                </div>
            <?php else: ?>

                <form method="POST" action="setoran.php">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label for="santri_id" class="form-label">
                                Nama Santri *
                            </label>

                            <select name="santri_id" id="santri_id"
                                    class="form-select" required>
                                <option value="">-- Pilih Santri --</option>

                                <?php foreach ($daftarSantri as $santri): ?>
                                    <option value="<?= (int) $santri['id'] ?>"
                                        <?= (string) ($_POST['santri_id'] ?? '') === (string) $santri['id'] ? 'selected' : '' ?>>
                                        <?= e($santri['nama']) ?>
                                        — Kamar <?= e($santri['kamar'] ?: '-') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="tanggal" class="form-label">
                                Tanggal Setoran *
                            </label>

                            <input type="date" name="tanggal" id="tanggal"
                                   class="form-control"
                                   value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>"
                                   required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nama_surah" class="form-label">
                                Nama Surah *
                            </label>

                            <input type="text" name="nama_surah" id="nama_surah"
                                   class="form-control" maxlength="100"
                                   placeholder="Contoh: Al-Baqarah"
                                   value="<?= e($_POST['nama_surah'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="ayat_awal" class="form-label">
                                Ayat Awal *
                            </label>

                            <input type="number" name="ayat_awal" id="ayat_awal"
                                   class="form-control" min="1"
                                   value="<?= e($_POST['ayat_awal'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="ayat_akhir" class="form-label">
                                Ayat Akhir *
                            </label>

                            <input type="number" name="ayat_akhir" id="ayat_akhir"
                                   class="form-control" min="1"
                                   value="<?= e($_POST['ayat_akhir'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nilai" class="form-label">
                                Nilai (0–100)
                            </label>

                            <input type="number" name="nilai" id="nilai"
                                   class="form-control" min="0" max="100"
                                   step="0.01" placeholder="Contoh: 90"
                                   value="<?= e($_POST['nilai'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="keterangan" class="form-label">
                                Keterangan
                            </label>

                            <input type="text" name="keterangan" id="keterangan"
                                   class="form-control" maxlength="255"
                                   placeholder="Contoh: Lancar atau perlu mengulang"
                                   value="<?= e($_POST['keterangan'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                Simpan Setoran
                            </button>

                            <a href="index.php" class="btn btn-outline-secondary">
                                Kembali ke Data Santri
                            </a>
                        </div>

                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <!-- PENCARIAN DAN FILTER -->
    <div class="setoran-card">
        <div class="setoran-card-title">
            Cari dan Filter Setoran
        </div>

        <div class="setoran-card-body">

            <form method="GET" action="setoran.php">
                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label for="cari" class="form-label">
                            Nama Santri atau Surah
                        </label>

                        <input type="search" name="cari" id="cari"
                               class="form-control"
                               placeholder="Ketik nama santri atau surah..."
                               value="<?= e($cari) ?>">
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="kamar" class="form-label">
                            Filter Kamar
                        </label>

                        <select name="kamar" id="kamar" class="form-select">
                            <option value="">Semua Kamar</option>
                            <option value="1" <?= $filterKamar === '1' ? 'selected' : '' ?>>
                                Kamar 1
                            </option>
                            <option value="2" <?= $filterKamar === '2' ? 'selected' : '' ?>>
                                Kamar 2
                            </option>
                            <option value="3" <?= $filterKamar === '3' ? 'selected' : '' ?>>
                                Kamar 3
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-success">
                            Cari
                        </button>

                        <a href="setoran.php"
                           class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- TABEL DAFTAR SETORAN -->
    <div class="setoran-card">

        <div class="setoran-card-title">
            Daftar Setoran Hafalan
            <span class="badge bg-success ms-2">
                <?= count($daftarSetoran) ?> data
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover setoran-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Nama Santri</th>
                        <th>Kamar</th>
                        <th>Surah</th>
                        <th>Rentang Ayat</th>
                        <th>Nilai</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($daftarSetoran)): ?>
                        <tr>
                            <td colspan="9"
                                class="text-center text-muted py-4">
                                Tidak ada data setoran yang cocok.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($daftarSetoran as $no => $setoran): ?>
                            <tr>
                                <td><?= $no + 1 ?></td>
                                <td><?= e($setoran['tanggal']) ?></td>
                                <td><?= e($setoran['nama_santri']) ?></td>
                                <td><?= e($setoran['kamar'] ?: '-') ?></td>
                                <td><?= e($setoran['nama_surah']) ?></td>

                                <td>
                                    <?= (int) $setoran['ayat_awal'] ?>
                                    –
                                    <?= (int) $setoran['ayat_akhir'] ?>
                                </td>

                                <td>
                                    <?= $setoran['nilai'] !== null
                                        ? e(number_format((float) $setoran['nilai'], 2, ',', '.'))
                                        : '-' ?>
                                </td>

                                <td><?= e($setoran['keterangan'] ?: '-') ?></td>

                                <td>
                                    <div class="aksi-buttons">

                                        <a href="edit_setoran.php?id=<?= (int) $setoran['id'] ?>"
                                           class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <form action="hapus_setoran.php"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus setoran ini?');">

                                            <input type="hidden" name="id"
                                                   value="<?= (int) $setoran['id'] ?>">

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger">
                                                Hapus
                                            </button>

                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>