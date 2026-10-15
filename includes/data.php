<?php
// Data statis program donasi
$programs = [
    [
        'id'            => 1,
        'kategori'      => 'Pendidikan',
        'kategori_slug' => 'pendidikan',
        'judul'         => 'Bantu Renovasi Sekolah Pelosok Negeri 1',
        'penyelenggara' => 'Himpunan Mahasiswa Informatika (HIMAFOR)',
        'target'        => 50000000,
        'terkumpul'     => 32500000,
        'persen'        => 65,
        'sisa_hari'     => 12,
        'gambar'        => '/Donasi/assets/img/program_sekolah.jpg',
        'badge_color'   => '#1A73E8',
        'deskripsi'     => 'Program renovasi sekolah pelosok negeri 1 merupakan bentuk kepedulian untuk membantu sekolah yang membutuhkan renovasi bangunan. Pengusul mengajukan program ini dengan target khusus untuk Mahasiswa Informatika (HIMAFOR) saja agar dapat berpartisipasi dan berkontribusi secara langsung dalam pembangunan sarana pendidikan yang lebih layak.',
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
        'judul'         => 'Paket alat tulis untuk panti asuhan',
        'penyelenggara' => 'UKM forum kajian Kitab Kuning',
        'target'        => 70000000,
        'terkumpul'     => 45000000,
        'persen'        => 75,
        'sisa_hari'     => 9,
        'gambar'        => '/Donasi/assets/img/program_panti.jpg',
        'badge_color'   => '#34C759',
        'deskripsi'     => 'Program bantuan paket alat tulis ini ditujukan bagi anak-anak di panti asuhan agar dapat belajar dengan peralatan yang memadai. Dana yang terkumpul akan digunakan untuk memenuhi kebutuhan alat tulis sekolah dan buku bacaan.',
        'rincian'       => [
            ['item' => 'Buku tulis & bacaan', 'biaya' => 30000000],
            ['item' => 'Alat tulis & tas sekolah', 'biaya' => 30000000],
            ['item' => 'Distribusi bantuan', 'biaya' => 10000000],
        ]
    ],
    [
        'id'            => 3,
        'kategori'      => 'Kemanusiaan',
        'kategori_slug' => 'kemanusiaan',
        'judul'         => 'Bantuan untuk korban kebanjiran',
        'penyelenggara' => 'Lembaga Dakwah Mahasiswa (LDM)',
        'target'        => 80000000,
        'terkumpul'     => 25600000,
        'persen'        => 35,
        'sisa_hari'     => 30,
        'gambar'        => '/Donasi/assets/img/program_banjir.jpg',
        'badge_color'   => '#FF6B35',
        'deskripsi'     => 'Program Bantuan Korban Banjir merupakan bentuk kepedulian untuk membantu masyarakat yang terdampak bencana alam. Dana yang terkumpul akan digunakan untuk memenuhi kebutuhan mendesak seperti makanan, air bersih, pakaian, obat-obatan, dan kebutuhan pokok lainnya.',
        'rincian'       => [
            ['item' => 'Sembako & makanan', 'biaya' => 40000000],
            ['item' => 'Obat-obatan & kesehatan', 'biaya' => 30000000],
            ['item' => 'Distribusi bantuan', 'biaya' => 10000000],
        ]
    ],
    [
        'id'            => 4,
        'kategori'      => 'Kesehatan',
        'kategori_slug' => 'kesehatan',
        'judul'         => 'Operasi Katarak Gratis untuk Lansia',
        'penyelenggara' => 'Yayasan Peduli Kesehatan',
        'target'        => 120000000,
        'terkumpul'     => 87000000,
        'persen'        => 73,
        'sisa_hari'     => 27,
        'gambar'        => '/Donasi/assets/img/program_katarak.jpg',
        'badge_color'   => '#AF52DE',
        'deskripsi'     => 'Program ini bertujuan membiayai operasi katarak gratis bagi lansia yang kurang mampu. Melalui program ini, kita berharap dapat mengembalikan penglihatan mereka sehingga dapat kembali beraktivitas dengan baik.',
        'rincian'       => [
            ['item' => 'Biaya operasi 20 lansia', 'biaya' => 100000000],
            ['item' => 'Perawatan pasca operasi', 'biaya' => 15000000],
            ['item' => 'Akomodasi & logistik', 'biaya' => 5000000],
        ]
    ],
    [
        'id'            => 5,
        'kategori'      => 'Keagamaan',
        'kategori_slug' => 'keagamaan',
        'judul'         => 'Penanaman 10.000 Pohon di Kalimantan',
        'penyelenggara' => 'Komunitas Hijau Indonesia',
        'target'        => 50000000,
        'terkumpul'     => 22000000,
        'persen'        => 44,
        'sisa_hari'     => 87,
        'gambar'        => '/Donasi/assets/img/program_pohon.jpg',
        'badge_color'   => '#34C759',
        'deskripsi'     => 'Mari wujudkan Kalimantan yang lebih hijau melalui program penanaman 10.000 bibit pohon. Gerakan ini merupakan langkah nyata untuk melestarikan lingkungan, mencegah erosi, dan menyediakan oksigen bagi generasi mendatang.',
        'rincian'       => [
            ['item' => 'Pembelian 10.000 bibit', 'biaya' => 30000000],
            ['item' => 'Perawatan & pupuk', 'biaya' => 15000000],
            ['item' => 'Operasional relawan', 'biaya' => 5000000],
        ]
    ],
    [
        'id'            => 6,
        'kategori'      => 'Kemanusiaan',
        'kategori_slug' => 'kemanusiaan',
        'judul'         => 'Bantuan Gempa Cianjur',
        'penyelenggara' => 'Relawan Bencana Nusantara',
        'target'        => 200000000,
        'terkumpul'     => 156000000,
        'persen'        => 78,
        'sisa_hari'     => 25,
        'gambar'        => '/Donasi/assets/img/program_gempa.jpg',
        'badge_color'   => '#FF6B35',
        'deskripsi'     => 'Program Bantuan Korban Gempa Cianjur dirancang untuk merespon secara cepat kebutuhan darurat masyarakat. Bantuan ini meliputi pembangunan hunian sementara, logistik makanan, serta fasilitas kesehatan darurat di lokasi bencana.',
        'rincian'       => [
            ['item' => 'Pembangunan hunian sementara', 'biaya' => 100000000],
            ['item' => 'Logistik & sembako', 'biaya' => 70000000],
            ['item' => 'Fasilitas medis & obat', 'biaya' => 30000000],
        ]
    ],
];

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
