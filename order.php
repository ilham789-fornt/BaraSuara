<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

$user = require_login('Silakan masuk atau daftar akun terlebih dahulu untuk memesan tiket.');

expire_stale_orders();

$tourId = (int) ($_GET['tour'] ?? 0);
$tour = $tourId > 0 ? find_tour($tourId) : null;
if (!$tour) {
    flash('error', 'Jadwal konser tidak ditemukan.');
    redirect('tour.php');
}

$tiers = get_tour_tiers($tourId);
$sale = tour_sale_state($tour, $tiers);
if ($sale['state'] !== 'on_sale') {
    flash('error', 'Tiket untuk konser ' . $tour['kota'] . ' sedang tidak dijual.');
    redirect('tour.php');
}

$tier = $sale['tier'];
$alreadyHeld = user_tickets_in_tier((int) $user['id'], (int) $tier['id']);
$maxQty = min((int) $tier['max_per_order'] - $alreadyHeld, (int) $tier['remaining']);

if ($maxQty <= 0) {
    flash('info', 'Anda sudah mencapai batas maksimal ' . (int) $tier['max_per_order']
        . ' tiket untuk gelombang ' . $tier['nama_gelombang'] . ' konser ' . $tour['kota'] . '.');
    redirect('my-orders.php');
}

// Data lama jika validasi gagal di process-order.php
$old = $_SESSION['order_old'] ?? [];
$errors = $_SESSION['order_errors'] ?? [];
unset($_SESSION['order_old'], $_SESSION['order_errors']);

$oldQty = min(max(1, (int) ($old['quantity'] ?? 1)), $maxQty);
$oldHolders = $old['holders'] ?? [$user['full_name']];
$otherTiers = array_filter($tiers, static fn($t) => $t['id'] !== $tier['id']);

$pageTitle = 'Pesan Tiket ' . $tour['kota'];
$pageDescription = 'Formulir pemesanan tiket konser Barasuara ' . $tour['nama_tur'] . ' di ' . $tour['kota'] . '.';
$activePage = 'tour';
$extraScripts = '<script src="lib/order.js" defer></script>';
require __DIR__ . '/includes/layout_header.php';
?>

