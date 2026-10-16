
<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

// ========================================
// 1. FILTER KAMAR
// ========================================
$filterKamar = trim((string) ($_GET['kamar'] ?? ''));

if (!in_array($filterKamar, ['', '1', '2', '3'], true)) {
    $filterKamar = '';
}

// ========================================
// 2. PAGINATION
// ========================================
$batas = 10;

$halaman = filter_input(INPUT_GET, 'halaman', FILTER_VALIDATE_INT);

if (!$halaman || $halaman < 1) {
    $halaman = 1;
}

// ========================================
// 3. HITUNG JUMLAH DATA
// ========================================
if ($filterKamar !== '') {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM santri WHERE kamar = :kamar"
    );
    $stmt->execute(['kamar' => $filterKamar]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM santri");
}

$totalData = (int) $stmt->fetchColumn();
$totalHalaman = max(1, (int) ceil($totalData / $batas));

if ($halaman > $totalHalaman) {
    $halaman = $totalHalaman;
}

$offset = ($halaman - 1) * $batas;

// ========================================
// 4. AMBIL DATA SANTRI
// ========================================
if ($filterKamar !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM santri
         WHERE kamar = :kamar
         ORDER BY id DESC
         LIMIT :batas OFFSET :offset"
    );

    $stmt->bindValue(':kamar', $filterKamar, PDO::PARAM_STR);
    $stmt->bindValue(':batas', $batas, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT * FROM santri
         ORDER BY id DESC
         LIMIT :batas OFFSET :offset"
    );

    $stmt->bindValue(':batas', $batas, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$daftar = $stmt->fetchAll();

include 'header.php';
?>

<style>
    /* Khusus tampilan halaman daftar santri */
    .santri-page {
        padding-top: 24px;
        padding-bottom: 32px;
    }

    .santri-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .santri-heading h4 {
        margin: 0 0 5px;
        font-weight: 700;
        color: #212529;
    }

    .santri-heading p {
        margin: 0;
        color: #6c757d;
        font-size: 14px;
    }

    .santri-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .santri-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.045);
    }

    .santri-filter {
        margin-bottom: 24px;
    }

    .santri-filter .card-body {
        padding: 20px;
    }

    .santri-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        color: #6c757d;
        font-size: 14px;
    }

    .santri-table {
        min-width: 1050px;
        margin-bottom: 0;
        vertical-align: middle;
    }

    .santri-table thead th {
        padding: 13px 12px;
        white-space: nowrap;
        font-size: 13px;
        background: #d1e7dd;
        color: #234b39;
        border-bottom: 1px solid #badbcc;
    }

    .santri-table tbody td {
        padding: 12px;
        font-size: 13px;
    }

    .santri-table tbody tr:hover {
        background: #f1f8f4;
    }

    .santri-table .btn {
        margin: 2px 2px 2px 0;
    }

    .santri-pagination {
        margin-top: 24px;
    }

    .santri-pagination .page-link {
        color: #198754;
    }

    .santri-pagination .active .page-link {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }

    @media (max-width: 576px) {
        .santri-page {
            padding-top: 16px;
        }

        .santri-heading {
            align-items: stretch;
        }

        .santri-actions {
            width: 100%;
        }

        .santri-actions .btn {
            flex: 1;
        }

        .santri-filter .card-body {
            padding: 15px;
        }
    }
</style>

