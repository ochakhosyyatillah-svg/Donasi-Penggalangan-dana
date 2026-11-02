<?php
require_once __DIR__ . '/../assets/config/database.php';

$akun = [
    [
        'nama' => 'Admin',
        'email' => 'admin@siberasa.id',
        'password' => 'AdminSiberasa123!'
    ],
    [
        'nama' => 'Pengusul',
        'email' => 'ocha@gmail.com',
        'password' => 'Pengusul1'
    ]
];

foreach ($akun as $data) {

    $stmt = $pdo->prepare(
        "SELECT id_user, nama, email, password, role, status_verifikasi
         FROM users WHERE email = ?"
    );

    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Pengecekan " . $data['nama'] . "</h3>";

    if (!$user) {
        echo "Akun tidak ditemukan.<br>";
    } else {
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Role: " . htmlspecialchars($user['role']) . "<br>";
        echo "Status: " . htmlspecialchars($user['status_verifikasi']) . "<br>";

        if (password_verify($data['password'], $user['password'])) {
            echo "Password: BENAR<br>";
        } else {
            echo "Password: TIDAK COCOK<br>";
        }
    }

    echo "<hr>";
}
?>