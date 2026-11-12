<?php
/**
 * CRUD Tabel `albums` dan `tracks` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
$pdo = db();

/** Ekstrak 11 karakter ID video YouTube dari URL, Iframe, atau ID murni */
function clean_youtube_id(string $input): string
{
    $input = trim($input);
    if ($input === '')
        return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input))
        return $input;
    if (preg_match('/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/i', $input, $m))
        return $m[1];
    if (preg_match('/src=["\'](?:https?:)?\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/([\w-]{11})/i', $input, $m))
        return $m[1];
    if (preg_match('/([\w-]{11})/', $input, $m))
        return $m[1];
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

/** Menangani upload file cover dari file explorer */
function handle_cover_upload(?array $file, string $fallback = 'images/Taifun.png'): string
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $fallback;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal mengunggah file gambar (Error code: ' . $file['error'] . ')');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file gambar maksimal 5 MB.');
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format gambar tidak didukung. Harap gunakan format PNG, JPG, JPEG, atau WebP.');
    }

    $uploadDir = __DIR__ . '/../images/covers';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'cover_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Gagal memindahkan file yang diunggah ke folder server.');
    }

    return 'images/covers/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_album':
                $coverUrl = handle_cover_upload(
                    $_FILES['cover_file'] ?? null,
                    trim((string) ($_POST['cover_url'] ?? 'images/Taifun.png'))
                );
                $pdo->prepare(
                    'INSERT INTO albums (judul_album, tahun_rilis, cover_url, deskripsi) VALUES (?, ?, ?, ?)'
                )->execute([
                            trim((string) $_POST['judul_album']),
                            (int) $_POST['tahun_rilis'],
                            $coverUrl,
                            trim((string) $_POST['deskripsi']),
                        ]);
                flash('success', 'Album baru berhasil ditambahkan beserta gambar covernya.');
                break;

            case 'update_album':
                $oldCover = trim((string) ($_POST['cover_url'] ?? 'images/Taifun.png'));
                $coverUrl = handle_cover_upload($_FILES['cover_file'] ?? null, $oldCover);
                $pdo->prepare(
                    'UPDATE albums SET judul_album = ?, tahun_rilis = ?, cover_url = ?, deskripsi = ? WHERE id = ?'
                )->execute([
                            trim((string) $_POST['judul_album']),
                            (int) $_POST['tahun_rilis'],
                            $coverUrl,
                            trim((string) $_POST['deskripsi']),
                            (int) $_POST['id'],
                        ]);
                flash('success', 'Data album berhasil diperbarui.');
                break;

            case 'delete_album':
                $pdo->prepare('DELETE FROM albums WHERE id = ?')->execute([(int) $_POST['id']]);
                flash('success', 'Album beserta daftar lagunya berhasil dihapus.');
                break;

            case 'create_track':
                $ytId = clean_youtube_id((string) $_POST['youtube_url']);
                $pdo->prepare(
                    'INSERT INTO tracks (album_id, judul_lagu, track_number, youtube_url) VALUES (?, ?, ?, ?)'
                )->execute([
                            (int) $_POST['album_id'],
                            trim((string) $_POST['judul_lagu']),
                            (int) $_POST['track_number'],
                            $ytId,
                        ]);
                flash('success', 'Lagu baru berhasil ditambahkan.');
                break;

            case 'update_track':
                $ytId = clean_youtube_id((string) $_POST['youtube_url']);
                $pdo->prepare(
                    'UPDATE tracks SET album_id = ?, judul_lagu = ?, track_number = ?, youtube_url = ? WHERE id = ?'
                )->execute([
                            (int) $_POST['album_id'],
                            trim((string) $_POST['judul_lagu']),
                            (int) $_POST['track_number'],
                            $ytId,
                            (int) $_POST['id'],
                        ]);
                flash('success', 'Data lagu berhasil diperbarui.');
                break;

            case 'delete_track':
                $pdo->prepare('DELETE FROM tracks WHERE id = ?')->execute([(int) $_POST['id']]);
                flash('success', 'Lagu berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('music.php#tracks-section');
}

$albums = $pdo->query(
    'SELECT a.*, (SELECT COUNT(*) FROM tracks t WHERE t.album_id = a.id) AS track_count
     FROM albums a ORDER BY a.tahun_rilis ASC, a.id ASC'
)->fetchAll();

