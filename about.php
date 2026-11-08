<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Profil, diskografi, sejarah, dan personel Barasuara. Kenali lebih dekat energi di balik musik mereka.">
    <title>Tentang - Barasuara | Portal Fans Resmi</title>

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
        <input type="checkbox" id="nav-toggle-about" class="hamburger-toggle" aria-hidden="true">
        <label for="nav-toggle-about" class="hamburger-btn" aria-label="Buka/tutup menu navigasi">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <nav aria-label="Navigasi Utama">
            <a href="index.php">Beranda</a>
            <a href="about.php" class="active" aria-current="page">Tentang</a>
            <a href="music.php">Musik</a>
            <a href="tour.php">Jadwal Tour</a>
            <a href="contact.php">Kontak</a>
        </nav>
    </header>

    <main style="padding-bottom: 60px;">
        <!-- SECTION 1: PROFIL BAND -->
        <section class="section fade-in" aria-labelledby="profil-band">
            <div class="about-container">
                <div class="about-text">
                    <h2 id="profil-band">Profil Band</h2>
                    <dl class="info-list">
                        <dt>Nama:</dt>
                        <dd>Barasuara</dd>
                        <dt>Asal:</dt>
                        <dd>Jakarta, Indonesia</dd>
                        <dt>Tahun Bentuk:</dt>
                        <dd>2012</dd>
                        <dt>Genre:</dt>
                        <dd>Indie Rock, Alternatif, Folk</dd>
                    </dl>
                    <p style="line-height: 1.6; margin-top: 15px;">
                        Barasuara adalah entitas indie rock asal Indonesia yang dikenal melalui dentuman musik
                        emosional, aransemen kompleks, dan lirik puitis. Hantaman mereka di kancah musik nasional mulai
                        menggema lewat perilisan album debut “Taifun” pada tahun 2015.
                    </p>
                </div>
                <div class="about-image">
                    <img src="images/band.jpeg" alt="Formasi lengkap personel Barasuara" width="600" height="400"
                        loading="lazy" style="border-radius: 8px; width: 100%; height: auto;">
                </div>
            </div>
        </section>

        <!-- SECTION 2: DISKOGRAFI -->
        <section class="section fade-in text-center" aria-labelledby="diskografi-title">
            <h2 id="diskografi-title">Jejak Diskografi</h2>
            <div class="timeline" style="margin: 30px auto 0 auto; max-width: 800px; text-align: left;">
                <div class="timeline-item" style="margin-bottom: 20px;">
                    <time class="timeline-date" datetime="2015"
                        style="font-weight: bold; color: var(--primary-color, #e63946);">2015</time>
                    <div class="timeline-content"
                        style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; margin-top: 5px;">
                        <h3 style="margin-top: 0;">Taifun</h3>
                        <p style="margin-bottom: 0;">Album debut yang meledakkan nama Barasuara, memadukan distorsi dan
                            melodi vokal bersahutan.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <time class="timeline-date" datetime="2019"
                        style="font-weight: bold; color: var(--primary-color, #e63946);">2019</time>
                    <div class="timeline-content"
                        style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; margin-top: 5px;">
                        <h3 style="margin-top: 0;">Pikiran dan Perjalanan</h3>
                        <p style="margin-bottom: 0;">Eksplorasi aransemen yang lebih kompleks, reflektif, dan matang
                            secara musikalitas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3: PERSONEL (SIMETRIS & CENTERED) -->
        <section class="section fade-in text-center" aria-labelledby="personel-title">
            <h2 id="personel-title" style="margin-bottom: 30px;">Personel</h2>
            <div class="music-grid"
                style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; max-width: 1200px; margin: 0 auto;">
                <article class="card personnel-card" data-member="iga" data-hover="images/Iga Massardi 1.png"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/Iga Massardi.jpeg" alt="Iga Massardi - Vokal dan Gitar" loading="lazy" width="250"
                        height="250" style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">Iga Massardi</h3>
                    <p style="color: #aaa;"><i class="fas fa-microphone" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Vokal utama, gitar</p>
                </article>

                <article class="card personnel-card" data-member="gerald" data-hover="images/Gerald Situmorang 1.jpeg"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/Gerald Situmorang.jpeg" alt="Gerald Situmorang - Gitar dan Vokal" loading="lazy"
                        width="250" height="250" style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">Gerald Situmorang</h3>
                    <p style="color: #aaa;"><i class="fas fa-guitar" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Gitar, vokal</p>
                </article>

                <article class="card personnel-card" data-member="marco" data-hover="images/Marco Steffiano 1.jpeg"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/Marco Steffiano.jpeg" alt="Marco Steffiano - Drum" loading="lazy" width="250"
                        height="250" style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">Marco Steffiano</h3>
                    <p style="color: #aaa;"><i class="fas fa-drum" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Drum</p>
                </article>

                <article class="card personnel-card" data-member="asteriska" data-hover="images/Asteriska 1.jpeg"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/Asteriska.jpeg" alt="Asteriska - Vokal" loading="lazy" width="250" height="250"
                        style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">Asteriska</h3>
                    <p style="color: #aaa;"><i class="fas fa-microphone" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Vokal</p>
                </article>

                <article class="card personnel-card" data-member="puti" data-hover="images/Puti Chitara 1.jpeg"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/Puti Chitara.jpeg" alt="Puti Chitara - Bass dan Vokal" loading="lazy" width="250"
                        height="250" style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">Puti Chitara</h3>
                    <p style="color: #aaa;"><i class="fas fa-guitar" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Bass, vokal</p>
                </article>

                <article class="card personnel-card" data-member="tj" data-hover="images/TJ Kusuma.png"
                    style="flex: 0 1 calc(33.333% - 25px); min-width: 280px; box-sizing: border-box;">
                    <img src="images/TJ Kusuma.png" alt="TJ Kusuma - Gitar" loading="lazy" width="250" height="250"
                        style="width: 100%; height: auto; border-radius: 6px;">
                    <h3 style="margin: 15px 0 5px 0;">TJ Kusuma</h3>
                    <p style="color: #aaa;"><i class="fas fa-guitar" aria-hidden="true"
                            style="color: var(--primary-color, #e63946);"></i> Gitar</p>
                </article>
            </div>
        </section>

        <!-- SECTION 4: ANATOMI SUARA (RE-DESIGNED & TIDY) -->
        <section class="section fade-in text-center" aria-labelledby="ciri-khas">
            <h2 id="ciri-khas" style="margin-bottom: 10px;">Anatomi Suara</h2>
            <p style="color: #aaa; margin-bottom: 30px;">Karakteristik unik yang membentuk identitas musikalitas
                Barasuara.</p>

            <div class="anatomy-grid">
                <div class="anatomy-item"
                    style="background: rgba(255,255,255,0.03); padding: 20px; border-left: 3px solid var(--primary-color, #e63946); border-radius: 0 6px 6px 0;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-pen-nib" style="color: var(--primary-color, #e63946);"></i> Lirik Metaforis
                    </h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc; line-height: 1.4;">Penyusunan lirik puitis dan
                        penggunaan metafora tingkat tinggi di setiap bait lagu.</p>
                </div>

                <div class="anatomy-item"
                    style="background: rgba(255,255,255,0.03); padding: 20px; border-left: 3px solid var(--primary-color, #e63946); border-radius: 0 6px 6px 0;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-users" style="color: var(--primary-color, #e63946);"></i> Harmonisasi Silang
                    </h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc; line-height: 1.4;">Paduan bersahutan dan
                        harmoni vokal yang presisi antara karakter vokal pria dan wanita.</p>
                </div>

                <div class="anatomy-item"
                    style="background: rgba(255,255,255,0.03); padding: 20px; border-left: 3px solid var(--primary-color, #e63946); border-radius: 0 6px 6px 0;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-sliders-h" style="color: var(--primary-color, #e63946);"></i> Ritme Asimetris
                    </h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc; line-height: 1.4;">Aransemen instrumen berlapis
                        yang dikombinasikan dengan ketukan ritme tidak biasa.</p>
                </div>

                <div class="anatomy-item"
                    style="background: rgba(255,255,255,0.03); padding: 20px; border-left: 3px solid var(--primary-color, #e63946); border-radius: 0 6px 6px 0;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-fire" style="color: var(--primary-color, #e63946);"></i> Energi Panggung
                    </h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc; line-height: 1.4;">Penyampaian performa live
                        yang meledak-ledak, intens, dan penuh luapan emosi.</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content text-center">
            <div class="social-links" style="justify-content: center; display: flex; gap: 20px; margin-bottom: 15px;">
                <a href="https://www.instagram.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="https://open.spotify.com/intl-id/artist/5RbI1PF4n02wWKmgjk7hAE?si=zHUmzxMMSkq_e1ENO2OcPQ&nd=1&dlsi=a7cf13a4230f4098"
                    target="_blank" rel="noopener noreferrer" aria-label="Spotify"><i class="fab fa-spotify"></i></a>
            </div>
            <p>© 2026 Barasuara Fans Project. Dikelola Oleh SIG Group.</p>
        </div>
    </footer>

    <script src="lib/script.js" defer></script>
</body>

</html>

