<?php
require_once __DIR__ . '/includes/bootstrap.php';

// SELECT: Ambil seluruh album dan lagu dari database
$albums = db()->query('SELECT * FROM albums ORDER BY tahun_rilis ASC, id ASC')->fetchAll();
$tracksStmt = db()->query('SELECT * FROM tracks ORDER BY album_id ASC, track_number ASC, id ASC');
$tracksByAlbum = [];
foreach ($tracksStmt->fetchAll() as $track) {
    $tracksByAlbum[(int) $track['album_id']][] = $track;
}

$albumFocusSlug = static function (string $title): string {
    $clean = preg_replace('/\bdan\b/i', '', $title);
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $clean), '-'));
    return $slug !== '' ? $slug : 'album';
};

$pageTitle = 'Musik';
$pageDescription = 'Eksplorasi diskografi lengkap Barasuara - Temukan video musik dan audio dari era Taifun hingga Jalaran Sadrah.';
$activePage = 'music';
require __DIR__ . '/includes/layout_header.php';
?>

    <main style="padding-bottom: 80px;">
        <section class="section fade-in" aria-labelledby="diskografi-heading">
            <div class="section-header text-center">
                <h2 id="diskografi-heading">Diskografi Lengkap</h2>
                <p class="subtitle">Eksplorasi sonik dari seluruh ruang rekam Barasuara.</p>
            </div>

            <!-- PLAYER VIDEO UTAMA (Symmetric & Centered) -->
            <div id="video-player-container" class="video-container hidden" aria-live="assertive"
                style="max-width: 900px; margin: 0 auto 50px auto;">
                <div class="video-wrapper">
                    <iframe id="youtube-frame" src="" title="YouTube Video Player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
                <div class="player-controls-ui">
                    <div>
                        <h3 id="playing-title" style="font-size: 1.1rem; margin: 0;">Pilih lagu untuk membuka preview
                            YouTube</h3><a id="youtube-fallback" class="youtube-fallback"
                            href="https://www.youtube.com/@Barasuara" target="_blank" rel="noopener noreferrer">Buka di
                            YouTube</a>
                    </div>
                    <button class="btn-sm close-video-btn" aria-label="Tutup pemutar video">Tutup Player</button>
                </div>
            </div>

            <!-- ALBUM SHOWCASE PLATFORM (DINAMIS DARI TABEL albums & tracks) -->
            <div class="music-showcase"
                style="display: flex; flex-direction: column; gap: 50px; max-width: 1000px; margin: 0 auto;">

                <?php if (!$albums): ?>
                    <p class="text-center" style="color: #888;">Belum ada data album di katalog diskografi.</p>
                <?php endif; ?>

                <?php foreach ($albums as $album):
                    $slug = $albumFocusSlug($album['judul_album']);
                    $cover = !empty($album['cover_url']) ? $album['cover_url'] : 'images/Taifun.png';
                    $albumTracks = $tracksByAlbum[(int) $album['id']] ?? [];
                    ?>
                    <article id="album-<?= e($slug) ?>" class="album-card"
                        style="display: flex; gap: 40px; background: rgba(255,255,255,0.03); padding: 35px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); align-items: center; flex-wrap: wrap;">
                        <div class="album-cover" style="flex: 1 1 250px; max-width: 280px; margin: 0 auto;">
                            <img src="<?= e($cover) ?>" alt="Cover Album <?= e($album['judul_album']) ?>" width="300"
                                height="300"
                                style="width: 100%; height: auto; border-radius: 8px; box-shadow: 0 12px 24px rgba(0,0,0,0.6);">
                        </div>
                        <div class="album-info" style="flex: 2 1 500px;">
                            <h3 style="font-size: 1.8rem; margin: 0 0 10px 0; color: #fff;">
                                <?= e($album['judul_album']) ?> (<?= (int) $album['tahun_rilis'] ?>)
                            </h3>
                            <p style="color: #aaa; margin: 0 0 20px 0; font-size: 0.95rem; line-height: 1.5;">
                                <?= e($album['deskripsi']) ?>
                            </p>

                            <h4 style="margin: 0 0 15px 0; font-size: 1rem; color: var(--secondary-color, #ff6a3d);">
                                Daftar Lagu (klik untuk preview YouTube):
                            </h4>

                            <?php if ($albumTracks): ?>
                                <div class="button-group"
                                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: 100%;">
                                    <?php foreach ($albumTracks as $idx => $track):
                                        $no = $track['track_number'] ?: ($idx + 1);
                                        ?>
                                        <button class="btn play-video-btn"
                                            data-title="<?= e($track['judul_lagu']) ?>"
                                            data-vid="<?= e($track['youtube_url']) ?>"
                                            style="padding: 12px; font-size: 0.85rem; justify-content: flex-start; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                            <i class="fas fa-play-circle"></i> <?= (int) $no ?>. <?= e($track['judul_lagu']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: #777; font-size: 0.9rem;">Belum ada lagu yang ditambahkan pada album ini.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

            </div>
        </section>
    </main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
