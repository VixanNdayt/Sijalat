(function () {
    'use strict';

    var data = window.SIJALAT_HOME || {};
    var STORAGE_KEY = 'sijalat_cart_v1';

    var alats = new Map();
    (data.alats || []).forEach(function (alat) {
        alats.set(String(alat.id), alat);
    });

    /* ---------------------------------------------------------
       Utilitas
       --------------------------------------------------------- */
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(value || 0);
    }

    var toast = document.getElementById('homeToast');
    var toastTimer = null;

    function showToast(message) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('is-visible');

        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2300);
    }

    // Tampilkan pesan sukses setelah masuk/daftar di homepage.
    if (data.flash) {
        window.setTimeout(function () {
            showToast(data.flash);
        }, 300);
    }

    /* ---------------------------------------------------------
       Menu navigasi mobile
       --------------------------------------------------------- */
    var menuToggle = document.getElementById('homeMenuToggle');
    var navLinks = document.getElementById('homeNavLinks');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function () {
            var open = navLinks.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ---------------------------------------------------------
       Carousel alat contoh
       --------------------------------------------------------- */
    var alatTrack = document.getElementById('alatTrack');
    var alatPrev = document.getElementById('alatPrev');
    var alatNext = document.getElementById('alatNext');
    var alatDots = document.getElementById('alatDots');

    if (alatTrack && alatTrack.classList.contains('is-carousel')) {
        var slides = Array.prototype.slice.call(alatTrack.querySelectorAll('.home-card'));
        var dots = [];
        var scrollTicking = false;
        var marqueeFrame = null;
        var lastFrameTime = 0;
        // Gerak lambat dan terus-menerus: 34 piksel per detik.
        var marqueeSpeed = 0.034;
        var autoplayPaused = false;
        var carouselVisible = true;
        var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

        // Gandakan kartu agar perpindahan dari kartu terakhir ke pertama mulus.
        var clones = [];

        function buildClones() {
            if (reducedMotion || clones.length || !slides.length) {
                return;
            }

            slides.forEach(function (slide) {
                var clone = slide.cloneNode(true);
                clone.classList.add('is-clone');
                clone.setAttribute('aria-hidden', 'true');

                if ('inert' in clone) {
                    clone.inert = true;
                }

                Array.prototype.forEach.call(
                    clone.querySelectorAll('a, button, input, select, textarea, [tabindex]'),
                    function (element) {
                        element.setAttribute('tabindex', '-1');
                        element.setAttribute('aria-hidden', 'true');
                    }
                );

                alatTrack.appendChild(clone);
                clones.push(clone);
            });
        }

        function slideGap() {
            if (slides.length < 2) {
                return 18;
            }

            return Math.max(0, slides[1].offsetLeft - (slides[0].offsetLeft + slides[0].offsetWidth));
        }

        function slideStep() {
            if (!slides.length) {
                return alatTrack.clientWidth || 1;
            }

            return slides[0].offsetWidth + slideGap();
        }

        function cardsPerView() {
            var step = slideStep();

            if (!step) {
                return 1;
            }

            return Math.max(1, Math.round((alatTrack.clientWidth + slideGap()) / step));
        }

        function pageCount() {
            return Math.max(1, Math.ceil(slides.length / cardsPerView()));
        }

        // Jarak satu putaran penuh (kartu asli + celah), bukan termasuk salinan.
        function loopSpan() {
            return slides.length * slideStep();
        }

        function activePage() {
            var step = slideStep() * cardsPerView();

            if (!step) {
                return 0;
            }

            return Math.min(pageCount() - 1, Math.round(alatTrack.scrollLeft / step));
        }

        // Bila posisi berada di area salinan, pindahkan ke posisi setara di awal.
        function normalizePosition() {
            var span = loopSpan();

            if (!span) {
                return 0;
            }

            var left = alatTrack.scrollLeft;

            if (left >= span || left < 0) {
                var normalized = ((left % span) + span) % span;
                alatTrack.scrollLeft = normalized;
                return normalized;
            }

            return left;
        }

        function scrollToPage(page) {
            var step = slideStep() * cardsPerView();

            normalizePosition();

            alatTrack.scrollTo({
                left: page * step,
                behavior: 'smooth'
            });
        }

        function canAutoplay() {
            return !reducedMotion
                && !autoplayPaused
                && carouselVisible
                && !document.hidden
                && pageCount() > 1;
        }

        function stopAutoplay() {
            if (marqueeFrame) {
                window.cancelAnimationFrame(marqueeFrame);
                marqueeFrame = null;
            }

            lastFrameTime = 0;
        }

        function fillFrame() {
            marqueeFrame = null;

            if (!canAutoplay()) {
                return;
            }

            // Pakai jam dinding agar kecepatan tetap sama di semua refresh rate.
            var now = window.performance && window.performance.now
                ? window.performance.now()
                : Date.now();

            if (!lastFrameTime) {
                lastFrameTime = now;
            }

            var delta = Math.min(64, now - lastFrameTime);
            lastFrameTime = now;

            var span = loopSpan();

            if (span > 0) {
                var next = alatTrack.scrollLeft + marqueeSpeed * delta;

                if (next >= span) {
                    next = ((next % span) + span) % span;
                }

                alatTrack.scrollLeft = next;
            }

            marqueeFrame = window.requestAnimationFrame(fillFrame);
        }

        function startAutoplay() {
            if (!canAutoplay() || marqueeFrame) {
                return;
            }

            normalizePosition();
            lastFrameTime = 0;
            marqueeFrame = window.requestAnimationFrame(fillFrame);
        }

        function pauseAutoplay() {
            autoplayPaused = true;
            stopAutoplay();
        }

        function resumeAutoplay() {
            autoplayPaused = false;
            startAutoplay();
        }

        function restartAutoplay() {
            if (autoplayPaused) {
                return;
            }

            startAutoplay();
        }

        var holdTimer = null;

        // Hentikan sementara saat pengguna menggeser/menekan navigasi.
        function holdAutoplay(delay) {
            stopAutoplay();

            if (holdTimer) {
                window.clearTimeout(holdTimer);
            }

            holdTimer = window.setTimeout(function () {
                holdTimer = null;
                restartAutoplay();
            }, delay);
        }

        function renderDots() {
            if (!alatDots) {
                return;
            }

            var count = pageCount();

            if (dots.length === count) {
                return;
            }

            alatDots.innerHTML = '';
            dots = [];

            for (var index = 0; index < count; index += 1) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'home-carousel-dot';
                dot.setAttribute('aria-label', 'Tampilkan kelompok alat ' + (index + 1));
                dot.addEventListener('click', (function (page) {
                    return function () {
                        scrollToPage(page);
                        holdAutoplay(800);
                    };
                })(index));
                alatDots.appendChild(dot);
                dots.push(dot);
            }
        }

        function updateCarousel() {
            var page = activePage();
            var single = pageCount() <= 1;

            if (alatPrev) {
                alatPrev.disabled = single;
            }

            if (alatNext) {
                alatNext.disabled = single;
            }

            dots.forEach(function (dot, index) {
                dot.classList.toggle('is-active', index === page);
                dot.setAttribute('aria-current', index === page ? 'true' : 'false');
            });
        }

        function refreshCarousel() {
            renderDots();
            updateCarousel();
        }

        if (alatPrev) {
            alatPrev.addEventListener('click', function () {
                scrollToPage((activePage() - 1 + pageCount()) % pageCount());
                holdAutoplay(800);
            });
        }

        if (alatNext) {
            alatNext.addEventListener('click', function () {
                scrollToPage((activePage() + 1) % pageCount());
                holdAutoplay(800);
            });
        }

        alatTrack.addEventListener('scroll', function () {
            if (scrollTicking) {
                return;
            }

            scrollTicking = true;
            window.requestAnimationFrame(function () {
                scrollTicking = false;
                updateCarousel();
            });
        }, { passive: true });

        // Hentikan autoplay saat kursor/fokus berada di carousel.
        alatTrack.addEventListener('mouseenter', pauseAutoplay);
        alatTrack.addEventListener('mouseleave', resumeAutoplay);
        alatTrack.addEventListener('focusin', pauseAutoplay);
        alatTrack.addEventListener('focusout', function (event) {
            if (!alatTrack.contains(event.relatedTarget)) {
                resumeAutoplay();
            }
        });

        // Beri waktu membaca setelah pengguna menggeser di layar sentuh.
        alatTrack.addEventListener('touchstart', pauseAutoplay, { passive: true });
        alatTrack.addEventListener('touchend', function () {
            autoplayPaused = false;
            holdAutoplay(1200);
        }, { passive: true });
        alatTrack.addEventListener('touchcancel', function () {
            autoplayPaused = false;
            holdAutoplay(1200);
        }, { passive: true });

        window.addEventListener('resize', function () {
            normalizePosition();
            refreshCarousel();
        });

        // Jeda saat tab tidak terlihat.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopAutoplay();
            } else {
                restartAutoplay();
            }
        });

        // Jangan berjalan saat carousel berada di luar layar.
        if ('IntersectionObserver' in window) {
            var carouselObserver = new IntersectionObserver(function (entries) {
                carouselVisible = entries[0].isIntersecting;

                if (carouselVisible) {
                    restartAutoplay();
                } else {
                    stopAutoplay();
                }
            }, { threshold: 0.25 });

            carouselObserver.observe(alatTrack);
        }

        buildClones();
        refreshCarousel();
        startAutoplay();
    }

    /* ---------------------------------------------------------
       Keranjang multi-item
       --------------------------------------------------------- */
    var cartFab = document.getElementById('cartFab');
    var cartFabCount = document.getElementById('cartFabCount');
    var cartOverlay = document.getElementById('cartOverlay');
    var cartItems = document.getElementById('cartItems');
    var cartEmpty = document.getElementById('cartEmpty');
    var cartTotal = document.getElementById('cartTotal');
    var cartSubmit = document.getElementById('cartSubmit');
    var cartNote = document.getElementById('cartNote');

    var cart = readCart();

    function readCart() {
        var result = {};

        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            var parsed = raw ? JSON.parse(raw) : {};

            if (!parsed || typeof parsed !== 'object') {
                return result;
            }

            Object.keys(parsed).forEach(function (key) {
                var item = alats.get(String(key));

                if (!item) {
                    return;
                }

                var max = parseInt(item.max, 10) || 0;
                var qty = parseInt(parsed[key], 10);

                if (!isFinite(qty) || max <= 0) {
                    return;
                }

                qty = Math.min(Math.max(qty, 1), max);
                result[String(key)] = qty;
            });
        } catch (error) {
            return {};
        }

        return result;
    }

    function saveCart() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        } catch (error) {
            // Penyimpanan bisa diblokir di mode privat; keranjang tetap jalan
            // selama halaman belum dimuat ulang.
        }
    }

    function cartEntries() {
        return Object.keys(cart).map(function (id) {
            return [id, cart[id]];
        });
    }

    function cartUnitTotal() {
        return cartEntries().reduce(function (total, entry) {
            return total + entry[1];
        }, 0);
    }

    function renderCart() {
        var entries = cartEntries();
        var total = cartUnitTotal();

        if (cartFab) {
            cartFab.hidden = entries.length === 0;
        }

        if (cartFabCount) {
            cartFabCount.textContent = formatNumber(total);
        }

        if (cartTotal) {
            cartTotal.textContent = formatNumber(total);
        }

        if (cartItems) {
            cartItems.innerHTML = entries.map(function (entry) {
                var id = entry[0];
                var qty = entry[1];
                var item = alats.get(id);

                if (!item) {
                    return '';
                }

                var max = parseInt(item.max, 10) || 0;

                return '' +
                    '<div class="home-cart-line">' +
                        '<img src="' + escapeHtml(item.img) + '" alt="">' +
                        '<div class="home-cart-line-info">' +
                            '<strong>' + escapeHtml(item.nama) + '</strong>' +
                            '<span>' + escapeHtml(item.code) + ' &middot; ' + escapeHtml(item.jenis) + '</span>' +
                        '</div>' +
                        '<div class="home-cart-qty">' +
                            '<button type="button" data-cart-dec="' + escapeHtml(id) + '" aria-label="Kurangi jumlah">&minus;</button>' +
                            '<span>' + qty + '</span>' +
                            '<button type="button" data-cart-inc="' + escapeHtml(id) + '" aria-label="Tambah jumlah"' + (qty >= max ? ' disabled' : '') + '>+</button>' +
                        '</div>' +
                        '<button type="button" class="home-cart-remove" data-cart-remove="' + escapeHtml(id) + '" aria-label="Hapus dari keranjang">&times;</button>' +
                    '</div>';
            }).join('');
        }

        if (cartEmpty) {
            cartEmpty.hidden = entries.length > 0;
        }

        if (cartSubmit && cartNote) {
            if (entries.length === 0) {
                cartSubmit.textContent = 'Pilih alat dulu';
                cartSubmit.setAttribute('href', '#katalog');
                cartSubmit.setAttribute('aria-disabled', 'true');
                cartSubmit.classList.add('is-disabled');
                cartNote.textContent = 'Tambahkan alat dari katalog untuk mulai menyusun pengajuan.';
            } else if (data.authenticated) {
                var pairs = entries.map(function (entry) {
                    return entry[0] + ':' + entry[1];
                }).join(',');

                cartSubmit.textContent = 'Lanjut ke form peminjaman';
                cartSubmit.setAttribute('href', data.borrowUrl + '?cart=' + encodeURIComponent(pairs));
                cartSubmit.removeAttribute('aria-disabled');
                cartSubmit.classList.remove('is-disabled');
                cartNote.textContent = total + ' unit dari ' + entries.length + ' jenis alat akan diajukan dalam satu transaksi.';
            } else {
                var guestPairs = entries.map(function (entry) {
                    return entry[0] + ':' + entry[1];
                }).join(',');

                cartSubmit.textContent = 'Masuk & lanjutkan pengajuan';
                cartSubmit.setAttribute('href', data.borrowUrl + '?cart=' + encodeURIComponent(guestPairs));
                cartSubmit.removeAttribute('aria-disabled');
                cartSubmit.classList.remove('is-disabled');
                cartNote.textContent = 'Kamu akan diarahkan ke halaman masuk. Setelah masuk, pengajuan dilanjutkan otomatis dan keranjang tetap tersimpan.';
            }
        }
    }

    function setQty(id, qty) {
        id = String(id);
        var item = alats.get(id);

        if (!item) {
            return;
        }

        var max = parseInt(item.max, 10) || 0;
        qty = Math.min(Math.max(qty, 0), max);

        if (qty <= 0) {
            delete cart[id];
        } else {
            cart[id] = qty;
        }

        saveCart();
        renderCart();
    }

    function addItem(id) {
        id = String(id);
        var item = alats.get(id);

        if (!item) {
            return;
        }

        if ((parseInt(item.max, 10) || 0) <= 0) {
            showToast('Stok ' + item.nama + ' sedang habis.');
            return;
        }

        var current = cart[id] || 0;

        if (current >= item.max) {
            showToast('Jumlah ' + item.nama + ' sudah mencapai stok tersedia.');
            return;
        }

        cart[id] = current + 1;
        saveCart();
        renderCart();
        showToast(item.nama + ' ditambahkan ke keranjang.');
    }

    function openCart() {
        if (!cartOverlay) {
            return;
        }

        cartOverlay.classList.add('is-open');
        cartOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeCart() {
        if (!cartOverlay) {
            return;
        }

        cartOverlay.classList.remove('is-open');
        cartOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (cartFab) {
        cartFab.addEventListener('click', openCart);
    }

    document.addEventListener('click', function (event) {
        var target = event.target;

        var addButton = target.closest ? target.closest('[data-add-alat]') : null;

        if (addButton) {
            addItem(addButton.getAttribute('data-add-alat'));
            return;
        }

        var increment = target.closest ? target.closest('[data-cart-inc]') : null;

        if (increment) {
            var incId = increment.getAttribute('data-cart-inc');
            setQty(incId, (cart[incId] || 0) + 1);
            return;
        }

        var decrement = target.closest ? target.closest('[data-cart-dec]') : null;

        if (decrement) {
            var decId = decrement.getAttribute('data-cart-dec');
            setQty(decId, (cart[decId] || 0) - 1);
            return;
        }

        var removeButton = target.closest ? target.closest('[data-cart-remove]') : null;

        if (removeButton) {
            setQty(removeButton.getAttribute('data-cart-remove'), 0);
            return;
        }

        var closeButton = target.closest ? target.closest('[data-cart-close]') : null;

        if (closeButton) {
            closeCart();
        }
    });

    renderCart();

    /* ---------------------------------------------------------
       Scanner QR / barcode
       --------------------------------------------------------- */
    var scanModal = document.getElementById('scanModal');
    var scanOpenBtn = document.getElementById('scanOpenBtn');
    var scanStart = document.getElementById('scanStart');
    var scanStop = document.getElementById('scanStop');
    var scanVideo = document.getElementById('scanVideo');
    var scanPlaceholder = document.getElementById('scanPlaceholder');
    var scanStatus = document.getElementById('scanStatus');
    var scanManual = document.getElementById('scanManual');
    var scanCode = document.getElementById('scanCode');

    var scanStream = null;
    var scanDetector = null;
    var scanFrame = null;
    var scanning = false;

    function normalizeCode(value) {
        var code = String(value == null ? '' : value).trim();

        if (code === '') {
            return '';
        }

        try {
            var url = new URL(code);
            var fromQuery = url.searchParams.get('q');

            if (fromQuery) {
                return fromQuery.trim();
            }
        } catch (error) {
            // Bukan URL, gunakan nilai mentah.
        }

        return code;
    }

    function goSearch(value) {
        var code = normalizeCode(value);

        if (code === '') {
            return;
        }

        window.location.href = data.homeUrl + '?q=' + encodeURIComponent(code) + '#katalog';
    }

    function stopScan() {
        scanning = false;

        if (scanFrame) {
            window.cancelAnimationFrame(scanFrame);
            scanFrame = null;
        }

        if (scanStream) {
            scanStream.getTracks().forEach(function (track) {
                track.stop();
            });
            scanStream = null;
        }

        if (scanVideo) {
            scanVideo.srcObject = null;
        }

        if (scanPlaceholder) {
            scanPlaceholder.hidden = false;
        }

        if (scanStart) {
            scanStart.hidden = false;
        }

        if (scanStop) {
            scanStop.hidden = true;
        }
    }

    function openScan() {
        if (!scanModal) {
            return;
        }

        scanModal.classList.add('is-open');
        scanModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (scanPlaceholder) {
            scanPlaceholder.hidden = false;
        }

        if (scanCode) {
            window.setTimeout(function () {
                scanCode.focus();
            }, 120);
        }
    }

    function closeScan() {
        if (!scanModal) {
            return;
        }

        stopScan();
        scanModal.classList.remove('is-open');
        scanModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function scanLoop() {
        if (!scanning || !scanDetector || !scanVideo) {
            return;
        }

        scanDetector.detect(scanVideo).then(function (codes) {
            if (codes && codes.length > 0) {
                var value = codes[0].rawValue;
                stopScan();
                goSearch(value);
                return;
            }

            scanFrame = window.requestAnimationFrame(scanLoop);
        }).catch(function () {
            scanFrame = window.requestAnimationFrame(scanLoop);
        });
    }

    function startScan() {
        if (!scanStatus) {
            return;
        }

        if (!('BarcodeDetector' in window)) {
            scanStatus.textContent = 'Browser ini belum mendukung pemindaian otomatis. Masukkan kode inventaris secara manual di bawah.';
            if (scanCode) {
                scanCode.focus();
            }
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            scanStatus.textContent = 'Kamera tidak tersedia. Masukkan kode inventaris secara manual di bawah.';
            return;
        }

        scanStatus.textContent = 'Meminta izin kamera...';

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        }).then(function (stream) {
            scanStream = stream;

            if (scanVideo) {
                scanVideo.srcObject = stream;
                return scanVideo.play();
            }

            return null;
        }).then(function () {
            try {
                scanDetector = new BarcodeDetector();
            } catch (error) {
                scanDetector = null;
            }

            if (!scanDetector) {
                scanStatus.textContent = 'Pemindai otomatis tidak tersedia. Masukkan kode inventaris secara manual.';
                stopScan();
                return;
            }

            scanning = true;

            if (scanPlaceholder) {
                scanPlaceholder.hidden = true;
            }

            if (scanStart) {
                scanStart.hidden = true;
            }

            if (scanStop) {
                scanStop.hidden = false;
            }

            if (scanStatus) {
                scanStatus.textContent = 'Arahkan kamera ke label QR atau barcode alat.';
            }

            scanLoop();
        }).catch(function () {
            scanStatus.textContent = 'Tidak bisa mengakses kamera. Periksa izin kamera atau masukkan kode inventaris manual.';
            stopScan();
        });
    }

    if (scanOpenBtn) {
        scanOpenBtn.addEventListener('click', openScan);
    }

    if (scanStart) {
        scanStart.addEventListener('click', startScan);
    }

    if (scanStop) {
        scanStop.addEventListener('click', stopScan);
    }

    if (scanManual) {
        scanManual.addEventListener('submit', function (event) {
            event.preventDefault();

            if (scanCode) {
                goSearch(scanCode.value);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        var closeButton = target.closest ? target.closest('[data-scan-close]') : null;

        if (closeButton) {
            closeScan();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (cartOverlay && cartOverlay.classList.contains('is-open')) {
            closeCart();
        }

        if (scanModal && scanModal.classList.contains('is-open')) {
            closeScan();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopScan();
        }
    });

    window.addEventListener('pagehide', stopScan);
})();
