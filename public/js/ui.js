/* =========================================================
   SIJALAT - interaksi UI tanpa library eksternal.
   Pengganti Alpine.js agar tidak memerlukan 'unsafe-eval'
   pada Content-Security-Policy.
   ========================================================= */
(function () {
    'use strict';

    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    /* -----------------------------
       Dropdown menu (navbar)
       ----------------------------- */
    var dropdowns = Array.prototype.slice.call(document.querySelectorAll('[data-dropdown]'));

    function closeDropdown(dropdown) {
        var trigger = dropdown.querySelector('[data-dropdown-trigger]');
        var panel = dropdown.querySelector('[data-dropdown-panel]');

        if (!panel) {
            return;
        }

        panel.hidden = true;

        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    }

    function closeAllDropdowns(except) {
        dropdowns.forEach(function (dropdown) {
            if (dropdown !== except) {
                closeDropdown(dropdown);
            }
        });
    }

    dropdowns.forEach(function (dropdown) {
        var trigger = dropdown.querySelector('[data-dropdown-trigger]');
        var panel = dropdown.querySelector('[data-dropdown-panel]');

        if (!trigger || !panel) {
            return;
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();

            var willOpen = panel.hidden;

            closeAllDropdowns(dropdown);
            panel.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', String(willOpen));
        });

        panel.addEventListener('click', function (event) {
            if (event.target.closest('a, button, [data-dropdown-close]')) {
                closeDropdown(dropdown);
            }
        });
    });

    document.addEventListener('click', function (event) {
        dropdowns.forEach(function (dropdown) {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    /* -----------------------------
       Menu navigasi mobile
       ----------------------------- */
    var navToggle = document.querySelector('[data-nav-toggle]');
    var navMenu = document.getElementById('sijalat-nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            var isOpen = !navMenu.classList.contains('hidden');

            navMenu.classList.toggle('hidden', isOpen);
            navMenu.classList.toggle('block', !isOpen);
            navToggle.setAttribute('aria-expanded', String(!isOpen));

            var openIcon = navToggle.querySelector('[data-nav-icon="open"]');
            var closeIcon = navToggle.querySelector('[data-nav-icon="close"]');

            if (openIcon) {
                openIcon.classList.toggle('hidden', !isOpen);
                openIcon.classList.toggle('inline-flex', isOpen);
            }

            if (closeIcon) {
                closeIcon.classList.toggle('hidden', isOpen);
                closeIcon.classList.toggle('inline-flex', !isOpen);
            }
        });
    }

    /* -----------------------------
       Modal (konfirmasi hapus akun)
       ----------------------------- */
    var modals = Array.prototype.slice.call(document.querySelectorAll('[data-modal]'));
    var activeModal = null;
    var previousFocus = null;

    function showModal(modal) {
        if (!modal) {
            return;
        }

        previousFocus = document.activeElement;
        modal.hidden = false;
        modal.style.display = 'block';
        document.body.classList.add('overflow-y-hidden');
        activeModal = modal;

        var target = modal.querySelector('[data-modal-autofocus]') || modal.querySelector(FOCUSABLE);

        if (target) {
            window.setTimeout(function () {
                target.focus();
            }, 50);
        }
    }

    function hideModal(modal) {
        if (!modal) {
            return;
        }

        modal.style.display = 'none';
        modal.hidden = true;
        document.body.classList.remove('overflow-y-hidden');

        if (activeModal === modal) {
            activeModal = null;
        }

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    modals.forEach(function (modal) {
        if (modal.getAttribute('data-modal-visible') === 'true') {
            showModal(modal);
        }

        modal.querySelectorAll('[data-modal-close]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                hideModal(modal);
            });
        });

        var backdrop = modal.querySelector('[data-modal-backdrop]');

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                hideModal(modal);
            });
        }

        modal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                hideModal(modal);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            var focusable = Array.prototype.slice.call(modal.querySelectorAll(FOCUSABLE)).filter(function (element) {
                return element.offsetParent !== null;
            });

            if (focusable.length === 0) {
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-modal-open]');

        if (!opener) {
            return;
        }

        event.preventDefault();

        var name = opener.getAttribute('data-modal-open');
        var modal = name ? document.querySelector('[data-modal="' + name + '"]') : null;

        if (modal) {
            showModal(modal);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && activeModal) {
            hideModal(activeModal);
        }
    });

    /* -----------------------------
       Pesan status yang hilang otomatis
       ----------------------------- */
    document.querySelectorAll('[data-autohide]').forEach(function (element) {
        var delay = parseInt(element.getAttribute('data-autohide'), 10);

        window.setTimeout(function () {
            element.hidden = true;
        }, isNaN(delay) ? 2000 : delay);
    });
}());
