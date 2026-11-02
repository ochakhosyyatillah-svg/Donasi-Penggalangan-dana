<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../assets/config/database.php';

// Handle AJAX / Form Submit Pengunggahan Bukti Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'upload_bukti' || isset($_FILES['bukti']))) {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => 'Silakan pilih berkas bukti pembayaran terlebih dahulu.'
            ]);
            exit;
        }

        $file = $_FILES['bukti'];
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $maxSize) {
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran file terlalu besar! Maksimal 5 MB.'
            ]);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($ext, $allowedExt)) {
            echo json_encode([
                'success' => false,
                'message' => 'Format file tidak didukung! Gunakan format JPG, PNG, atau PDF.'
            ]);
            exit;
        }

        $uploadDir = __DIR__ . '/../assets/img/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'bukti_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan file bukti pembayaran ke server.'
            ]);
            exit;
        }

        // Ambil data donasi
        $postNominal   = isset($_POST['nominal']) ? (int)$_POST['nominal'] : (int)($_SESSION['pembayaran_data']['nominal'] ?? 100000);
        $postProgramId = isset($_POST['id_program']) ? (int)$_POST['id_program'] : (int)($_SESSION['pembayaran_data']['id'] ?? 10);
        $postMetode    = isset($_POST['metode']) ? trim($_POST['metode']) : ($_SESSION['pembayaran_data']['metode'] ?? 'QRIS');
        $catatanDonatur = trim($_POST['catatan'] ?? '');

        // Validasi program ID di database
        $stmtCek = $pdo->prepare("SELECT id_program FROM program_donasi WHERE id_program = ?");
        $stmtCek->execute([$postProgramId]);
        if (!$stmtCek->fetchColumn()) {
            $firstId = $pdo->query("SELECT id_program FROM program_donasi ORDER BY id_program ASC LIMIT 1")->fetchColumn();
            if ($firstId) {
                $postProgramId = (int)$firstId;
            }
        }

        // Data Donatur
        $namaDonatur = trim($_SESSION['pembayaran_data']['nama'] ?? ($_SESSION['nama'] ?? 'Donatur SIBERASA'));
        if (empty($namaDonatur)) $namaDonatur = 'Donatur SIBERASA';
        $emailDonatur = trim($_SESSION['pembayaran_data']['email'] ?? ($_SESSION['email'] ?? ''));
        $teleponDonatur = trim($_SESSION['pembayaran_data']['telepon'] ?? '');

        if (empty($catatanDonatur) && !empty($_SESSION['pembayaran_data']['pesan'])) {
            $catatanDonatur = $_SESSION['pembayaran_data']['pesan'];
        }

        // Normalisasi metode pembayaran ke enum DB: 'bank', 'qris', 'e-wallet'
        $metodeLower = strtolower($postMetode);
        if (strpos($metodeLower, 'bank') !== false || strpos($metodeLower, 'transfer') !== false) {
            $metodeDb = 'bank';
        } elseif (strpos($metodeLower, 'wallet') !== false || strpos($metodeLower, 'ewallet') !== false) {
            $metodeDb = 'e-wallet';
        } else {
            $metodeDb = 'qris';
        }

        // Cari akun tujuan pembayaran yang sesuai
        $stmtAkun = $pdo->prepare("SELECT id_akun FROM akun_tujuan_pembayaran WHERE id_program = ? AND metode = ? LIMIT 1");
        $stmtAkun->execute([$postProgramId, $metodeDb]);
        $idAkun = $stmtAkun->fetchColumn();

        if (!$idAkun) {
            $stmtAkunProg = $pdo->prepare("SELECT id_akun FROM akun_tujuan_pembayaran WHERE id_program = ? LIMIT 1");
            $stmtAkunProg->execute([$postProgramId]);
            $idAkun = $stmtAkunProg->fetchColumn();
        }

        if (!$idAkun) {
            $idAkun = $pdo->query("SELECT id_akun FROM akun_tujuan_pembayaran LIMIT 1")->fetchColumn();
        }

        if (!$idAkun) {
            $insAkun = $pdo->prepare("INSERT INTO akun_tujuan_pembayaran (id_program, metode, nama_penyedia, nama_pemilik, nomor_tujuan, status_aktif) VALUES (?, ?, 'SIBERASA Rekening', 'Yayasan SIBERASA', '1234567890', 1)");
            $insAkun->execute([$postProgramId, $metodeDb]);
            $idAkun = (int)$pdo->lastInsertId();
        }

        // Generate Kode Tracking Unik
        $kodeTracking = 'DON' . date('YmdHis') . rand(10, 99);

        $pdo->beginTransaction();

        // 1. Simpan ke tabel donasi
        $stmtDonasi = $pdo->prepare("
            INSERT INTO donasi (kode_tracking, id_program, nama_donatur, email_donatur, no_hp_donatur, nominal, pesan_donatur, status_donasi, tanggal_donasi)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'menunggu_verifikasi', NOW())
        ");
        $stmtDonasi->execute([
            $kodeTracking,
            $postProgramId,
            $namaDonatur,
            $emailDonatur,
            $teleponDonatur,
            $postNominal,
            $catatanDonatur
        ]);
        $idDonasi = (int)$pdo->lastInsertId();

        // 2. Simpan ke tabel pembayaran
        $stmtPembayaran = $pdo->prepare("
            INSERT INTO pembayaran (id_donasi, id_akun, metode_pembayaran, tanggal_pembayaran, bukti_pembayaran, status_verifikasi, catatan_admin)
            VALUES (?, ?, ?, NOW(), ?, 'menunggu', NULL)
        ");
        $stmtPembayaran->execute([
            $idDonasi,
            $idAkun,
            $metodeDb,
            $newFileName
        ]);

        $pdo->commit();

        // Bersihkan session data pembayaran
        unset($_SESSION['pembayaran_data']);

        echo json_encode([
            'success' => true,
            'kode'    => $kodeTracking,
            'message' => 'Bukti pembayaran berhasil dikirim dan tersimpan ke sistem!'
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Ambil data dari GET (jika ada) atau dari SESSION pembayaran
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $nominal = isset($_GET['nominal']) ? (int)$_GET['nominal'] : 100000;
    $rawMetode = isset($_GET['metode']) ? trim($_GET['metode']) : 'QRIS';

    if (stripos($rawMetode, 'transfer') !== false || stripos($rawMetode, 'bank') !== false) {
        $metode = 'Transfer Bank';
    } elseif (stripos($rawMetode, 'ewallet') !== false || stripos($rawMetode, 'wallet') !== false) {
        $metode = 'E-Wallet';
    } else {
        $metode = 'QRIS';
    }

    if (!isset($_SESSION['pembayaran_data'])) {
        $_SESSION['pembayaran_data'] = [];
    }
    $_SESSION['pembayaran_data']['id'] = $id;
    $_SESSION['pembayaran_data']['nominal'] = $nominal;
    $_SESSION['pembayaran_data']['metode'] = $metode;
} elseif (isset($_SESSION['pembayaran_data'])) {
    $data = $_SESSION['pembayaran_data'];
    $id = (int)($data['id'] ?? 10);
    $nominal = (int)($data['nominal'] ?? 100000);
    $rawMetode = $data['metode'] ?? 'QRIS';

    if (stripos($rawMetode, 'transfer') !== false || stripos($rawMetode, 'bank') !== false) {
        $metode = 'Transfer Bank';
    } elseif (stripos($rawMetode, 'ewallet') !== false || stripos($rawMetode, 'wallet') !== false) {
        $metode = 'E-Wallet';
    } else {
        $metode = 'QRIS';
    }
} else {
    // Default fallback
    $id      = 10;
    $nominal = 100000;
    $metode  = 'QRIS';
}

// Cari program yang cocok di database
$prog = null;
foreach ($programs as $p) {
    if ($p['id'] === $id) {
        $prog = $p;
        break;
    }
}
if (!$prog && !empty($programs)) {
    $prog = $programs[0];
    $id = (int)$prog['id'];
}
if (!$prog) {
    $prog = [
        'id' => $id,
        'judul' => 'Program Donasi SIBERASA',
        'kategori' => 'Kemanusiaan',
        'deskripsi' => 'Program bantuan dan donasi kebaikan sesama.',
        'gambar' => '/Donasi/assets/img/program1.jpg',
        'target' => 50000000,
        'terkumpul' => 25000000,
        'persen' => 50
    ];
}


$page_title   = 'Upload Bukti Pembayaran - SIBERASA';
$page_desc    = 'Unggah bukti pembayaran donasi Anda untuk verifikasi.';
$current_page = 'program';

require_once '../includes/header.php';
?>

<!-- Google Fonts Caveat for handwritten text -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&display=swap" rel="stylesheet">

<!-- ═══════════════════════════════════════════════════════════════════
     HALAMAN UPLOAD BUKTI PEMBAYARAN - PERSIS SESUAI DESAIN REFERENSI
════════════════════════════════════════════════════════════════════ -->
<div class="ub-page-wrapper">
    <div class="container ub-main-container">

        <!-- ── TOMBOL KEMBALI ── -->
        <div class="ub-back-row">
            <a href="pembayaran.php" class="ub-btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Detail Pembayaran
            </a>
        </div>

        <div class="ub-grid-layout">
            
            <!-- ══════════════════════════════════════════════════════
                 KOLOM KIRI: FORM & DETAIL DONASI
                 ══════════════════════════════════════════════════════ -->
            <div class="ub-left-col">
                
                <!-- 1. Header Judul Halaman -->
                <div class="ub-page-header">
                    <div class="ub-title-icon-box">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="ub-title-text">
                        <h1>Upload Bukti Pembayaran</h1>
                        <p>Setelah melakukan pembayaran, silakan unggah bukti pembayaran agar donasi Anda dapat segera diverifikasi oleh tim kami.</p>
                    </div>
                </div>

                <!-- 2. Alert Banner Info -->
                <div class="ub-alert-banner">
                    <div class="ub-alert-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="ub-alert-text">
                        <strong>Pastikan Bukti Pembayaran Jelas</strong>
                        <p>Gambar harus terlihat dengan jelas dan tidak terpotong, agar proses verifikasi berjalan lebih cepat.</p>
                    </div>
                </div>

                <!-- 3. Kartu Informasi Donasi -->
                <div class="ub-card ub-info-donasi-card">
                    <h3 class="ub-card-title">Informasi Donasi</h3>

                    <div class="ub-program-brief">
                        <div class="ub-program-thumb">
                            <img src="<?= $prog['gambar'] ?>" alt="<?= htmlspecialchars($prog['judul']) ?>">
                        </div>
                        <div class="ub-program-details">
                            <div class="ub-program-title-line">
                                <h4><?= htmlspecialchars($prog['judul']) ?></h4>
                                <span class="ub-badge-kat">Program <?= htmlspecialchars($prog['kategori']) ?></span>
                            </div>
                            <p class="ub-program-desc">
                                <?= htmlspecialchars(mb_strimwidth($prog['deskripsi'], 0, 95, '...')) ?>
                            </p>
                        </div>
                    </div>

                    <!-- 3 Data Metrics Strip -->
                    <div class="ub-metrics-strip">
                        <div class="ub-metric-cell">
                            <div class="ub-cell-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="ub-cell-info">
                                <span class="label">Nominal Donasi</span>
                                <strong class="val"><?= formatRupiah($nominal) ?></strong>
                            </div>
                        </div>

                        <div class="ub-metric-cell">
                            <div class="ub-cell-icon"><i class="far fa-calendar-alt"></i></div>
                            <div class="ub-cell-info">
                                <span class="label">Tanggal Donasi</span>
                                <strong class="val"><?= date('d M Y') ?></strong>
                            </div>
                        </div>

                        <div class="ub-metric-cell">
                            <div class="ub-cell-icon"><i class="fas fa-university"></i></div>
                            <div class="ub-cell-info">
                                <span class="label">Metode Pembayaran</span>
                                <strong class="val"><?= $metode ?></strong>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- 4. Form Unggah Bukti Pembayaran -->
                <div class="ub-card ub-upload-card">
                    <h3 class="ub-card-title">Unggah Bukti Pembayaran</h3>

                    <form id="formUploadBukti" enctype="multipart/form-data" onsubmit="handleFormSubmit(event)">
                        <input type="hidden" name="action" value="upload_bukti">
                        <input type="hidden" name="id_program" value="<?= (int)$prog['id'] ?>">
                        <input type="hidden" name="nominal" value="<?= (int)$nominal ?>">
                        <input type="hidden" name="metode" value="<?= htmlspecialchars($metode) ?>">
                        
                        <!-- Drag & Drop Upload Zone -->
                        <div class="ub-dropzone" id="dropZone" onclick="triggerFileInput()">
                            <input type="file" id="fileInput" name="bukti" accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf" style="display:none;" onchange="handleFileChange(this)">
                            
                            <div class="ub-drop-icon">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <p class="ub-drop-text">
                                <span class="ub-drop-highlight">Klik untuk mengunggah</span> atau seret file ke sini
                            </p>
                            <small class="ub-drop-hint">Format yang didukung: JPG, PNG, PDF (maks. 5MB)</small>
                        </div>

                        <!-- File Preview Card Item -->
                        <div class="ub-file-preview-card" id="filePreviewCard" style="display:none;">
                            <div class="ub-preview-thumb">
                                <i class="fas fa-file-invoice" id="previewIcon"></i>
                                <img src="" alt="Bukti Transfer" id="previewImage" style="display:none;">
                            </div>
                            <div class="ub-preview-details">
                                <strong class="ub-file-name" id="fileName">Belum ada file dipilih</strong>
                                <span class="ub-file-size" id="fileSize">0 MB</span>
                            </div>
                            <button type="button" class="ub-btn-remove-file" onclick="removeSelectedFile()" aria-label="Hapus file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Catatan Opsional -->
                        <div class="ub-form-group">
                            <label for="inputCatatan" class="ub-label">Catatan (Opsional)</label>
                            <textarea id="inputCatatan" name="catatan" class="ub-textarea" rows="3" maxlength="200" placeholder="Contoh: Terima kasih sudah membantu..." oninput="updateCharCounter(this)"></textarea>
                            <div class="ub-counter-wrap">
                                <span id="charCounter">0/200</span>
                            </div>
                        </div>

                        <!-- Pesan Alert Error -->
                        <div id="uploadAlertError" style="display:none; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:10px; padding:12px 16px; font-size:0.86rem; margin-bottom:16px;"></div>

                        <!-- Pesan Alert Sukses -->
                        <div id="uploadAlertSuccess" style="display:none; background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; border-radius:10px; padding:14px 18px; font-size:0.88rem; margin-bottom:16px; line-height:1.5;"></div>

                        <!-- Submit Button -->
                        <button type="submit" id="btnSubmitBukti" class="ub-btn-submit">
                            <i class="fas fa-cloud-arrow-up"></i> Kirim Bukti Pembayaran
                        </button>
                    </form>
                </div>

            </div>


            <!-- ══════════════════════════════════════════════════════
                 KOLOM KANAN: BANNER GRAFIS & PANDUAN
                 ══════════════════════════════════════════════════════ -->
            <div class="ub-right-col">
                
                <!-- 1. Top Card: Illustration & Script Message -->
                <div class="ub-side-banner">
                    <div class="ub-side-banner-inner">
                        <div class="ub-side-illu">
                            <img src="/Donasi/assets/img/hands_heart.jpg" alt="Donasi Terverifikasi" onerror="this.src='/Donasi/assets/img/phone_verified.jpg'">
                        </div>
                        <div class="ub-side-quote">
                            <span>Donasi Anda</span>
                            <span>sangat berarti</span>
                            <span>untuk mereka &hearts;</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Panduan Upload Bukti Pembayaran -->
                <div class="ub-side-card">
                    <h3 class="ub-side-title">Panduan Upload Bukti Pembayaran</h3>

                    <div class="ub-guide-list">
                        <div class="ub-guide-step">
                            <div class="ub-step-number">1</div>
                            <div class="ub-step-desc">
                                Lakukan pembayaran sesuai nominal yang tertera pada program donasi.
                            </div>
                        </div>

                        <div class="ub-guide-step">
                            <div class="ub-step-number">2</div>
                            <div class="ub-step-desc">
                                Ambil screenshot atau foto bukti pembayaran (misal: transfer, struk, atau riwayat transaksi).
                            </div>
                        </div>

                        <div class="ub-guide-step">
                            <div class="ub-step-number">3</div>
                            <div class="ub-step-desc">
                                Unggah file pada form yang tersedia.
                            </div>
                        </div>

                        <div class="ub-guide-step">
                            <div class="ub-step-number">4</div>
                            <div class="ub-step-desc">
                                Pastikan informasi pada bukti pembayaran jelas (tanggal, nominal, dan nama pengirim).
                            </div>
                        </div>

                        <div class="ub-guide-step">
                            <div class="ub-step-number">5</div>
                            <div class="ub-step-desc">
                                Klik tombol "Kirim Bukti Pembayaran" untuk mengirimkan bukti Anda.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Data Anda Aman -->
                <div class="ub-side-card ub-security-card">
                    <div class="ub-sec-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="ub-sec-text">
                        <h4>Data Anda Aman</h4>
                        <p>Kami menjaga keamanan data dan informasi pembayaran Anda dengan sistem yang terenkripsi.</p>
                    </div>
                </div>

                <!-- 4. Butuh Bantuan -->
                <div class="ub-side-card ub-help-card">
                    <div class="ub-help-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="ub-help-text">
                        <h4>Butuh Bantuan?</h4>
                        <p>Jika mengalami kendala, silakan hubungi admin kami melalui:</p>
                        <a href="https://wa.me/6281234567890" target="_blank" class="ub-btn-hubungi-admin">
                            <i class="fab fa-whatsapp"></i> Hubungi Admin &nbsp;&rarr;
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════
     MODAL SUKSES PENGIRIMAN BUKTI
════════════════════════════════════════════════════════════════════ -->
<div class="ub-modal-overlay" id="modalSuksesUpload" onclick="closeModalOnBg(event)">
    <div class="ub-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="ub-modal-close-btn" onclick="tutupModalSukses()" title="Tutup Modal">&times;</button>
        <div class="ub-modal-icon-wrap">
            <i class="fas fa-check"></i>
        </div>
        <h3>Bukti Pembayaran Berhasil Dikirim!</h3>
        <p>Terima kasih atas kepedulian Anda! Bukti transfer telah tersimpan ke sistem dan saat ini sedang dalam proses verifikasi tim admin (maks. <strong>1x24 jam kerja</strong>).</p>

        <div class="ub-ticket-box">
            <div class="ub-ticket-header">
                <span class="label">Kode Tracking Donasi</span>
                <span class="ub-ticket-status"><i class="fas fa-clock"></i> Menunggu Verifikasi</span>
            </div>
            <div class="ub-ticket-code-row">
                <strong class="code" id="modalTrxCode">DONASI-2026-8921</strong>
                <button type="button" class="btn-copy-ticket" onclick="salinKodeTracking()">
                    <i class="far fa-copy"></i> Salin
                </button>
            </div>
            <small style="color:#64748b; font-size:0.75rem; display:block;">Simpan kode di atas untuk memantau status donasi Anda kapan saja.</small>
        </div>

        <div class="ub-modal-actions" style="display:flex; flex-direction:column; gap:10px;">
            <button type="button" class="ub-btn-submit" onclick="menujuCekStatusDonasi()" style="width:100%; justify-content:center; padding:13px 20px; font-size:0.92rem;">
                <i class="fas fa-search"></i> Cek Status Donasi Sekarang &rarr;
            </button>
            <button type="button" class="ub-btn-back" onclick="window.location.href='index.php'" style="width:100%; justify-content:center; padding:12px; border:1px solid #cbd5e1; border-radius:50px; background:#f8fafc; color:#334155; font-weight:600; cursor:pointer;">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </button>
        </div>
    </div>
</div>

<!-- FLOATING TOAST NOTIFIKASI -->
<div class="ub-toast" id="ubToast">
    <i class="fas fa-check-circle" style="color: #38bdf8; font-size:1.15rem;"></i>
    <span id="ubToastMsg">Bukti pembayaran berhasil diunggah!</span>
</div>


<!-- ═══════════════════════════════════════════════════════════════════
     CSS STYLING KHUSUS UPLOAD BUKTI PEMBAYARAN
════════════════════════════════════════════════════════════════════ -->
<style>
/* Base Page Reset */
.ub-page-wrapper {
    background-color: #f0f6fc;
    min-height: calc(100vh - 70px);
    font-family: 'Poppins', sans-serif;
    padding: 30px 0 60px;
    color: #1a202c;
}

.ub-main-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Tombol Kembali */
.ub-back-row {
    margin-bottom: 24px;
}
.ub-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #4a5568;
    text-decoration: none;
    transition: color 0.2s, transform 0.2s;
}
.ub-btn-back:hover {
    color: #1a73e8;
    transform: translateX(-3px);
}

/* 2 Kolom Layout */
.ub-grid-layout {
    display: grid;
    grid-template-columns: 1.45fr 1fr;
    gap: 28px;
    align-items: start;
}

/* Header Halaman (Kiri) */
.ub-page-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
}
.ub-title-icon-box {
    width: 48px;
    height: 48px;
    background-color: #1a73e8;
    color: #ffffff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(26,115,232,0.28);
}
.ub-title-text h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #15325e;
    line-height: 1.25;
    margin-bottom: 4px;
}
.ub-title-text p {
    font-size: 0.84rem;
    color: #64748b;
    line-height: 1.5;
    margin: 0;
}

