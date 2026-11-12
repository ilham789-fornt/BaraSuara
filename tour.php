<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ticketing.php';

expire_stale_orders();

$tours = db()->query('SELECT * FROM tours ORDER BY tanggal_konser ASC')->fetchAll();

$pageTitle = 'Tour';
$pageDescription = 'Jadwal tur resmi dan pemesanan tiket konser Barasuara 2026. Jangan lewatkan ledakan energi mereka di kotamu.';
$activePage = 'tour';
require __DIR__ . '/includes/layout_header.php';
?>

<main style="padding-bottom: 60px;">
    <section class="section fade-in" aria-labelledby="tour-heading">
        <div class="tour-header-wrapper text-center" style="margin-bottom: 40px;">
            <h2 id="tour-heading" style="font-size: 2rem; margin-bottom: 10px;">Jadwal Tour 2026: Jalaran Sadrah</h2>
            <p style="color: #aaa;">Amankan tempatmu di barisan depan sakralnya takdir.</p>
        </div>

        <div class="tour-container">
            <?php if (!$tours): ?>
                <p class="text-center" style="color: #888;">Belum ada jadwal tur yang diumumkan.</p>
            <?php endif; ?>

            <?php foreach ($tours as $tour):
                $tiers = get_tour_tiers((int) $tour['id']);
                $sale = tour_sale_state($tour, $tiers);
                $isDimmed = in_array($sale['state'], ['sold_out', 'closed', 'finished'], true);
                ?>
                <article id="<?= e(strtolower($tour['kota'])) ?>" class="tour-card<?= $isDimmed ? ' sold-out' : '' ?>">
                    <div class="tour-date">
                        <time datetime="<?= e($tour['tanggal_konser']) ?>">
                            <span class="day"><?= date('j', strtotime($tour['tanggal_konser'])) ?></span>
                            <span class="month"><?= e(bulan_singkat($tour['tanggal_konser'])) ?></span>
                            <span class="year"><?= date('Y', strtotime($tour['tanggal_konser'])) ?></span>
                        </time>
                    </div>

                    <div class="tour-details">
                        <h3><?= e($tour['kota']) ?></h3>
                        <p><i class="fas fa-map-marker-alt" style="margin-right: 5px; color: #666;"></i>
                            <?= e($tour['venue']) ?></p>

                        <?php if ($tiers && $sale['state'] !== 'finished'): ?>
                            <ul class="tier-price-list" aria-label="Harga tiket <?= e($tour['kota']) ?>">
                                <?php foreach ($tiers as $tier):
                                    [$tierLabel, $tierBadge] = tier_status_label($tier); ?>
                                    <li class="tier-price tier-<?= e($tier['status']) ?>">
                                        <span class="tier-name"><?= e($tier['nama_gelombang']) ?></span>
                                        <span class="tier-amount"><?= rupiah($tier['price']) ?></span>
                                        <span class="badge <?= e($tierBadge) ?>"><?= e($tierLabel) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($sale['state'] === 'on_sale'): ?>
                                <p class="tier-note">
                                    <i class="fas fa-clock"></i> <?= e($sale['tier']['nama_gelombang']) ?> berakhir
                                    <?= e(tanggal_id($sale['tier']['waktu_berakhir'], true)) ?> &middot;
                                    sisa <?= (int) $sale['tier']['remaining'] ?> tiket
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tour-action">
                        <?php switch ($sale['state']):
                            case 'on_sale': ?>
                                <a class="btn" href="order.php?tour=<?= (int) $tour['id'] ?>"
                                    aria-label="Pesan tiket konser <?= e($tour['kota']) ?>">
                                    <i class="fas fa-ticket-alt"></i> Pesan Tiket
                                </a>
                                <?php break;
                            case 'coming_soon': ?>
                                <button class="btn btn-disabled" disabled>Segera Dibuka</button>
                                <?php break;
                            case 'finished': ?>
                                <button class="btn btn-disabled" disabled>Selesai</button>
                                <?php break;
                            case 'closed': ?>
                                <button class="btn btn-disabled" disabled>Penjualan Ditutup</button>
                                <?php break;
                            default: ?>
                                <button class="btn btn-disabled" disabled
                                    aria-label="Tiket untuk <?= e($tour['kota']) ?> sudah habis">Sold Out</button>
                        <?php endswitch; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="tour-footnote text-center">
            <i class="fas fa-info-circle"></i> Gelombang tiket (Presale/Normal) berlaku otomatis sesuai jadwal
            penjualan. Pemesanan tiket memerlukan akun.
        </p>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
