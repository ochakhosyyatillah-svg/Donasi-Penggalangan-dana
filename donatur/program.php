<?php
$page_title   = 'Program Donasi - SIBERASA';
$page_desc    = 'Temukan dan dukung program donasi pilihan di SIBERASA. Pendidikan, Sosial, Kemanusiaan, Kesehatan, dan lainnya.';
$current_page = 'program';

require_once __DIR__ . '/../assets/config/database.php';
require_once '../includes/header.php';
?>

<!-- ===== PROGRAM PAGE ===== -->
<div class="prog-page">
    <div class="container">

        <!-- PAGE TITLE -->
        <div class="prog-header">
            <h1 class="prog-title">Program Donasi Terbaru</h1>
        </div>

        <!-- SEARCH & DROPDOWN ROW -->
        <div class="prog-controls">
            <div class="prog-search-wrap">
                <i class="fas fa-search prog-search-icon"></i>
                <input
                    type="text"
                    id="progSearch"
                    class="prog-search-input"
                    placeholder="Cari Program Donasi...."
                    oninput="filterCards()"
                    autocomplete="off"
                >
            </div>
            <div class="prog-select-wrap">
                <select id="progCategory" class="prog-select" onchange="filterCards()">
                    <option value="">Semua Kategori</option>
                    <option value="sosial">Sosial</option>
                    <option value="pendidikan">Pendidikan</option>
                    <option value="kemanusiaan">Kemanusiaan</option>
                    <option value="kesehatan">Kesehatan</option>
                    <option value="keagamaan">Keagamaan</option>
                    <option value="lingkungan">Lingkungan</option>
                </select>
                <i class="fas fa-chevron-down prog-select-icon"></i>
            </div>
        </div>

        <!-- FILTER PILL TABS -->
        <div class="prog-tabs" id="progTabs">
            <button class="prog-tab active" data-filter="semua" onclick="setTab(this, 'semua')">Semua</button>
            <button class="prog-tab" data-filter="sosial" onclick="setTab(this, 'sosial')">Sosial</button>
            <button class="prog-tab" data-filter="pendidikan" onclick="setTab(this, 'pendidikan')">Pendidikan</button>
            <button class="prog-tab" data-filter="kemanusiaan" onclick="setTab(this, 'kemanusiaan')">Kemanusiaan</button>
            <button class="prog-tab" data-filter="kesehatan" onclick="setTab(this, 'kesehatan')">Kesehatan</button>
            <button class="prog-tab" data-filter="keagamaan" onclick="setTab(this, 'keagamaan')">Keagamaan</button>
            <button class="prog-tab" data-filter="lingkungan" onclick="setTab(this, 'lingkungan')">Lingkungan</button>
        </div>

        <!-- CARDS GRID -->
        <div class="prog-cards-wrap">
        <div class="prog-grid" id="progGrid">
            <?php foreach ($programs as $prog): ?>
            <div class="prog-card" data-kategori="<?= $prog['kategori_slug'] ?>">
                <!-- Card Image -->
                <div class="prog-card-img">
                    <img src="<?= $prog['gambar'] ?>" alt="<?= htmlspecialchars($prog['judul']) ?>" loading="lazy">
                    <span class="prog-badge" style="background:<?= $prog['badge_color'] ?>;">
                        <?= $prog['kategori'] ?>
                    </span>
                </div>
                <!-- Card Body -->
                <div class="prog-card-body">
                    <h3 class="prog-card-title"><?= htmlspecialchars($prog['judul']) ?></h3>
                    <p class="prog-card-org"><?= htmlspecialchars($prog['penyelenggara']) ?></p>

                    <!-- Progress -->
                    <div class="prog-progress-wrap">
                        <div class="prog-progress-bar">
                            <div class="prog-progress-fill" style="width:<?= $prog['persen'] ?>%"></div>
                        </div>
                        <span class="prog-persen"><?= $prog['persen'] ?>% terkumpul</span>
                    </div>

                    <!-- Amounts -->
                    <div class="prog-amounts">
                        <div class="prog-amount-item">
                            <strong><?= formatRupiah($prog['terkumpul']) ?></strong>
                            <small>terkumpul</small>
                        </div>
                        <div class="prog-amount-item prog-amount-right">
                            <strong><?= formatRupiah($prog['target']) ?></strong>
                            <small>target</small>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="prog-card-footer">
                        <div class="prog-deadline">
                            <i class="far fa-calendar-alt"></i>
                            berakhir dalam <?= $prog['sisa_hari'] ?> hari
                        </div>
                        <a href="detail.php?id=<?= $prog['id'] ?>" class="prog-detail-btn">
                            Lihat Detail <i class="fas fa-arrow-right-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- EMPTY STATE -->
        <div class="prog-empty" id="progEmpty" style="display:none;">
            <i class="fas fa-search"></i>
            <p>Tidak ada program yang ditemukan</p>
        </div>

        </div><!-- /prog-cards-wrap -->

    </div>
</div>

<style>
/* ===== PROGRAM PAGE STYLES ===== */
.prog-page {
    background: #f0f6ff;
    min-height: 80vh;
    padding: 40px 0 60px;
}

.prog-header {
    margin-bottom: 28px;
}

.prog-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1a2a4a;
    letter-spacing: -0.5px;
}

/* CONTROLS ROW */
.prog-controls {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    align-items: center;
}

/* Search */
.prog-search-wrap {
    position: relative;
    flex: 1;
    max-width: 460px;
}

.prog-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 0.9rem;
}

