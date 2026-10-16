<?php
require_once 'koneksi.php';
require_once __DIR__ . '/auth.php';

/* =========================================
   1. STATISTIK UTAMA
========================================= */

// Total santri
$stmt = $pdo->query("SELECT COUNT(*) FROM santri");
$totalSantri = (int) $stmt->fetchColumn();

// Total setoran
$stmt = $pdo->query("SELECT COUNT(*) FROM setoran_hafalan");
$totalSetoran = (int) $stmt->fetchColumn();

// Rata-rata nilai
$stmt = $pdo->query("SELECT AVG(nilai) FROM setoran_hafalan");
$rataNilai = $stmt->fetchColumn();

// Santri yang sudah menyetor
$stmt = $pdo->query(
    "SELECT COUNT(DISTINCT santri_id)
     FROM setoran_hafalan"
);
$santriSudahSetor = (int) $stmt->fetchColumn();

// Santri yang belum menyetor
$santriBelumSetor = max(0, $totalSantri - $santriSudahSetor);

// Persentase santri yang sudah menyetor
$persentaseSetor = $totalSantri > 0
    ? round(($santriSudahSetor / $totalSantri) * 100, 1)
    : 0;

// Jumlah setoran hari ini
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM setoran_hafalan
     WHERE tanggal = :tanggal"
);
$stmt->execute(['tanggal' => date('Y-m-d')]);
$setoranHariIni = (int) $stmt->fetchColumn();


/* =========================================
   2. LIMA SETORAN TERBARU
========================================= */

$stmt = $pdo->query(
    "SELECT
        sh.id,
        sh.tanggal,
        sh.nama_surah,
        sh.ayat_awal,
        sh.ayat_akhir,
        sh.nilai,
        s.nama AS nama_santri,
        s.kamar
     FROM setoran_hafalan sh
     INNER JOIN santri s ON sh.santri_id = s.id
     ORDER BY sh.tanggal DESC, sh.id DESC
     LIMIT 5"
);

$setoranTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================
   3. STATISTIK SANTRI PER KAMAR
========================================= */

$stmt = $pdo->query(
    "SELECT
        COALESCE(NULLIF(kamar, ''), 'Belum diatur') AS nama_kamar,
        COUNT(*) AS jumlah
     FROM santri
     GROUP BY COALESCE(NULLIF(kamar, ''), 'Belum diatur')
     ORDER BY nama_kamar ASC"
);

$dataKamar = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================
   4. DATA GRAFIK 12 BULAN TERAKHIR
========================================= */

$stmt = $pdo->query(
    "SELECT
        DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
        COUNT(*) AS jumlah
     FROM setoran_hafalan
     WHERE tanggal >= DATE_FORMAT(
         DATE_SUB(CURDATE(), INTERVAL 11 MONTH),
         '%Y-%m-01'
     )
     AND tanggal < DATE_ADD(
         LAST_DAY(CURDATE()),
         INTERVAL 1 DAY
     )
     GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
     ORDER BY bulan ASC"
);

$dataGrafik = $stmt->fetchAll(PDO::FETCH_ASSOC);

$namaBulan = [
    '01' => 'Jan',
    '02' => 'Feb',
    '03' => 'Mar',
    '04' => 'Apr',
    '05' => 'Mei',
    '06' => 'Jun',
    '07' => 'Jul',
    '08' => 'Agu',
    '09' => 'Sep',
    '10' => 'Okt',
    '11' => 'Nov',
    '12' => 'Des'
];

$rekapGrafik = [];

for ($i = 11; $i >= 0; $i--) {
    $waktu = strtotime("-$i months");
    $bulanKey = date('Y-m', $waktu);

    $rekapGrafik[$bulanKey] = [
        'label' => $namaBulan[date('m', $waktu)]
            . ' ' . date('Y', $waktu),
        'jumlah' => 0
    ];
}

foreach ($dataGrafik as $item) {
    if (isset($rekapGrafik[$item['bulan']])) {
        $rekapGrafik[$item['bulan']]['jumlah'] =
            (int) $item['jumlah'];
    }
}

$labelGrafik = array_column($rekapGrafik, 'label');
$jumlahGrafik = array_column($rekapGrafik, 'jumlah');


/* =========================================
   5. TANGGAL INDONESIA
========================================= */

$namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$namaBulanIndonesia = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$tanggalSekarang = $namaHari[date('l')] . ', '
    . date('d') . ' '
    . $namaBulanIndonesia[(int) date('n')] . ' '
    . date('Y');

include 'header.php';
?>

