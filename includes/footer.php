<!-- NEW FOOTER -->
<footer class="footer-new">
    <div class="container">
        <div class="footer-new-grid">
            <div class="footer-new-brand">
                <a href="/Donasi/" class="nav-logo">
                    <img src="/Donasi/assets/img/logo_donasi.png" alt="SIBERASA" style="height: 48px;">
                </a>
                <p class="brand-desc">Bersama kita wujudkan kebaikan, untuk membantu sesama dan membangun masa depan yang lebih baik melalui sistem donasi terpercaya.</p>
                <div class="footer-new-socials" style="display: flex; flex-direction: row; align-items: center; gap: 12px;">
                    <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn" title="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-btn" title="X (Twitter)"><i class="fab fa-x-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-new-col">
                <h5>Program &amp; Donasi</h5>
                <ul>
                    <li><a href="/Donasi/"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                    <li><a href="/Donasi/donatur/program.php"><i class="fas fa-chevron-right"></i> Program Donasi</a></li>
                    <li><a href="/Donasi/donatur/galangdana.php"><i class="fas fa-chevron-right"></i> Galang Dana</a></li>
                    <li><a href="/Donasi/donatur/cek_status.php"><i class="fas fa-chevron-right"></i> Cek Status Donasi</a></li>
                </ul>
            </div>

            <div class="footer-new-col">
                <h5>Informasi &amp; Layanan</h5>
                <ul>
                    <li><a href="/Donasi/donatur/tentang.php"><i class="fas fa-chevron-right"></i> Tentang SIBERASA</a></li>
                    <li><a href="/Donasi/donatur/kontak.php"><i class="fas fa-chevron-right"></i> Kontak Kami</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Syarat &amp; Ketentuan</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Kebijakan Privasi</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-new-bottom">
            <p>&copy; <?= date('Y') ?> SIBERASA - Sistem Donasi &amp; Pengumpulan Dana. All rights reserved.</p>
            <div class="footer-new-bottom-links">
                <a href="#">Privasi</a> <span class="sep">|</span>
                <a href="#">Syarat &amp; Ketentuan</a> <span class="sep">|</span>
                <a href="#">Bantuan</a>
            </div>
        </div>
    </div>
</footer>

<!-- MODAL AUTH -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOnOverlay(event)">
    <div class="modal" id="authModal">
        <button class="modal-close" onclick="closeModal()" aria-label="Tutup"><i class="fas fa-times"></i></button>

        <div class="modal-logo">
            <img src="/Donasi/assets/img/logo_donasi.png" alt="SIBERASA">
            <h3 id="modalTitle">Masuk ke SIBERASA</h3>
            <p id="modalSubtitle">Bergabung dan mulai berdonasi</p>
        </div>

        <div class="modal-tab" id="modalTab">
            <button id="tabMasuk" class="active" onclick="switchTab('masuk')">Masuk</button>
            <button id="tabDaftar" onclick="switchTab('daftar')">Daftar</button>
        </div>

        <!-- Form Masuk -->
        <form id="formMasuk" onsubmit="return false;">
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="loginEmail" placeholder="email@contoh.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="loginPassword">Kata Sandi</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="loginPassword" placeholder="Masukkan kata sandi" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;padding:12px;" onclick="showToast('Fitur login segera hadir!','info')">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <!-- Form Daftar -->
        <form id="formDaftar" style="display:none;" onsubmit="return false;">
            <div class="form-group">
                <label for="regNama">Nama Lengkap</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="regNama" placeholder="Nama lengkap Anda" required>
                </div>
            </div>
            <div class="form-group">
                <label for="regEmail">Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="regEmail" placeholder="email@contoh.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="regPhone">No. Telepon</label>
                <div class="input-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="regPhone" placeholder="08xxxxxxxxxx" required>
                </div>
            </div>
            <div class="form-group">
                <label for="regPassword">Kata Sandi</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="regPassword" placeholder="Min. 8 karakter" required>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary" style="width:100%;margin-top:8px;padding:12px;" onclick="showToast('Fitur daftar segera hadir!','success')">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <p class="modal-footer-text" id="modalSwitchText">
            Belum punya akun? <a href="#" onclick="switchTab('daftar')">Daftar sekarang</a>
        </p>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div id="toastContainer" style="position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;gap:10px;align-items:center;pointer-events:none;"></div>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Kembali ke atas">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Main JS -->
<script src="/Donasi/assets/js/main.js"></script>
</body>
</html>
