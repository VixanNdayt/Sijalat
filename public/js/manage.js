(function () {
    'use strict';

    /* ---------------------------------------------------------
       Pencarian katalog alat (filter di sisi klien)
       --------------------------------------------------------- */
    const searchInput = document.getElementById('cariAlat');
    const grid = document.getElementById('gridKatalog');
    const countLabel = document.getElementById('alatCount');
    const emptyResult = document.getElementById('katalogKosong');

    if (searchInput && grid) {
        const cards = Array.from(grid.querySelectorAll('.card-alat'));
        const total = cards.length;

        const applyFilter = function () {
            const term = searchInput.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach(function (card) {
                const match = term === '' || (card.dataset.cari || '').includes(term);
                card.hidden = !match;

                if (match) {
                    visible += 1;
                }
            });

            if (emptyResult) {
                emptyResult.hidden = visible > 0 || total === 0;
            }

            if (countLabel) {
                countLabel.textContent = term === ''
                    ? total + ' alat terdaftar'
                    : visible + ' dari ' + total + ' alat';
            }
        };

        searchInput.addEventListener('input', applyFilter);
        applyFilter();
    }

    const form = document.getElementById('formAlat');

    if (!form) {
        return;
    }

    const storeUrl = form.dataset.storeUrl;
    const updateBase = form.dataset.updateUrl;
    const openOnLoad = form.dataset.openOnLoad === '1';

    const title = document.getElementById('formTitle');
    const methodInput = document.getElementById('formMethod');
    const idInput = document.getElementById('inputAlatId');
    const submitBtn = document.getElementById('btnSubmit');
    const cancelBtn = document.getElementById('btnBatal');
    const fotoInput = document.getElementById('inputFoto');

    const fields = {
        nama: document.getElementById('inputNama'),
        code: document.getElementById('inputCode'),
        lokasi: document.getElementById('inputLokasi'),
        deskripsi: document.getElementById('inputDeskripsi'),
        jenis: document.getElementById('inputJenis'),
        baik: document.getElementById('inputBaik'),
        cek: document.getElementById('inputCek'),
        rusak: document.getElementById('inputRusak'),
        mtc: document.getElementById('inputMtc')
    };

    function setModeCreate() {
        title.textContent = 'Tambah Alat Baru';
        methodInput.value = 'POST';
        form.action = storeUrl;
        idInput.value = '';
        submitBtn.textContent = 'Simpan Alat';
        cancelBtn.style.display = 'none';

        if (fotoInput) {
            fotoInput.setAttribute('required', 'required');
        }
    }

    function setModeEdit() {
        methodInput.value = 'PUT';
        form.action = updateBase + '/' + idInput.value;
        submitBtn.textContent = 'Update Alat';
        cancelBtn.style.display = 'block';

        if (fotoInput) {
            fotoInput.removeAttribute('required');
        }
    }

    // Tombol "Tambah Alat" pada toolbar
    window.modeTambahAlat = function () {
        form.reset();
        setModeCreate();
        window.bukaModal('modal-alat');
    };

    // Tombol "Edit" pada setiap kartu alat
    window.editAlat = function (alat) {
        title.textContent = 'Edit Alat: ' + alat.nama;
        idInput.value = alat.id;
        setModeEdit();

        fields.nama.value = alat.nama || '';
        fields.code.value = alat.code || '';
        if (fields.lokasi) {
            fields.lokasi.value = alat.lokasi || '';
        }
        fields.deskripsi.value = alat.description || alat.deskripsi || '';
        fields.jenis.value = alat.jenis || '';
        fields.jenis.dispatchEvent(new Event('change', { bubbles: true }));
        fields.baik.value = alat.kondisi_baik != null ? alat.kondisi_baik : 0;
        fields.cek.value = alat.kondisi_pengecekan != null ? alat.kondisi_pengecekan : 0;
        fields.rusak.value = alat.kondisi_rusak != null ? alat.kondisi_rusak : 0;
        fields.mtc.value = alat.kondisi_maintenance != null ? alat.kondisi_maintenance : 0;

        window.bukaModal('modal-alat');
    };

    // Tombol "Batal" di dalam form edit
    window.resetForm = function () {
        form.reset();
        setModeCreate();
        window.tutupModal('modal-alat');
    };

    // Setelah validasi gagal, form dibuka kembali dengan isian sebelumnya
    if (openOnLoad) {
        if (methodInput.value === 'PUT' && idInput.value) {
            title.textContent = 'Edit Alat';
            setModeEdit();
        } else {
            setModeCreate();
        }

        window.bukaModal('modal-alat');
    }
})();
