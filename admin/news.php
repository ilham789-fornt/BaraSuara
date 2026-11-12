<?php
/**
 * CRUD Tabel `news_posts` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
$pdo = db();

$makeSlug = static function (string $title): string {
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    return ($slug !== '' ? $slug : 'berita') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_news':
                $judul = trim((string) $_POST['judul']);
                $slug = trim((string) ($_POST['slug'] ?? ''));
                if ($slug === '') {
                    $slug = $makeSlug($judul);
                }
                $pdo->prepare(
                    'INSERT INTO news_posts (judul, slug, konten, kategori, author_id) VALUES (?, ?, ?, ?, ?)'
                )->execute([
                            $judul,
                            $slug,
                            trim((string) $_POST['konten']),
                            trim((string) $_POST['kategori']),
                            (int) $admin['id'],
                        ]);
                flash('success', 'Berita baru berhasil dipublikasikan.');
                break;

            case 'update_news':
                $pdo->prepare(
                    'UPDATE news_posts SET judul = ?, slug = ?, konten = ?, kategori = ? WHERE id = ?'
                )->execute([
                            trim((string) $_POST['judul']),
                            trim((string) $_POST['slug']),
                            trim((string) $_POST['konten']),
                            trim((string) $_POST['kategori']),
                            (int) $_POST['id'],
                        ]);
                flash('success', 'Berita berhasil diperbarui.');
                break;

            case 'delete_news':
                $pdo->prepare('DELETE FROM news_posts WHERE id = ?')->execute([(int) $_POST['id']]);
                flash('success', 'Berita berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('news.php');
}

$newsList = $pdo->query(
    'SELECT np.*, a.username AS author_name
     FROM news_posts np LEFT JOIN admins a ON a.id = np.author_id
     ORDER BY np.published_at DESC, np.id DESC'
)->fetchAll();

$editNews = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM news_posts WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editNews = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola Berita';
$adminNav = 'news';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Berita &amp; Rilis Resmi</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>news_posts</code> yang tampil di Beranda.</p> -->
        </div>
    </div>

    <div class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editNews ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editNews ? 'Edit Berita #' . (int) $editNews['id'] : 'Tulis Berita Baru' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editNews ? 'update_news' : 'create_news' ?>">
                <?php if ($editNews): ?>
                    <input type="hidden" name="id" value="<?= (int) $editNews['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Judul Berita</label>
                    <input type="text" name="judul" required maxlength="200" value="<?= e($editNews['judul'] ?? '') ?>"
                        placeholder="Judul rilis atau pengumuman tur">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Slug URL (Kosongkan untuk otomatis)</label>
                        <input type="text" name="slug" maxlength="220" value="<?= e($editNews['slug'] ?? '') ?>"
                            placeholder="pengumuman-tur-2026">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" required>
                            <?php foreach (['Tour', 'Update', 'Rilis', 'Merchandise'] as $kat): ?>
                                <option value="<?= $kat ?>" <?= (($editNews['kategori'] ?? 'Update') === $kat) ? 'selected' : '' ?>>
                                    <?= $kat ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konten Lengkap Berita</label>
                    <textarea name="konten" rows="6" required
                        placeholder="Tuliskan seluruh isi berita, pengumuman tur, atau siaran pers secara lengkap di sini..."><?= e($editNews['konten'] ?? '') ?></textarea>
                    <small class="muted-label" style="display: block; margin-top: 4px;">
                        Cukup masukkan seluruh isi berita. Sistem akan secara otomatis memotong beberapa kata awal
                        sebagai ringkasan kartu berita, dan seluruh konten dapat dibaca saat pengunjung menekan tombol
                        &ldquo;Selengkapnya&rdquo;.
                    </small>
                </div>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editNews ? 'Simpan Perubahan' : 'Publikasikan Berita' ?></button>
                    <?php if ($editNews): ?>
                        <a href="news.php" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-newspaper"></i> Daftar Berita</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Judul &amp; Ringkasan</th>
                            <th>Kategori</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsList as $n): ?>
                            <tr>
                                <td>
                                    <strong><?= e($n['judul']) ?></strong><br>
                                    <small class="muted-label"><?= e(ringkasan_teks($n['konten'], 18, 90)) ?></small>
                                </td>
                                <td><span class="badge badge-info"><?= e($n['kategori']) ?></span></td>
                                <td><small class="muted-label"><?= e(tanggal_id($n['published_at'])) ?><br>oleh
                                        <?= e($n['author_name'] ?? 'admin') ?></small></td>
                                <td class="table-actions">
                                    <a href="news.php?edit=<?= (int) $n['id'] ?>" class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus berita ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_news">
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
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
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>