/* ============================================
   SIBERASA - Main JavaScript
   ============================================ */

// ============================================
// NAVBAR: Scroll effect & hamburger
// ============================================
const navbar = document.getElementById('navbar');
const scrollTopBtn = document.getElementById('scrollTop');
const navLinks = document.getElementById('navLinks');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
        scrollTopBtn.classList.add('show');
    } else {
        navbar.classList.remove('scrolled');
        scrollTopBtn.classList.remove('show');
    }

    // Animate on scroll
    animateOnScroll();
});

function toggleMobileMenu() {
    navLinks.classList.toggle('mobile-open');
    const hamburger = document.getElementById('hamburger');
    const spans = hamburger.querySelectorAll('span');
    if (navLinks.classList.contains('mobile-open')) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
    } else {
        spans[0].style.transform = '';
        spans[1].style.opacity = '';
        spans[2].style.transform = '';
    }
}

// Close mobile menu when clicking a link
if (navLinks) {
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('mobile-open');
        });
    });
}

// ============================================
// SMOOTH SCROLL for anchor links
// ============================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = 80;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    });
});

// ============================================
// HERO COUNTER ANIMATION
// ============================================
const counters = {
    countDonasi: { end: 12500, suffix: '+', prefix: '' },
    countProgram: { end: 6, suffix: '', prefix: '' },
    countTerkumpul: { end: 4.2, suffix: ' M', prefix: 'Rp ' },
};

function animateCounter(id, data) {
    const el = document.getElementById(id);
    if (!el || el.dataset.animated) return;
    el.dataset.animated = 'true';

    const duration = 2000;
    const start = performance.now();
    const isDecimal = data.end % 1 !== 0;

    function update(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = eased * data.end;

        el.textContent = data.prefix + (isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString('id-ID')) + data.suffix;

        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}

function runCounters() {
    const heroSection = document.querySelector('.hero');
    if (!heroSection) return;
    const rect = heroSection.getBoundingClientRect();
    if (rect.top < window.innerHeight) {
        Object.entries(counters).forEach(([id, data]) => animateCounter(id, data));
    }
}

// ============================================
// ANIMATE ON SCROLL (Intersection Observer)
// ============================================
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

function animateOnScroll() {
    document.querySelectorAll('.program-card, .stat-item, .how-card, .testi-card').forEach(el => {
        if (!el.dataset.observed) {
            el.dataset.observed = 'true';
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        }
    });
    runCounters();
}

// Run on initial load
window.addEventListener('load', () => {
    setTimeout(animateOnScroll, 200);
    runCounters();
});

// ============================================
// PROGRAM FILTER
// ============================================
function filterPrograms(kategori, btn) {
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.program-card');
    cards.forEach((card, i) => {
        const cardKat = card.dataset.kategori;
        const show = (kategori === 'semua') || (cardKat === kategori);

        if (show) {
            card.style.display = '';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, i * 80);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => { card.style.display = 'none'; }, 300);
        }
    });
}

// ============================================
// SEARCH BAR
// ============================================
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = searchInput.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.program-card');
            if (!cards.length) return;

            cards.forEach(card => {
                const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const org = card.querySelector('.card-org')?.textContent.toLowerCase() || '';
                const match = !query || title.includes(query) || org.includes(query);
                card.style.display = match ? '' : 'none';
            });
        }, 300);
    });
}

// ============================================
// MODAL AUTH
// ============================================
function openModal(tab = 'masuk') {
    if (tab === 'masuk') {
        window.location.href = '/Donasi/login.php';
    } else {
        window.location.href = '/Donasi/register.php';
    }
}

function closeModal() {
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function closeModalOnOverlay(e) {
    if (e.target === document.getElementById('modalOverlay')) {
        closeModal();
    }
}

function switchTab(tab) {
    const formMasuk = document.getElementById('formMasuk');
    const formDaftar = document.getElementById('formDaftar');
    const tabMasuk = document.getElementById('tabMasuk');
    const tabDaftar = document.getElementById('tabDaftar');
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');
    const switchText = document.getElementById('modalSwitchText');

    if (!formMasuk) return;

    if (tab === 'masuk') {
        formMasuk.style.display = '';
        formDaftar.style.display = 'none';
        tabMasuk.classList.add('active');
        tabDaftar.classList.remove('active');
        title.textContent = 'Masuk ke SIBERASA';
        subtitle.textContent = 'Selamat datang kembali!';
        switchText.innerHTML = 'Belum punya akun? <a href="#" onclick="switchTab(\'daftar\')">Daftar sekarang</a>';
    } else {
        formMasuk.style.display = 'none';
        formDaftar.style.display = '';
        tabMasuk.classList.remove('active');
        tabDaftar.classList.add('active');
        title.textContent = 'Daftar ke SIBERASA';
        subtitle.textContent = 'Bergabung dan mulai berdonasi';
        switchText.innerHTML = 'Sudah punya akun? <a href="#" onclick="switchTab(\'masuk\')">Masuk</a>';
    }
}

// Close modal on ESC key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});

// ============================================
// TOAST NOTIFICATION
// ============================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const colors = {
        success: { bg: '#10B981', icon: 'fa-check-circle' },
        error: { bg: '#EF4444', icon: 'fa-times-circle' },
        info: { bg: '#3B82F6', icon: 'fa-info-circle' },
        warning: { bg: '#F59E0B', icon: 'fa-exclamation-triangle' },
    };

    const c = colors[type] || colors.info;
    const toast = document.createElement('div');
    toast.style.cssText = `
        display: flex; align-items: center; gap: 10px;
        background: ${c.bg}; color: white;
        padding: 12px 20px; border-radius: 50px;
        font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        opacity: 0; transform: translateY(20px);
        transition: all 0.3s ease; pointer-events: auto;
        white-space: nowrap;
    `;
    toast.innerHTML = `<i class="fas ${c.icon}"></i> ${message}`;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ============================================
// PROGRESS BAR ANIMATION on scroll
// ============================================
const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const bar = entry.target;
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => { bar.style.width = width; }, 200);
            progressObserver.unobserve(bar);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.progress-bar-fill').forEach(bar => {
    progressObserver.observe(bar);
});

// ============================================
// STATS SECTION ANIMATION
// ============================================
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('.stat-item').forEach((item, i) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, i * 150);
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.3 });

const statsSection = document.querySelector('.stats-section');
if (statsSection) {
    statsSection.querySelectorAll('.stat-item').forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'all 0.5s ease';
    });
    statsObserver.observe(statsSection);
}

// ============================================
// DONASI BUTTON ON DETAIL PAGE
// ============================================
function donasiNow(id) {
    showToast('Fitur donasi segera hadir!', 'info');
}

console.log('%cSIBERASA 🌿', 'color: #1A73E8; font-size: 24px; font-weight: bold;');
console.log('%cSistem Donasi dan Penggalangan Dana', 'color: #34C759; font-size: 14px;');
