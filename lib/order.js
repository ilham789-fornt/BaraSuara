/**
 * Formulir pemesanan tiket: stepper jumlah, input pemegang tiket dinamis, dan total harga.
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const form = document.getElementById('order-form');
    if (!form) return;

    const price = Number(form.dataset.price) || 0;
    const max = Number(form.dataset.max) || 1;
    const qtyInput = document.getElementById('quantity');
    const holders = form.querySelectorAll('.holder-item');
    const summaryQty = document.getElementById('summary-qty');
    const summaryTotal = document.getElementById('summary-total');
    const decreaseBtn = form.querySelector('[data-action="decrease"]');
    const increaseBtn = form.querySelector('[data-action="increase"]');

    const formatRupiah = (value) => 'Rp ' + value.toLocaleString('id-ID');

    function render() {
        const qty = Math.min(Math.max(Number(qtyInput.value) || 1, 1), max);
        qtyInput.value = qty;

        holders.forEach((item, index) => {
            const input = item.querySelector('input');
            const visible = index < qty;
            item.hidden = !visible;
            // Input yang disembunyikan di-disable agar tidak ikut terkirim & tidak divalidasi.
            input.disabled = !visible;
            input.required = visible;
        });

        summaryQty.textContent = qty;
        summaryTotal.textContent = formatRupiah(price * qty);
        decreaseBtn.disabled = qty <= 1;
        increaseBtn.disabled = qty >= max;
    }

    decreaseBtn.addEventListener('click', () => { qtyInput.value = Number(qtyInput.value) - 1; render(); });
    increaseBtn.addEventListener('click', () => { qtyInput.value = Number(qtyInput.value) + 1; render(); });

    // Cegah submit ganda.
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) return;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn.dataset.submitting) {
            event.preventDefault();
            return;
        }
        submitBtn.dataset.submitting = '1';
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    });

    render();
});