/* Alert Banner */
.ub-alert-banner {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background-color: #eaf3fe;
    border: 1px solid #c9e0fc;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 22px;
}
.ub-alert-icon {
    color: #1a73e8;
    font-size: 1.2rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.ub-alert-text strong {
    display: block;
    font-size: 0.88rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 2px;
}
.ub-alert-text p {
    font-size: 0.8rem;
    color: #4a5568;
    margin: 0;
    line-height: 1.45;
}

/* Card Style Umum */
.ub-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    margin-bottom: 22px;
}
.ub-card-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 18px;
}

/* Informasi Donasi Card */
.ub-program-brief {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 20px;
}
.ub-program-thumb {
    width: 90px;
    height: 68px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background-color: #e2e8f0;
}
.ub-program-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ub-program-details {
    flex: 1;
}
.ub-program-title-line {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}
.ub-program-title-line h4 {
    font-size: 0.98rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}
.ub-badge-kat {
    background-color: #fee2e2;
    color: #ef4444;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 50px;
}
.ub-program-desc {
    font-size: 0.8rem;
    color: #718096;
    margin: 0;
    line-height: 1.45;
}

/* 3 Metrics Strip */
.ub-metrics-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    background-color: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    padding: 14px 18px;
}
.ub-metric-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ub-cell-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #eef5fc;
    color: #1a73e8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.ub-cell-info .label {
    display: block;
    font-size: 0.72rem;
    color: #718096;
    margin-bottom: 2px;
}
.ub-cell-info .val {
    display: block;
    font-size: 0.88rem;
    font-weight: 700;
    color: #15325e;
}

