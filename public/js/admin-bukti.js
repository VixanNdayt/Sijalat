(function () {
    'use strict';

    const lightbox = document.getElementById('proofLightbox');

    if (!lightbox) {
        return;
    }

    const triggers = Array.from(document.querySelectorAll('.proof-thumb[data-foto]'));

    if (triggers.length === 0) {
        lightbox.remove();
        return;
    }

    const image = document.getElementById('proofLightboxImage');
    const caption = document.getElementById('proofLightboxCaption');
    const kicker = document.getElementById('proofLightboxKicker');
    const counter = document.getElementById('proofLightboxCounter');
    const openLink = document.getElementById('proofLightboxOpen');
    const closeButton = lightbox.querySelector('.proof-lightbox-close');
    const dialog = lightbox.querySelector('.proof-lightbox-dialog');

    let index = 0;
    let sebelumnya = null;

    function render(nextIndex) {
        index = (nextIndex + triggers.length) % triggers.length;

        const trigger = triggers[index];
        const src = trigger.dataset.foto;

        image.src = src;
        image.alt = trigger.dataset.caption || 'Bukti transaksi';
        caption.textContent = trigger.dataset.caption || 'Bukti transaksi';
        kicker.textContent = trigger.dataset.kicker || '';
        counter.textContent = (index + 1) + ' dari ' + triggers.length;
        openLink.href = src;
    }

    function buka(nextIndex, trigger) {
        sebelumnya = trigger || document.activeElement;
        render(nextIndex);

        lightbox.hidden = false;
        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');

        closeButton.focus();
    }

    function tutup() {
        lightbox.classList.remove('open');
        lightbox.hidden = true;
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        image.removeAttribute('src');

        if (sebelumnya) {
            sebelumnya.focus();
        }
    }

    triggers.forEach(function (trigger, i) {
        trigger.addEventListener('click', function () {
            buka(i, trigger);
        });
    });

    lightbox.querySelectorAll('[data-lightbox-close]').forEach(function (element) {
        element.addEventListener('click', tutup);
    });

    lightbox.querySelector('[data-lightbox-prev]').addEventListener('click', function () {
        render(index - 1);
    });

    lightbox.querySelector('[data-lightbox-next]').addEventListener('click', function () {
        render(index + 1);
    });

    document.addEventListener('keydown', function (event) {
        if (lightbox.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            tutup();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            render(index - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            render(index + 1);
        }
    });

    // Jaga fokus keyboard tetap berada di dalam dialog.
    dialog.addEventListener('keydown', function (event) {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = dialog.querySelectorAll('button, a[href]');

        if (focusable.length === 0) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
