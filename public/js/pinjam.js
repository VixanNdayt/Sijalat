(function () {
    'use strict';

    /* ------------------------------------------------------------------
     * Keranjang peminjaman
     * ------------------------------------------------------------------ */
    let keranjang = [];

    const container = document.getElementById('keranjang-container');
    const summary = document.getElementById('keranjang-summary');
    const kosongPanel = document.getElementById('keranjang-kosong');
    const kosongModal = document.getElementById('keranjang-modal-kosong');
    const countBadge = document.getElementById('keranjang-count');
    const btnBukaForm = document.getElementById('btnBukaForm');
    const form = document.getElementById('formPeminjaman');
    const panelKeranjang = document.getElementById('keranjang-panel');
    const btnToggleKeranjang = document.getElementById('btnToggleKeranjang');

    /* Tampil/sembunyikan rincian keranjang (dipakai di layar kecil) */
    function setDetailKeranjang(terbuka) {
        if (!panelKeranjang || !btnToggleKeranjang) {
            return;
        }

        panelKeranjang.classList.toggle('is-open', terbuka);
        btnToggleKeranjang.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        btnToggleKeranjang.setAttribute(
            'aria-label',
            terbuka ? 'Sembunyikan rincian keranjang' : 'Tampilkan rincian keranjang'
        );
    }

    if (btnToggleKeranjang) {
        btnToggleKeranjang.addEventListener('click', function () {
            setDetailKeranjang(!panelKeranjang.classList.contains('is-open'));
        });
    }

    const alatMap = window.alatMap || {};

    function namaAlat(id) {
        return alatMap[id] || 'Alat';
    }

    // Hindari karakter HTML merusak markup
    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateState() {
        const jumlah = keranjang.length;

        if (countBadge) {
            countBadge.textContent = jumlah;
        }

        if (kosongPanel) {
            kosongPanel.style.display = jumlah === 0 ? 'block' : 'none';
        }

        if (kosongModal) {
            kosongModal.style.display = jumlah === 0 ? 'block' : 'none';
        }

        if (btnBukaForm) {
            btnBukaForm.disabled = jumlah === 0;
        }
    }

    function tambahKeKeranjang(id, qty) {
        const nama = namaAlat(id);
        const jumlah = parseInt(qty, 10) > 0 ? parseInt(qty, 10) : 1;

        keranjang.push(Number(id));

        // --- Item asli (input yang dikirim ke server) ---
        container.insertAdjacentHTML('beforeend', `
            <div class="item-cart" id="item-${id}">
                <div class="item-cart-info">
                    <p class="item-cart-nama">${escapeHtml(nama)}</p>
                    <input type="hidden" name="alat_id[]" value="${id}">
                </div>
                <div class="cart-value">
                    <div class="cart-qty-box">
                        <label class="cart-qty-label">Jumlah</label>
                        <input type="number" name="jumlah_minta[]" value="${jumlah}" min="1"
                               class="cart-qty" oninput="ubahJumlah(${id}, this.value)">
                    </div>
                    <button type="button" class="cart-remove" onclick="hapusBarang(${id})" aria-label="Hapus alat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18"></path>
                            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"></path>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            </div>
        `);

        // --- Ringkasan di panel kanan ---
        summary.insertAdjacentHTML('beforeend', `
            <div class="sum-item" id="sum-${id}">
                <span class="sum-nama">${escapeHtml(nama)}</span>
                <span class="sum-qty" id="sum-qty-${id}">${jumlah}x</span>
            </div>
        `);

        updateState();
    }

    window.pilihBarang = function (id, nama) {
        if (keranjang.includes(Number(id))) {
            alert('Barang ini sudah ada di keranjang!');
            return;
        }

        tambahKeKeranjang(id, 1);
    };

    window.hapusBarang = function (id) {
        const item = document.getElementById('item-' + id);
        const sum = document.getElementById('sum-' + id);

        if (item) {
            item.remove();
        }

        if (sum) {
            sum.remove();
        }

        keranjang = keranjang.filter(function (itemId) {
            return itemId !== Number(id);
        });

        updateState();
    };

    window.ubahJumlah = function (id, value) {
        const label = document.getElementById('sum-qty-' + id);

        if (label) {
            label.textContent = (parseInt(value, 10) || 1) + 'x';
        }
    };

    window.bukaFormPeminjaman = function () {
        if (keranjang.length === 0) {
            alert('Pilih minimal satu alat terlebih dahulu.');
            return;
        }

        window.bukaModal('modal-pinjam');
    };

    // Cegah submit jika keranjang kosong
    if (form) {
        form.addEventListener('submit', function (event) {
            if (keranjang.length === 0) {
                event.preventDefault();
                alert('Keranjang masih kosong. Pilih alat terlebih dahulu.');
            }
        });
    }

    /* ------------------------------------------------------------------
     * Filter & pencarian katalog
     * ------------------------------------------------------------------ */
    const searchInput = document.getElementById('searchInput');
    const filterKategori = document.getElementById('filterKategori');

    function jalankanFilter() {
        const keyword = (searchInput ? searchInput.value : '').toLowerCase();
        const kategori = filterKategori ? filterKategori.value.toLowerCase() : '';

        document.querySelectorAll('.item-box').forEach(function (card) {
            const namaBarang = card.getAttribute('data-nama') || '';
            const kategoriBarang = card.getAttribute('data-kategori') || '';

            const cocokNama = namaBarang.includes(keyword);
            const cocokKategori = kategori === '' || kategoriBarang.includes(kategori);

            card.style.display = cocokNama && cocokKategori ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', jalankanFilter);
    }

    if (filterKategori) {
        filterKategori.addEventListener('change', jalankanFilter);
    }

    /* ------------------------------------------------------------------
     * Pulihkan keranjang setelah validasi gagal (old input)
     * ------------------------------------------------------------------ */
    if (Array.isArray(window.oldCart) && window.oldCart.length > 0) {
        window.oldCart.forEach(function (item) {
            if (item && item.id && !keranjang.includes(Number(item.id))) {
                tambahKeKeranjang(item.id, item.qty || 1);
            }
        });

        // Buka form lagi supaya pesan error langsung terlihat
        window.bukaModal('modal-pinjam');
    }
})();
