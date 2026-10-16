<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Santri</title>

    <link rel="icon" type="image/png" href="img/logotkj.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">

        <a class="navbar-brand fw-bold" href="dashboard.php">
            Data Santri
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuSantri"
                aria-controls="menuSantri"
                aria-expanded="false"
                aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuSantri">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        Data Santri
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="tambah.php">
                        Tambah Santri
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="setoran.php">
                        Setoran Hafalan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="laporan.php">
                        Laporan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="export.php">
                        Export CSV
                    </a>
                </li>

            </ul>

            <?php if (!empty($_SESSION['admin_id'])): ?>
                <span class="navbar-text text-white me-3">
                    Admin: <?= e($_SESSION['admin_username'] ?? 'Admin') ?>
                </span>

                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-success alert-dismissible fade show"
             role="alert">

            <?= e($_SESSION['flash']) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup"></button>
        </div>

        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>