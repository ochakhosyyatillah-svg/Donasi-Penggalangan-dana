<?php
require_once '../includes/data.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$prog = null;
foreach ($programs as $p) {
    if ($p['id'] === $id) { $prog = $p; break; }
}

if (!$prog) {
    header('Location: program.php');
    exit;
}

$page_title = 'Formulir Donasi - ' . htmlspecialchars($prog['judul']);
$page_desc  = 'Formulir donasi untuk program ' . htmlspecialchars($prog['judul']);
$current_page = 'program';

require_once '../includes/header.php';
?>

<div style="background: #f4f8fb; min-height: 100vh; padding: 40px 0;">
    <div class="container" style="max-width: 1080px; margin: 0 auto; padding: 0 24px;">
        
        <form action="upload_bukti.php" method="GET" id="formDonasiUtama">
            <input type="hidden" name="id" value="<?= $prog['id'] ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: start;">
                
                <!-- KIRI -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    
                    <!-- Kartu Program -->
                    <div style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; position: relative;">
                        <a href="detail.php?id=<?= $prog['id'] ?>" style="display: inline-block; font-size: 1.4rem; color: #1a73e8; margin-bottom: 16px;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        
                        <div style="display: flex; gap: 16px;">
                            <div style="width: 140px; height: 100px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                                <img src="<?= $prog['gambar'] ?>" alt="<?= htmlspecialchars($prog['judul']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="display: flex; flex-direction: column; justify-content: center;">
                                <h3 style="font-size: 1.1rem; font-weight: 700; color: #1a202c; margin-bottom: 8px; line-height: 1.3;">
                                    <?= htmlspecialchars($prog['judul']) ?>
                                </h3>
                                
                                <span style="background: #eef2ff; color: #1a73e8; padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; display: inline-block; width: fit-content; margin-bottom: 12px;">
                                    <?= $prog['kategori'] ?>
                                </span>
                                
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem; font-weight: 600; color: #1a202c; margin-bottom: 6px;">
                                    <span><?= formatRupiah($prog['terkumpul']) ?><span style="color: #718096; font-weight: 500;">/<?= formatRupiah($prog['target']) ?></span></span>
                                    <span style="color: #34c759;"><?= $prog['persen'] ?>%</span>
                                </div>
                                <div style="height: 8px; background: #e2e8f0; border-radius: 50px; overflow: hidden;">
                                    <div style="width: <?= $prog['persen'] ?>%; height: 100%; background: #34c759; border-radius: 50px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Detail Donasi -->
                    <div style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                        <h2 style="font-size: 1.4rem; font-weight: 700; color: #1a202c; margin-bottom: 24px;">Detail Donasi</h2>
                        
                        <!-- Nominal -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #1a202c; margin-bottom: 12px;">Nominal Donasi <span style="color: red;">*</span></label>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                                <button type="button" class="btn-nominal active" data-val="10000">Rp 10.000</button>
                                <button type="button" class="btn-nominal" data-val="50000">Rp 50.000</button>
                                <button type="button" class="btn-nominal" data-val="100000">Rp 100.000</button>
                                <button type="button" class="btn-nominal" data-val="200000">Rp 200.000</button>
                            </div>
                            <input type="number" name="nominal" id="inputNominal" placeholder="Masukkan Nominal Donasi" value="10000" min="10000" style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1.5px solid #d1e0f0; font-family: 'Poppins', sans-serif; font-size: 0.95rem; color: #1a202c; outline: none; transition: 0.2s;" required>
                        </div>

                        <!-- Nama -->
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #1a202c; margin-bottom: 8px;">Nama Lengkap <span style="color: red;">*</span></label>
                            <input type="text" name="nama" placeholder="Masukkan Nama Lengkap" required style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1.5px solid #d1e0f0; font-family: 'Poppins', sans-serif; font-size: 0.95rem; outline: none; color: #1a202c;">
                        </div>

                        <!-- Email -->
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #1a202c; margin-bottom: 8px;">Email <span style="color: red;">*</span></label>
                            <input type="email" name="email" placeholder="Masukkan Email" required style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1.5px solid #d1e0f0; font-family: 'Poppins', sans-serif; font-size: 0.95rem; outline: none; color: #1a202c;">
                        </div>

                        <!-- Telepon -->
                        <div style="margin-bottom: 8px;">
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #1a202c; margin-bottom: 8px;">Nomer Telepon <span style="color: red;">*</span></label>
                            <input type="tel" name="telepon" placeholder="Masukkan Nomer Telepon" required style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1.5px solid #d1e0f0; font-family: 'Poppins', sans-serif; font-size: 0.95rem; outline: none; color: #1a202c;">
                        </div>

                    </div>
                </div>

                <!-- KANAN -->
                <div style="position: sticky; top: 100px;">
                <div style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <h2 style="font-size: 1.3rem; font-weight: 700; color: #1a202c; margin-bottom: 20px;">Metode Pembayaran <span style="color: red;">*</span></h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                        
                        <!-- QRIS -->
                        <label class="payment-method-label">
                            <input type="radio" name="metode" value="qris" required checked>
                            <span class="radio-custom"></span>
                            <i class="fas fa-qrcode payment-icon"></i>
                            <div>
                                <span class="payment-name">QRIS</span>
                                <p style="font-size:0.75rem; color:#a0aec0; margin:2px 0 0;">Scan QR dari semua e-wallet & bank</p>
                            </div>
                        </label>

                        <!-- Transfer Bank -->
                        <label class="payment-method-label">
                            <input type="radio" name="metode" value="transfer">
                            <span class="radio-custom"></span>
                            <i class="fas fa-university payment-icon"></i>
                            <div>
                                <span class="payment-name">Transfer Bank</span>
                                <p style="font-size:0.75rem; color:#a0aec0; margin:2px 0 0;">BCA · BNI · Mandiri · BRI</p>
                            </div>
                        </label>

                        <!-- E-Wallet -->
                        <label class="payment-method-label">
                            <input type="radio" name="metode" value="ewallet">
                            <span class="radio-custom"></span>
                            <i class="fas fa-wallet payment-icon"></i>
                            <div>
                                <span class="payment-name">E-Wallet</span>
                                <p style="font-size:0.75rem; color:#a0aec0; margin:2px 0 0;">GoPay · OVO · Dana · ShopeePay</p>
                            </div>
                        </label>

                    </div>

                    <!-- RINGKASAN DONASI -->
                    <div class="donation-summary">
                        <p class="summary-title"><i class="fas fa-receipt" style="margin-right:6px;"></i>Ringkasan Donasi</p>
                        <div class="summary-row">
                            <span>Nominal</span>
                            <strong id="summaryNominal">Rp 10.000</strong>
                        </div>
                        <div class="summary-row">
                            <span>Biaya Layanan</span>
                            <strong style="color:#34c759;">Gratis</strong>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total Pembayaran</span>
                            <strong id="summaryTotal">Rp 10.000</strong>
                        </div>
                    </div>

                    <!-- PESAN OPSIONAL -->
                    <div style="margin-bottom: 16px;">
                        <label style="display:block; font-size:0.88rem; font-weight:600; color:#1a202c; margin-bottom:8px;">
                            <i class="fas fa-comment-dots" style="color:#1a73e8; margin-right:6px;"></i>Pesan untuk Penerima <span style="color:#a0aec0; font-weight:400;">(opsional)</span>
                        </label>
                        <textarea name="pesan" placeholder="Tulis pesan atau doa untuk program ini..." rows="3"
                            style="width:100%; padding:12px 14px; border-radius:8px; border:1.5px solid #d1e0f0; font-family:'Poppins',sans-serif; font-size:0.88rem; color:#1a202c; outline:none; resize:none; transition:0.2s; box-sizing:border-box;"></textarea>
                    </div>

                    <!-- ANONIM CHECKBOX -->
                    <label class="anon-check-label" style="margin-bottom:20px;">
                        <input type="checkbox" name="anonim" id="chkAnonim" value="1">
                        <span class="anon-custom"><i class="fas fa-check" style="font-size:10px;"></i></span>
                        <span style="font-size:0.88rem; color:#4a5568;">Sembunyikan nama saya <span style="color:#a0aec0;">(donasi anonim)</span></span>
                    </label>

                    <button type="submit" class="btn-submit-donasi">
                        <i class="fas fa-lock" style="margin-right:8px; font-size:0.9rem;"></i>Lanjutkan Pembayaran
                    </button>

                    <!-- BADGE KEAMANAN -->
                    <div class="security-badges">
                        <div class="sec-badge"><i class="fas fa-shield-alt"></i><span>SSL Terenkripsi</span></div>
                        <div class="sec-badge"><i class="fas fa-lock"></i><span>Transaksi Aman</span></div>
                        <div class="sec-badge"><i class="fas fa-check-circle"></i><span>Terverifikasi</span></div>
                    </div>
                </div>
                </div>

            </div>
        </form>

    </div>
</div>


<style>
.btn-nominal {
    padding: 8px 16px;
    background: #f4f8fb;
    border: 1.5px solid transparent;
    border-radius: 50px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #5a7698;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-nominal:hover { background: #e6f0fa; color: #1a73e8; }
.btn-nominal.active { background: #e6f0fa; color: #1a73e8; border-color: #1a73e8; }

input:focus, textarea:focus {
    border-color: #1a73e8 !important;
    box-shadow: 0 0 0 3px rgba(26,115,232,0.1);
}

/* Payment method */
.payment-method-label {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    gap: 0;
}
.payment-method-label:hover { border-color: #1a73e8; background: #f7fbff; }
.payment-method-label:has(input:checked) { border-color: #1a73e8; background: #f0f7ff; }
.payment-method-label input { display: none; }
.radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e0;
    border-radius: 50%;
    margin-right: 14px;
    position: relative;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.payment-method-label input:checked + .radio-custom { border-color: #1a73e8; }
.payment-method-label input:checked + .radio-custom::after {
    content: '';
    width: 10px;
    height: 10px;
    background: #1a73e8;
    border-radius: 50%;
}
.payment-icon {
    font-size: 1.3rem;
    color: #1a73e8;
    margin-right: 14px;
    width: 28px;
    text-align: center;
    flex-shrink: 0;
}
.payment-name { font-size: 0.95rem; font-weight: 600; color: #1a202c; display: block; }

/* Donation Summary */
.donation-summary {
    background: #f7fbff;
    border: 1.5px solid #dbeafe;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
}
.summary-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1a73e8;
    margin-bottom: 12px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.88rem;
    color: #4a5568;
    padding: 5px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.summary-row:last-child { border-bottom: none; }
.summary-row strong { font-weight: 700; color: #1a202c; }
.summary-total { margin-top: 6px; font-size: 0.95rem; }
.summary-total span, .summary-total strong { color: #1a73e8; font-weight: 800; }

/* Anonim checkbox */
.anon-check-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    margin-bottom: 20px;
}
.anon-check-label input { display: none; }
.anon-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e0;
    border-radius: 5px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    color: transparent;
}
.anon-check-label input:checked + .anon-custom {
    background: #1a73e8;
    border-color: #1a73e8;
    color: #fff;
}

/* Submit button */
.btn-submit-donasi {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #1a73e8, #0d5bcf);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 16px;
    box-shadow: 0 4px 14px rgba(26,115,232,0.35);
}
.btn-submit-donasi:hover {
    background: linear-gradient(135deg, #0d5bcf, #1a73e8);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(26,115,232,0.45);
}

/* Security Badges */
.security-badges {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.sec-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: #718096;
    font-weight: 500;
}
.sec-badge i { color: #34c759; font-size: 0.9rem; }

@media (max-width: 768px) {
    #formDonasiUtama > div {
        grid-template-columns: 1fr !important;
    }
    [style*="position: sticky"] { position: static !important; }
}
</style>

<script>
function formatRp(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function updateSummary() {
    const val = parseInt(document.getElementById('inputNominal').value) || 0;
    document.getElementById('summaryNominal').textContent = formatRp(val);
    document.getElementById('summaryTotal').textContent = formatRp(val);
}

document.querySelectorAll('.btn-nominal').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-nominal').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('inputNominal').value = this.dataset.val;
        updateSummary();
    });
});

document.getElementById('inputNominal').addEventListener('input', function() {
    let val = this.value;
    document.querySelectorAll('.btn-nominal').forEach(b => {
        b.classList.toggle('active', b.dataset.val === val);
    });
    updateSummary();
});

// Init summary
updateSummary();
</script>

<?php require_once '../includes/footer.php'; ?>
