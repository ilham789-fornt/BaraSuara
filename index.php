<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

// Proses form langganan newsletter (UPDATE tabel users jika email terdaftar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    verify_csrf();
    $nlEmail = strtolower(trim((string) $_POST['newsletter_email']));
    if (filter_var($nlEmail, FILTER_VALIDATE_EMAIL)) {
        $token = bin2hex(random_bytes(16));
        $stmt = db()->prepare(
            'UPDATE users SET is_subscribed = 1, subscribe_token = COALESCE(subscribe_token, ?), subscribed_at = NOW() WHERE email = ?'
        );
        $stmt->execute([$token, $nlEmail]);
        flash('success', 'Terima kasih! Email ' . $nlEmail . ' telah terdaftar di buletin Barasuara.');
    } else {
        flash('error', 'Format email langganan tidak valid.');
    }
    redirect('index.php#newsletter-heading');
}

// SELECT: Berita terbaru dari tabel news_posts
$newsList = db()->query(
    'SELECT np.*, a.username AS author_name
     FROM news_posts np
     LEFT JOIN admins a ON a.id = np.author_id
     ORDER BY np.published_at DESC, np.id DESC
     LIMIT 6'
)->fetchAll();

// SELECT: Daftar album dari tabel albums
$albums = db()->query('SELECT * FROM albums ORDER BY tahun_rilis ASC, id ASC')->fetchAll();

// SELECT: Jadwal tur mendatang dari tabel tours
$upcomingTours = db()->query(
    'SELECT * FROM tours WHERE status_event = 1 ORDER BY tanggal_konser ASC LIMIT 3'
)->fetchAll();

$albumFocusSlug = static function (string $title): string {
    $clean = preg_replace('/\bdan\b/i', '', $title);
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $clean), '-'));
    return $slug !== '' ? $slug : 'album';
};