/* Upload Card & Dropzone */
.ub-dropzone {
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-bottom: 16px;
}
.ub-dropzone:hover, .ub-dropzone.dragover {
    border-color: #1a73e8;
    background-color: #f7fbff;
}
.ub-drop-icon {
    font-size: 2.2rem;
    color: #1a73e8;
    margin-bottom: 10px;
}
.ub-drop-text {
    font-size: 0.88rem;
    color: #4a5568;
    margin-bottom: 4px;
}
.ub-drop-highlight {
    color: #1a73e8;
    font-weight: 700;
}
.ub-drop-hint {
    font-size: 0.76rem;
    color: #94a3b8;
}

/* File Preview Item Card */
.ub-file-preview-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.02);
}
.ub-preview-thumb {
    width: 42px;
    height: 42px;
    border-radius: 6px;
    background-color: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #64748b;
    overflow: hidden;
    flex-shrink: 0;
}
.ub-preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ub-preview-details {
    flex: 1;
}
.ub-file-name {
    display: block;
    font-size: 0.86rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}
.ub-file-size {
    display: block;
    font-size: 0.74rem;
    color: #94a3b8;
}
.ub-btn-remove-file {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1rem;
    cursor: pointer;
    padding: 6px;
    border-radius: 4px;
    transition: color 0.2s, background-color 0.2s;
}
.ub-btn-remove-file:hover {
    color: #ef4444;
    background-color: #fee2e2;
}

