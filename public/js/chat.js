/* =========================================================
   Interaksi halaman chat: polling, kirim pesan, pencarian
   ========================================================= */
(function () {
    'use strict';

    const root = document.getElementById('chatRoot');
    if (!root) {
        return;
    }

    const mode = root.dataset.mode || 'inbox';
    const stream = document.getElementById('chatStream');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const sendButton = document.getElementById('chatSend');
    const alertBox = document.getElementById('chatAlert');
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrf ? csrf.content : '';

    const pollUrl = root.dataset.pollUrl || '';
    const sendUrl = root.dataset.sendUrl || '';
    const pollInterval = 4000;

    /* Lampiran gambar */
    const attachBtn = document.getElementById('chatAttach');
    const imageInput = document.getElementById('chatImageInput');
    const previewBox = document.getElementById('chatPreview');
    const previewImg = document.getElementById('chatPreviewImg');
    const previewSize = document.getElementById('chatPreviewSize');
    const previewRemove = document.getElementById('chatPreviewRemove');

    const MAX_DIMENSI = 1600;
    const KUALITAS_JPEG = 0.82;
    const BATAS_GAMBAR = 5 * 1024 * 1024;

    let lampiran = null; // { blob, name }
    let previewUrl = '';

    let lastId = parseInt(root.dataset.after || '0', 10) || 0;
    let lastDate = (stream && stream.dataset.lastDate) || '';
    let sending = false;
    let pollTimer = null;
    let errorTimer = null;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- Pencarian percakapan (admin) ---------------- */

    const search = document.getElementById('chatSearch');
    const contacts = Array.from(document.querySelectorAll('.chat-contact'));
    const noResult = document.getElementById('chatNoResult');

    if (search && contacts.length) {
        search.addEventListener('input', function () {
            const keyword = search.value.trim().toLowerCase();
            let visible = 0;

            contacts.forEach(function (contact) {
                const cocok = keyword === '' || (contact.dataset.search || '').includes(keyword);
                contact.hidden = !cocok;

                if (cocok) {
                    visible += 1;
                }
            });

            if (noResult) {
                noResult.hidden = visible !== 0;
            }
        });
    }

    /* ---------------- Utilitas ---------------- */

    function initials(name) {
        return String(name || '?')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (word) {
                return word.charAt(0);
            })
            .join('')
            .toUpperCase() || '?';
    }

    function nearBottom() {
        if (!stream) {
            return true;
        }

        return stream.scrollHeight - stream.scrollTop - stream.clientHeight < 120;
    }

    function scrollToBottom(force) {
        if (!stream) {
            return;
        }

        if (force || nearBottom()) {
            const behavior = reduceMotion ? 'auto' : 'smooth';
            stream.scrollTo({ top: stream.scrollHeight, behavior: behavior });
        }
    }

    function showError(message) {
        if (!alertBox) {
            return;
        }

        alertBox.textContent = message;
        alertBox.classList.add('show');

        if (errorTimer) {
            clearTimeout(errorTimer);
        }

        errorTimer = setTimeout(function () {
            alertBox.classList.remove('show');
        }, 7000);
    }

    function clearError() {
        if (!alertBox) {
            return;
        }

        if (errorTimer) {
            clearTimeout(errorTimer);
            errorTimer = null;
        }

        alertBox.textContent = '';
        alertBox.classList.remove('show');
    }

    function resizeInput() {
        if (!input) {
            return;
        }

        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    /* ---------------- Kompres gambar sebelum dikirim ---------------- */

    function formatUkuran(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
        }

        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function namaLampiran(nama, tipe) {
        const ekstensi = tipe === 'image/png'
            ? '.png'
            : (tipe === 'image/webp' ? '.webp' : '.jpg');

        const dasar = String(nama || 'gambar').replace(/\.[^.]+$/, '').slice(0, 60);

        return dasar + ekstensi;
    }

    function muatGambar(file) {
        if (typeof window.createImageBitmap === 'function') {
            return window.createImageBitmap(file);
        }

        return new Promise(function (resolve, reject) {
            const url = URL.createObjectURL(file);
            const gambar = new Image();

            gambar.onload = function () {
                URL.revokeObjectURL(url);
                resolve(gambar);
            };

            gambar.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('Gambar tidak dapat dibaca.'));
            };

            gambar.src = url;
        });
    }

    /**
     * Perkecil dimensi dan encode ulang ke JPEG agar hemat kuota.
     */
    async function kompresGambar(file) {
        let sumber;

        try {
            sumber = await muatGambar(file);
        } catch (error) {
            return file;
        }

        const lebarAsli = sumber.width || sumber.naturalWidth || 0;
        const tinggiAsli = sumber.height || sumber.naturalHeight || 0;

        if (!lebarAsli || !tinggiAsli) {
            return file;
        }

        const skala = Math.min(1, MAX_DIMENSI / Math.max(lebarAsli, tinggiAsli));
        const lebar = Math.max(1, Math.round(lebarAsli * skala));
        const tinggi = Math.max(1, Math.round(tinggiAsli * skala));

        const kanvas = document.createElement('canvas');
        kanvas.width = lebar;
        kanvas.height = tinggi;

        const konteks = kanvas.getContext('2d');

        if (!konteks) {
            return file;
        }

        // Latar putih supaya PNG transparan tidak menjadi hitam di JPEG.
        konteks.fillStyle = '#ffffff';
        konteks.fillRect(0, 0, lebar, tinggi);
        konteks.imageSmoothingEnabled = true;
        konteks.imageSmoothingQuality = 'high';
        konteks.drawImage(sumber, 0, 0, lebar, tinggi);

        if (typeof sumber.close === 'function') {
            sumber.close();
        }

        const blob = await new Promise(function (resolve) {
            kanvas.toBlob(resolve, 'image/jpeg', KUALITAS_JPEG);
        });

        if (!blob) {
            return file;
        }

        // Jangan memperbesar file yang memang sudah kecil.
        if (blob.size >= file.size && skala === 1) {
            return file;
        }

        return blob;
    }

    function bersihkanLampiran() {
        lampiran = null;

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }

        if (previewImg) {
            previewImg.removeAttribute('src');
        }

        if (previewBox) {
            previewBox.hidden = true;
        }

        if (imageInput) {
            imageInput.value = '';
        }
    }

    async function pilihGambar(file) {
        if (!file) {
            return;
        }

        if (!/^image\/(jpeg|jpg|png|webp)$/i.test(file.type)) {
            showError('Format gambar harus JPG, PNG, atau WEBP.');
            return;
        }

        if (attachBtn) {
            attachBtn.disabled = true;
        }

        try {
            const blob = await kompresGambar(file);

            if (blob.size > BATAS_GAMBAR) {
                showError('Gambar masih lebih dari 5 MB setelah dikompres. Pilih gambar lain.');
                return;
            }

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }

            previewUrl = URL.createObjectURL(blob);
            lampiran = { blob: blob, name: namaLampiran(file.name, blob.type) };

            if (previewImg) {
                previewImg.src = previewUrl;
            }

            if (previewSize) {
                previewSize.textContent = formatUkuran(blob.size) + ' siap dikirim';
            }

            if (previewBox) {
                previewBox.hidden = false;
            }
        } catch (error) {
            showError('Gambar gagal diproses. Coba gambar lain.');
        } finally {
            if (attachBtn) {
                attachBtn.disabled = false;
            }
        }
    }

    /* ---------------- Render satu pesan ---------------- */

    function addMessage(message, forceScroll) {
        if (!stream || !message) {
            return false;
        }

        if (stream.querySelector('[data-id="' + message.id + '"]')) {
            return false;
        }

        const empty = document.getElementById('chatEmpty');
        if (empty) {
            empty.remove();
        }

        if (message.date && message.date !== lastDate) {
            const day = document.createElement('div');
            day.className = 'chat-day';
            day.dataset.day = message.date;

            const label = document.createElement('span');
            label.textContent = message.date_label || message.date;
            day.appendChild(label);
            stream.appendChild(day);

            lastDate = message.date;
        }

        const row = document.createElement('div');
        row.className = 'chat-row ' + (message.mine ? 'mine' : 'theirs');
        row.dataset.id = message.id;

        if (message.edit_url) {
            row.dataset.editUrl = message.edit_url;
        }

        if (message.delete_url) {
            row.dataset.deleteUrl = message.delete_url;
        }

        if (!message.mine) {
            const avatar = document.createElement('span');
            avatar.className = 'chat-avatar';
            avatar.textContent = initials(message.sender_name);
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';

        if (!message.mine) {
            const sender = document.createElement('span');
            sender.className = 'chat-sender';
            sender.textContent = message.sender_name || 'Admin';
            bubble.appendChild(sender);
        }

        if (message.image_url) {
            const tautan = document.createElement('a');
            tautan.className = 'chat-image-link';
            tautan.href = message.image_url;
            tautan.target = '_blank';
            tautan.rel = 'noopener';

            const gambar = document.createElement('img');
            gambar.className = 'chat-image';
            gambar.src = message.image_url;
            gambar.alt = 'Lampiran gambar';
            gambar.loading = 'lazy';

            tautan.appendChild(gambar);
            bubble.appendChild(tautan);
        }

        if (message.body) {
            const body = document.createElement('p');
            body.textContent = message.body;
            bubble.appendChild(body);
        }

        const time = document.createElement('span');
        time.className = 'chat-time';
        time.textContent = message.time || '';
        bubble.appendChild(time);

        row.appendChild(bubble);
        stream.appendChild(row);

        if (message.id > lastId) {
            lastId = message.id;
        }

        scrollToBottom(forceScroll);
        return true;
    }

    /* ---------------- Polling pesan baru ---------------- */

    async function poll() {
        if (!pollUrl || sending || document.visibilityState !== 'visible') {
            return;
        }

        const separator = pollUrl.indexOf('?') === -1 ? '?' : '&';

        try {
            const response = await fetch(pollUrl + separator + 'after=' + lastId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const messages = Array.isArray(data.messages) ? data.messages : [];
            const wasNearBottom = nearBottom();
            let added = 0;

            messages.forEach(function (message) {
                if (addMessage(message, false)) {
                    added += 1;
                }
            });

            if (typeof data.last_id === 'number' && data.last_id > lastId) {
                lastId = data.last_id;
            }

            if (added > 0 && wasNearBottom) {
                scrollToBottom(true);
            }
        } catch (error) {
            /* Jaringan sedang bermasalah: coba lagi pada polling berikutnya. */
        }
    }

    function startPolling() {
        if (!pollUrl || pollTimer) {
            return;
        }

        pollTimer = setInterval(poll, pollInterval);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    /* ---------------- Kirim pesan ---------------- */

    async function kirimPesan(body) {
        sending = true;
        clearError();

        if (sendButton) {
            sendButton.disabled = true;
        }

        try {
            const payload = new FormData();
            payload.append('body', body);

            if (lampiran) {
                payload.append('image', lampiran.blob, lampiran.name);
            }

            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: payload
            });

            let data = null;

            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }

            if (!response.ok) {
                if (response.status === 419) {
                    throw new Error('Sesi kamu sudah berakhir. Muat ulang halaman lalu coba lagi.');
                }

                if (response.status === 422 && data && data.errors) {
                    const pesan = data.errors.body || data.errors.image;

                    if (pesan && pesan.length) {
                        throw new Error(pesan[0]);
                    }
                }

                throw new Error((data && data.message) || 'Pesan gagal dikirim. Coba lagi.');
            }

            if (data && data.message) {
                addMessage(data.message, true);
            }

            bersihkanLampiran();

            if (input) {
                input.value = '';
                resizeInput();
                input.focus();
            }
        } catch (error) {
            showError(error.message || 'Pesan gagal dikirim. Periksa koneksi lalu coba lagi.');
        } finally {
            sending = false;

            if (sendButton) {
                sendButton.disabled = false;
            }
        }
    }

    /* ---------------- Hapus pesan / riwayat ---------------- */

    function tampilkanKosong() {
        if (!stream || stream.querySelector('.chat-row')) {
            return;
        }

        stream.querySelectorAll('.chat-day').forEach(function (hari) {
            hari.remove();
        });

        if (stream.querySelector('#chatEmpty')) {
            return;
        }

        const kotak = document.createElement('div');
        kotak.className = 'chat-empty-state';
        kotak.id = 'chatEmpty';

        const judul = document.createElement('h3');
        judul.textContent = 'Belum ada percakapan';

        const isi = document.createElement('p');
        isi.textContent = 'Riwayat chat sudah kosong. Kirim pesan baru untuk memulai lagi.';

        kotak.appendChild(judul);
        kotak.appendChild(isi);
        stream.appendChild(kotak);
    }

    /* ---------------- Menu klik kanan ---------------- */

    const menu = document.createElement('div');
    menu.className = 'chat-menu';
    menu.hidden = true;
    menu.setAttribute('role', 'menu');
    document.body.appendChild(menu);

    function tutupMenu() {
        menu.hidden = true;
        menu.textContent = '';
    }

    function itemMenu(label, aksi, jenis) {
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'chat-menu-item' + (jenis ? ' ' + jenis : '');
        tombol.setAttribute('role', 'menuitem');
        tombol.textContent = label;

        tombol.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            tutupMenu();
            aksi();
        });

        return tombol;
    }

    function bukaMenu(baris, x, y) {
        const editUrl = baris.dataset.editUrl || '';
        const deleteUrl = baris.dataset.deleteUrl || '';

        tutupMenu();

        if (editUrl) {
            menu.appendChild(itemMenu('Edit pesan', function () {
                editPesan(baris, editUrl);
            }));
        }

        if (deleteUrl) {
            menu.appendChild(itemMenu('Hapus pesan', function () {
                hapusPesan(baris, deleteUrl);
            }, 'danger'));
        }

        if (!menu.childElementCount) {
            return;
        }

        menu.hidden = false;
        menu.style.left = '0px';
        menu.style.top = '0px';

        const kotak = menu.getBoundingClientRect();
        const kiri = Math.max(8, Math.min(x, window.innerWidth - kotak.width - 8));
        const atas = Math.max(8, Math.min(y, window.innerHeight - kotak.height - 8));

        menu.style.left = kiri + 'px';
        menu.style.top = atas + 'px';
    }

    async function editPesan(baris, url) {
        const teksLama = baris.querySelector('.chat-bubble > p');
        const sekarang = teksLama ? teksLama.textContent : '';
        const hasil = window.prompt('Edit pesan', sekarang);

        if (hasil === null) {
            return;
        }

        const teks = hasil.trim();

        if (teks === '') {
            showError('Pesan tidak boleh kosong.');
            return;
        }

        if (teks.length > 2000) {
            showError('Pesan terlalu panjang. Maksimal 2000 karakter.');
            return;
        }

        if (teks === sekarang) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ body: teks })
            });

            if (!response.ok) {
                throw new Error('Pesan gagal diperbarui. Coba lagi.');
            }

            const data = await response.json();

            if (data && data.message) {
                perbaruiPesan(baris, data.message);
            }
        } catch (error) {
            showError(error.message);
        }
    }

    function perbaruiPesan(baris, message) {
        const bubble = baris.querySelector('.chat-bubble');

        if (!bubble) {
            return;
        }

        const waktu = bubble.querySelector('.chat-time');
        let isi = bubble.querySelector('p');

        if (!isi) {
            isi = document.createElement('p');
            bubble.insertBefore(isi, waktu || null);
        }

        isi.textContent = message.body || '';

        if (waktu) {
            waktu.textContent = (message.time || '') + (message.edited ? ' \u00b7 diedit' : '');
        }
    }

    async function hapusPesan(baris, url) {
        if (!url) {
            return;
        }

        if (!window.confirm('Hapus pesan ini?')) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Pesan gagal dihapus. Coba lagi.');
            }

            baris.remove();
            tampilkanKosong();
        } catch (error) {
            showError(error.message);
        }
    }

    async function hapusRiwayat(tombol) {
        const url = tombol.dataset.clearUrl;

        if (!url) {
            return;
        }

        if (!window.confirm(tombol.dataset.konfirmasi || 'Hapus seluruh riwayat chat?')) {
            return;
        }

        tombol.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Riwayat chat gagal dihapus. Coba lagi.');
            }

            if (tombol.dataset.reload === '1') {
                window.location.reload();
                return;
            }

            if (stream) {
                stream.querySelectorAll('.chat-row, .chat-day').forEach(function (el) {
                    el.remove();
                });
            }

            lastId = 0;
            lastDate = '';
            tampilkanKosong();
        } catch (error) {
            showError(error.message);
        } finally {
            tombol.disabled = false;
        }
    }

    /* ---------------- Pasang event menu & hapus riwayat ---------------- */

    if (stream) {
        stream.addEventListener('contextmenu', function (event) {
            const baris = event.target.closest('.chat-row');

            if (!baris) {
                return;
            }

            if (!baris.dataset.editUrl && !baris.dataset.deleteUrl) {
                return;
            }

            event.preventDefault();
            bukaMenu(baris, event.clientX, event.clientY);
        });

        // Tekan lama di layar sentuh
        let tekanTimer = null;

        stream.addEventListener('touchstart', function (event) {
            const baris = event.target.closest('.chat-row');

            if (!baris) {
                return;
            }

            clearTimeout(tekanTimer);
            tekanTimer = setTimeout(function () {
                const sentuh = event.touches[0];

                if (sentuh) {
                    bukaMenu(baris, sentuh.clientX, sentuh.clientY);
                }
            }, 550);
        }, { passive: true });

        ['touchend', 'touchmove', 'touchcancel'].forEach(function (nama) {
            stream.addEventListener(nama, function () {
                clearTimeout(tekanTimer);
            }, { passive: true });
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.chat-menu')) {
            tutupMenu();
        }

        const tombolBersih = event.target.closest('[data-clear-url]');

        if (tombolBersih) {
            event.preventDefault();
            hapusRiwayat(tombolBersih);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupMenu();
        }
    });

    window.addEventListener('scroll', tutupMenu, true);
    window.addEventListener('resize', tutupMenu);

    /* ---------------- Pasang event ---------------- */

    if (attachBtn && imageInput) {
        attachBtn.addEventListener('click', function () {
            imageInput.click();
        });

        imageInput.addEventListener('change', function () {
            pilihGambar(imageInput.files && imageInput.files[0]);
        });
    }

    if (previewRemove) {
        previewRemove.addEventListener('click', function () {
            bersihkanLampiran();
        });
    }

    if (form && input && sendUrl) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const body = input.value.trim();

            if ((body === '' && !lampiran) || sending) {
                return;
            }

            if (body.length > 2000) {
                showError('Pesan terlalu panjang. Maksimal 2000 karakter.');
                return;
            }

            kirimPesan(body);
        });

        input.addEventListener('input', resizeInput);

        input.addEventListener('paste', function (event) {
            const items = event.clipboardData ? event.clipboardData.items : null;

            if (!items) {
                return;
            }

            for (let i = 0; i < items.length; i += 1) {
                if (items[i].type && items[i].type.indexOf('image/') === 0) {
                    const file = items[i].getAsFile();

                    if (file) {
                        event.preventDefault();
                        pilihGambar(file);
                    }

                    return;
                }
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        resizeInput();
    }

    if (stream && mode !== 'inbox') {
        window.requestAnimationFrame(function () {
            scrollToBottom(true);
        });

        startPolling();

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                poll();
                startPolling();
            } else {
                stopPolling();
            }
        });
    }
})();
