<?php
require __DIR__ . '/../assets/config/database.php';

function cariStatusByKode($kode, $pdo) {
    $kode = trim($kode);
    if (empty($kode) || !isset($pdo)) {
        return null;
    }

    // 1. Cek di tabel donasi terlebih dahulu
    try {
        $stmtDon = $pdo->prepare("
            SELECT d.*, p.judul AS nama_program, pb.status_verifikasi, pb.tanggal_pembayaran
            FROM donasi d
            JOIN program_donasi p ON d.id_program = p.id_program
            LEFT JOIN pembayaran pb ON d.id_donasi = pb.id_donasi
            WHERE d.kode_tracking = ?
            ORDER BY d.id_donasi DESC
            LIMIT 1
        ");
        $stmtDon->execute([$kode]);
        $donRow = $stmtDon->fetch(PDO::FETCH_ASSOC);

        if ($donRow) {
            $stVerif = $donRow['status_verifikasi'] ?? 'menunggu';
            if ($donRow['status_donasi'] === 'berhasil') {
                $stVerif = 'diterima';
            } elseif ($donRow['status_donasi'] === 'ditolak') {
                $stVerif = 'ditolak';
            }

            $statusText = 'Menunggu Verifikasi';
            $statusType = 'warning';
            $tglDonasi = date('d F Y', strtotime($donRow['tanggal_donasi']));
            $tglBayar = !empty($donRow['tanggal_pembayaran']) ? date('d M Y', strtotime($donRow['tanggal_pembayaran'])) : date('d M Y', strtotime($donRow['tanggal_donasi']));

            $steps = [
                ['name' => 'Donasi Dibuat', 'date' => date('d M Y', strtotime($donRow['tanggal_donasi'])), 'status' => 'done'],
                ['name' => 'Bukti Pembayaran Diunggah', 'date' => $tglBayar, 'status' => 'done'],
                ['name' => 'Verifikasi Admin', 'date' => ($stVerif === 'diterima' ? 'Terverifikasi' : ($stVerif === 'ditolak' ? 'Ditolak' : 'Proses')), 'status' => ($stVerif === 'diterima' ? 'done' : 'active')],
                ['name' => 'Donasi Disalurkan', 'date' => ($stVerif === 'diterima' ? 'Siap Disalurkan' : '-'), 'status' => ($stVerif === 'diterima' ? 'done' : 'pending')]
            ];

            if ($stVerif === 'diterima') {
                $statusText = 'Terverifikasi & Selesai';
                $statusType = 'success';
            } elseif ($stVerif === 'ditolak') {
                $statusText = 'Ditolak';
                $statusType = 'danger';
            }

            return [
                'type' => 'donasi',
                'kode' => $donRow['kode_tracking'],
                'title' => 'Status Donasi Anda',
                'program' => $donRow['nama_program'],
                'pengusul' => $donRow['nama_donatur'] ?: 'Donatur SIBERASA',
                'nominal' => function_exists('formatRupiah') ? formatRupiah($donRow['nominal']) : ('Rp ' . number_format($donRow['nominal'], 0, ',', '.')),
                'tanggal' => $tglDonasi,
                'statusText' => $statusText,
                'statusType' => $statusType,
                'infoMessage' => ($stVerif === 'diterima') 
                    ? 'Donasi Anda telah berhasil diverifikasi oleh admin dan berhasil tercatat. Terima kasih atas kepedulian Anda!' 
                    : (($stVerif === 'ditolak') 
                        ? 'Bukti donasi tidak dapat diverifikasi oleh admin. Silakan periksa kembali atau unggah ulang bukti yang sesuai.' 
                        : 'Bukti pembayaran donasi Anda telah diterima dan sedang dalam tahap verifikasi oleh admin keuangan kami.'),
                'stepperTitle' => 'Proses Donasi',
                'steps' => $steps
            ];
        }
    } catch (Exception $e) {}

    // 2. Jika tidak ditemukan di donasi, cek tabel program_donasi (pengajuan program)
    try {
        $progId = null;
        if (is_numeric($kode)) {
            $progId = (int)$kode;
        } elseif (preg_match('/(?:PGN[ -]?)(\d+)/i', $kode, $m)) {
            $progId = (int)$m[1];
        }

        $progRow = null;
        if ($progId !== null) {
            $stmtProg = $pdo->prepare("
                SELECT p.*, u.nama AS nama_pengusul, u.nama_organisasi
                FROM program_donasi p
                LEFT JOIN users u ON p.id_pengusul = u.id_user
                WHERE p.id_program = ?
                LIMIT 1
            ");
            $stmtProg->execute([$progId]);
            $progRow = $stmtProg->fetch(PDO::FETCH_ASSOC);
        }

        if (!$progRow && strlen($kode) >= 4) {
            $stmtProg2 = $pdo->prepare("
                SELECT p.*, u.nama AS nama_pengusul, u.nama_organisasi
                FROM program_donasi p
                LEFT JOIN users u ON p.id_pengusul = u.id_user
                WHERE p.judul LIKE ?
                LIMIT 1
            ");
            $stmtProg2->execute(['%' . $kode . '%']);
            $progRow = $stmtProg2->fetch(PDO::FETCH_ASSOC);
        }

        if ($progRow) {
            $stProg = strtolower($progRow['status_program']);
            $kodeFormatted = 'PGN-' . str_pad($progRow['id_program'], 5, '0', STR_PAD_LEFT);
            $namaOrg = $progRow['nama_organisasi'] ?: ($progRow['nama_pengusul'] ?: 'Pengusul Terdaftar');
            $tglAjukan = date('d F Y', strtotime($progRow['dibuat_pada']));

            $step1 = ['name' => 'Pengajuan Dikirim', 'date' => date('d M Y', strtotime($progRow['dibuat_pada'])), 'status' => 'done'];
            $step2 = ['name' => 'Sedang Diverifikasi', 'date' => '-', 'status' => 'pending'];
            $step3 = ['name' => 'Disetujui', 'date' => '-', 'status' => 'pending'];
            $step4 = ['name' => 'Program Dipublikasikan', 'date' => '-', 'status' => 'pending'];

            $statusText = 'Menunggu Verifikasi';
            $statusType = 'warning';
            $infoMsg = 'Pengajuan Anda sedang dalam proses verifikasi oleh admin.<br>Silakan cek kembali secara berkala atau hubungi kontak kami jika ada pertanyaan.';

            if ($stProg === 'menunggu') {
                $step2['status'] = 'active';
            } elseif ($stProg === 'disetujui' || $stProg === 'berlangsung') {
                $statusText = 'Disetujui';
                $statusType = 'success';
                $tglVerif = !empty($progRow['diverifikasi_pada']) ? date('d M Y', strtotime($progRow['diverifikasi_pada'])) : date('d M Y');
                $step2 = ['name' => 'Sedang Diverifikasi', 'date' => $tglVerif, 'status' => 'done'];
                $step3 = ['name' => 'Disetujui', 'date' => $tglVerif, 'status' => 'done'];
                $step4 = ['name' => 'Program Dipublikasikan', 'date' => ($stProg === 'berlangsung' ? 'Aktif' : 'Persiapan'), 'status' => ($stProg === 'berlangsung' ? 'done' : 'active')];
                $infoMsg = 'Pengajuan program donasi Anda telah disetujui oleh admin! Program telah aktif dan dapat menerima donasi.';
            } elseif ($stProg === 'selesai') {
                $statusText = 'Selesai';
                $statusType = 'success';
                $step2['status'] = 'done';
                $step3['status'] = 'done';
                $step4 = ['name' => 'Program Selesai', 'date' => 'Selesai', 'status' => 'done'];
                $infoMsg = 'Program penggalangan dana telah selesai dilaksanakan. Terima kasih atas partisipasi Anda.';
            } elseif ($stProg === 'ditolak') {
                $statusText = 'Ditolak';
                $statusType = 'danger';
                $step2 = ['name' => 'Diverifikasi Admin', 'date' => 'Ditolak', 'status' => 'done'];
                $step3 = ['name' => 'Ditolak', 'date' => 'Tidak Disetujui', 'status' => 'active'];
                $step4 = ['name' => 'Program Dibatalkan', 'date' => '-', 'status' => 'pending'];
                $catatan = !empty($progRow['catatan_admin']) ? '<br><strong>Catatan Admin:</strong> ' . htmlspecialchars($progRow['catatan_admin']) : '';
                $infoMsg = 'Pengajuan program belum dapat disetujui.' . $catatan;
            }

            return [
                'type' => 'pengajuan',
                'kode' => $kodeFormatted,
                'title' => 'Status Pengajuan Donasi',
                'program' => $progRow['judul'],
                'pengusul' => $namaOrg,
                'nominal' => function_exists('formatRupiah') ? formatRupiah($progRow['target_dana']) : ('Rp ' . number_format($progRow['target_dana'], 0, ',', '.')),
                'tanggal' => $tglAjukan,
                'statusText' => $statusText,
                'statusType' => $statusType,
                'infoMessage' => $infoMsg,
                'stepperTitle' => 'Proses Pengajuan',
                'steps' => [$step1, $step2, $step3, $step4]
            ];
        }
    } catch (Exception $e) {}

    return null;
}

echo "=== TEST DON2026102716575899 ===\n";
print_r(cariStatusByKode('DON2026102716575899', $pdo));

echo "=== TEST DON2026102807365698 ===\n";
print_r(cariStatusByKode('DON2026102807365698', $pdo));

echo "=== TEST PGN-10 ===\n";
print_r(cariStatusByKode('PGN-10', $pdo));

echo "=== TEST PGN-00005 ===\n";
print_r(cariStatusByKode('PGN-00005', $pdo));