<main style="padding-bottom: 60px;">
    <section class="section fade-in" aria-labelledby="order-heading">
        <div class="text-center" style="margin-bottom: 30px;">
            <h2 id="order-heading" style="font-size: 2rem;">Pesan Tiket</h2>
        </div>

        <?php if ($errors): ?>
            <div class="flash flash-error order-errors" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="process-order.php" method="post" id="order-form" class="order-layout"
            data-price="<?= (int) $tier['price'] ?>" data-max="<?= (int) $maxQty ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tour_id" value="<?= (int) $tour['id'] ?>">

            <div class="order-main">
                <!-- 1. Info konser & gelombang (otomatis, tidak dipilih user) -->
                <div class="order-panel">
                    <h3 class="panel-title"><span class="step">1</span> Detail Konser</h3>
                    <div class="concert-info">
                        <div>
                            <p class="concert-name"><?= e($tour['nama_tur']) ?> &mdash; <?= e($tour['kota']) ?></p>
                            <p class="concert-meta"><i class="fas fa-map-marker-alt"></i> <?= e($tour['venue']) ?></p>
                            <p class="concert-meta"><i class="fas fa-calendar-alt"></i>
                                <?= e(tanggal_id($tour['tanggal_konser'])) ?></p>
                        </div>
                        <div class="current-tier">
                            <span class="badge badge-success">Gelombang <?= e($tier['nama_gelombang']) ?></span>
                            <span class="current-price"><?= rupiah($tier['price']) ?><small>/tiket</small></span>
                            <span class="tier-deadline">Berlaku s.d. <?= e(tanggal_id($tier['waktu_berakhir'], true)) ?></span>
                        </div>
                    </div>
                    <?php foreach ($otherTiers as $other):
                        if ($other['status'] !== 'upcoming') continue; ?>
                        <p class="tier-note"><i class="fas fa-info-circle"></i>
                            Harga <?= e($other['nama_gelombang']) ?> <?= rupiah($other['price']) ?> berlaku mulai
                            <?= e(tanggal_id($other['waktu_mulai'], true)) ?>.</p>
                    <?php endforeach; ?>
                </div>

                <!-- 2. Jumlah tiket -->
                <div class="order-panel">
                    <h3 class="panel-title"><span class="step">2</span> Jumlah Tiket</h3>
                    <div class="qty-row">
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" data-action="decrease" aria-label="Kurangi tiket">
                                <i class="fas fa-minus"></i></button>
                            <input type="number" id="quantity" name="quantity" min="1" max="<?= (int) $maxQty ?>"
                                value="<?= $oldQty ?>" required aria-label="Jumlah tiket" readonly>
                            <button type="button" class="qty-btn" data-action="increase" aria-label="Tambah tiket">
                                <i class="fas fa-plus"></i></button>
                        </div>
                        <p class="qty-hint">Maksimal <?= (int) $tier['max_per_order'] ?> tiket per akun untuk gelombang ini.
                            <?php if ($alreadyHeld > 0): ?>Anda sudah memiliki <?= $alreadyHeld ?> tiket.<?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- 3. Data pemesan -->
                <div class="order-panel">
                    <h3 class="panel-title"><span class="step">3</span> Data Pemesan</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="buyer_name">Nama Pemesan</label>
                            <input type="text" id="buyer_name" value="<?= e($user['full_name']) ?>" readonly
                                class="input-readonly">
                        </div>
                        <div class="form-group">
                            <label for="email">Email (pengiriman e-ticket)</label>
                            <input type="email" id="email" name="email" required maxlength="150"
                                value="<?= e($old['email'] ?? $user['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="no_whatsapp">No. WhatsApp</label>
                            <input type="tel" id="no_whatsapp" name="no_whatsapp" required maxlength="20"
                                pattern="^(\+62|62|0)8[0-9]{7,12}$" placeholder="08xxxxxxxxxx"
                                value="<?= e($old['no_whatsapp'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- 4. Identitas pemegang tiket (dinamis sesuai jumlah) -->
                <div class="order-panel">
                    <h3 class="panel-title"><span class="step">4</span> Identitas Pemegang Tiket</h3>
                    <p class="panel-desc">Nama pemegang tiket akan dicetak pada e-ticket dan dicocokkan dengan
                        identitas (KTP/Kartu Pelajar) saat masuk venue.</p>
                    <div id="holder-list" class="holder-list">
                        <?php for ($i = 0; $i < $maxQty; $i++): ?>
                            <div class="form-group holder-item" data-index="<?= $i ?>" <?= $i >= $oldQty ? 'hidden' : '' ?>>
                                <label for="holder_<?= $i ?>">Tiket #<?= $i + 1 ?> &mdash; Nama Lengkap</label>
                                <input type="text" id="holder_<?= $i ?>" name="holders[]" maxlength="150"
                                    value="<?= e($oldHolders[$i] ?? '') ?>" placeholder="Nama sesuai identitas"
                                    <?= $i >= $oldQty ? 'disabled' : 'required' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- 5. Metode pembayaran: hanya QRIS -->
                <div class="order-panel">
                    <h3 class="panel-title"><span class="step">5</span> Metode Pembayaran</h3>
                    <label class="payment-option selected">
                        <input type="radio" name="payment_method" value="QRIS" checked>
                        <span class="payment-icon"><i class="fas fa-qrcode"></i></span>
                        <span>
                            <strong>QRIS</strong>
                            <small>Bayar dari aplikasi m-banking atau e-wallet apa pun (GoPay, OVO, DANA, ShopeePay,
                                BCA, Mandiri, dll).</small>
                        </span>
                        <i class="fas fa-check-circle payment-check"></i>
                    </label>
                </div>
            </div>

            <!-- Ringkasan pesanan (sticky) -->
            <aside class="order-summary" aria-label="Ringkasan pesanan">
                <h3>Ringkasan Pesanan</h3>
                <dl>
                    <div><dt>Konser</dt><dd><?= e($tour['kota']) ?></dd></div>
                    <div><dt>Gelombang</dt><dd><?= e($tier['nama_gelombang']) ?></dd></div>
                    <div><dt>Harga</dt><dd><?= rupiah($tier['price']) ?></dd></div>
                    <div><dt>Jumlah</dt><dd><span id="summary-qty"><?= $oldQty ?></span> tiket</dd></div>
                    <div><dt>Pembayaran</dt><dd>QRIS</dd></div>
                    <div class="summary-total"><dt>Total</dt>
                        <dd id="summary-total"><?= rupiah($tier['price'] * $oldQty) ?></dd></div>
                </dl>
                <label class="agree-check">
                    <input type="checkbox" name="agree" value="1" required>
                    <span>Saya menyetujui syarat &amp; ketentuan pembelian tiket. Tiket yang sudah dibayar tidak dapat
                        dikembalikan.</span>
                </label>
                <button type="submit" class="btn btn-block"><i class="fas fa-lock"></i> Buat Pesanan</button>
                <p class="summary-note">Selesaikan pembayaran dalam <?= ORDER_HOLD_MINUTES ?> menit setelah pesanan dibuat.</p>
            </aside>
        </form>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

