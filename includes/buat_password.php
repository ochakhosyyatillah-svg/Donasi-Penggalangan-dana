<?php
require_once __DIR__ . '/../assets/config/database.php';

$email = 'admin@siberasa.id';
$password = 'AdminSiberasa123!';
$nama = 'Admin SIBERASA';

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        "INSERT INTO users
        (nama, email, password, role, status_verifikasi)
        VALUES (?, ?, ?, 'admin', 'diterima')
        ON DUPLICATE KEY UPDATE
        nama = VALUES(nama),
        password = VALUES(password),
        role = 'admin',
        status_verifikasi = 'diterima'"
    );

    $stmt->execute([$nama, $email, $hash]);

    echo "Akun admin berhasil dibuat atau diperbarui.<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password: " . htmlspecialchars($password) . "<br>";
} catch (PDOException $e) {
    echo "Gagal: " . $e->getMessage();
}
?>
