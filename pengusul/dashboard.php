<?php
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['id_user']) ||
    $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/donatur/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengusul - SIBERASA</title>
</head>
<body>
    <h1>Dashboard Pengusul SIBERASA</h1>

    <p>
        Selamat datang,
        <?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?>!
    </p>

    <p>Kamu berhasil login sebagai pengusul.</p>

    <a href="../auth/logout.php">Logout</a>
</body>
</html>