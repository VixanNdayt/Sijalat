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

    const pinjamDataEl = document.getElementById('pinjam-data');
    const pinjamData = pinjamDataEl ? JSON.parse(pinjamDataEl.textContent) : {};
    const alatMap = pinjamData.alatMap || {};
    const stokMap = pinjamData.stokMap || {};

    function namaAlat(id) {
        return alatMap[id] || 'Alat';
    }

    function stokAlat(id) {
        return Math.max(0, parseInt(stokMap[id], 10) || 0);
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
        const stok = stokAlat(id);
        const batas = stok > 0 ? stok : 1;
        let jumlah = parseInt(qty, 10) || 1;

        jumlah = Math.min(Math.max(jumlah, 1), batas);

        keranjang.push(Number(id));

        // --- Item asli (input yang dikirim ke server) ---
        container.insertAdjacentHTML('beforeend', `
            <div class="item-cart" id="item-${id}">
                <div class="item-cart-info">
                    <p class="item-cart-nama">${escapeHtml(nama)}</p>
                    <p class="item-cart-stok">Stok tersedia: ${batas}</p>
                    <input type="hidden" name="alat_id[]" value="${id}">
                </div>
                <div class="cart-value">
                    <div class="cart-qty-box">
                        <label class="cart-qty-label">Jumlah</label>
                        <input type="number" name="jumlah_minta[]" value="${jumlah}" min="1" max="${batas}"
                               class="cart-qty" data-stok="${batas}" data-cart-id="${id}">
                    </div>
                    <button type="button" class="cart-remove" data-cart-remove="${id}" aria-label="Hapus alat">
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
        const stok = stokAlat(id);

        if (stok <= 0) {
            alert('Stok alat ini sedang habis.');
            return;
        }

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
        const input = document.querySelector('#item-' + id + ' .cart-qty');
        const batas = Math.max(1, parseInt(input ? input.dataset.stok : 0, 10) || stokAlat(id) || 1);
        let nilai = parseInt(value, 10) || 1;

        if (nilai > batas) {
            nilai = batas;
            if (input) {
                input.value = batas;
            }
            alert('Jumlah melebihi stok tersedia (' + batas + ').');
        } else if (nilai < 1) {
            nilai = 1;
            if (input) {
                input.value = 1;
            }
        }

        const label = document.getElementById('sum-qty-' + id);

        if (label) {
            label.textContent = nilai + 'x';
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
    const btnSearch = document.getElementById('btnSearch');

    function jalankanFilter() {
        const keyword = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const kategori = filterKategori ? filterKategori.value.trim().toLowerCase() : '';

        document.querySelectorAll('.item-box').forEach(function (card) {
            const namaBarang = (card.getAttribute('data-nama') || '').toLowerCase();
            const kategoriBarang = (card.getAttribute('data-kategori') || '').trim().toLowerCase();

            const cocokNama = namaBarang.includes(keyword);
            const cocokKategori = kategori === '' || kategoriBarang === kategori;

            card.style.display = cocokNama && cocokKategori ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', jalankanFilter);

        // Tekan Enter di kolom pencarian untuk langsung memfilter
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                jalankanFilter();
            }
        });
    }

    if (filterKategori) {
        filterKategori.addEventListener('change', jalankanFilter);
    }

    if (btnSearch) {
        btnSearch.addEventListener('click', jalankanFilter);
    }

    /* Dropdown filter jenis alat (tampilan kustom) */
    const filterSelect = document.getElementById('filterSelect');
    const filterTrigger = document.getElementById('filterKategoriTrigger');
    const filterMenu = document.getElementById('filterKategoriMenu');
    const filterLabel = document.getElementById('filterKategoriLabel');

    if (filterSelect && filterTrigger && filterMenu && filterLabel) {
        const filterOptions = Array.prototype.slice.call(
            filterMenu.querySelectorAll('.filter-select-option')
        );

        function bukaFilter(open) {
            filterMenu.hidden = !open;
            filterTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function pilihFilter(option) {
            filterOptions.forEach(function (item) {
                const selected = item === option;

                item.setAttribute('aria-selected', selected ? 'true' : 'false');
                item.classList.toggle('is-selected', selected);
            });

            filterLabel.textContent = option.textContent.trim();

            if (filterKategori) {
                filterKategori.value = option.getAttribute('data-value') || '';
                filterKategori.dispatchEvent(new Event('change', { bubbles: true }));
            }

            bukaFilter(false);
            filterTrigger.focus();
        }

        function fokusOpsi(arah) {
            const aktif = document.activeElement;
            let index = filterOptions.indexOf(aktif);

            if (index === -1) {
                index = filterOptions.findIndex(function (option) {
                    return option.getAttribute('aria-selected') === 'true';
                });

                if (index === -1) {
                    index = 0;
                }
            } else {
                index = Math.max(0, Math.min(filterOptions.length - 1, index + arah));
            }

            if (filterOptions[index]) {
                filterOptions[index].focus();
            }
        }

        filterTrigger.addEventListener('click', function () {
            bukaFilter(filterMenu.hidden);
        });

        filterTrigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                bukaFilter(true);
                fokusOpsi(0);
            }
        });

        filterMenu.addEventListener('click', function (event) {
            const option = event.target.closest('.filter-select-option');

            if (option) {
                pilihFilter(option);
            }
        });

        filterMenu.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                fokusOpsi(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                fokusOpsi(-1);
            } else if (event.key === 'Enter' || event.key === ' ') {
                if (document.activeElement && document.activeElement.classList.contains('filter-select-option')) {
                    event.preventDefault();
                    pilihFilter(document.activeElement);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                bukaFilter(false);
                filterTrigger.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (!filterMenu.hidden && !filterSelect.contains(event.target)) {
                bukaFilter(false);
            }
        });

        document.addEventListener('focusin', function (event) {
            if (!filterMenu.hidden && !filterSelect.contains(event.target)) {
                bukaFilter(false);
            }
        });
    }

    /* ------------------------------------------------------------------
     * Batasi waktu peminjaman sesuai jam sekolah
     * ------------------------------------------------------------------ */
    const jadwalData = pinjamData.jadwal || {};
    const AMBIL_MULAI = jadwalData.ambil_mulai || '06:30';
    const KEMBALI_TERAKHIR = jadwalData.kembali_terakhir || {};

    const inputMulai = document.querySelector('input[name="dipinjam_saat"]');
    const inputKembali = document.querySelector('input[name="dikembalikan_saat"]');

    function aturanHari(tanggal) {
        // getDay(): 0 = Minggu, 1 = Senin, ... 6 = Sabtu. Samakan ke ISO.
        const hari = tanggal.getDay() === 0 ? 7 : tanggal.getDay();
        const tutup = KEMBALI_TERAKHIR[hari] || KEMBALI_TERAKHIR[String(hari)];

        if (!tutup) {
            return null;
        }

        return { buka: AMBIL_MULAI, tutup: tutup };
    }

    function aturBatasJam(input, jenis) {
        if (!input) {
            return;
        }

        // Selalu bersihkan pesan lama, termasuk saat kolom dikosongkan.
        input.setCustomValidity('');

        if (!input.value || input.value.length < 10) {
            input.removeAttribute('min');
            input.removeAttribute('max');
            return;
        }

        const tanggal = input.value.slice(0, 10);
        const waktu = input.value.slice(11, 16) || '00:00';
        const aturan = aturanHari(new Date(tanggal + 'T00:00:00'));

        if (!aturan) {
            input.removeAttribute('min');
            input.removeAttribute('max');
            input.setCustomValidity(
                (jenis === 'mulai' ? 'Pengambilan' : 'Pengembalian')
                + ' hanya dapat dilakukan pada hari Senin sampai Jumat.'
            );
            return;
        }

        let batasBawah = aturan.buka;

        if (jenis === 'kembali' && inputMulai && inputMulai.value
            && inputMulai.value.slice(0, 10) === tanggal) {
            batasBawah = inputMulai.value.slice(11, 16) || aturan.buka;
        }

        input.min = tanggal + 'T' + batasBawah;
        input.max = tanggal + 'T' + aturan.tutup;

        if (jenis === 'mulai') {
            if (waktu < aturan.buka) {
                input.setCustomValidity(
                    'Pengambilan paling cepat pukul ' + aturan.buka.replace(':', '.')
                    + ' pada tanggal yang dipilih.'
                );
            } else if (waktu > aturan.tutup) {
                input.setCustomValidity(
                    'Pengambilan paling lambat pukul ' + aturan.tutup.replace(':', '.')
                    + ' (jam terakhir pembelajaran).'
                );
            }

            return;
        }

        if (waktu < batasBawah) {
            input.setCustomValidity('Waktu pengembalian tidak boleh sebelum waktu pengambilan.');
        } else if (waktu > aturan.tutup) {
            input.setCustomValidity(
                'Pengembalian paling lambat pukul ' + aturan.tutup.replace(':', '.')
                + ' (jam terakhir pembelajaran).'
            );
        }
    }

    function perbaruiBatasWaktu() {
        aturBatasJam(inputMulai, 'mulai');
        aturBatasJam(inputKembali, 'kembali');

        if (inputMulai && inputKembali && inputMulai.value && inputKembali.value) {
            const tanggalSama = inputMulai.value.slice(0, 10) === inputKembali.value.slice(0, 10);

            if (tanggalSama && inputKembali.value < inputMulai.value && !inputKembali.validationMessage) {
                inputKembali.setCustomValidity('Waktu pengembalian tidak boleh sebelum waktu pengambilan.');
            }
        }
    }

    [inputMulai, inputKembali].forEach(function (input) {
        if (!input) {
            return;
        }

        input.addEventListener('input', perbaruiBatasWaktu);
        input.addEventListener('change', perbaruiBatasWaktu);
    });

    perbaruiBatasWaktu();

    /* ------------------------------------------------------------------
     * Delegasi event keranjang (pengganti onclick/oninput yang diblokir CSP)
     * ------------------------------------------------------------------ */
    document.addEventListener('input', function (event) {
        const input = event.target.closest('.cart-qty');

        if (input && input.dataset.cartId) {
            window.ubahJumlah(Number(input.dataset.cartId), input.value);
        }
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-cart-remove]');

        if (button) {
            window.hapusBarang(Number(button.dataset.cartRemove));
        }
    });

    /* ------------------------------------------------------------------
     * Pulihkan keranjang setelah validasi gagal (old input)
     * ------------------------------------------------------------------ */
    if (Array.isArray(pinjamData.oldCart) && pinjamData.oldCart.length > 0) {
        pinjamData.oldCart.forEach(function (item) {
            if (item && item.id && !keranjang.includes(Number(item.id))) {
                tambahKeKeranjang(item.id, item.qty || 1);
            }
        });

        // Buka form lagi supaya pesan error langsung terlihat
        window.bukaModal('modal-pinjam');
    }

    /* ------------------------------------------------------------------
     * Delegasi tombol katalog (pengganti onclick yang diblokir CSP)
     * ------------------------------------------------------------------ */
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-action]');

        if (!trigger) {
            return;
        }

        if (trigger.dataset.action === 'pilih-barang') {
            window.pilihBarang(Number(trigger.dataset.id), trigger.dataset.nama);
        } else if (trigger.dataset.action === 'buka-form') {
            window.bukaFormPeminjaman();
        }
    });
})();