/* Catatan Input */
.ub-form-group {
    margin-bottom: 22px;
}
.ub-label {
    display: block;
    font-size: 0.86rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}
.ub-textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
    font-family: inherit;
    font-size: 0.86rem;
    color: #1e293b;
    outline: none;
    resize: none;
    background-color: #ffffff;
    transition: border 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.ub-textarea:focus {
    border-color: #1a73e8;
    box-shadow: 0 0 0 3px rgba(26,115,232,0.12);
}
.ub-counter-wrap {
    text-align: right;
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 4px;
}

/* Submit Button */
.ub-btn-submit {
    width: 100%;
    background-color: #1a73e8;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 14px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(26,115,232,0.28);
    transition: all 0.25s ease;
}
.ub-btn-submit:hover {
    background-color: #1557b0;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(26,115,232,0.36);
}

/* ══════════════════════════════════════════════════════
   KOLOM KANAN: SIDEBAR CARDS
   ══════════════════════════════════════════════════════ */
.ub-side-banner {
    background: linear-gradient(135deg, #e8f3fe 0%, #d8ecfe 100%);
    border-radius: 16px;
    padding: 22px 24px;
    margin-bottom: 22px;
    border: 1px solid #cce3fd;
}
.ub-side-banner-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.ub-side-illu {
    width: 105px;
    height: 105px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(26, 115, 232, 0.12);
}
.ub-side-illu img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 14px;
}
.ub-side-quote {
    display: flex;
    flex-direction: column;
    font-family: 'Caveat', cursive, sans-serif;
    font-size: 1.7rem;
    font-weight: 700;
    color: #1a73e8;
    line-height: 1.15;
    text-align: right;
}

