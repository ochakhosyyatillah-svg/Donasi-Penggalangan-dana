<?php
require_once __DIR__ . '/../assets/config/database.php';

// Parameter URL atau default ke program #3 (Bantuan Korban Banjir) persis seperti di desain
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 3;
$nominal = isset($_GET['nominal']) ? (int)$_GET['nominal'] : 100000;
$metode  = isset($_GET['metode']) ? htmlspecialchars($_GET['metode']) : 'Bank Transfer';

// Cari program yang cocok
$prog = null;
foreach ($programs as $p) {
    if ($p['id'] === $id) {
        $prog = $p;
        break;
    }
}
if (!$prog) {
    $prog = $programs[2]; // Default ke program banjir (id: 3)
}

// Ambil daftar rekening / akun pembayaran yang didaftarkan pengusul untuk program ini
try {
    $stmtAkun = $pdo->prepare("SELECT * FROM akun_tujuan_pembayaran WHERE id_program = ? AND status_aktif = 1");
    $stmtAkun->execute([$prog['id']]);
    $daftarAkun = $stmtAkun->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftarAkun = [];
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
            <a href="pembayaran.php?id=<?= $prog['id'] ?>&nominal=<?= $nominal ?>&metode=<?= urlencode($metode) ?>" class="ub-btn-back">
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

                <!-- 3b. Rekening / Akun Tujuan Donasi (Dari Akun Pengusul) -->
                <div class="ub-card" style="margin-bottom: 24px;">
                    <h3 class="ub-card-title" style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <span><i class="fas fa-credit-card" style="color: #0284c7; margin-right: 8px;"></i>Rekening / Akun Tujuan Donasi</span>
                        <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">Transfer ke salah satu rekening/akun di bawah</span>
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (!empty($daftarAkun)): ?>
                            <?php foreach ($daftarAkun as $ak): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                                            <?php if ($ak['metode'] === 'bank'): ?>
                                                <i class="fas fa-building-columns"></i>
                                            <?php elseif ($ak['metode'] === 'qris'): ?>
                                                <i class="fas fa-qrcode"></i>
                                            <?php else: ?>
                                                <i class="fas fa-wallet"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #1e293b; font-size: 0.92rem;">
                                                <?= htmlspecialchars($ak['nama_penyedia']) ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                a.n. <?= htmlspecialchars($ak['nama_pemilik'] ?: 'Penyelenggara Program') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <?php if (!empty($ak['nomor_tujuan'])): ?>
                                            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-weight: 700; font-family: monospace; font-size: 0.92rem; color: #0f172a;">
                                                <?= htmlspecialchars($ak['nomor_tujuan']) ?>
                                            </div>
                                            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($ak['nomor_tujuan']) ?>'); alert('Nomor rekening/akun berhasil disalin!');" style="padding: 6px 12px; background: #0284c7; color: #fff; border: none; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="far fa-copy"></i> Salin
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($ak['gambar_qris'])): ?>
                                            <a href="/Donasi/<?= htmlspecialchars($ak['gambar_qris']) ?>" target="_blank" style="padding: 6px 12px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 0.78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fas fa-qrcode"></i> Lihat QRIS
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                <div>
                                    <div style="font-weight: 700; color: #1e293b;">Bank BCA (SIBERASA Resmi)</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">a.n. Yayasan SIBERASA Peduli</div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-weight: 700; font-family: monospace;">8271029384</div>
                                    <button type="button" onclick="navigator.clipboard.writeText('8271029384'); alert('Nomor rekening berhasil disalin!');" style="padding: 6px 12px; background: #0284c7; color: #fff; border: none; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer;">
                                        <i class="far fa-copy"></i> Salin
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Form Unggah Bukti Pembayaran -->
                <div class="ub-card ub-upload-card">
                    <h3 class="ub-card-title">Unggah Bukti Pembayaran</h3>

                    <form id="formUploadBukti" onsubmit="handleFormSubmit(event)">
                        
                        <!-- Drag & Drop Upload Zone -->
                        <div class="ub-dropzone" id="dropZone" onclick="triggerFileInput()">
                            <input type="file" id="fileInput" accept="image/jpeg,image/png,image/jpg,application/pdf" style="display:none;" onchange="handleFileChange(this)">
                            
                            <div class="ub-drop-icon">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <p class="ub-drop-text">
                                <span class="ub-drop-highlight">Klik untuk mengunggah</span> atau seret file ke sini
                            </p>
                            <small class="ub-drop-hint">Format yang didukung: JPG, PNG, PDF (maks. 5MB)</small>
                        </div>

                        <!-- File Preview Card Item -->
                        <div class="ub-file-preview-card" id="filePreviewCard">
                            <div class="ub-preview-thumb">
                                <i class="fas fa-file-invoice" id="previewIcon"></i>
                                <img src="" alt="Bukti Transfer" id="previewImage" style="display:none;">
                            </div>
                            <div class="ub-preview-details">
                                <strong class="ub-file-name" id="fileName">bukti-transfer.jpg</strong>
                                <span class="ub-file-size" id="fileSize">2.4 MB</span>
                            </div>
                            <button type="button" class="ub-btn-remove-file" onclick="removeSelectedFile()" aria-label="Hapus file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Catatan Opsional -->
                        <div class="ub-form-group">
                            <label for="inputCatatan" class="ub-label">Catatan (Opsional)</label>
                            <textarea id="inputCatatan" class="ub-textarea" rows="3" maxlength="200" placeholder="Contoh: Terima kasih sudah membantu..." oninput="updateCharCounter(this)"></textarea>
                            <div class="ub-counter-wrap">
                                <span id="charCounter">0/200</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="ub-btn-submit">
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
                            <img src="/Donasi/assets/img/phone_verified.jpg" alt="Donasi Terverifikasi">
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
<div class="modal-overlay" id="modalSuksesUpload" style="display:none;" onclick="closeModalOnBg(event)">
    <div class="ub-modal-box" onclick="event.stopPropagation()">
        <div class="ub-modal-icon-wrap">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Bukti Pembayaran Berhasil Dikirim!</h3>
        <p>Terima kasih atas kebaikan dan kepedulian Anda. Tim SIBERASA sedang memverifikasi donasi Anda dalam waktu <strong>1x24 jam kerja</strong>.</p>

        <div class="ub-ticket-box">
            <span class="label">Kode Transaksi Donasi:</span>
            <strong class="code" id="modalTrxCode">DONASI-2026-8921</strong>
            <small>Bukti pembayaran telah berhasil disimpan ke sistem.</small>
        </div>

        <div class="ub-modal-actions" style="display:flex; flex-direction:column; gap:10px;">
            <button type="button" class="ub-btn-submit" onclick="menujuCekStatusDonasi()" style="width:100%; justify-content:center;">
                <i class="fas fa-search"></i> Cek Status Donasi
            </button>
            <button type="button" class="ub-btn-back" onclick="window.location.href='index.php'" style="width:100%; justify-content:center; padding:12px; border:1px solid #d0e2f9; border-radius:50px; background:#f7fbff; color:#1a73e8; font-weight:600; cursor:pointer;">
                Kembali ke Beranda
            </button>
        </div>
    </div>
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
    width: 110px;
    height: 105px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ub-side-illu img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 10px;
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

