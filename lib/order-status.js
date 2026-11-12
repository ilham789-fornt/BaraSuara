/**
 * Halaman status pesanan: render QR code (QRIS & e-ticket) dan hitung mundur batas pembayaran.
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // --- QR code (library qrcodejs dari CDN) ---
    const renderQr = (el, size) => {
        if (typeof QRCode === 'undefined' || !el.dataset.payload) return;
        new QRCode(el, {
            text: el.dataset.payload,
            width: size,
            height: size,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
        });
    };

    const qris = document.getElementById('qris-code');
    if (qris) renderQr(qris, 200);
    document.querySelectorAll('.ticket-qr').forEach((el) => renderQr(el, 130));

    // --- Countdown batas pembayaran ---
    const countdown = document.getElementById('countdown');
    if (!countdown) return;

    let seconds = Number(countdown.dataset.seconds) || 0;
    const tick = () => {
        if (seconds <= 0) {
            countdown.textContent = '00:00';
            countdown.classList.add('expired');
            // Muat ulang agar server menandai pesanan sebagai kedaluwarsa.
            window.setTimeout(() => window.location.reload(), 1500);
            return;
        }
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        countdown.textContent = `${m}:${s}`;
        seconds -= 1;
        window.setTimeout(tick, 1000);
    };
    tick();
});