.prog-search-input {
    width: 100%;
    padding: 12px 16px 12px 42px;
    border: 1.5px solid #D0D9E8;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: #1a2a4a;
    background: #fff;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.prog-search-input:focus {
    border-color: #1A73E8;
    box-shadow: 0 0 0 3px rgba(26,115,232,0.1);
}

.prog-search-input::placeholder { color: #a0aec0; }

/* Dropdown */
.prog-select-wrap {
    position: relative;
    min-width: 220px;
}

.prog-select {
    width: 100%;
    padding: 12px 42px 12px 16px;
    border: 1.5px solid #D0D9E8;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: #4a5568;
    background: #fff;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.prog-select:focus { border-color: #1A73E8; }

.prog-select-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #4a5568;
    pointer-events: none;
    font-size: 0.8rem;
}

/* FILTER TABS */
.prog-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.prog-tab {
    padding: 8px 22px;
    border-radius: 50px;
    border: 1.5px solid #D0D9E8;
    background: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 0.88rem;
    font-weight: 500;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.2s ease;
}

.prog-tab:hover {
    border-color: #1A73E8;
    color: #1A73E8;
    background: #EBF4FF;
}

.prog-tab.active {
    background: #1A73E8;
    color: #fff;
    border-color: #1A73E8;
    font-weight: 600;
}

/* GRID */
.prog-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

/* CARDS WRAPPER — border hijau seperti homepage */
.prog-cards-wrap {
    border: 2.5px solid #4caf50;
    border-radius: 18px;
    padding: 24px;
    background: #fff;
    box-shadow: 0 0 0 5px rgba(76,175,80,.07);
}

/* CARD */
.prog-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1.5px solid #E8EEF7;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    animation: fadeInUp 0.4s ease both;
}

.prog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(26,115,232,0.13);
}

/* Card Image */
.prog-card-img {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.prog-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.prog-card:hover .prog-card-img img {
    transform: scale(1.05);
}

.prog-badge {
    position: absolute;
    bottom: 12px;
    left: 12px;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 14px;
    border-radius: 50px;
    backdrop-filter: blur(4px);
    letter-spacing: 0.3px;
}

/* Card Body */
.prog-card-body {
    padding: 18px 20px 16px;
}

.prog-card-title {
    font-size: 0.97rem;
    font-weight: 700;
    color: #1a2a4a;
    line-height: 1.4;
    margin-bottom: 5px;
}

.prog-card-org {
    font-size: 0.8rem;
    color: #1A73E8;
    font-weight: 500;
    margin-bottom: 14px;
}

/* Progress */
.prog-progress-wrap {
    margin-bottom: 12px;
}

.prog-progress-bar {
    width: 100%;
    height: 7px;
    background: #E8EEF7;
    border-radius: 50px;
    overflow: hidden;
    margin-bottom: 5px;
}

.prog-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #34C759, #2ecc71);
    border-radius: 50px;
    transition: width 0.6s ease;
}

.prog-persen {
    font-size: 0.78rem;
    font-weight: 600;
    color: #34C759;
}

/* Amounts */
.prog-amounts {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 16px;
    gap: 8px;
}

.prog-amount-item {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.prog-amount-item strong {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1a2a4a;
    line-height: 1.2;
}

.prog-amount-item small {
    font-size: 0.72rem;
    color: #a0aec0;
    font-weight: 400;
}

.prog-amount-right {
    text-align: right;
}

/* Card Footer */
.prog-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid #F0F4F8;
}

.prog-deadline {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #a0aec0;
}

.prog-deadline i { font-size: 0.8rem; }

.prog-detail-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #1A73E8;
    border: 1.5px solid #1A73E8;
    border-radius: 50px;
    padding: 5px 14px;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.prog-detail-btn:hover {
    background: #1A73E8;
    color: #fff;
}

.prog-detail-btn i { font-size: 0.9rem; }

/* EMPTY STATE */
.prog-empty {
    text-align: center;
    padding: 80px 0;
    color: #a0aec0;
}

.prog-empty i {
    font-size: 3rem;
    margin-bottom: 16px;
    display: block;
}

.prog-empty p {
    font-size: 1rem;
    font-weight: 500;
}

/* HIDDEN CARD */
.prog-card.hidden { display: none; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .prog-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
    .prog-grid { grid-template-columns: 1fr; }
    .prog-controls { flex-direction: column; }
    .prog-select-wrap { min-width: unset; width: 100%; }
    .prog-search-wrap { max-width: 100%; }
    .prog-title { font-size: 1.5rem; }
}
</style>

<script>
let activeTab = 'semua';

function setTab(btn, filter) {
    // Update active tab style
    document.querySelectorAll('.prog-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeTab = filter;

    // Sync dropdown if tab changed
    const sel = document.getElementById('progCategory');
    sel.value = filter === 'semua' ? '' : filter;

    filterCards();
}

function filterCards() {
    const search   = document.getElementById('progSearch').value.toLowerCase().trim();
    const category = document.getElementById('progCategory').value;

    // Sync tab if dropdown changed
    if (category !== '') {
        document.querySelectorAll('.prog-tab').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === category);
        });
        activeTab = category;
    } else {
        document.querySelectorAll('.prog-tab').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === 'semua');
        });
        activeTab = 'semua';
    }

    const cards = document.querySelectorAll('.prog-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const kat   = card.dataset.kategori;
        const title = card.querySelector('.prog-card-title').textContent.toLowerCase();
        const org   = card.querySelector('.prog-card-org').textContent.toLowerCase();

        const matchCat    = activeTab === 'semua' || kat === activeTab;
        const matchSearch = !search || title.includes(search) || org.includes(search);

        if (matchCat && matchSearch) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });

    document.getElementById('progEmpty').style.display = visibleCount === 0 ? 'block' : 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
