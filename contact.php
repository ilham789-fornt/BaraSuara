<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$errors = [];
$old = [];

$categories = [
    'booking'     => 'Booking / Manggung',
    'media'       => 'Media / Wawancara',
    'partnership' => 'Sponsorship / Kemitraan',
    'general'     => 'Pertanyaan Umum',
];

// CREATE: simpan pesan kontak (hanya untuk user yang sudah login).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_login('Silakan masuk atau daftar akun terlebih dahulu untuk menghubungi Barasuara.');
    verify_csrf();

    // Honeypot anti-bot: jika terisi, abaikan diam-diam.
    if (!empty($_POST['bot-field'])) {
        redirect('contact.php');
    }

    $old = [
        'name'       => trim((string) ($_POST['name'] ?? '')),
        'email'      => strtolower(trim((string) ($_POST['email'] ?? ''))),
        'whatsapp'   => preg_replace('/[\s\-]/', '', (string) ($_POST['whatsapp'] ?? '')),
        'subject'    => (string) ($_POST['subject'] ?? ''),
        'event_date' => (string) ($_POST['event_date'] ?? ''),
        'message'    => trim((string) ($_POST['message'] ?? '')),
    ];

    if (mb_strlen($old['name']) < 3 || mb_strlen($old['name']) > 150) {
        $errors[] = 'Nama / instansi minimal 3 karakter.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors[] = 'Email tidak valid.';
    }
    if (!preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $old['whatsapp'])) {
        $errors[] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';
    }
    if (!isset($categories[$old['subject']])) {
        $errors[] = 'Pilih subjek keperluan.';
    }
    $eventDate = null;
    if ($old['event_date'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $old['event_date']);
        if (!$d || $d->format('Y-m-d') !== $old['event_date']) {
            $errors[] = 'Tanggal acara tidak valid.';
        } else {
            $eventDate = $old['event_date'];
        }
    }
    if (mb_strlen($old['message']) < 10) {
        $errors[] = 'Pesan minimal 10 karakter.';
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO contact_inquiries (nama_pengirim, email, no_whatsapp, kategori_pesan, tanggal_acara_usulan, isi_pesan)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$old['name'], $old['email'], $old['whatsapp'], $old['subject'], $eventDate, $old['message']]);

        flash('success', 'Pesan Anda berhasil terkirim. Tim manajemen Barasuara akan menghubungi Anda melalui email/WhatsApp.');
        redirect('contact.php');
    }
}

$pageTitle = 'Kontak';
$pageDescription = 'Hubungi manajemen Barasuara untuk urusan bisnis, pemesanan panggung, atau pertanyaan pers.';
$activePage = 'contact';
require __DIR__ . '/includes/layout_header.php';
?>

<main style="padding-bottom: 60px;">
    <section class="section fade-in" aria-labelledby="contact-heading">
        <div class="contact-container">

            <div class="contact-header text-center">
                <h2 id="contact-heading" style="font-size: 2rem; margin-bottom: 12px; display: inline-block;">
                    Hubungi Kami</h2>
                <p>Untuk kebutuhan <em>booking</em>, tawaran kolaborasi, atau pertanyaan seputar tur, silakan
                    kirimkan pesan melalui formulir di bawah ini.</p>
            </div>

            <?php if (!$user): ?>
                <div class="login-gate text-center">
                    <i class="fas fa-user-lock"></i>
                    <p>Anda perlu <strong>masuk</strong> atau <strong>mendaftar akun</strong> terlebih dahulu untuk
                        mengirim pesan kepada manajemen Barasuara.</p>
                    <div class="gate-actions">
                        <a href="login.php?redirect=contact.php" class="btn"><i class="fas fa-sign-in-alt"></i> Masuk</a>
                        <a href="register.php?redirect=contact.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Daftar Akun</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="flash flash-error order-errors" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="contact-form">
                    <?= csrf_field() ?>
                    <div class="visually-hidden" aria-hidden="true">
                        <label for="bot-field">Tinggalkan kosong jika Anda manusia:</label>
                        <input type="text" id="bot-field" name="bot-field" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="user-name">Nama Lengkap / Instansi</label>
                        <input type="text" id="user-name" name="name" required maxlength="150"
                            placeholder="Cth: Gusty Faqikh - BEM Polnep"
                            value="<?= e($old['name'] ?? $user['full_name']) ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="user-email">Alamat Email</label>
                            <input type="email" id="user-email" name="email" required maxlength="150"
                                value="<?= e($old['email'] ?? $user['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="user-whatsapp">No. WhatsApp</label>
                            <input type="tel" id="user-whatsapp" name="whatsapp" required maxlength="20"
                                pattern="^(\+62|62|0)8[0-9]{7,12}$" placeholder="08xxxxxxxxxx"
                                value="<?= e($old['whatsapp'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="message-subject">Subjek Keperluan</label>
                            <select id="message-subject" name="subject" required>
                                <option value="" disabled <?= empty($old['subject']) ? 'selected' : '' ?>>Pilih keperluan...</option>
                                <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= ($old['subject'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="event-date">Tanggal Acara <small>(opsional)</small></label>
                            <input type="date" id="event-date" name="event_date" min="<?= date('Y-m-d') ?>"
                                value="<?= e($old['event_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="user-message">Pesan Rinci</label>
                        <textarea id="user-message" name="message" rows="5" required minlength="10"
                            placeholder="Tulis rincian pesan Anda di sini..."
                            style="resize: vertical; line-height: 1.5;"><?= e($old['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-block" style="margin-top: 10px;">
                        Kirim Pesan <i class="fas fa-paper-plane" style="margin-left: 5px;"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="contact-direct text-center">
                <p>Atau hubungi via email secara langsung:</p>
                <a href="mailto:booking@barasuara.com" class="direct-email"><i class="fas fa-envelope"
                        style="margin-right: 8px;"></i> booking@barasuara.com</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