/* Side Card Umum */
.ub-side-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    margin-bottom: 18px;
}
.ub-side-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 18px;
}

/* Panduan List */
.ub-guide-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.ub-guide-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.ub-step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #1a73e8;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.ub-step-desc {
    font-size: 0.8rem;
    color: #4a5568;
    line-height: 1.5;
}

/* Security & Help Card */
.ub-security-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.ub-sec-icon {
    color: #1a73e8;
    font-size: 1.35rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.ub-sec-text h4 {
    font-size: 0.92rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 3px;
}
.ub-sec-text p {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.ub-help-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.ub-help-icon {
    color: #1a73e8;
    font-size: 1.35rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.ub-help-text h4 {
    font-size: 0.92rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 3px;
}
.ub-help-text p {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0 0 12px 0;
    line-height: 1.5;
}
.ub-btn-hubungi-admin {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border-radius: 50px;
    border: 1.5px solid #b9d8fb;
    color: #1a73e8;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    background-color: #f7fbff;
}
.ub-btn-hubungi-admin:hover {
    background-color: #1a73e8;
    color: #ffffff;
    border-color: #1a73e8;
}

/* ═══════════════════════════════════════════════════════════════════
   MODAL SUKSES POPUP & OVERLAY (BULLETPROOF & CENTERED)
════════════════════════════════════════════════════════════════════ */
.ub-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.72) !important;
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    z-index: 999999 !important;
    display: none;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease-out;
}

.ub-modal-overlay.active,
.ub-modal-overlay.show {
    display: flex !important;
    opacity: 1 !important;
    pointer-events: all !important;
}

.ub-modal-box {
    background: #ffffff;
    border-radius: 20px;
    max-width: 480px;
    width: 100%;
    padding: 36px 30px 30px;
    text-align: center;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.28);
    position: relative;
    transform: scale(0.92) translateY(15px);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}

