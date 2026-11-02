<!-- NEW FOOTER -->
<footer class="footer-new">
    <div class="container">
        <div class="footer-new-grid">
            <div class="footer-new-brand">
                <a href="/Donasi/" class="nav-logo">
                    <img src="/logo%20donasi.png" alt="SIBERASA" style="height: 48px;">
                </a>
                <p class="brand-desc">Bersama kita wujudkan kebaikan,<br>untuk membantu sesama dan<br>membangun masa depan yang lebih baik.</p>
                <div class="footer-new-socials">
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-x-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-new-col">
                <h5>Tautan Cepat</h5>
                <ul>
                    <li><a href="/Donasi/"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                    <li><a href="/Donasi/donatur/program.php"><i class="fas fa-chevron-right"></i> Program Donasi</a></li>
                    <li><a href="/Donasi/donatur/galangdana.php"><i class="fas fa-chevron-right"></i> Galang Dana</a></li>
                    <li><a href="/Donasi/donatur/cek_status.php"><i class="fas fa-chevron-right"></i> Cek Status</a></li>
                    <li><a href="/Donasi/donatur/tentang.php"><i class="fas fa-chevron-right"></i> Tentang Kami</a></li>
                    <li><a href="/Donasi/donatur/kontak.php"><i class="fas fa-chevron-right"></i> Kontak</a></li>
                </ul>
            </div>
            
            <div class="footer-new-col">
                <h5>Hubungi Kami</h5>
                <div class="footer-new-contact-item">
                    <div class="icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="contact-text">Jl. Pendidikan No. 123<br>Kota Cirebon, Jawa Barat</div>
                </div>
                <div class="footer-new-contact-item">
                    <div class="icon-circle"><i class="fas fa-envelope"></i></div>
                    <div class="contact-text">info@donasi.org</div>
                </div>
                <div class="footer-new-contact-item">
                    <div class="icon-circle"><i class="fas fa-phone-alt"></i></div>
                    <div class="contact-text">(0231) 1234 5678</div>
                </div>
                <div class="footer-new-contact-item">
                    <div class="icon-circle"><i class="far fa-clock"></i></div>
                    <div class="contact-text">Senin - Jumat<br>08.00 - 16.00 WIB</div>
                </div>
            </div>

            <div class="footer-new-col footer-newsletter">
                <h5>Berlangganan Newsletter</h5>
                <p>Dapatkan informasi terbaru mengenai<br>program donasi dan kegiatan kami.</p>
                <form class="newsletter-form" onsubmit="return false;">
                    <div class="input-wrap">
                        <i class="far fa-envelope"></i>
                        <input type="email" placeholder="Masukkan email Anda" required>
                    </div>
                    <button type="submit" class="btn-subscribe">Berlangganan &rarr;</button>
                </form>
            </div>
        </div>
        
        <div class="footer-new-bottom">
            <p>&copy; <?= date('Y') ?> Sistem Donasi &amp; Pengumpulan Dana. All rights reserved.</p>
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
            <img src="/logo%20donasi.png" alt="SIBERASA" onerror="this.src='/Donasi/assets/img/logo_donasi.png'">
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
