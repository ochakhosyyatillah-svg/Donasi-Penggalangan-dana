<?php
// includes/data.php - Dynamic Donation Programs Data

require_once __DIR__ . '/../assets/config/database.php';

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('getBadgeColor')) {
    function getBadgeColor($kategori) {
        $kat = strtolower(trim((string)$kategori));
        if (strpos($kat, 'pendidikan') !== false) return '#1A73E8';
        if (strpos($kat, 'sosial') !== false) return '#34C759';
        if (strpos($kat, 'kemanusiaan') !== false) return '#FF6B35';
        if (strpos($kat, 'kesehatan') !== false) return '#AF52DE';
        if (strpos($kat, 'keagamaan') !== false) return '#EAB308';
        if (strpos($kat, 'lingkungan') !== false) return '#10B981';
        if (strpos($kat, 'bencana') !== false) return '#EF4444';
        return '#1A73E8';
    }
}

if (!function_exists('getCategorySlug')) {
    function getCategorySlug($kategori) {
        $kat = strtolower(trim((string)$kategori));
        if (strpos($kat, 'sosial') !== false) return 'sosial';
        if (strpos($kat, 'pendidikan') !== false) return 'pendidikan';
        if (strpos($kat, 'kemanusiaan') !== false) return 'kemanusiaan';
        if (strpos($kat, 'kesehatan') !== false) return 'kesehatan';
        if (strpos($kat, 'keagamaan') !== false) return 'keagamaan';
        if (strpos($kat, 'lingkungan') !== false) return 'lingkungan';
        $cleaned = preg_replace('/[^a-z0-9]+/', '', $kat);
        return $cleaned ?: 'umum';
    }
}

$programs = [];

try {
    // Ambil program dengan status 'disetujui' atau 'berlangsung'
    $sql = "
        SELECT 
            p.id_program,
            p.judul,
            p.kategori,
            p.deskripsi,
            p.target_dana,
            p.tujuan_dana,
            p.penerima_dana,
            p.tanggal_mulai,
            p.tanggal_selesai,
            p.foto_program,
            p.status_program,
            COALESCE(u.nama_organisasi, u.nama, 'Komunitas Peduli') AS nama_pengusul_db,
            COALESCE(SUM(CASE WHEN d.status_donasi = 'berhasil' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul
        FROM program_donasi p
        LEFT JOIN users u ON p.id_pengusul = u.id_user
        LEFT JOIN donasi d ON p.id_program = d.id_program
        WHERE p.status_program IN ('disetujui', 'berlangsung')
        GROUP BY p.id_program
        ORDER BY p.id_program DESC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $target = (float)$row['target_dana'];
        $terkumpul = (float)$row['dana_terkumpul'];
        $persen = ($target > 0) ? min(100, round(($terkumpul / $target) * 100)) : 0;

        $tglSelesai = !empty($row['tanggal_selesai']) ? strtotime($row['tanggal_selesai']) : (time() + (30 * 86400));
        $sisaHari = max(0, (int)ceil(($tglSelesai - time()) / 86400));

        // Format path gambar
        $img = '/Donasi/assets/img/program_banjir.jpg';
        if (!empty($row['foto_program'])) {
            $foto = trim($row['foto_program']);
            if (strpos($foto, '/') === 0) {
                $img = $foto;
            } elseif (strpos($foto, 'assets/') === 0) {
                $img = '/Donasi/' . $foto;
            } else {
                $img = '/Donasi/assets/img/' . $foto;
            }
        }

        // Penyelenggara
        $penyelenggara = !empty($row['penerima_dana']) ? $row['penerima_dana'] : $row['nama_pengusul_db'];

        // Kategori slug
        $kategoriSlug = getCategorySlug($row['kategori']);

        // Rincian penggunaan dana
        $rincian = [];
        if (!empty($row['tujuan_dana'])) {
            $decoded = json_decode($row['tujuan_dana'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    $rincian[] = [
                        'item'  => $item['kebutuhan'] ?? ($item['item'] ?? 'Kebutuhan'),
                        'biaya' => (float)($item['jumlah'] ?? ($item['biaya'] ?? 0))
                    ];
                }
            } else {
                $rincian[] = [
                    'item'  => $row['tujuan_dana'],
                    'biaya' => $target
                ];
            }
        }

        if (empty($rincian)) {
            $rincian = [
                ['item' => 'Penyaluran Bantuan Donasi', 'biaya' => $target]
            ];
        }

        $programs[] = [
            'id'            => (int)$row['id_program'],
            'kategori'      => $row['kategori'],
            'kategori_slug' => $kategoriSlug,
            'slug_kat'      => $kategoriSlug,
            'judul'         => $row['judul'],
            'penyelenggara' => $penyelenggara,
            'org'           => $penyelenggara,
            'target'        => $target,
            'terkumpul'     => $terkumpul,
            'persen'        => $persen,
            'sisa_hari'     => $sisaHari,
            'gambar'        => $img,
            'img'           => $img,
            'badge_color'   => getBadgeColor($row['kategori']),
            'deskripsi'     => $row['deskripsi'],
            'rincian'       => $rincian
        ];
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

// Fallback data statis jika di database belum ada program sama sekali
if (empty($programs)) {
    $programs = [
        [
            'id'            => 1,
            'kategori'      => 'Pendidikan',
            'kategori_slug' => 'pendidikan',
            'slug_kat'      => 'pendidikan',
            'judul'         => 'Bantu Renovasi Sekolah Pelosok Negeri 1',
            'penyelenggara' => 'Himpunan Mahasiswa Informatika (HIMAFOR)',
            'org'           => 'Himpunan Mahasiswa Informatika (HIMAFOR)',
            'target'        => 50000000,
            'terkumpul'     => 32500000,
            'persen'        => 65,
            'sisa_hari'     => 12,
            'gambar'        => '/Donasi/assets/img/program_sekolah.jpg',
            'img'           => '/Donasi/assets/img/program_sekolah.jpg',
            'badge_color'   => '#1A73E8',
            'deskripsi'     => 'Program renovasi sekolah pelosok negeri 1 merupakan bentuk kepedulian untuk membantu sekolah yang membutuhkan renovasi bangunan.',
            'rincian'       => [
                ['item' => 'Bahan bangunan', 'biaya' => 20000000],
                ['item' => 'Alat tulis & meja', 'biaya' => 15000000],
                ['item' => 'Distribusi & operasional', 'biaya' => 15000000],
            ]
        ],
        [
            'id'            => 2,
            'kategori'      => 'Sosial',
            'kategori_slug' => 'sosial',
            'slug_kat'      => 'sosial',
            'judul'         => 'Paket alat tulis untuk panti asuhan',
            'penyelenggara' => 'UKM forum kajian Kitab Kuning',
            'org'           => 'UKM forum kajian Kitab Kuning',
            'target'        => 70000000,
            'terkumpul'     => 45000000,
            'persen'        => 75,
            'sisa_hari'     => 9,
            'gambar'        => '/Donasi/assets/img/program_panti.jpg',
            'img'           => '/Donasi/assets/img/program_panti.jpg',
            'badge_color'   => '#34C759',
            'deskripsi'     => 'Program bantuan paket alat tulis ini ditujukan bagi anak-anak di panti asuhan agar dapat belajar dengan peralatan yang memadai.',
            'rincian'       => [
                ['item' => 'Buku tulis & bacaan', 'biaya' => 30000000],
                ['item' => 'Alat tulis & tas sekolah', 'biaya' => 30000000],
                ['item' => 'Distribusi bantuan', 'biaya' => 10000000],
            ]
        ]
    ];
}