.ub-modal-overlay.active .ub-modal-box,
.ub-modal-overlay.show .ub-modal-box {
    transform: scale(1) translateY(0);
}

.ub-modal-close-btn {
    position: absolute;
    top: 16px;
    right: 18px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.ub-modal-close-btn:hover {
    background: #fee2e2;
    color: #ef4444;
}

.ub-modal-icon-wrap {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background-color: #dcfce7;
    color: #16a34a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.3rem;
    margin-bottom: 18px;
    box-shadow: 0 0 0 8px #f0fdf4;
    animation: ubPulse 2s infinite;
}

@keyframes ubPulse {
    0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
    70% { box-shadow: 0 0 0 14px rgba(34, 197, 94, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.ub-modal-box h3 {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
    line-height: 1.3;
}

.ub-modal-box p {
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 22px;
}

.ub-ticket-box {
    background-color: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    text-align: left;
}

.ub-ticket-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.ub-ticket-header .label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
}

.ub-ticket-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.74rem;
    color: #b45309;
    background: #fef3c7;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.ub-ticket-code-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 8px;
}

.ub-ticket-code-row .code {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0284c7;
    letter-spacing: 1px;
    font-family: 'Consolas', 'Courier New', monospace;
}

.btn-copy-ticket {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #0284c7;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-copy-ticket:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

/* Toast Floating */
.ub-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    z-index: 9999999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: all 0.25s ease;
}

.ub-toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Responsif */
@media (max-width: 992px) {
    .ub-grid-layout {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .ub-metrics-strip {
        grid-template-columns: 1fr;
    }
    .ub-program-brief {
        flex-direction: column;
        align-items: flex-start;
    }
    .ub-program-thumb {
        width: 100%;
        height: 120px;
    }
}
</style>


<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT LOGIC INTERAKTIF
════════════════════════════════════════════════════════════════════ -->
<script>
// Trigger file dialog
function triggerFileInput() {
    document.getElementById('fileInput').click();
}

// Handle File Select
function handleFileChange(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Cek ukuran maks 5MB
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal ukuran file adalah 5 MB.');
            input.value = '';
            return;
        }

        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = (file.size / (1024 * 1024)).toFixed(1) + ' MB';

        // Tampilkan thumbnail jika image
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('previewImage');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('previewIcon').style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('previewImage').style.display = 'none';
            document.getElementById('previewIcon').style.display = 'block';
        }

        document.getElementById('filePreviewCard').style.display = 'flex';
    }
}

