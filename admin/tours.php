<?php
/**
 * CRUD Tabel `tours` dan `ticket_tiers` (INSERT, SELECT, UPDATE, DELETE).
 */
require_once __DIR__ . '/includes/auth.php';

$admin = require_admin(['admin']);
$pdo = db();

/** Otomatis hitung dan buat kode tur berformat KOTA-01, KOTA-02 berdasarkan frekuensi kota */
function format_auto_tour_code(PDO $pdo, string $inputCode, ?int $ignoreId = null): string
{
    $clean = strtoupper(trim($inputCode));
    $clean = preg_replace('/[^A-Z0-9-]/', '', $clean);
    if ($clean === '') {
        $clean = 'TUR';
    }

    $prefix = explode('-', $clean)[0];
    if ($prefix === '') {
        $prefix = 'TUR';
    }

    $sql = 'SELECT tour_code FROM tours WHERE (tour_code = ? OR tour_code LIKE ?)';
    $params = [$prefix, $prefix . '-%'];
    if ($ignoreId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $ignoreId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $maxNum = 0;
    foreach ($existing as $code) {
        if ($code === $prefix) {
            $maxNum = max($maxNum, 1);
        } elseif (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string)$code, $matches)) {
            $maxNum = max($maxNum, (int)$matches[1]);
        }
    }

    if ($ignoreId !== null && preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $clean)) {
        return $clean;
    }

    $nextNum = $maxNum + 1;
    return sprintf('%s-%02d', $prefix, $nextNum);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'create_tour':
                $generatedCode = format_auto_tour_code($pdo, (string) $_POST['tour_code']);
                $stmt = $pdo->prepare(
                    'INSERT INTO tours (tour_code, nama_tur, kota, venue, tanggal_konser, status_event)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $generatedCode,
                    trim((string) $_POST['nama_tur']),
                    trim((string) $_POST['kota']),
                    trim((string) $_POST['venue']),
                    (string) $_POST['tanggal_konser'],
                    !empty($_POST['status_event']) ? 1 : 0,
                ]);
                flash('success', 'Jadwal tur baru berhasil ditambahkan dengan kode otomatis ' . $generatedCode . '.');
                break;

            case 'update_tour':
                $tourId = (int) $_POST['id'];
                $generatedCode = format_auto_tour_code($pdo, (string) $_POST['tour_code'], $tourId);
                $stmt = $pdo->prepare(
                    'UPDATE tours
                     SET tour_code = ?, nama_tur = ?, kota = ?, venue = ?, tanggal_konser = ?, status_event = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $generatedCode,
                    trim((string) $_POST['nama_tur']),
                    trim((string) $_POST['kota']),
                    trim((string) $_POST['venue']),
                    (string) $_POST['tanggal_konser'],
                    !empty($_POST['status_event']) ? 1 : 0,
                    $tourId,
                ]);
                flash('success', 'Data jadwal tur ' . $generatedCode . ' berhasil diperbarui.');
                break;

            case 'delete_tour':
                $stmt = $pdo->prepare('DELETE FROM tours WHERE id = ?');
                $stmt->execute([(int) $_POST['id']]);
                flash('success', 'Jadwal tur berhasil dihapus.');
                break;

            case 'create_tier':
                $stmt = $pdo->prepare(
                    'INSERT INTO ticket_tiers (tour_id, nama_gelombang, price, kuota, max_per_order, waktu_mulai, waktu_berakhir, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int) $_POST['tour_id'],
                    (string) $_POST['nama_gelombang'],
                    (float) $_POST['price'],
                    (int) $_POST['kuota'],
                    (int) $_POST['max_per_order'],
                    str_replace('T', ' ', (string) $_POST['waktu_mulai']) . ':00',
                    str_replace('T', ' ', (string) $_POST['waktu_berakhir']) . ':59',
                    !empty($_POST['is_active']) ? 1 : 0,
                ]);
                flash('success', 'Gelombang tiket baru berhasil ditambahkan.');
                break;

            case 'update_tier':
                $mulai = str_replace('T', ' ', (string) $_POST['waktu_mulai']);
                if (strlen($mulai) === 16) {
                    $mulai .= ':00';
                }
                $akhir = str_replace('T', ' ', (string) $_POST['waktu_berakhir']);
                if (strlen($akhir) === 16) {
                    $akhir .= ':59';
                }
                $stmt = $pdo->prepare(
                    'UPDATE ticket_tiers
                     SET tour_id = ?, nama_gelombang = ?, price = ?, kuota = ?, max_per_order = ?,
                         waktu_mulai = ?, waktu_berakhir = ?, is_active = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    (int) $_POST['tour_id'],
                    (string) $_POST['nama_gelombang'],
                    (float) $_POST['price'],
                    (int) $_POST['kuota'],
                    (int) $_POST['max_per_order'],
                    $mulai,
                    $akhir,
                    !empty($_POST['is_active']) ? 1 : 0,
                    (int) $_POST['id'],
                ]);
                flash('success', 'Gelombang tiket berhasil diperbarui.');
                break;

            case 'delete_tier':
                $stmt = $pdo->prepare('DELETE FROM ticket_tiers WHERE id = ?');
                $stmt->execute([(int) $_POST['id']]);
                flash('success', 'Gelombang tiket berhasil dihapus.');
                break;
        }
    } catch (Throwable $e) {
        flash('error', 'Operasi gagal: ' . $e->getMessage());
    }

    redirect('tours.php');
}

