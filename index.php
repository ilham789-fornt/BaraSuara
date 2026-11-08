<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Portal Resmi Fans Barasuara - Energi, Emosi, Eksplorasi. Dapatkan info terbaru, jadwal tur, dan diskografi lengkap.">
    <title>Barasuara - Beranda | Portal Fans Resmi</title>

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>
</head>

<body>
    <header>
        <div class="logo">
            <h1>Barasuara</h1>
        </div>
        <input type="checkbox" id="nav-toggle-index" class="hamburger-toggle" aria-hidden="true">
        <label for="nav-toggle-index" class="hamburger-btn" aria-label="Buka/tutup menu navigasi">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <nav aria-label="Navigasi Utama">
            <a href="index.php" class="active" aria-current="page">Beranda</a>
            <a href="about.php">Tentang</a>
            <a href="music.php">Musik</a>
            <a href="tour.php">Jadwal Tour</a>
            <a href="contact.php">Kontak</a>
        </nav>
    </header>

    <main style="padding-bottom: 40px;">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-content">
                <h1 id="hero-title" class="glitch" data-text="BARASUARA">BARASUARA</h1>
                <p>Energi. Emosi. Eksplorasi.</p>
                <div class="hero-btns">
                    <a href="music.php" class="btn">Jelajahi Musik</a>
                    <a href="#latest-news" class="btn btn-outline">Berita Terbaru</a>
                </div>
            </div>
        </section>

        <section id="latest-news" class="section fade-in" aria-labelledby="news-heading">
            <div class="section-header text-center">
                <h2 id="news-heading">Berita Terbaru</h2>
                <p class="subtitle">Kabar terkini dari riuh redam ruang Barasuara.</p>
            </div>

            <div class="news-grid"
                style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; max-width: 900px; margin: 0 auto;">
                <article class="card news-card" style="flex: 1 1 380px; min-width: 280px; text-align: left;">
                    <time class="news-date" datetime="2026-04-25"
                        style="color: var(--secondary-color, #ff6a3d); font-size: 0.85rem;">25 April 2026</time>
                    <h3 style="margin: 10px 0;">Persiapan Tour "Pikiran dan Perjalanan" 2026</h3>
                    <p style="color: #bbb; font-size: 0.9rem; line-height: 1.5; margin-bottom: 15px;">Simak persiapan
                        intensif Iga Massardi dkk menjelang pembukaan tour resmi di Jakarta...</p>
                    <a href="tour.php" class="read-more" aria-label="Baca selengkapnya tentang persiapan tur"
                        style="color: var(--primary-color, #c24b2f); text-decoration: none; font-weight: bold;">Selengkapnya
                        →</a>
                </article>
                <article class="card news-card" style="flex: 1 1 380px; min-width: 280px; text-align: left;">
                    <time class="news-date" datetime="2026-04-20"
                        style="color: var(--secondary-color, #ff6a3d); font-size: 0.85rem;">20 April 2026</time>
                    <h3 style="margin: 10px 0;">Merchandise Eksklusif Edisi Terbatas</h3>
                    <p style="color: #bbb; font-size: 0.9rem; line-height: 1.5; margin-bottom: 15px;">Kolaborasi visual
                        terbaru untuk koleksi pakaian eksklusif dan cetakan piringan hitam vinyl album Taifun...</p>
                    <a href="https://www.instagram.com/barasuara" target="_blank" rel="noopener noreferrer"
                        class="read-more" aria-label="Lihat informasi merchandise di Instagram Barasuara"
                        style="color: var(--primary-color, #c24b2f); text-decoration: none; font-weight: bold;">Selengkapnya
                        →</a>
                </article>
            </div>
        </section>

        <section class="section fade-in" aria-labelledby="music-heading">
            <div class="section-header text-center">
                <h2 id="music-heading">Musik Terbaru</h2>
                <p class="subtitle">Dengarkan kembali ledakan karya masa lalu dan masa kini.</p>
            </div>

            <div class="music-grid"
                style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; max-width: 1100px; margin: 0 auto;">
                <article class="card" style="flex: 1 1 280px; max-width: 320px; box-sizing: border-box;">
                    <div class="card-img-placeholder" style="margin-bottom: 15px;">
                        <img src="images/Taifun.png" alt="Cover Album Taifun" width="250" height="250" loading="lazy"
                            style="margin: 0 auto; border-radius: 6px; width: 100%; height: auto;">
                    </div>
                    <h3 style="margin-bottom: 15px;">Taifun</h3>
                    <a href="music.php?focus=taifun" class="btn"
                        style="width: 100%; padding: 10px 0; font-size: 0.9rem;">Lihat Album</a>
                </article>
                <article class="card" style="flex: 1 1 280px; max-width: 320px; box-sizing: border-box;">
                    <div class="card-img-placeholder" style="margin-bottom: 15px;">
                        <img src="images/pikiran-perjalanan.png" alt="Cover Album Pikiran dan Perjalanan" width="250"
                            height="250" loading="lazy"
                            style="margin: 0 auto; border-radius: 6px; width: 100%; height: auto;">
                    </div>
                    <h3 style="margin-bottom: 15px;">Pikiran dan Perjalanan</h3>
                    <a href="music.php?focus=pikiran-perjalanan" class="btn"
                        style="width: 100%; padding: 10px 0; font-size: 0.9rem;">Lihat Album</a>
                </article>

                <article class="card" style="flex: 1 1 280px; max-width: 320px; box-sizing: border-box;">
                    <div class="card-img-placeholder" style="margin-bottom: 15px;">
                        <img src="images/JALARAN_SADRAH.png" alt="Cover Album Jalaran Sadrah" width="250" height="250"
                            loading="lazy"
                            style="margin: 0 auto; border-radius: 6px; width: 100%; height: auto; filter: hue-rotate(180deg);">
                    </div>
                    <h3 style="margin-bottom: 15px;">Jalaran Sadrah</h3>
                    <a href="music.php?focus=jalaran-sadrah" class="btn"
                        style="width: 100%; padding: 10px 0; font-size: 0.9rem;">Lihat Album</a>
                </article>
            </div>
        </section>

        <section class="section fade-in" aria-labelledby="tour-heading">
            <div class="section-header text-center">
                <h2 id="tour-heading">Jadwal Tour Mendatang</h2>
                <p class="subtitle">Amankan barisan depanmu.</p>
            </div>

            <div class="tour-container"
                style="max-width: 750px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px;">
                <article class="tour-card"
                    style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); padding: 15px 25px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap; gap: 15px;">
                    <div class="tour-date" style="text-align: center; min-width: 60px;">
                        <time datetime="2026-05-10" style="display: flex; flex-direction: column; line-height: 1.1;">
                            <span class="day"
                                style="font-size: 1.8rem; font-weight: 800; color: var(--secondary-color, #ff6a3d);">10</span>
                            <span class="month"
                                style="font-size: 0.8rem; text-transform: uppercase; color: #888;">Mei</span>
                        </time>
                    </div>
                    <div class="tour-details" style="flex: 1 1 200px;">
                        <h3 style="margin: 0; font-size: 1.2rem;">Jakarta</h3>
                    </div>
                    <div class="tour-action">
                        <a href="tour.php#jakarta" class="btn btn-sm" aria-label="Lihat informasi tiket tur Jakarta"
                            style="padding: 8px 20px;">Info Tiket</a>
                    </div>
                </article>

                <article class="tour-card"
                    style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); padding: 15px 25px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap; gap: 15px;">
                    <div class="tour-date" style="text-align: center; min-width: 60px;">
                        <time datetime="2026-05-15" style="display: flex; flex-direction: column; line-height: 1.1;">
                            <span class="day"
                                style="font-size: 1.8rem; font-weight: 800; color: var(--secondary-color, #ff6a3d);">15</span>
                            <span class="month"
                                style="font-size: 0.8rem; text-transform: uppercase; color: #888;">Mei</span>
                        </time>
                    </div>
                    <div class="tour-details" style="flex: 1 1 200px;">
                        <h3 style="margin: 0; font-size: 1.2rem;">Bandung</h3>
                    </div>
                    <div class="tour-action">
                        <a href="tour.php#bandung" class="btn btn-sm" aria-label="Lihat informasi tiket tur Bandung"
                            style="padding: 8px 20px;">Info Tiket</a>
                    </div>
                </article>
            </div>
            <div class="text-center" style="margin-top: 35px;">
                <a href="tour.php" class="btn btn-outline">Lihat Semua Jadwal</a>
            </div>
        </section>

        <section class="section fade-in" aria-labelledby="newsletter-heading">
            <div class="contact-container text-center"
                style="max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.03); padding: 35px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <h2 id="newsletter-heading" style="margin-bottom: 10px; font-size: 1.8rem;">Bergabung dengan Barisan
                </h2>
                <p style="margin-bottom: 25px; color: var(--text-muted, #bbbbbb); font-size: 0.95rem;">Dapatkan berita
                    langsung ke emailmu tentang rilisan dan tour mendatang.</p>
                <form id="newsletter-form" class="form-group"
                    style="display: flex; flex-direction: row; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <label for="newsletter-email" class="visually-hidden">Alamat Email</label>
                    <input type="email" id="newsletter-email" name="email" placeholder="Email Anda..." required
                        style="flex: 1; min-width: 250px; max-width: 380px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; color: white;">
                    <button type="submit" class="btn" style="padding: 12px 28px;">Langganan</button>
                </form>
                <p id="newsletter-status" class="form-status" role="status" aria-live="polite"></p>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content text-center">
            <div class="social-links" style="justify-content: center; display: flex; gap: 20px; margin-bottom: 15px;">
                <a href="https://www.instagram.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="Instagram Resmi Barasuara"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="YouTube Resmi Barasuara"><i class="fab fa-youtube"></i></a>
                <a href="https://open.spotify.com/intl-id/artist/5RbI1PF4n02wWKmgjk7hAE?si=zHUmzxMMSkq_e1ENO2OcPQ&nd=1&dlsi=a7cf13a4230f4098"
                    target="_blank" rel="noopener noreferrer" aria-label="Spotify Resmi Barasuara"><i
                        class="fab fa-spotify"></i></a>
            </div>
            <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted, #bbbbbb);">© 2026 Barasuara Fans Project.
                Dikelola Oleh SIG Group.</p>
        </div>
    </footer>

    <script src="lib/script.js" defer></script>
</body>

</html>

