/**
 * BARASUARA FANS PROJECT - CORE ENGINE v5.0
 * YouTube preview, album deep-link, form feedback, and adaptive effects.
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const RuntimeConfig = {
        isLowPerformance: false,
        init() {
            const connection = navigator.connection;
            if (connection && (connection.saveData || ['slow-2g', '2g', '3g'].includes(connection.effectiveType))) {
                this.isLowPerformance = true;
                document.documentElement.classList.add('low-perf-mode');
            }
        }
    };
    RuntimeConfig.init();

    const fadeElements = document.querySelectorAll('.fade-in');
    if ('IntersectionObserver' in window && !RuntimeConfig.isLowPerformance) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        fadeElements.forEach((element) => observer.observe(element));
    } else {
        fadeElements.forEach((element) => element.classList.add('show'));
    }

    function parseYouTubeId(input) {
        if (!input) return '';
        const str = String(input).trim();
        // 1. Jika sudah berupa 11 karakter ID murni (cth: KfR2iLLan6U)
        if (/^[a-zA-Z0-9_-]{11}$/.test(str)) {
            return str;
        }
        // 2. Format URL: youtu.be/<id>, watch?v=<id>, embed/<id>, dll
        const urlMatch = str.match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/i);
        if (urlMatch && urlMatch[1]) {
            return urlMatch[1];
        }
        // 3. Format tag iframe embed
        const iframeMatch = str.match(/src=["'](?:https?:)?\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([\w-]{11})/i);
        if (iframeMatch && iframeMatch[1]) {
            return iframeMatch[1];
        }
        // 4. Fallback jika ada urutan 11 karakter id di dalamnya
        const fallbackMatch = str.match(/([a-zA-Z0-9_-]{11})/);
        return fallbackMatch ? fallbackMatch[1] : str.replace(/[^a-zA-Z0-9_-]/g, '');
    }

    const VideoController = {
        container: document.getElementById('video-player-container'),
        frame: document.getElementById('youtube-frame'),
        titleDisplay: document.getElementById('playing-title'),
        fallback: document.getElementById('youtube-fallback'),

        play(title, videoId) {
            if (!this.container || !this.frame || !this.titleDisplay || !videoId) return;
            const safeId = parseYouTubeId(videoId);
            if (!safeId) return;

            this.frame.src = 'https:' + '//' + 'www.youtube-nocookie.com/embed/' + safeId + '?autoplay=1&rel=0';
            this.frame.title = `Preview YouTube: ${title}`;
            this.titleDisplay.textContent = `Memutar preview: ${title}`;
            if (this.fallback) this.fallback.href = 'https:' + '//' + 'www.youtube.com/watch?v=' + safeId;
            this.container.classList.remove('hidden');
            this.container.scrollIntoView({ behavior: RuntimeConfig.isLowPerformance ? 'auto' : 'smooth', block: 'center' });
        },

        close() {
            if (!this.container || !this.frame) return;
            this.frame.src = '';
            this.container.classList.add('hidden');
            document.querySelector('.play-video-btn')?.focus();
        }
    };

    function focusAlbumFromUrl() {
        const focus = new URLSearchParams(window.location.search).get('focus');
        if (!focus) return;
        const safeFocus = focus.toLowerCase().replace(/[^a-z0-9-]/g, '');
        const album = document.getElementById(`album-${safeFocus}`);
        if (!album) return;
        album.classList.add('album-focus');
        requestAnimationFrame(() => album.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        window.setTimeout(() => album.classList.remove('album-focus'), 2600);
    }
    focusAlbumFromUrl();

    const newsletterForm = document.getElementById('newsletter-form');
    newsletterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = document.getElementById('newsletter-email');
        const status = document.getElementById('newsletter-status');
        if (!input?.checkValidity()) {
            input?.reportValidity();
            return;
        }
        try {
            localStorage.setItem('barasuaraNewsletterEmail', input.value.trim());
        } catch (error) {
            console.warn('Penyimpanan lokal tidak tersedia.', error);
        }
        if (status) {
            status.className = 'form-status success';
            status.textContent = 'Terima kasih! Email tersimpan di perangkat ini untuk versi demo.';
        }
        newsletterForm.reset();
    });

    // Formulir kontak kini diproses server-side oleh contact.php (wajib login, disimpan ke DB).

    document.addEventListener('click', (event) => {
        const playButton = event.target.closest('.play-video-btn');
        if (playButton) {
            VideoController.play(playButton.dataset.title, playButton.dataset.vid);
            return;
        }
        if (event.target.closest('.close-video-btn')) VideoController.close();
    });

    if (!RuntimeConfig.isLowPerformance) {
        document.addEventListener('mousemove', (event) => {
            const card = event.target.closest('.card, .album-card, .tour-card');
            if (!card) return;
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', `${event.clientX - rect.left}px`);
            card.style.setProperty('--mouse-y', `${event.clientY - rect.top}px`);
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') VideoController.close();
    });

    // --- MOBILE NAV: Auto-close menu saat link diklik ---
    const navToggle = document.querySelector('.hamburger-toggle');
    if (navToggle) {
        document.querySelectorAll('nav a').forEach((link) => {
            link.addEventListener('click', () => {
                navToggle.checked = false;
            });
        });
        // Tutup menu saat klik di luar header
        document.addEventListener('click', (event) => {
            if (!event.target.closest('header') && navToggle.checked) {
                navToggle.checked = false;
            }
        });
    }

    // --- INTERAKSI FOTO PERSONEL: HOVER & PRESS TOGGLE ---
    const personnelCards = document.querySelectorAll('.personnel-card');
    personnelCards.forEach((card) => {
        const img = card.querySelector('img');
        if (!img) return;

        const originalSrc = img.getAttribute('src');
        const hoverSrc = card.getAttribute('data-hover');
        if (!hoverSrc) return;

        img.dataset.original = originalSrc;

        // Preload gambar hover untuk mencegah kedipan (blank/loading lag)
        const preloadImg = new Image();
        preloadImg.src = hoverSrc;

        // Hover Kursor (Desktop)
        card.addEventListener('mouseenter', () => {
            img.src = hoverSrc;
        });

        card.addEventListener('mouseleave', () => {
            img.src = originalSrc;
        });

        // Sentuhan / Tekan (Mobile / Touch Devices)
        card.addEventListener('click', (event) => {
            // Lewati jika perangkat mendukung hover sejati (desktop dengan mouse)
            if (window.matchMedia('(hover: hover)').matches) return;

            event.stopPropagation();
            const isCurrentlyHover = img.src.includes(hoverSrc);

            // Kembalikan semua gambar personel lain ke semula
            personnelCards.forEach((otherCard) => {
                const otherImg = otherCard.querySelector('img');
                if (otherImg && otherImg.dataset.original) {
                    otherImg.src = otherImg.dataset.original;
                }
            });

            // Toggle gambar untuk kartu yang aktif saat ini
            if (!isCurrentlyHover) {
                img.src = hoverSrc;
            }
        });
    });

    // Reset semua kartu saat pengguna mengetuk di luar area kartu personel pada mobile
    document.addEventListener('click', (event) => {
        if (window.matchMedia('(hover: hover)').matches) return;
        if (!event.target.closest('.personnel-card')) {
            personnelCards.forEach((card) => {
                const img = card.querySelector('img');
                if (img && img.dataset.original) {
                    img.src = img.dataset.original;
                }
            });
        }
    });

    window.playYouTube = (title, id) => VideoController.play(title, id);
    window.closeVideo = () => VideoController.close();
});