// Remove File
function removeSelectedFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('fileName').textContent = 'Belum ada file dipilih';
    document.getElementById('fileSize').textContent = '0 MB';
    document.getElementById('previewImage').style.display = 'none';
    document.getElementById('previewIcon').style.display = 'block';
    document.getElementById('filePreviewCard').style.display = 'none';
}

// Drag & Drop Listeners
const dropZone = document.getElementById('dropZone');
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    }, false);
});
['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    }, false);
});
dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files && files[0]) {
        document.getElementById('fileInput').files = files;
        handleFileChange(document.getElementById('fileInput'));
    }
}, false);

// Counter text
function updateCharCounter(textarea) {
    document.getElementById('charCounter').textContent = textarea.value.length + '/200';
}

// Submit Handler
function handleFormSubmit(e) {
    e.preventDefault();

    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files && fileInput.files[0];
    const alertBox = document.getElementById('uploadAlertError');
    const successBox = document.getElementById('uploadAlertSuccess');
    if (alertBox) alertBox.style.display = 'none';
    if (successBox) successBox.style.display = 'none';

    if (!file) {
        if (alertBox) {
            alertBox.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Silakan pilih atau unggah berkas bukti pembayaran terlebih dahulu.';
            alertBox.style.display = 'block';
        } else {
            alert('Silakan pilih atau unggah berkas bukti pembayaran terlebih dahulu.');
        }
        return;
    }

    const btnSubmit = document.getElementById('btnSubmitBukti');
    const originalBtnHtml = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.style.opacity = '0.8';
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Mengunggah Bukti...';

    const form = document.getElementById('formUploadBukti');
    const formData = new FormData(form);

    fetch('upload_bukti.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Ubah tombol jadi status berhasil
            btnSubmit.style.background = '#16a34a';
            btnSubmit.style.borderColor = '#16a34a';
            btnSubmit.style.opacity = '1';
            btnSubmit.innerHTML = '<i class="fas fa-check-circle"></i> Berhasil Terkirim!';

            // Tampilkan alert sukses pada form
            if (successBox) {
                successBox.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Berhasil!</strong> Bukti pembayaran donasi Anda telah tersimpan ke sistem. Kode Tracking: <strong>' + data.kode + '</strong>';
                successBox.style.display = 'block';
            }

            // Buka Modal Pop-up Sukses yang Jelas
            bukaModalSukses(data.kode);

            // Munculkan Toast Notifikasi
            showToast('Bukti pembayaran berhasil diunggah!');
        } else {
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.innerHTML = originalBtnHtml;
            if (alertBox) {
                alertBox.innerHTML = '<i class="fas fa-circle-xmark"></i> ' + (data.message || 'Gagal mengunggah bukti pembayaran.');
                alertBox.style.display = 'block';
            } else {
                alert(data.message || 'Gagal mengunggah bukti pembayaran.');
            }
        }
    })
    .catch(err => {
        btnSubmit.disabled = false;
        btnSubmit.style.opacity = '1';
        btnSubmit.innerHTML = originalBtnHtml;
        console.error(err);
        if (alertBox) {
            alertBox.innerHTML = '<i class="fas fa-circle-xmark"></i> Terjadi kesalahan sistem saat mengirim bukti pembayaran. Silakan coba lagi.';
            alertBox.style.display = 'block';
        } else {
            alert('Terjadi kesalahan saat mengirim bukti pembayaran.');
        }
    });
}

