<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

/* =========================
   MENGAMBIL FILTER LAPORAN
========================= */
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$santri_id = $_GET['santri_id'] ?? '';
$kamar = $_GET['kamar'] ?? '';

$error = '';

/* Validasi format tanggal */
foreach ([
    'Tanggal awal' => $tanggal_awal,
    'Tanggal akhir' => $tanggal_akhir
] as $label => $tanggal) {
    if ($tanggal !== '') {
        $d = DateTime::createFromFormat('!Y-m-d', $tanggal);

        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            $error = $label . ' tidak valid.';
            break;
        }
    }
}

if (
    $error === '' &&
    $tanggal_awal !== '' &&
    $tanggal_akhir !== '' &&
    $tanggal_awal > $tanggal_akhir
) {
    $error = 'Tanggal awal tidak boleh melewati tanggal akhir.';
}

if ($santri_id !== '' && !ctype_digit((string) $santri_id)) {
    $error = 'Pilihan santri tidak valid.';
}

if ($kamar !== '' && !in_array($kamar, ['1', '2', '3'], true)) {
    $error = 'Pilihan kamar tidak valid.';
}

/* =========================
   DATA UNTUK FILTER
========================= */
$stmtSantri = $pdo->query(
    "SELECT id, nama, kamar
     FROM santri
     ORDER BY nama ASC"
);
$daftarSantri = $stmtSantri->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   MEMBUAT QUERY LAPORAN
========================= */
$where = [];
$params = [];

if ($tanggal_awal !== '') {
    $where[] = 'sh.tanggal >= :tanggal_awal';
    $params[':tanggal_awal'] = $tanggal_awal;
}

if ($tanggal_akhir !== '') {
    $where[] = 'sh.tanggal <= :tanggal_akhir';
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

if ($santri_id !== '') {
    $where[] = 'sh.santri_id = :santri_id';
    $params[':santri_id'] = (int) $santri_id;
}

if ($kamar !== '') {
    $where[] = 's.kamar = :kamar';
    $params[':kamar'] = $kamar;
}

$sql = "
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
    INNER JOIN santri s ON s.id = sh.santri_id
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY sh.tanggal DESC, sh.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   MENGHITUNG RINGKASAN
========================= */
$totalSetoran = count($laporan);
$totalNilai = 0;
$jumlahNilai = 0;
$totalSantri = [];

foreach ($laporan as $row) {
    $totalSantri[$row['nama_santri']] = true;

    if ($row['nilai'] !== null && $row['nilai'] !== '') {
        $totalNilai += (float) $row['nilai'];
        $jumlahNilai++;
    }
}

$rataRata = $jumlahNilai > 0
    ? $totalNilai / $jumlahNilai
    : null;

include 'header.php';
?>

<style>
    .report-title {
        font-weight: 800;
        color: #173b70;
    }

    .report-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 3px 14px rgba(0, 0, 0, .06);
    }

    .report-number {
        font-size: 1.7rem;
        font-weight: 800;
        color: #173b70;
    }

    .table-report th {
        background: #eaf2ff;
        color: #173b70;
        white-space: nowrap;
    }

    .report-meta {
        font-size: .9rem;
        color: #5f6b7a;
    }

    @media print {
        @page {
            size: landscape;
            margin: 12mm;
        }

        body {
            background: white !important;
            font-size: 10pt;
            color: black !important;
        }

        nav,
        footer,
        .no-print,
        .btn,
        form,
        .alert {
            display: none !important;
        }

        .container {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .report-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            break-inside: avoid;
        }

        .report-title {
            color: black !important;
        }

        .table-report th {
            background: #eaf2ff !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .table-responsive {
            overflow: visible !important;
        }

        table {
            width: 100% !important;
            font-size: 9pt;
        }

        tr {
            break-inside: avoid;
        }

        a {
            color: black !important;
            text-decoration: none !important;
        }

        .report-meta {
            color: #333 !important;
        }
    }
</style>