<div class="santri-page">

    <!-- Judul dan tombol -->
    <div class="santri-heading">
        <div>
            <h4>Daftar Santri</h4>
            <p>Kelola dan pantau data santri dengan mudah.</p>
        </div>

        <div class="santri-actions">
            <a href="export.php?kamar=<?= urlencode($filterKamar) ?>"
               class="btn btn-primary">
                Export CSV
            </a>

            <a href="tambah.php" class="btn btn-success">
                + Tambah Santri
            </a>
        </div>
    </div>

    <!-- Filter kamar -->
    <div class="santri-card santri-filter">
        <div class="card-body">
            <form method="GET" action="index.php"
                  class="row g-3 align-items-end">

                <div class="col-12 col-md-5">
                    <label for="kamar" class="form-label fw-semibold">
                        Filter Berdasarkan Kamar
                    </label>

                    <select name="kamar" id="kamar" class="form-select">
                        <option value=""
                            <?= $filterKamar === '' ? 'selected' : '' ?>>
                            Semua Kamar
                        </option>

                        <option value="1"
                            <?= $filterKamar === '1' ? 'selected' : '' ?>>
                            Kamar 1
                        </option>

                        <option value="2"
                            <?= $filterKamar === '2' ? 'selected' : '' ?>>
                            Kamar 2
                        </option>

                        <option value="3"
                            <?= $filterKamar === '3' ? 'selected' : '' ?>>
                            Kamar 3
                        </option>
                    </select>
                </div>

                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Informasi data -->
    <div class="santri-info">
        <div>
            Menampilkan
            <strong><?= $totalData > 0 ? $offset + 1 : 0 ?></strong>
            –
            <strong><?= min($offset + count($daftar), $totalData) ?></strong>
            dari <strong><?= $totalData ?></strong> data
        </div>

        <div>
            Halaman <strong><?= $halaman ?></strong>
            dari <strong><?= $totalHalaman ?></strong>
        </div>
    </div>

    <!-- Tabel santri -->
    <div class="santri-card">
        <div class="table-responsive">
            <table class="table table-striped table-hover santri-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>Umur</th>
                        <th>Asal Daerah</th>
                        <th>Kamar</th>
                        <th>Angkatan</th>
                        <th>No. HP Wali</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td colspan="10"
                                class="text-center text-muted py-5">
                                <?php if ($filterKamar !== ''): ?>
                                    Tidak ada santri di Kamar
                                    <?= e($filterKamar) ?>.
                                <?php else: ?>
                                    Belum ada data santri.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($daftar as $no => $s): ?>
                        <?php
                        $umur = '-';

                        if (!empty($s['tanggal_lahir'])) {
                            try {
                                $tanggalLahir = new DateTime(
                                    $s['tanggal_lahir']
                                );
                                $hariIni = new DateTime();

                                if ($tanggalLahir <= $hariIni) {
                                    $umur = $tanggalLahir
                                        ->diff($hariIni)->y;
                                }
                            } catch (Exception $ex) {
                                $umur = '-';
                            }
                        }

                        $nomor = $offset + $no + 1;
                        ?>

                        <tr>
                            <td><?= $nomor ?></td>
                            <td><?= e($s['nama']) ?></td>

                            <td>
                                <?php
                                if ($s['jenis_kelamin'] === 'L') {
                                    echo 'Laki-laki';
                                } elseif ($s['jenis_kelamin'] === 'P') {
                                    echo 'Perempuan';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>

                            <td><?= e($s['tanggal_lahir'] ?: '-') ?></td>

                            <td>
                                <?= e($umur) ?>
                                <?= $umur !== '-' ? 'tahun' : '' ?>
                            </td>

                            <td><?= e($s['asal_daerah'] ?: '-') ?></td>
                            <td><?= e($s['kamar'] ?: '-') ?></td>
                            <td><?= e($s['angkatan'] ?? '-') ?></td>
                            <td><?= e($s['no_hp_wali'] ?: '-') ?></td>

                            <td>
                                <a href="edit.php?id=<?= (int) $s['id'] ?>"
                                   class="btn btn-sm btn-warning">
                                    Edit
                                </a>

                                <form action="hapus.php" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus data santri ini?');">

                                    <input type="hidden" name="id"
                                           value="<?= (int) $s['id'] ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalData > 0 && $totalHalaman > 1): ?>
        <nav class="santri-pagination"
             aria-label="Navigasi halaman data santri">

            <ul class="pagination justify-content-center flex-wrap">

                <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link"
                       href="?halaman=<?= max(1, $halaman - 1) ?>&kamar=<?= urlencode($filterKamar) ?>">
                        &laquo; Sebelumnya
                    </a>
                </li>

                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <li class="page-item <?= $i === $halaman ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?halaman=<?= $i ?>&kamar=<?= urlencode($filterKamar) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>">
                    <a class="page-link"
                       href="?halaman=<?= min($totalHalaman, $halaman + 1) ?>&kamar=<?= urlencode($filterKamar) ?>">
                        Berikutnya &raquo;
                    </a>
                </li>

            </ul>
        </nav>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