$tracks = $pdo->query(
    'SELECT t.*, a.judul_album
     FROM tracks t JOIN albums a ON a.id = t.album_id
     ORDER BY a.tahun_rilis ASC, t.track_number ASC, t.id ASC'
)->fetchAll();

$editAlbum = null;
if (isset($_GET['edit_album'])) {
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_album']]);
    $editAlbum = $stmt->fetch() ?: null;
}

$editTrack = null;
if (isset($_GET['edit_track'])) {
    $stmt = $pdo->prepare('SELECT * FROM tracks WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_track']]);
    $editTrack = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola Album & Lagu';
$adminNav = 'music';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Katalog Diskografi (Album &amp; Lagu)</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>albums</code> dan <code>tracks</code> yang tampil di halaman Musik.</p> -->
        </div>
    </div>

    <!-- ==================== BAGIAN 1: TABEL ALBUMS ==================== -->
    <div class="admin-two-col" style="margin-bottom: 35px;">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editAlbum ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editAlbum ? 'Edit Album #' . (int) $editAlbum['id'] : 'Tambah Album Baru' ?>
            </h3>
            <form method="post" enctype="multipart/form-data" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editAlbum ? 'update_album' : 'create_album' ?>">
                <?php if ($editAlbum): ?>
                    <input type="hidden" name="id" value="<?= (int) $editAlbum['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Judul Album</label>
                        <input type="text" name="judul_album" required maxlength="150"
                            value="<?= e($editAlbum['judul_album'] ?? '') ?>" placeholder="Judul Album">
                    </div>
                    <div class="form-group">
                        <label>Tahun Rilis</label>
                        <input type="number" name="tahun_rilis" required min="1990" max="2035"
                            value="<?= (int) ($editAlbum['tahun_rilis'] ?? date('Y')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Gambar Cover Album</label>
                    <input type="file" name="cover_file" id="cover_file_input"
                        accept="image/png, image/jpeg, image/jpg, image/webp" class="form-control mb-2">
                    <small class="muted-label d-block mb-2">
                        Pilih file gambar cover langsung dari device Anda (format PNG, JPG, WebP max 5MB).
                    </small>

                    <!-- Preview & Info Path -->
                    <div class="d-flex align-items-center gap-3 p-2 rounded"
                        style="background: var(--bs-tertiary-bg); border: 1px dashed var(--bs-border-color);">
                        <?php
                        $curCover = $editAlbum['cover_url'] ?? 'images/Taifun.png';
                        $imgSrc = str_starts_with($curCover, 'http') ? $curCover : '../' . $curCover;
                        ?>
                        <img id="cover_preview" src="<?= e($imgSrc) ?>" alt="Preview"
                            style="width: 58px; height: 58px; object-fit: cover; border-radius: 6px; border: 1px solid var(--bs-border-color);">
                        <div class="flex-grow-1">
                            <span class="small fw-semibold d-block text-secondary">Path Gambar Saat Ini:</span>
                            <input type="text" name="cover_url" id="cover_url_input" maxlength="255"
                                value="<?= e($curCover) ?>" class="form-control form-control-sm mt-1"
                                placeholder="images/Taifun.png">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Deskripsi Album</label>
                    <textarea name="deskripsi" rows="3" required><?= e($editAlbum['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editAlbum ? 'Simpan Album' : 'Tambah Album' ?></button>
                    <?php if ($editAlbum): ?>
                        <a href="music.php" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-compact-disc"></i> Daftar Album</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Judul Album</th>
                            <th>Tahun</th>
                            <th>Lagu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albums as $al):
                            $coverSrc = str_starts_with((string) $al['cover_url'], 'http') ? $al['cover_url'] : '../' . $al['cover_url'];
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= e($coverSrc) ?>" alt="<?= e($al['judul_album']) ?>"
                                        style="width: 42px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--bs-border-color);">
                                </td>
                                <td>
                                    <strong><?= e($al['judul_album']) ?></strong><br>
                                    <small
                                        class="muted-label"><?= e(mb_strimwidth((string) $al['deskripsi'], 0, 50, '...')) ?></small>
                                </td>
                                <td><?= (int) $al['tahun_rilis'] ?></td>
                                <td><span class="badge text-bg-primary rounded-pill"><?= (int) $al['track_count'] ?>
                                        lagu</span></td>
                                <td class="table-actions">
                                    <a href="music.php?edit_album=<?= (int) $al['id'] ?>" class="btn-xs btn-outline"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus album <?= e($al['judul_album']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_album">
                                        <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== BAGIAN 2: TABEL TRACKS ==================== -->
    <div id="tracks-section" class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editTrack ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editTrack ? 'Edit Lagu #' . (int) $editTrack['id'] : 'Tambah Lagu Baru' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editTrack ? 'update_track' : 'create_track' ?>">
                <?php if ($editTrack): ?>
                    <input type="hidden" name="id" value="<?= (int) $editTrack['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Pilih Album</label>
                        <select name="album_id" id="track_album_select" required>
                            <?php foreach ($albums as $al): ?>
                                <option value="<?= (int) $al['id'] ?>" <?= ((int) ($editTrack['album_id'] ?? 0) === (int) $al['id']) ? 'selected' : '' ?>>
                                    <?= e($al['judul_album']) ?> (<?= (int) $al['tahun_rilis'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nomor Urut Track</label>
                        <input type="number" name="track_number" id="track_number_input" required min="1" max="50"
                            value="<?= (int) ($editTrack['track_number'] ?? 1) ?>" <?= $editTrack ? 'data-is-edit="1"' : '' ?>>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Judul Lagu</label>
                        <input type="text" name="judul_lagu" required maxlength="150"
                            value="<?= e($editTrack['judul_lagu'] ?? '') ?>" placeholder="Bahas Bahasa">
                    </div>
                    <div class="form-group">
                        <label>Link Lengkap atau ID Video YouTube</label>
                        <input type="text" name="youtube_url" required maxlength="255"
                            value="<?= e($editTrack['youtube_url'] ?? '') ?>"
                            placeholder="Cth: https://youtu.be/KfR2iLLan6U atau KfR2iLLan6U">
                        <small class="muted-label" style="display: block; margin-top: 4px;">
                            Bisa tempel <strong>Link Lengkap</strong> (<code>https://youtu.be/...</code>), kode
                            <strong>Sematkan (iframe)</strong>, atau cukup <strong>11 karakter ID</strong> videonya
                            saja.
                        </small>
                    </div>
                </div>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editTrack ? 'Simpan Lagu' : 'Tambah Lagu' ?></button>
                    <?php if ($editTrack): ?>
                        <a href="music.php#tracks-section" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">
                <h3 class="panel-title m-0 border-0 p-0">
                    <i class="fas fa-music"></i> <span id="tracks_header_title">Daftar Lagu (<code>tracks</code>)</span>
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-secondary mb-0" for="table_album_filter">Filter:</label>
                    <select id="table_album_filter" class="form-select form-select-sm"
                        style="width: auto; max-width: 220px;">
                        <option value="all">-- Semua Album (<?= count($tracks) ?>) --</option>
                        <?php foreach ($albums as $al): ?>
                            <option value="<?= (int) $al['id'] ?>">
                                <?= e($al['judul_album']) ?> (<?= (int) $al['track_count'] ?> lagu)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="admin-table-wrap" style="max-height: 440px; overflow-y: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul Lagu &amp; Album</th>
                            <th>YouTube ID</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="track_table_body">
                        <?php foreach ($tracks as $tr): ?>
                            <tr class="track-row" data-album-id="<?= (int) $tr['album_id'] ?>"
                                data-album-title="<?= e($tr['judul_album']) ?>"
                                data-track-number="<?= (int) $tr['track_number'] ?>">
                                <td class="fw-bold"><?= (int) $tr['track_number'] ?></td>
                                <td>
                                    <strong><?= e($tr['judul_lagu']) ?></strong><br>
                                    <small
                                        class="muted-label badge text-bg-secondary opacity-75"><?= e($tr['judul_album']) ?></small>
                                </td>
                                <td>
                                    <code><?= e($tr['youtube_url']) ?></code>
                                    <a href="https://youtu.be/<?= e($tr['youtube_url']) ?>" target="_blank" rel="noopener"
                                        class="text-secondary ms-1" title="Buka di YouTube">
                                        <i class="fas fa-external-link-alt" style="font-size: 0.75rem;"></i>
                                    </a>
                                </td>
                                <td class="table-actions">
                                    <a href="music.php?edit_track=<?= (int) $tr['id'] ?>#tracks-section"
                                        class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus lagu <?= e($tr['judul_lagu']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_track">
                                        <input type="hidden" name="id" value="<?= (int) $tr['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="empty_filter_notice" style="display: none;">
                            <td colspan="4" class="text-center py-4 text-secondary">
                                <i class="fas fa-compact-disc fs-4 d-block mb-1"></i>
                                Belum ada lagu pada album ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Live Preview Cover File Upload
        const coverFileInput = document.getElementById('cover_file_input');
        const coverPreview = document.getElementById('cover_preview');
        const coverUrlInput = document.getElementById('cover_url_input');

        if (coverFileInput && coverPreview) {
            coverFileInput.addEventListener('change', function (e) {
                const file = e.target.files && e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        coverPreview.src = evt.target.result;
                    };
                    reader.readAsDataURL(file);
                    if (coverUrlInput) {
                        coverUrlInput.value = 'images/covers/' + file.name;
                    }
                }
            });
        }

        // 2. Filter Daftar Lagu Berdasarkan Album yang Dipilih
        const albumSelectForm = document.getElementById('track_album_select');
        const albumFilterTable = document.getElementById('table_album_filter');
        const trackRows = document.querySelectorAll('.track-row');
        const trackNumberInput = document.getElementById('track_number_input');
        const tracksHeaderTitle = document.getElementById('tracks_header_title');
        const emptyNotice = document.getElementById('empty_filter_notice');

        function filterTracksByAlbum(albumId, isFormTriggered = false) {
            let visibleCount = 0;
            let maxTrackNum = 0;
            let selectedTitle = '';

            trackRows.forEach(row => {
                const rowAlbumId = row.getAttribute('data-album-id');
                const rowTrackNum = parseInt(row.getAttribute('data-track-number') || '0', 10);
                const rowAlbumTitle = row.getAttribute('data-album-title');

                if (albumId === 'all' || rowAlbumId === String(albumId)) {
                    row.style.display = '';
                    visibleCount++;
                    if (rowTrackNum > maxTrackNum) {
                        maxTrackNum = rowTrackNum;
                    }
                    if (!selectedTitle && rowAlbumTitle) {
                        selectedTitle = rowAlbumTitle;
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyNotice) {
                emptyNotice.style.display = (visibleCount === 0) ? '' : 'none';
            }

            if (tracksHeaderTitle) {
                if (albumId === 'all') {
                    tracksHeaderTitle.innerHTML = `Daftar Lagu (<code>tracks</code> &mdash; ${visibleCount} lagu)`;
                } else {
                    const opt = albumFilterTable ? albumFilterTable.querySelector(`option[value="${albumId}"]`) : null;
                    const albumName = opt ? opt.textContent.replace(/\s*\(\d+\s*lagu\)/i, '').trim() : (selectedTitle || 'Album Terpilih');
                    tracksHeaderTitle.innerHTML = `Daftar Lagu &mdash; <strong>${albumName}</strong> (${visibleCount} lagu)`;
                }
            }

            // Auto-suggest nomor urut track berikutnya jika menambah lagu baru
            if (isFormTriggered && trackNumberInput && !trackNumberInput.getAttribute('data-is-edit')) {
                trackNumberInput.value = maxTrackNum + 1;
            }
        }

        // Listener saat memilih album di form Tambah/Edit Lagu
        if (albumSelectForm) {
            albumSelectForm.addEventListener('change', function () {
                const chosenId = this.value;
                if (albumFilterTable) {
                    albumFilterTable.value = chosenId;
                }
                filterTracksByAlbum(chosenId, true);
            });
        }

        // Listener saat filter di atas tabel diubah secara manual
        if (albumFilterTable) {
            albumFilterTable.addEventListener('change', function () {
                filterTracksByAlbum(this.value, false);
            });
        }

        // Default aktif saat halaman pertama kali dibuka
        if (albumSelectForm) {
            const initialVal = albumSelectForm.value;
            if (albumFilterTable) {
                albumFilterTable.value = initialVal;
            }
            filterTracksByAlbum(initialVal, false);
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>