<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4 no-print">
        <div>
            <h2 class="report-title mb-1">Laporan Setoran Hafalan</h2>
            <p class="text-secondary mb-0">
                Laporan perkembangan hafalan santri.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                Kembali ke Dashboard
            </a>

            <button type="button" onclick="window.print()"
                    class="btn btn-primary">
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger no-print">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- FORM FILTER -->
    <div class="card report-card mb-4 no-print">
        <div class="card-body">
            <h5 class="mb-3">Filter Laporan</h5>

            <form method="GET" action="laporan.php">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Awal</label>
                        <input type="date" name="tanggal_awal"
                               class="form-control"
                               value="<?= e($tanggal_awal) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir"
                               class="form-control"
                               value="<?= e($tanggal_akhir) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nama Santri</label>
                        <select name="santri_id" class="form-select">
                            <option value="">Semua Santri</option>

                            <?php foreach ($daftarSantri as $santri): ?>
                                <option
                                    value="<?= (int) $santri['id'] ?>"
                                    <?= $santri_id !== '' &&
                                        (string) $santri['id'] ===
                                        (string) $santri_id
                                        ? 'selected' : '' ?>>
                                    <?= e($santri['nama']) ?>
                                    <?php if ($santri['kamar'] !== null &&
                                              $santri['kamar'] !== ''): ?>
                                        - Kamar <?= e($santri['kamar']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kamar</label>
                        <select name="kamar" class="form-select">
                            <option value="">Semua Kamar</option>
                            <?php foreach (['1', '2', '3'] as $nomor): ?>
                                <option value="<?= $nomor ?>"
                                    <?= $kamar === $nomor
                                        ? 'selected' : '' ?>>
                                    Kamar <?= $nomor ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>

                        <a href="laporan.php"
                           class="btn btn-outline-secondary">
                            Reset Filter
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <?php if ($error === ''): ?>

        <!-- JUDUL CETAK -->
        <div class="text-center mb-4">
            <h3 class="report-title mb-1">
                LAPORAN SETORAN HAFALAN SANTRI
            </h3>
            <div class="report-meta">
                Dicetak pada <?= date('d-m-Y H:i') ?>
            </div>

            <?php if ($tanggal_awal !== '' || $tanggal_akhir !== ''): ?>
                <div class="report-meta mt-1">
                    Periode:
                    <?= $tanggal_awal !== ''
                        ? date('d-m-Y', strtotime($tanggal_awal))
                        : 'Awal data' ?>
                    sampai
                    <?= $tanggal_akhir !== ''
                        ? date('d-m-Y', strtotime($tanggal_akhir))
                        : 'Data terbaru' ?>
                </div>
            <?php endif; ?>

            <?php if ($santri_id !== ''): ?>
                <div class="report-meta">
                    Santri:
                    <?php
                    foreach ($daftarSantri as $santri) {
                        if ((string) $santri['id'] === (string) $santri_id) {
                            echo e($santri['nama']);
                            break;
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($kamar !== ''): ?>
                <div class="report-meta">Kamar <?= e($kamar) ?></div>
            <?php endif; ?>
        </div>

        <!-- RINGKASAN -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card report-card h-100">
                    <div class="card-body">
                        <div class="text-secondary">Total Setoran</div>
                        <div class="report-number">
                            <?= number_format($totalSetoran) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card report-card h-100">
                    <div class="card-body">
                        <div class="text-secondary">Santri yang Menyetor</div>
                        <div class="report-number">
                            <?= number_format(count($totalSantri)) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card report-card h-100">
                    <div class="card-body">
                        <div class="text-secondary">Rata-rata Nilai</div>
                        <div class="report-number">
                            <?= $rataRata !== null
                                ? number_format($rataRata, 2)
                                : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL LAPORAN -->
        <div class="card report-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-report
                                  table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>Nama Santri</th>
                                <th>Kamar</th>
                                <th>Nama Surah</th>
                                <th>Ayat</th>
                                <th>Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($laporan)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        Tidak ada data setoran yang sesuai
                                        dengan filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($laporan as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <?= date(
                                                'd-m-Y',
                                                strtotime($row['tanggal'])
                                            ) ?>
                                        </td>
                                        <td><?= e($row['nama_santri']) ?></td>
                                        <td>
                                            <?= $row['kamar'] !== null &&
                                                $row['kamar'] !== ''
                                                ? 'Kamar ' . e($row['kamar'])
                                                : '-' ?>
                                        </td>
                                        <td><?= e($row['nama_surah']) ?></td>
                                        <td>
                                            <?= (int) $row['ayat_awal'] ?>
                                            -
                                            <?= (int) $row['ayat_akhir'] ?>
                                        </td>
                                        <td>
                                            <?= $row['nilai'] !== null &&
                                                $row['nilai'] !== ''
                                                ? number_format(
                                                    (float) $row['nilai'],
                                                    2
                                                )
                                                : '-' ?>
                                        </td>
                                        <td>
                                            <?= e($row['keterangan'] ?: '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-secondary small mt-3 mb-0">
                    Jumlah data ditampilkan: <?= $totalSetoran ?> setoran.
                </p>
            </div>
        </div>

        <!-- AREA TANDA TANGAN -->
        <div class="row mt-5 text-center">
            <div class="col-6"></div>
            <div class="col-6">
                <p>Mengetahui,</p>
                <p>Pembimbing Hafalan</p>
                <br><br><br>
                <p>________________________</p>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
    // Membuka dialog cetak browser.
    // Pilih "Save as PDF" untuk menyimpan laporan sebagai PDF.
</script>

<?php include 'footer.php'; ?>