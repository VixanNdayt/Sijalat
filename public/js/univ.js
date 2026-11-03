(function () {
    'use strict';

    /* ------------------------------------------------------------------
     * Sidebar (menu samping)
     * ------------------------------------------------------------------ */
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const overlay = document.getElementById('overlay');

    function toggleSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    /* ------------------------------------------------------------------
     * Modal / pop-up
     * ------------------------------------------------------------------ */

    /**
     * Buka modal berdasarkan id (string "#modal-alat" / "modal-alat")
     * atau elemen modal langsung.
     */
    window.bukaModal = function (target) {
        let modal = null;

        if (typeof target === 'string') {
            modal = document.querySelector(target) || document.getElementById(target.replace(/^#/, ''));
        } else {
            modal = target;
        }

        if (!modal) {
            return;
        }

        modal.classList.add('open');
        document.body.classList.add('modal-open');

        const focusable = modal.querySelector(
            'input:not([type="hidden"]):not([readonly]), select, textarea, button'
        );

        if (focusable) {
            window.setTimeout(function () {
                focusable.focus({ preventScroll: true });
            }, 200);
        }
    };

    /**
     * Tutup satu modal, atau semua modal jika tanpa argumen.
     */
    window.tutupModal = function (target) {
        let list = null;

        if (target) {
            const modal = typeof target === 'string'
                ? (document.querySelector(target) || document.getElementById(target.replace(/^#/, '')))
                : target;

            list = modal ? [modal] : [];
        } else {
            list = Array.prototype.slice.call(document.querySelectorAll('.modal.open'));
        }

        list.forEach(function (modal) {
            if (modal) {
                modal.classList.remove('open');
            }
        });

        if (!document.querySelector('.modal.open')) {
            document.body.classList.remove('modal-open');
        }
    };

    // Tombol dengan atribut data-modal-open / data-modal-close
    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-modal-open]');

        if (opener) {
            event.preventDefault();
            window.bukaModal(opener.getAttribute('data-modal-open'));
            return;
        }

        const closer = event.target.closest('[data-modal-close]');

        if (closer) {
            event.preventDefault();
            window.tutupModal(closer.closest('.modal'));
        }
    });

    // Tekan Esc untuk menutup modal
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            window.tutupModal();
        }
    });

    /* ------------------------------------------------------------------
     * Tandai menu sidebar yang sedang aktif
     * ------------------------------------------------------------------ */
    (function tandaiMenuAktif() {
        const menu = document.querySelectorAll('.sidebar-menu a[href]');
        const path = window.location.pathname.replace(/\/+$/, '') || '/';
        let aktif = null;
        let panjangTerbaik = -1;

        menu.forEach(function (link) {
            const href = link.getAttribute('href');

            if (!href) {
                return;
            }

            const target = (link.pathname || '/').replace(/\/+$/, '') || '/';

            if ((path === target || path.indexOf(target + '/') === 0) && target.length > panjangTerbaik) {
                aktif = link;
                panjangTerbaik = target.length;
            }
        });

        if (aktif) {
            aktif.classList.add('active');
            aktif.setAttribute('aria-current', 'page');
        }
    })();
})();
