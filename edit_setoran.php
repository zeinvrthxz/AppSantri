<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// ==========================================
// 1. AMBIL ID SETORAN
// ==========================================
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['pesan_setoran'] = 'ID setoran tidak valid.';
    $_SESSION['jenis_pesan_setoran'] = 'danger';
    header('Location: setoran.php');
    exit;
}

// ==========================================
// 2. AMBIL DATA SETORAN
// ==========================================
$stmt = $pdo->prepare(
    "SELECT * FROM setoran_hafalan WHERE id = :id"
);
$stmt->execute(['id' => $id]);
$setoran = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$setoran) {
    $_SESSION['pesan_setoran'] = 'Data setoran tidak ditemukan.';
    $_SESSION['jenis_pesan_setoran'] = 'danger';
    header('Location: setoran.php');
    exit;
}

// ==========================================
// 3. PROSES UPDATE SETORAN
// ==========================================
$pesan = '';
$jenisPesan = 'danger';

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
    } elseif (
        $nilaiInput !== '' &&
        (!is_numeric($nilaiInput) || $nilai < 0 || $nilai > 100)
    ) {
        $pesan = 'Nilai harus berupa angka antara 0 sampai 100.';
    } elseif (
        strlen($namaSurah) > 100 ||
        strlen($keterangan) > 255
    ) {
        $pesan = 'Nama surah maksimal 100 karakter dan keterangan maksimal 255 karakter.';
    } else {
        try {
            // Pastikan santri yang dipilih tersedia
            $cekSantri = $pdo->prepare(
                "SELECT id FROM santri WHERE id = :id"
            );
            $cekSantri->execute(['id' => $santriId]);

            if (!$cekSantri->fetch()) {
                $pesan = 'Santri yang dipilih tidak ditemukan.';
            } else {
                // Simpan perubahan
                $stmt = $pdo->prepare(
                    "UPDATE setoran_hafalan SET
                        santri_id = :santri_id,
                        tanggal = :tanggal,
                        nama_surah = :nama_surah,
                        ayat_awal = :ayat_awal,
                        ayat_akhir = :ayat_akhir,
                        nilai = :nilai,
                        keterangan = :keterangan
                     WHERE id = :id"
                );

                $stmt->execute([
                    'santri_id' => $santriId,
                    'tanggal' => $tanggal,
                    'nama_surah' => $namaSurah,
                    'ayat_awal' => $ayatAwal,
                    'ayat_akhir' => $ayatAkhir,
                    'nilai' => $nilai,
                    'keterangan' => $keterangan !== ''
                        ? $keterangan
                        : null,
                    'id' => $id
                ]);

                $_SESSION['pesan_setoran'] = 'Setoran hafalan berhasil diperbarui!';
                $_SESSION['jenis_pesan_setoran'] = 'success';

                header('Location: setoran.php');
                exit;
            }
        } catch (PDOException $ex) {
            $pesan = 'Gagal memperbarui setoran. Periksa database dan koneksi.';
        }
    }

    // Tampilkan kembali input yang dikirim jika validasi gagal
    $setoran['santri_id'] = $santriId ?: $setoran['santri_id'];
    $setoran['tanggal'] = $tanggal;
    $setoran['nama_surah'] = $namaSurah;
    $setoran['ayat_awal'] = $ayatAwal ?: '';
    $setoran['ayat_akhir'] = $ayatAkhir ?: '';
    $setoran['nilai'] = $nilaiInput;
    $setoran['keterangan'] = $keterangan;
}

// ==========================================
// 4. AMBIL DAFTAR SANTRI
// ==========================================
$stmtSantri = $pdo->query(
    "SELECT id, nama, kamar FROM santri ORDER BY nama ASC"
);
$daftarSantri = $stmtSantri->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
    .edit-setoran-page {
        max-width: 850px;
        margin: 30px auto;
    }

    .edit-setoran-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .06);
    }

    .edit-setoran-title {
        background: #fff3cd;
        color: #664d03;
        padding: 18px 22px;
        font-weight: 700;
        border-bottom: 1px solid #ffecb5;
    }

    .edit-setoran-body {
        padding: 24px;
    }

    @media (max-width: 576px) {
        .edit-setoran-body {
            padding: 16px;
        }
    }
</style>

<div class="edit-setoran-page">
    <div class="edit-setoran-card">
        <div class="edit-setoran-title">
            Edit Setoran Hafalan
        </div>

        <div class="edit-setoran-body">

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-<?= e($jenisPesan) ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>

            <form method="POST"
                  action="edit_setoran.php?id=<?= (int) $id ?>">

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
                                    <?= (string) $setoran['santri_id'] === (string) $santri['id'] ? 'selected' : '' ?>>
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
                               value="<?= e($setoran['tanggal']) ?>"
                               required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="nama_surah" class="form-label">
                            Nama Surah *
                        </label>

                        <input type="text" name="nama_surah" id="nama_surah"
                               class="form-control" maxlength="100"
                               value="<?= e($setoran['nama_surah']) ?>"
                               required>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="ayat_awal" class="form-label">
                            Ayat Awal *
                        </label>

                        <input type="number" name="ayat_awal" id="ayat_awal"
                               class="form-control" min="1"
                               value="<?= e($setoran['ayat_awal']) ?>"
                               required>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="ayat_akhir" class="form-label">
                            Ayat Akhir *
                        </label>

                        <input type="number" name="ayat_akhir" id="ayat_akhir"
                               class="form-control" min="1"
                               value="<?= e($setoran['ayat_akhir']) ?>"
                               required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="nilai" class="form-label">
                            Nilai (0–100)
                        </label>

                        <input type="number" name="nilai" id="nilai"
                               class="form-control" min="0" max="100"
                               step="0.01"
                               value="<?= e($setoran['nilai'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="keterangan" class="form-label">
                            Keterangan
                        </label>

                        <input type="text" name="keterangan" id="keterangan"
                               class="form-control" maxlength="255"
                               value="<?= e($setoran['keterangan'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            Simpan Perubahan
                        </button>

                        <a href="setoran.php"
                           class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>