$pageTitle = 'Beranda';
$pageDescription = 'Portal Resmi Fans Barasuara - Energi, Emosi, Eksplorasi. Dapatkan info terbaru, jadwal tur, dan diskografi lengkap.';
$activePage = 'index';
require __DIR__ . '/includes/layout_header.php';
?>

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
                <?php if (!$newsList): ?>
                    <p class="text-center" style="color: #888;">Belum ada berita yang dipublikasikan.</p>
                <?php endif; ?>

                <?php foreach ($newsList as $news):
                    $isTourNews = strtolower((string) $news['kategori']) === 'tour';
                    $excerpt = ringkasan_teks($news['konten'], 22, 130);
                    ?>
                    <article class="card news-card" style="flex: 1 1 380px; min-width: 280px; text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                            <time class="news-date" datetime="<?= e(date('Y-m-d', strtotime($news['published_at']))) ?>"
                                style="color: var(--secondary-color, #ff6a3d); font-size: 0.85rem;">
                                <?= e(tanggal_id($news['published_at'])) ?>
                            </time>
                            <span class="badge badge-info"><?= e($news['kategori']) ?></span>
                        </div>
                        <h3 style="margin: 10px 0; font-size: 1.15rem; line-height: 1.35;"><?= e($news['judul']) ?></h3>
                        <p style="color: #bbb; font-size: 0.9rem; line-height: 1.5; margin-bottom: 15px;">
                            <?= e($excerpt) ?>
                        </p>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <button type="button" class="btn-read-news"
                                data-title="<?= e($news['judul']) ?>"
                                data-category="<?= e($news['kategori']) ?>"
                                data-date="<?= e(tanggal_id($news['published_at'])) ?>"
                                data-content="<?= e($news['konten']) ?>"
                                style="background: none; border: none; padding: 0; color: var(--secondary-color, #ff6a3d); font-weight: 700; cursor: pointer; font-size: 0.9rem;">
                                Selengkapnya &rarr;
                            </button>
                            <?php if ($isTourNews): ?>
                                <a href="tour.php" class="muted-label" style="text-decoration: underline; font-size: 0.8rem; color: #888;">
                                    Beli Tiket
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section fade-in" aria-labelledby="music-heading">
            <div class="section-header text-center">
                <h2 id="music-heading">Musik Terbaru</h2>
                <p class="subtitle">Dengarkan kembali ledakan karya masa lalu dan masa kini.</p>
            </div>

            <div class="music-grid"
                style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; max-width: 1100px; margin: 0 auto;">
                <?php foreach ($albums as $album):
                    $slug = $albumFocusSlug($album['judul_album']);
                    $cover = !empty($album['cover_url']) ? $album['cover_url'] : 'images/Taifun.png';
                    $isJalaran = stripos($album['judul_album'], 'Jalaran Sadrah') !== false;
                    ?>
                    <article class="card" style="flex: 1 1 280px; max-width: 320px; box-sizing: border-box;">
                        <div class="card-img-placeholder" style="margin-bottom: 15px;">
                            <img src="<?= e($cover) ?>" alt="Cover Album <?= e($album['judul_album']) ?>" width="250"
                                height="250" loading="lazy"
                                style="margin: 0 auto; border-radius: 6px; width: 100%; height: auto;<?= $isJalaran ? ' filter: hue-rotate(180deg);' : '' ?>">
                        </div>
                        <h3 style="margin-bottom: 6px;"><?= e($album['judul_album']) ?></h3>
                        <p style="color: #888; font-size: 0.85rem; margin: 0 0 15px;"><?= (int) $album['tahun_rilis'] ?></p>
                        <a href="music.php?focus=<?= e($slug) ?>" class="btn"
                            style="width: 100%; padding: 10px 0; font-size: 0.9rem;">Lihat Album</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section fade-in" aria-labelledby="tour-heading">
            <div class="section-header text-center">
                <h2 id="tour-heading">Jadwal Tour Mendatang</h2>
                <p class="subtitle">Amankan barisan depanmu.</p>
            </div>

            <div class="tour-container"
                style="max-width: 750px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($upcomingTours as $tour): ?>
                    <article class="tour-card"
                        style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); padding: 15px 25px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap; gap: 15px;">
                        <div class="tour-date" style="text-align: center; min-width: 60px;">
                            <time datetime="<?= e($tour['tanggal_konser']) ?>"
                                style="display: flex; flex-direction: column; line-height: 1.1;">
                                <span class="day"
                                    style="font-size: 1.8rem; font-weight: 800; color: var(--secondary-color, #ff6a3d);">
                                    <?= date('j', strtotime($tour['tanggal_konser'])) ?>
                                </span>
                                <span class="month"
                                    style="font-size: 0.8rem; text-transform: uppercase; color: #888;">
                                    <?= e(bulan_singkat($tour['tanggal_konser'])) ?>
                                </span>
                            </time>
                        </div>
                        <div class="tour-details" style="flex: 1 1 200px;">
                            <h3 style="margin: 0; font-size: 1.2rem;"><?= e($tour['kota']) ?></h3>
                            <p style="margin: 2px 0 0; font-size: 0.85rem; color: #888;"><?= e($tour['venue']) ?></p>
                        </div>
                        <div class="tour-action">
                            <a href="tour.php#<?= e(strtolower($tour['kota'])) ?>" class="btn btn-sm"
                                aria-label="Lihat informasi tiket tur <?= e($tour['kota']) ?>"
                                style="padding: 8px 20px;">Info Tiket</a>
                        </div>
                    </article>
                <?php endforeach; ?>
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
                <form method="post" action="index.php#newsletter-heading" class="form-group"
                    style="display: flex; flex-direction: row; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?= csrf_field() ?>
                    <label for="newsletter-email" class="visually-hidden">Alamat Email</label>
                    <input type="email" id="newsletter-email" name="newsletter_email" placeholder="Email Anda..." required
                        value="<?= e(current_user()['email'] ?? '') ?>"
                        style="flex: 1; min-width: 250px; max-width: 380px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; color: white;">
                    <button type="submit" class="btn" style="padding: 12px 28px;">Langganan</button>
                </form>
                <p id="newsletter-status" class="form-status" role="status" aria-live="polite"></p>
            </div>
        </section>

        <!-- Modal Popup Baca Berita Lengkap -->
        <div id="news-modal" class="news-modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="news-modal-card">
                <div class="news-modal-header">
                    <div>
                        <span id="news-modal-category" class="badge badge-info"></span>
                        <time id="news-modal-date" style="display: block; margin-top: 6px; font-size: 0.85rem; color: var(--secondary-color, #ff6a3d);"></time>
                        <h3 id="news-modal-title"></h3>
                    </div>
                    <button type="button" class="news-modal-close" aria-label="Tutup Modal">&times;</button>
                </div>
                <div id="news-modal-body" class="news-modal-body"></div>
                <div class="news-modal-footer">
                    <button type="button" class="btn btn-sm close-modal-btn">Tutup</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('news-modal');
            const modalTitle = document.getElementById('news-modal-title');
            const modalCategory = document.getElementById('news-modal-category');
            const modalDate = document.getElementById('news-modal-date');
            const modalBody = document.getElementById('news-modal-body');
            const closeBtns = modal ? modal.querySelectorAll('.news-modal-close, .close-modal-btn') : [];

            function openNewsModal(title, category, date, content) {
                if (!modal) return;
                modalTitle.textContent = title;
                modalCategory.textContent = category;
                modalDate.textContent = date;
                modalBody.textContent = content;
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeNewsModal() {
                if (!modal) return;
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('.btn-read-news').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openNewsModal(
                        this.dataset.title,
                        this.dataset.category,
                        this.dataset.date,
                        this.dataset.content
                    );
                });
            });

            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', closeNewsModal);
            });

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeNewsModal();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && modal.classList.contains('active')) {
                        closeNewsModal();
                    }
                });
            }
        });
        </script>
    </main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