function bukaModalSukses(kode) {
    const modal = document.getElementById('modalSuksesUpload');
    const codeElem = document.getElementById('modalTrxCode');
    if (codeElem && kode) codeElem.textContent = kode;
    if (modal) {
        modal.style.display = 'flex';
        // Animasi transisi masuk
        setTimeout(() => {
            modal.classList.add('active', 'show');
        }, 20);
    }
}

function tutupModalSukses() {
    const modal = document.getElementById('modalSuksesUpload');
    if (modal) {
        modal.classList.remove('active', 'show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 250);
    }
}

function closeModalOnBg(e) {
    if (e.target.id === 'modalSuksesUpload') {
        tutupModalSukses();
    }
}

function salinKodeTracking() {
    const code = document.getElementById('modalTrxCode').textContent.trim();
    salinTeks(code, 'Kode Tracking Donasi');
}

function salinTeks(text, label) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast(`${label} berhasil disalin!`);
        });
    } else {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast(`${label} berhasil disalin!`);
    }
}

function showToast(msg) {
    const toast = document.getElementById('ubToast');
    const toastMsg = document.getElementById('ubToastMsg');
    if (toast && toastMsg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }
}

function menujuCekStatusDonasi() {
    const trx = document.getElementById('modalTrxCode').textContent.trim();
    window.location.href = 'cek_status.php?kode=' + encodeURIComponent(trx);
}

// Bersihkan ?id=... dari address bar browser agar URL selalu tampil bersih: http://localhost/Donasi/donatur/upload_bukti.php
if (window.location.search) {
    window.history.replaceState(null, '', window.location.pathname);
}
</script>

<?php require_once '../includes/footer.php'; ?>
