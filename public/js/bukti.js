// ========================================================
// SIJALAT - Verifikasi QR transaksi & bukti pengambilan/pengembalian
// ========================================================

let scannerAmbil = null;
let scannerKembali = null;

const LABEL_SCAN = 'Buka Kamera Scan QR';

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// 1. Fungsi Ganti Tab (Pengambilan vs Pengembalian)
function switchTab(tab) {
    const btnAmbil = document.getElementById('btn-ambil');
    const btnKembali = document.getElementById('btn-kembali');
    const formAmbil = document.getElementById('form-ambil');
    const formKembali = document.getElementById('form-kembali');

    // Hentikan kamera scanner jika sedang menyala
    stopAllScanners();

    if (tab === 'ambil') {
        btnAmbil.classList.add('active');
        btnKembali.classList.remove('active');
        formAmbil.style.display = 'block';
        formKembali.style.display = 'none';
    } else {
        btnKembali.classList.add('active');
        btnAmbil.classList.remove('active');
        formKembali.style.display = 'block';
        formAmbil.style.display = 'none';
    }
}

// 2. Helper label tombol scan (mempertahankan ikon SVG)
function setScanLabel(btnElement, text) {
    if (!btnElement) return;
    const label = btnElement.querySelector('.btn-label');
    if (label) {
        label.textContent = text;
    } else {
        btnElement.innerText = text;
    }
}

// 3. Fungsi Menghentikan Scanner Kamera
function stopAllScanners() {
    if (scannerAmbil) {
        scannerAmbil.stop().then(() => {
            document.getElementById('reader-ambil').style.display = 'none';
            setScanLabel(document.getElementById('btn-scan-ambil'), LABEL_SCAN);
            scannerAmbil = null;
        }).catch(err => console.log(err));
    }
    if (scannerKembali) {
        scannerKembali.stop().then(() => {
            document.getElementById('reader-kembali').style.display = 'none';
            setScanLabel(document.getElementById('btn-scan-kembali'), LABEL_SCAN);
            scannerKembali = null;
        }).catch(err => console.log(err));
    }
}

// 4. Fungsi Toggle Kamera Scanner
function toggleScanner(jenis) {
    const readerId = 'reader-' + jenis;
    const btnId = 'btn-scan-' + jenis;
    const readerElement = document.getElementById(readerId);
    const btnElement = document.getElementById(btnId);

    let activeScanner = (jenis === 'ambil') ? scannerAmbil : scannerKembali;

    // Jika scanner sedang aktif, maka tutup
    if (activeScanner) {
        activeScanner.stop().then(() => {
            readerElement.style.display = 'none';
            setScanLabel(btnElement, LABEL_SCAN);
            if (jenis === 'ambil') scannerAmbil = null;
            else scannerKembali = null;
        }).catch(err => console.error(err));
        return;
    }

    if (typeof Html5Qrcode === 'undefined') {
        alert('Library kamera belum termuat. Periksa koneksi internet, atau gunakan kolom ketik kode QR di bawah.');
        return;
    }

    // Jika belum aktif, buka scanner kamera
    readerElement.style.display = 'block';
    setScanLabel(btnElement, 'Tutup Kamera');

    const html5QrCode = new Html5Qrcode(readerId);
    if (jenis === 'ambil') scannerAmbil = html5QrCode;
    else scannerKembali = html5QrCode;

    const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
        { facingMode: "environment" }, // Kamera belakang jika di HP
        qrConfig,
        (decodedText) => {
            // Hentikan kamera setelah mendeteksi QR
            html5QrCode.stop().then(() => {
                readerElement.style.display = 'none';
                setScanLabel(btnElement, LABEL_SCAN);
                if (jenis === 'ambil') scannerAmbil = null;
                else scannerKembali = null;
            }).catch(err => console.log(err));

            // Isi input teks dan jalankan verifikasi
            const inputElement = document.getElementById('input-kode-' + jenis);
            if (inputElement) inputElement.value = decodedText;

            verifikasiQr(decodedText, jenis);
        },
        (errorMessage) => {
            // Scanning frame berjalan, abaikan error per-frame
        }
    ).catch(err => {
        alert("Gagal mengakses kamera: " + err + ". Pastikan izin kamera telah diizinkan!");
        readerElement.style.display = 'none';
        setScanLabel(btnElement, LABEL_SCAN);
        if (jenis === 'ambil') scannerAmbil = null;
        else scannerKembali = null;
    });
}

// 5. Fungsi Cek Kode Manual
function cariKode(jenis) {
    const inputElement = document.getElementById('input-kode-' + jenis);
    const kode = inputElement ? inputElement.value.trim() : '';
    if (!kode) {
        alert('Silakan scan QR dari guru atau tempel kode QR-nya terlebih dahulu!');
        return;
    }
    verifikasiQr(kode, jenis);
}

