<?php
require __DIR__ . '/../assets/config/database.php';
$stmt = $pdo->prepare('SELECT p.*, u.nama, u.nama_organisasi FROM program_donasi p LEFT JOIN users u ON p.id_pengusul = u.id_user WHERE p.id_program = 5');
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt2 = $pdo->prepare('SELECT * FROM akun_tujuan_pembayaran WHERE id_program = 5');
$stmt2->execute();
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
