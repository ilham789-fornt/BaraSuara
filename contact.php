<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Hubungi manajemen Barasuara untuk urusan bisnis, pemesanan panggung, atau pertanyaan pers.">
    <title>Kontak - Barasuara | Portal Fans Resmi</title>

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
        <input type="checkbox" id="nav-toggle-contact" class="hamburger-toggle" aria-hidden="true">
        <label for="nav-toggle-contact" class="hamburger-btn" aria-label="Buka/tutup menu navigasi">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <nav aria-label="Navigasi Utama">
            <a href="index.php">Beranda</a>
            <a href="about.php">Tentang</a>
            <a href="music.php">Musik</a>
            <a href="tour.php">Jadwal Tour</a>
            <a href="contact.php" class="active" aria-current="page">Kontak</a>
        </nav>
    </header>

    <main style="padding-bottom: 60px;">
        <section class="section fade-in" aria-labelledby="contact-heading">
            <div class="contact-container"
                style="max-width: 650px; margin: 0 auto; background: rgba(255, 255, 255, 0.03); padding: 40px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">

                <div class="contact-header text-center" style="margin-bottom: 35px;">
                    <h2 id="contact-heading" style="font-size: 2rem; margin-bottom: 12px; display: inline-block;">
                        Hubungi Kami</h2>
                    <p style="color: #aaa; font-size: 0.95rem; line-height: 1.5; max-width: 500px; margin: 0 auto;">
                        Untuk kebutuhan <em>booking</em>, tawaran kolaborasi, atau pertanyaan seputar tur, silakan
                        kirimkan pesan melalui formulir di bawah ini.
                    </p>
                </div>

                <form id="contact-form" class="contact-form" style="display: flex; flex-direction: column; gap: 20px;">

                    <div class="visually-hidden" aria-hidden="true">
                        <label for="bot-field">Tinggalkan kosong jika Anda manusia:</label>
                        <input type="text" id="bot-field" name="bot-field" tabindex="-1">
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="user-name" style="font-size: 0.9rem; font-weight: 600; color: #ccc;">Nama Lengkap /
                            Instansi</label>
                        <input type="text" id="user-name" name="name" required
                            placeholder="Cth: Gusty Faqikh - BEM Polnep"
                            style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; color: #fff; font-family: inherit;">
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="user-email" style="font-size: 0.9rem; font-weight: 600; color: #ccc;">Alamat
                            Email</label>
                        <input type="email" id="user-email" name="email" required placeholder="email@domain.com"
                            style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; color: #fff; font-family: inherit;">
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="message-subject" style="font-size: 0.9rem; font-weight: 600; color: #ccc;">Subjek
                            Keperluan</label>
                        <select id="message-subject" name="subject" required
                            style="background: #111; border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; color: #fff; font-family: inherit; cursor: pointer;">
                            <option value="" disabled selected style="color: #666;">Pilih keperluan...</option>
                            <option value="booking">Booking / Manggung</option>
                            <option value="media">Media / Wawancara</option>
                            <option value="partnership">Sponsorship / Kemitraan</option>
                            <option value="general">Pertanyaan Umum</option>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                        <label for="user-message" style="font-size: 0.9rem; font-weight: 600; color: #ccc;">Pesan
                            Rinci</label>
                        <textarea id="user-message" name="message" rows="5" required
                            placeholder="Tulis rincian pesan Anda di sini..."
                            style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 6px; color: #fff; font-family: inherit; resize: vertical; line-height: 1.5;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block"
                        style="padding: 14px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 10px;">
                        Kirim Pesan <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
                    </button>
                </form>
                <p id="contact-status" class="form-status" role="status" aria-live="polite"></p>

                <div class="contact-direct text-center"
                    style="margin-top: 35px; padding-top: 25px; border-top: 1px solid rgba(255, 255, 255, 0.05)">
                    <p style="color: #888; font-size: 0.9rem; margin-bottom: 10px;">Atau hubungi via email secara
                        langsung:</p>
                    <a href="mailto:booking@barasuara.com" class="direct-email"
                        style="color: var(--secondary-color, #ff6a3d); text-decoration: none; font-weight: 600; font-size: 1.05rem; transition: opacity 0.2s;"><i
                            class="fas fa-envelope" style="margin-right: 8px;"></i> booking@barasuara.com</a>
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
            <p style="margin: 0; font-size: 0.9rem; color: #888;">© 2026 Barasuara Fans Project. Dikelola Oleh SIG
                Group.</p>
        </div>
    </footer>

    <script src="lib/script.js" defer></script>
</body>

</html>