/* Modal Sukses Popup */
.ub-modal-box {
    background: #ffffff;
    border-radius: 18px;
    max-width: 460px;
    width: 90%;
    padding: 34px 28px;
    text-align: center;
    box-shadow: 0 18px 48px rgba(0,0,0,0.18);
    animation: ubPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes ubPop {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
.ub-modal-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: #dcfce7;
    color: #16a34a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin-bottom: 16px;
}
.ub-modal-box h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 8px;
}
.ub-modal-box p {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 20px;
}
.ub-ticket-box {
    background-color: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 24px;
}
.ub-ticket-box .label {
    display: block;
    font-size: 0.72rem;
    color: #64748b;
    margin-bottom: 2px;
}
.ub-ticket-box .code {
    display: block;
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a73e8;
    letter-spacing: 0.5px;
}
.ub-ticket-box small {
    display: block;
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 3px;
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

    // Pastikan file sudah dipilih
    const file = document.getElementById('fileInput').files[0];
    // Jika belum pilih file baru, gunakan default sample bukti
    const randomTrx = 'DONASI-2026-' + Math.floor(1000 + Math.random() * 9000);
    document.getElementById('modalTrxCode').textContent = randomTrx;

    // Buka Modal Sukses
    document.getElementById('modalSuksesUpload').style.display = 'flex';
}

function closeModalOnBg(e) {
    if (e.target.id === 'modalSuksesUpload') {
        document.getElementById('modalSuksesUpload').style.display = 'none';
    }
}

function menujuCekStatusDonasi() {
    const trx = document.getElementById('modalTrxCode').textContent.trim();
    window.location.href = 'cek_status.php?kode=' + encodeURIComponent(trx);
}
</script>

<?php require_once '../includes/footer.php'; ?>
