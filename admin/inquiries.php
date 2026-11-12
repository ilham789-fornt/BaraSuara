<?php
/**
 * CRUD Tabel `contact_inquiries` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_inquiry':
                $tgl = !empty($_POST['tanggal_acara_usulan']) ? (string) $_POST['tanggal_acara_usulan'] : null;
                $pdo->prepare(
                    'INSERT INTO contact_inquiries (nama_pengirim, email, no_whatsapp, kategori_pesan, tanggal_acara_usulan, isi_pesan)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                            trim((string) $_POST['nama_pengirim']),
                            strtolower(trim((string) $_POST['email'])),
                            trim((string) $_POST['no_whatsapp']),
                            trim((string) $_POST['kategori_pesan']),
                            $tgl,
                            trim((string) $_POST['isi_pesan']),
                        ]);
                flash('success', 'Data pesan kontak berhasil ditambahkan.');
                break;

            case 'update_inquiry':
                $tgl = !empty($_POST['tanggal_acara_usulan']) ? (string) $_POST['tanggal_acara_usulan'] : null;
                $pdo->prepare(
                    'UPDATE contact_inquiries
                     SET nama_pengirim = ?, email = ?, no_whatsapp = ?, kategori_pesan = ?, tanggal_acara_usulan = ?, isi_pesan = ?
                     WHERE id = ?'
                )->execute([
                            trim((string) $_POST['nama_pengirim']),
                            strtolower(trim((string) $_POST['email'])),
                            trim((string) $_POST['no_whatsapp']),
                            trim((string) $_POST['kategori_pesan']),
                            $tgl,
                            trim((string) $_POST['isi_pesan']),
                            (int) $_POST['id'],
                        ]);
                flash('success', 'Data pesan kontak berhasil diperbarui.');
                break;

            case 'delete_inquiry':
                $pdo->prepare('DELETE FROM contact_inquiries WHERE id = ?')->execute([(int) $_POST['id']]);
                flash('success', 'Pesan kontak berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('inquiries.php');
}

$inquiries = $pdo->query('SELECT * FROM contact_inquiries ORDER BY created_at DESC, id DESC')->fetchAll();

$editInq = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM contact_inquiries WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editInq = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola Pesan Kontak';
$adminNav = 'inquiries';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Pesan Masuk &amp; Booking</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>contact_inquiries</code>.</p> -->
        </div>
    </div>

    <div class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editInq ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editInq ? 'Edit Pesan Kontak #' . (int) $editInq['id'] : 'Catat Pesan / Inquiry Baru' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editInq ? 'update_inquiry' : 'create_inquiry' ?>">
                <?php if ($editInq): ?>
                    <input type="hidden" name="id" value="<?= (int) $editInq['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama Pengirim / Instansi</label>
                    <input type="text" name="nama_pengirim" required maxlength="150"
                        value="<?= e($editInq['nama_pengirim'] ?? '') ?>" placeholder="Gusty Faqikh - BEM Polnep">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required maxlength="150"
                            value="<?= e($editInq['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp</label>
                        <input type="text" name="no_whatsapp" required maxlength="25"
                            value="<?= e($editInq['no_whatsapp'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Kategori Keperluan</label>
                        <select name="kategori_pesan" required>
                            <?php foreach (['booking', 'media', 'partnership', 'general'] as $kat): ?>
                                <option value="<?= $kat ?>" <?= (($editInq['kategori_pesan'] ?? 'booking') === $kat) ? 'selected' : '' ?>>
                                    <?= strtoupper($kat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Acara Usulan (Opsional)</label>
                        <input type="date" name="tanggal_acara_usulan"
                            value="<?= e($editInq['tanggal_acara_usulan'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Isi Pesan</label>
                    <textarea name="isi_pesan" rows="4" required><?= e($editInq['isi_pesan'] ?? '') ?></textarea>
                </div>

                <div class="status-actions">
                    <button type="submit" class="btn-sm"><i class="fas fa-save"></i>
                        <?= $editInq ? 'Simpan Perubahan' : 'Simpan Inquiry' ?></button>
                    <?php if ($editInq): ?>
                        <a href="inquiries.php" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-envelope-open-text"></i> Daftar Pesan Masuk</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pengirim &amp; Kontak</th>
                            <th>Kategori</th>
                            <th>Isi Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$inquiries): ?>
                            <tr>
                                <td colspan="4" class="muted-label">Belum ada pesan masuk.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td>
                                    <strong><?= e($inq['nama_pengirim']) ?></strong><br>
                                    <small class="muted-label"><?= e($inq['email']) ?> &middot;
                                        <?= e($inq['no_whatsapp']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($inq['kategori_pesan']) ?></span>
                                    <?php if ($inq['tanggal_acara_usulan']): ?>
                                        <br><small class="muted-label">Acara: <?= e($inq['tanggal_acara_usulan']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($inq['isi_pesan']) ?></td>
                                <td class="table-actions">
                                    <a href="inquiries.php?edit=<?= (int) $inq['id'] ?>" class="btn-xs btn-outline"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post"
                                        onsubmit="return confirm('Hapus pesan dari <?= e($inq['nama_pengirim']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_inquiry">
                                        <input type="hidden" name="id" value="<?= (int) $inq['id'] ?>">
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