$tours = $pdo->query('SELECT * FROM tours ORDER BY tanggal_konser ASC, id ASC')->fetchAll();
$tiers = $pdo->query(
    'SELECT tt.*, tr.kota, tr.tour_code, ' . sold_count_sql('tt') . ' AS sold
     FROM ticket_tiers tt
     JOIN tours tr ON tr.id = tt.tour_id
     ORDER BY tr.tanggal_konser ASC, tt.waktu_mulai ASC'
)->fetchAll();

$editTour = null;
if (isset($_GET['edit_tour'])) {
    $stmt = $pdo->prepare('SELECT * FROM tours WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_tour']]);
    $editTour = $stmt->fetch() ?: null;
}

$editTier = null;
if (isset($_GET['edit_tier'])) {
    $stmt = $pdo->prepare('SELECT * FROM ticket_tiers WHERE id = ?');
    $stmt->execute([(int) $_GET['edit_tier']]);
    $editTier = $stmt->fetch() ?: null;
}

$adminTitle = 'Kelola Tour & Gelombang Tiket';
$adminNav = 'tours';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-page-head">
        <div>
            <h2>Manajemen Jadwal Tour &amp; Gelombang Tiket</h2>
            <!-- <p>Operasi CRUD penuh untuk tabel <code>tours</code> dan <code>ticket_tiers</code>.</p> -->
        </div>
    </div>

    <!-- ==================== BAGIAN 1: TABEL TOURS ==================== -->
    <div class="admin-two-col" style="margin-bottom: 35px;">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editTour ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editTour ? 'Edit Jadwal Tour #' . (int) $editTour['id'] : 'Tambah Jadwal Tour Baru' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editTour ? 'update_tour' : 'create_tour' ?>">
                <?php if ($editTour): ?>
                    <input type="hidden" name="id" value="<?= (int) $editTour['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Kode Singkat / Inisial Kota</label>
                        <input type="text" name="tour_code" id="tour_code_input" required maxlength="20" placeholder="Cth: SBY, JKT, PTK"
                            value="<?= e($editTour['tour_code'] ?? '') ?>">
                        <small class="muted-label" style="display: block; margin-top: 4px;">
                            Cukup ketik inisial kota (misal: <code>SBY</code>). Sistem otomatis menghitung urutan tur ke berapa (contoh: <code>SBY-01</code>, <code>SBY-02</code>).
                        </small>
                        <div id="tour_code_preview" class="mt-1 small fw-semibold text-info"></div>
                    </div>
                    <div class="form-group">
                        <label>Nama Kota</label>
                        <input type="text" name="kota" required maxlength="100" placeholder="Surabaya"
                            value="<?= e($editTour['kota'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Rangkaian Tur</label>
                    <input type="text" name="nama_tur" required maxlength="150"
                        value="<?= e($editTour['nama_tur'] ?? 'Jalaran Sadrah 2026') ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Venue</label>
                        <input type="text" name="venue" required maxlength="200" placeholder="Jatim Expo"
                            value="<?= e($editTour['venue'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Konser</label>
                        <input type="date" name="tanggal_konser" required
                            value="<?= e($editTour['tanggal_konser'] ?? '') ?>">
                    </div>
                </div>

                <label class="agree-check" style="margin: 4px 0;">
                    <input type="checkbox" name="status_event" value="1"
                        <?= (!isset($editTour) || !empty($editTour['status_event'])) ? 'checked' : '' ?>>
                    <span>Status Event Aktif (Centang jika tiket tidak Sold Out / Ditutup)</span>
                </label>

                <div class="status-actions" style="margin-top: 8px;">
                    <button type="submit" class="btn-sm">
                        <i class="fas fa-save"></i> <?= $editTour ? 'Simpan Perubahan' : 'Tambah Tour' ?>
                    </button>
                    <?php if ($editTour): ?>
                        <a href="tours.php" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-map-marked-alt"></i> Daftar Jadwal Tour</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Kota &amp; Venue</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tours as $t): ?>
                            <tr>
                                <td><code><?= e($t['tour_code']) ?></code></td>
                                <td><strong><?= e($t['kota']) ?></strong><br><small class="muted-label"><?= e($t['venue']) ?></small></td>
                                <td><?= e(tanggal_id($t['tanggal_konser'])) ?></td>
                                <td>
                                    <span class="badge <?= $t['status_event'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $t['status_event'] ? 'Aktif' : 'Sold Out/Off' ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="tours.php?edit_tour=<?= (int) $t['id'] ?>" class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus jadwal tur <?= e($t['kota']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_tour">
                                        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== BAGIAN 2: TABEL TICKET_TIERS ==================== -->
    <div id="tiers-section" class="admin-two-col">
        <div class="order-panel">
            <h3 class="panel-title">
                <i class="fas <?= $editTier ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $editTier ? 'Edit Gelombang Tiket #' . (int) $editTier['id'] : 'Tambah Gelombang Tiket (Presale / Normal)' ?>
            </h3>
            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editTier ? 'update_tier' : 'create_tier' ?>">
                <?php if ($editTier): ?>
                    <input type="hidden" name="id" value="<?= (int) $editTier['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Pilih Kota Tour</label>
                        <select name="tour_id" required>
                            <?php foreach ($tours as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= ((int) ($editTier['tour_id'] ?? 0) === (int) $t['id']) ? 'selected' : '' ?>>
                                    <?= e($t['kota']) ?> (<?= e($t['tour_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Gelombang</label>
                        <select name="nama_gelombang" required>
                            <option value="Presale" <?= (($editTier['nama_gelombang'] ?? '') === 'Presale') ? 'selected' : '' ?>>Presale</option>
                            <option value="Normal" <?= (($editTier['nama_gelombang'] ?? '') === 'Normal') ? 'selected' : '' ?>>Normal</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Harga Tiket (Rp)</label>
                        <input type="number" name="price" required min="0" step="1000"
                            value="<?= e((string) (int) ($editTier['price'] ?? 175000)) ?>">
                    </div>
                    <div class="form-group">
                        <label>Total Kuota</label>
                        <input type="number" name="kuota" required min="1"
                            value="<?= (int) ($editTier['kuota'] ?? 300) ?>">
                    </div>
                    <div class="form-group">
                        <label>Maks. Tiket / Akun</label>
                        <input type="number" name="max_per_order" required min="1" max="10"
                            value="<?= (int) ($editTier['max_per_order'] ?? 2) ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Waktu Mulai Penjualan</label>
                        <input type="datetime-local" name="waktu_mulai" required
                            value="<?= e(isset($editTier['waktu_mulai']) ? date('Y-m-d\TH:i', strtotime($editTier['waktu_mulai'])) : date('Y-m-d\T10:00')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Waktu Berakhir Penjualan</label>
                        <input type="datetime-local" name="waktu_berakhir" required
                            value="<?= e(isset($editTier['waktu_berakhir']) ? date('Y-m-d\TH:i', strtotime($editTier['waktu_berakhir'])) : date('Y-m-d\T23:59', strtotime('+14 days'))) ?>">
                    </div>
                </div>

                <label class="agree-check" style="margin: 4px 0;">
                    <input type="checkbox" name="is_active" value="1"
                        <?= (!isset($editTier) || !empty($editTier['is_active'])) ? 'checked' : '' ?>>
                    <span>Gelombang Aktif</span>
                </label>

                <div class="status-actions" style="margin-top: 8px;">
                    <button type="submit" class="btn-sm">
                        <i class="fas fa-save"></i> <?= $editTier ? 'Simpan Gelombang' : 'Tambah Gelombang' ?>
                    </button>
                    <?php if ($editTier): ?>
                        <a href="tours.php#tiers-section" class="btn-sm btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="order-panel">
            <h3 class="panel-title"><i class="fas fa-layer-group"></i> Daftar Gelombang Tiket</h3>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kota &amp; Gelombang</th>
                            <th>Harga</th>
                            <th>Terjual / Kuota</th>
                            <th>Periode</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $tr):
                            $tr['remaining'] = max(0, (int) $tr['kuota'] - (int) $tr['sold']);
                            $tr['status'] = tier_status($tr);
                            [$stLabel, $stBadge] = tier_status_label($tr);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($tr['kota']) ?></strong> &mdash; <?= e($tr['nama_gelombang']) ?><br>
                                    <span class="badge <?= e($stBadge) ?>"><?= e($stLabel) ?></span>
                                </td>
                                <td><?= rupiah($tr['price']) ?></td>
                                <td><?= (int) $tr['sold'] ?> / <?= (int) $tr['kuota'] ?></td>
                                <td>
                                    <small class="muted-label">
                                        <?= e(date('d/m/y H:i', strtotime($tr['waktu_mulai']))) ?> &ndash;<br>
                                        <?= e(date('d/m/y H:i', strtotime($tr['waktu_berakhir']))) ?>
                                    </small>
                                </td>
                                <td class="table-actions">
                                    <a href="tours.php?edit_tier=<?= (int) $tr['id'] ?>#tiers-section" class="btn-xs btn-outline" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus gelombang <?= e($tr['nama_gelombang']) ?> <?= e($tr['kota']) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_tier">
                                        <input type="hidden" name="id" value="<?= (int) $tr['id'] ?>">
                                        <button type="submit" class="btn-xs btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tourCodeInput = document.getElementById('tour_code_input');
    const previewEl = document.getElementById('tour_code_preview');
    const existingCodes = <?= json_encode(array_column($tours, 'tour_code')) ?>;

    if (tourCodeInput && previewEl) {
        function updateTourCodePreview() {
            const raw = tourCodeInput.value.trim().toUpperCase().replace(/[^A-Z0-9-]/g, '');
            if (!raw) {
                previewEl.textContent = '';
                return;
            }
            const prefix = raw.split('-')[0];
            let maxIndex = 0;
            existingCodes.forEach(code => {
                const c = String(code).toUpperCase();
                if (c === prefix) {
                    maxIndex = Math.max(maxIndex, 1);
                } else if (c.startsWith(prefix + '-')) {
                    const num = parseInt(c.replace(prefix + '-', ''), 10);
                    if (!isNaN(num)) maxIndex = Math.max(maxIndex, num);
                }
            });
            const nextIndex = maxIndex + 1;
            const padded = String(nextIndex).padStart(2, '0');
            previewEl.innerHTML = `<i class="fas fa-magic me-1"></i> Estimasi Kode: <strong>${prefix}-${padded}</strong> (Kunjungan ke-${nextIndex} di kota ini)`;
        }

        tourCodeInput.addEventListener('input', updateTourCodePreview);
        if (tourCodeInput.value) {
            updateTourCodePreview();
        }
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
