<?php
$page_title = $page_title ?? 'SIBERASA - Sistem Donasi dan Penggalangan Dana';
$page_desc = $page_desc ?? 'SIBERASA adalah platform donasi online terpercaya. Berbagi Rasa, Hadirkan Manfaat.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>">
    <meta name="keywords" content="donasi, penggalangan dana, zakat, sedekah, siberasa">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_desc) ?>">
    <meta property="og:type" content="website">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="/Donasi/assets/css/style.css?v=<?= time() ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/logo%20donasi.png">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="/Donasi/" class="nav-logo">
            <img src="/Donasi/assets/img/logo_uin.jpg" alt="Logo UIN Siber Syekh Nurjati Cirebon" class="logo-uin">
            <span class="logo-divider"></span>
            <img src="/logo%20donasi.png" alt="SIBERASA Logo" onerror="this.src='/Donasi/assets/img/logo_donasi.png'" class="logo-siberasa">
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="/Donasi/" class="<?= ($current_page === 'beranda') ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="/Donasi/donatur/program.php" class="<?= ($current_page === 'program') ? 'active' : '' ?>">Program Donasi</a></li>
            <li><a href="/Donasi/donatur/galangdana.php" class="<?= ($current_page === 'galangdana') ? 'active' : '' ?>">Galang Dana</a></li>
            <li><a href="/Donasi/donatur/tentang.php" class="<?= ($current_page === 'tentang') ? 'active' : '' ?>">Tentang Kami</a></li>
            <li><a href="/Donasi/donatur/kontak.php" class="<?= ($current_page === 'kontak') ? 'active' : '' ?>">Kontak</a></li>
        </ul>



        <div class="nav-actions">
            <button class="btn btn-primary" id="btnMasuk" onclick="window.location.href='/Donasi/auth/login.php'">Masuk</button>
            <button class="btn btn-outline" id="btnDaftar" onclick="window.location.href='/Donasi/auth/register.php'">Daftar</button>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>