<style>
    .dashboard-page {
        padding: 25px 0 40px;
        color: #243447;
    }

    .dashboard-heading {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }

    .dashboard-heading h2 {
        color: #183b32;
        font-size: 29px;
        font-weight: 800;
        margin-bottom: 7px;
    }

    .dashboard-heading p {
        color: #6c757d;
        margin-bottom: 0;
    }

    .dashboard-date {
        background: #fff;
        border: 1px solid #dce8e0;
        padding: 12px 16px;
        border-radius: 12px;
        color: #35654b;
        font-size: 14px;
    }

    .dashboard-welcome {
        background: linear-gradient(120deg, #176b48, #239568);
        color: #fff;
        border-radius: 17px;
        padding: 27px;
        margin-bottom: 25px;
        box-shadow: 0 7px 22px rgba(25, 135, 84, .15);
    }

    .dashboard-welcome h3 {
        font-weight: 800;
        margin-bottom: 9px;
    }

    .dashboard-welcome p {
        margin-bottom: 0;
        opacity: .92;
    }

    .dashboard-stat {
        height: 100%;
        background: #fff;
        border: 1px solid #e2e8e4;
        border-radius: 15px;
        padding: 21px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .035);
        transition: transform .2s, box-shadow .2s;
    }

    .dashboard-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(0, 0, 0, .07);
    }

    .dashboard-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .dashboard-stat-label {
        color: #6c757d;
        font-size: 13px;
        margin-bottom: 7px;
    }

    .dashboard-stat-number {
        color: #198754;
        font-size: 31px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .dashboard-icon {
        width: 49px;
        height: 49px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e0f2e8;
        color: #198754;
        border-radius: 14px;
        font-size: 23px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .dashboard-stat-note {
        color: #7b8794;
        font-size: 12px;
        margin-top: 9px;
    }

    .dashboard-panel {
        height: 100%;
        background: #fff;
        border: 1px solid #e0e7e2;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .035);
    }

    .dashboard-panel-heading {
        padding: 17px 20px;
        background: #f0f8f3;
        color: #234b39;
        font-weight: 750;
        border-bottom: 1px solid #dce9df;
    }

    .dashboard-panel-body {
        padding: 20px;
    }

    .dashboard-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .dashboard-action {
        min-height: 95px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        text-align: center;
        color: #286548;
        background: #f5faf7;
        border: 1px solid #dce9df;
        border-radius: 13px;
        padding: 14px 8px;
        font-weight: 650;
        font-size: 13px;
        transition: .2s;
    }

    .dashboard-action:hover {
        color: #fff;
        background: #198754;
        border-color: #198754;
        transform: translateY(-2px);
    }

    .dashboard-action-icon {
        font-size: 25px;
    }

    .dashboard-table {
        min-width: 650px;
        margin-bottom: 0;
        vertical-align: middle;
    }

    .dashboard-table th {
        background: #f5f8f6;
        white-space: nowrap;
        color: #345b46;
        font-size: 12px;
    }

    .dashboard-table th,
    .dashboard-table td {
        padding: 13px 12px;
    }

    .dashboard-table td {
        font-size: 13px;
    }

    .dashboard-room {
        margin-bottom: 21px;
    }

    .dashboard-room:last-child {
        margin-bottom: 0;
    }

    .dashboard-room-label {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .dashboard-room-track,
    .dashboard-progress-track {
        width: 100%;
        height: 10px;
        border-radius: 20px;
        background: #e9eeeb;
        overflow: hidden;
    }

    .dashboard-room-fill,
    .dashboard-progress-fill {
        height: 100%;
        border-radius: 20px;
        background: linear-gradient(90deg, #198754, #52b788);
    }

    .dashboard-progress-fill {
        transition: width .3s;
    }

    .dashboard-empty {
        padding: 28px 15px;
        text-align: center;
        color: #7b8794;
    }

    .dashboard-footer-note {
        background: #edf7f0;
        color: #35654b;
        border: 1px solid #d6eadd;
        border-radius: 12px;
        padding: 15px 18px;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .dashboard-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-welcome {
            padding: 22px;
        }

        .dashboard-heading h2 {
            font-size: 25px;
        }
    }

    @media (max-width: 576px) {
        .dashboard-page {
            padding-top: 16px;
        }

        .dashboard-stat {
            padding: 17px;
        }

        .dashboard-stat-number {
            font-size: 27px;
        }

        .dashboard-panel-body {
            padding: 15px;
        }
    }
</style>

<div class="dashboard-page">

    <!-- JUDUL -->
    <div class="dashboard-heading">
        <div>
            <h2>Dashboard</h2>
            <p>Sistem Informasi Data Santri dan Hafalan</p>
        </div>

        <div class="dashboard-date">
            📅 <?= e($tanggalSekarang) ?>
        </div>
    </div>

    <!-- SAMBUTAN -->
    <div class="dashboard-welcome">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <h3>Selamat Datang! 👋</h3>
                <p>
                    Kelola data santri, catat setoran hafalan, dan
                    pantau perkembangan hafalan dalam satu halaman.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <a href="setoran.php" class="btn btn-light">
                    + Catat Setoran
                </a>
            </div>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-stat">
                <div class="dashboard-stat-top">
                    <div>
                        <div class="dashboard-stat-label">Total Santri</div>
                        <p class="dashboard-stat-number"><?= $totalSantri ?></p>
                    </div>
                    <div class="dashboard-icon">♟</div>
                </div>
                <a href="index.php" class="btn btn-sm btn-outline-success">
                    Lihat Data Santri
                </a>
                <div class="dashboard-stat-note">Seluruh santri terdaftar</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-stat">
                <div class="dashboard-stat-top">
                    <div>
                        <div class="dashboard-stat-label">Total Setoran</div>
                        <p class="dashboard-stat-number"><?= $totalSetoran ?></p>
                    </div>
                    <div class="dashboard-icon">▤</div>
                </div>
                <a href="setoran.php" class="btn btn-sm btn-outline-success">
                    Lihat Setoran
                </a>
                <div class="dashboard-stat-note">Seluruh riwayat setoran</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-stat">
                <div class="dashboard-stat-top">
                    <div>
                        <div class="dashboard-stat-label">Setoran Hari Ini</div>
                        <p class="dashboard-stat-number"><?= $setoranHariIni ?></p>
                    </div>
                    <div class="dashboard-icon">✓</div>
                </div>
                <a href="setoran.php" class="btn btn-sm btn-outline-success">
                    Catat Setoran
                </a>
                <div class="dashboard-stat-note">Berdasarkan tanggal setoran</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dashboard-stat">
                <div class="dashboard-stat-top">
                    <div>
                        <div class="dashboard-stat-label">Rata-rata Nilai</div>
                        <p class="dashboard-stat-number">
                            <?= $rataNilai !== null
                                ? number_format((float) $rataNilai, 1, ',', '.')
                                : '-' ?>
                        </p>
                    </div>
                    <div class="dashboard-icon">★</div>
                </div>
                <div class="dashboard-stat-note">Skala nilai 0–100</div>
            </div>
        </div>

    </div>

    <!-- MENU CEPAT -->
    <div class="dashboard-panel mb-4">
        <div class="dashboard-panel-heading">Menu Cepat</div>

        <div class="dashboard-panel-body">
            <div class="dashboard-actions">

                <a href="index.php" class="dashboard-action">
                    <span class="dashboard-action-icon">♟</span>
                    Data Santri
                </a>

                <a href="tambah.php" class="dashboard-action">
                    <span class="dashboard-action-icon">＋</span>
                    Tambah Santri
                </a>

                <a href="setoran.php" class="dashboard-action">
                    <span class="dashboard-action-icon">📖</span>
                    Setoran Hafalan
                </a>

                <a href="laporan.php" class="dashboard-action">
                    <span class="dashboard-action-icon">▤</span>
                    Laporan Hafalan
                </a>

            </div>
        </div>
    </div>

    <!-- GRAFIK 12 BULAN -->
    <div class="dashboard-panel mb-4">
        <div class="dashboard-panel-heading">
            Grafik Perkembangan Setoran
            <span class="badge text-bg-success ms-2">12 Bulan Terakhir</span>
        </div>

        <div class="dashboard-panel-body">
            <p class="text-secondary small">
                Jumlah setoran hafalan per bulan berdasarkan tanggal setoran.
            </p>

            <div style="position: relative; width: 100%; height: 320px;">
                <canvas id="grafikSetoran"
                        aria-label="Grafik setoran hafalan per bulan"
                        role="img"></canvas>
            </div>

            <noscript>
                <p class="text-danger">
                    Grafik memerlukan JavaScript aktif.
                </p>
            </noscript>
        </div>
    </div>

    <!-- PROGRES SETORAN -->
    <div class="dashboard-panel mb-4">
        <div class="dashboard-panel-heading">
            Progres Santri yang Sudah Menyetor
        </div>

        <div class="dashboard-panel-body">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                <span class="text-secondary small">
                    <?= $santriSudahSetor ?> dari <?= $totalSantri ?> santri
                </span>

                <strong class="text-success">
                    <?= number_format($persentaseSetor, 1, ',', '.') ?>%
                </strong>
            </div>

            <div class="dashboard-progress-track">
                <div class="dashboard-progress-fill"
                     style="width: <?= $persentaseSetor ?>%">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-sm-6">
                    <div class="p-3 rounded border">
                        <div class="text-secondary small">Sudah Menyetor</div>
                        <strong class="text-success fs-5">
                            <?= $santriSudahSetor ?> santri
                        </strong>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="p-3 rounded border">
                        <div class="text-secondary small">
                            Belum Pernah Menyetor
                        </div>
                        <strong class="text-danger fs-5">
                            <?= $santriBelumSetor ?> santri
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL DAN STATISTIK KAMAR -->
    <div class="row g-4">

        <div class="col-12 col-xl-8">
            <div class="dashboard-panel">

                <div class="dashboard-panel-heading d-flex
                            justify-content-between align-items-center gap-2">
                    <span>Setoran Terbaru</span>

                    <a href="setoran.php"
                       class="btn btn-sm btn-outline-success">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover dashboard-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>Nama Santri</th>
                                <th>Surah</th>
                                <th>Ayat</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($setoranTerbaru)): ?>
                                <tr>
                                    <td colspan="6" class="dashboard-empty">
                                        Belum ada setoran hafalan.
                                        <br>
                                        <a href="setoran.php"
                                           class="btn btn-sm btn-success mt-2">
                                            Tambahkan Setoran
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($setoranTerbaru as $no => $item): ?>
                                    <tr>
                                        <td><?= $no + 1 ?></td>
                                        <td>
                                            <?= e(date(
                                                'd-m-Y',
                                                strtotime($item['tanggal'])
                                            )) ?>
                                        </td>
                                        <td>
                                            <strong><?= e($item['nama_santri']) ?></strong>
                                            <br>
                                            <small class="text-secondary">
                                                Kamar <?= e($item['kamar'] ?: '-') ?>
                                            </small>
                                        </td>
                                        <td><?= e($item['nama_surah']) ?></td>
                                        <td>
                                            <?= (int) $item['ayat_awal'] ?>
                                            –
                                            <?= (int) $item['ayat_akhir'] ?>
                                        </td>
                                        <td>
                                            <?= $item['nilai'] !== null
                                                ? e(number_format(
                                                    (float) $item['nilai'],
                                                    1, ',', '.'
                                                ))
                                                : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="dashboard-panel">

                <div class="dashboard-panel-heading">
                    Data Santri per Kamar
                </div>

                <div class="dashboard-panel-body">
                    <?php if (empty($dataKamar)): ?>
                        <div class="dashboard-empty">
                            Belum ada data santri.
                        </div>
                    <?php else: ?>

                        <?php foreach ($dataKamar as $itemKamar): ?>
                            <?php
                            $jumlahKamar = (int) $itemKamar['jumlah'];

                            $persentaseKamar = $totalSantri > 0
                                ? ($jumlahKamar / $totalSantri) * 100
                                : 0;
                            ?>

                            <div class="dashboard-room">
                                <div class="dashboard-room-label">
                                    <span>
                                        Kamar <?= e($itemKamar['nama_kamar']) ?>
                                    </span>
                                    <strong><?= $jumlahKamar ?> santri</strong>
                                </div>

                                <div class="dashboard-room-track">
                                    <div class="dashboard-room-fill"
                                         style="width: <?= $persentaseKamar ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="dashboard-footer-note mt-4">
        <strong>Informasi:</strong>
        Statistik dashboard diperbarui saat halaman dimuat ulang.
        Pastikan data santri dan setoran yang dimasukkan sudah benar.
    </div>

</div>

<!-- LIBRARY GRAFIK -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const elemenGrafik = document.getElementById('grafikSetoran');

    if (!elemenGrafik || typeof Chart === 'undefined') {
        return;
    }

    const labelBulan = <?= json_encode(
        $labelGrafik,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const jumlahSetoran = <?= json_encode(
        $jumlahGrafik,
        JSON_NUMERIC_CHECK
    ) ?>;

    new Chart(elemenGrafik, {
        type: 'bar',

        data: {
            labels: labelBulan,
            datasets: [{
                label: 'Jumlah Setoran',
                data: jumlahSetoran,
                backgroundColor: 'rgba(25, 135, 84, 0.75)',
                borderColor: '#198754',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 38
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ' ' + context.parsed.y + ' setoran';
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Setoran'
                    }
                },

                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>