// 6. Bersihkan data transaksi yang sudah terverifikasi (misal QR salah)
function bersihkanTransaksi(jenis) {
    const pinjamInput = document.getElementById('pinjam_id_' + jenis);
    const tokenInput = document.getElementById('qr_token_' + jenis);
    const barangInput = document.getElementById('barang_' + jenis);

    if (pinjamInput) pinjamInput.value = '';
    if (tokenInput) tokenInput.value = '';
    if (barangInput) barangInput.value = '';

    // Kosongkan lagi detail jadwal/lokasi yang sebelumnya terisi otomatis
    if (jenis === 'ambil') {
        const selesai = document.getElementById('waktu_selesai_ambil');
        const tempat = document.getElementById('tempat_ambil');
        const hint = document.getElementById('hint-jadwal-ambil');
        if (selesai) selesai.value = '';
        if (tempat) tempat.value = '';
        if (hint) hint.style.display = 'none';
    }
}

// 6b. Isi otomatis detail transaksi dari hasil verifikasi QR
function isiDetailTransaksi(jenis, d) {
    if (jenis !== 'ambil') return;

    const setNilai = (id, nilai) => {
        const el = document.getElementById(id);
        if (el && nilai !== undefined && nilai !== null && nilai !== '') {
            el.value = nilai;
        }
    };

    setNilai('waktu_mulai_ambil', d.waktu_mulai);
    setNilai('waktu_selesai_ambil', d.waktu_selesai);
    setNilai('tempat_ambil', d.tempat);
    setNilai('kelas_ambil', d.kelas);

    const hint = document.getElementById('hint-jadwal-ambil');
    if (hint) {
        const jadwal = (d.jadwal || '').trim();

        if (jadwal && jadwal !== '-') {
            hint.textContent = 'Jadwal peminjaman: ' + jadwal;
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }
}

// 7. Verifikasi QR transaksi via AJAX
function verifikasiQr(kode, jenis) {
    const feedbackBox = document.getElementById('qr-feedback-' + jenis);
    if (!feedbackBox) return;

    feedbackBox.style.display = 'block';
    feedbackBox.className = 'qr-feedback qr-loading';
    feedbackBox.textContent = 'Memeriksa QR transaksi...';

    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    fetch('/transaksi/cari-kode', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            kode: kode,
            aksi: jenis.toUpperCase()
        })
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (res.status === 200 && res.body.success && res.body.data) {
            const d = res.body.data;

            // Bersihkan dulu supaya data scan sebelumnya tidak tertinggal
            bersihkanTransaksi(jenis);

            const pinjamInput = document.getElementById('pinjam_id_' + jenis);
            const tokenInput = document.getElementById('qr_token_' + jenis);
            const barangInput = document.getElementById('barang_' + jenis);

            if (pinjamInput) pinjamInput.value = d.id;
            if (tokenInput) tokenInput.value = d.token;
            if (barangInput) barangInput.value = d.barang;

            // Isi otomatis kelas, waktu, dan tempat dari data transaksi
            isiDetailTransaksi(jenis, d);

            feedbackBox.className = 'qr-feedback qr-success';
            feedbackBox.innerHTML =
                '<strong>QR cocok dengan transaksi Anda!</strong><br>' +
                escapeHtml(d.barang) + ' (' + escapeHtml(d.jumlah_minta) + ' unit) &middot; ' +
                '<strong>' + escapeHtml(d.kode_pinjam) + '</strong>' +
                (d.kelas ? '<br>Kelas: ' + escapeHtml(d.kelas) : '') +
                '<br><span style="font-size: 12px; color: #166534;">Silakan lengkapi formulir di bawah dan upload foto bukti barang.</span>';
        } else {
            bersihkanTransaksi(jenis);

            feedbackBox.className = 'qr-feedback qr-error';
            feedbackBox.innerHTML = escapeHtml(
                (res.body && res.body.message)
                    ? res.body.message
                    : 'QR Code tidak cocok. Pastikan men-scan QR yang ditunjukkan guru untuk transaksi ini.'
            );
        }
    })
    .catch(err => {
        console.error(err);
        bersihkanTransaksi(jenis);
        feedbackBox.className = 'qr-feedback qr-error';
        feedbackBox.textContent = 'Gagal memverifikasi QR. Silakan coba lagi.';
    });
}

// 8. Label Upload Foto
function tampilkanNamaFile(inputElement, labelId) {
    const label = document.getElementById(labelId);
    if (!label || !inputElement.files || !inputElement.files[0]) {
        return;
    }

    // Nama file berasal dari perangkat pengguna, jadi jangan pernah
    // dimasukkan lewat innerHTML. Susun DOM-nya dan pakai textContent.
    let text = label.querySelector('.upload-text');

    if (!text) {
        text = document.createElement('span');
        text.className = 'upload-text';
        label.appendChild(text);
    }

    const strong = document.createElement('strong');
    strong.textContent = 'Foto terpilih:';

    text.replaceChildren(strong, document.createTextNode(' ' + inputElement.files[0].name));

    label.style.color = '#004E39';
    label.style.borderColor = '#004E39';
    label.style.backgroundColor = '#e6f4ea';
}
