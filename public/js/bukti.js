// ========================================================
// SIJALAT - Logika Verifikasi QR Code Meja Lab & Bukti
// ========================================================

let scannerAmbil = null;
let scannerKembali = null;

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

// 2. Fungsi Menghentikan Scanner Kamera
function stopAllScanners() {
    if (scannerAmbil) {
        scannerAmbil.stop().then(() => {
            document.getElementById('reader-ambil').style.display = 'none';
            document.getElementById('btn-scan-ambil').innerText = '📷 Buka Kamera Scan QR Meja';
            scannerAmbil = null;
        }).catch(err => console.log(err));
    }
    if (scannerKembali) {
        scannerKembali.stop().then(() => {
            document.getElementById('reader-kembali').style.display = 'none';
            document.getElementById('btn-scan-kembali').innerText = '📷 Buka Kamera Scan QR Meja';
            scannerKembali = null;
        }).catch(err => console.log(err));
    }
}

// 3. Fungsi Toggle Kamera Scanner Meja Lab
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
            btnElement.innerText = '📷 Buka Kamera Scan QR Meja';
            if (jenis === 'ambil') scannerAmbil = null;
            else scannerKembali = null;
        }).catch(err => console.error(err));
        return;
    }

    // Jika belum aktif, buka scanner kamera
    readerElement.style.display = 'block';
    btnElement.innerText = '✕ Tutup Kamera';

    const html5QrCode = new Html5Qrcode(readerId);
    if (jenis === 'ambil') scannerAmbil = html5QrCode;
    else scannerKembali = html5QrCode;

    const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
        { facingMode: "environment" }, // Kamera belakang jika di HP
        qrConfig,
        (decodedText) => {
            console.log("QR Meja Lab Terdeteksi:", decodedText);
            
            // Hentikan kamera setelah mendeteksi QR
            html5QrCode.stop().then(() => {
                readerElement.style.display = 'none';
                btnElement.innerText = '📷 Buka Kamera Scan QR Meja';
                if (jenis === 'ambil') scannerAmbil = null;
                else scannerKembali = null;
            }).catch(err => console.log(err));

            // Isi input teks dan jalankan verifikasi
            const inputElement = document.getElementById('input-kode-' + jenis);
            if (inputElement) inputElement.value = decodedText;

            verifikasiKodeMeja(decodedText, jenis);
        },
        (errorMessage) => {
            // Scanning frame berjalan, abaikan error per-frame
        }
    ).catch(err => {
        alert("Gagal mengakses kamera: " + err + ". Pastikan izin kamera telah diizinkan!");
        readerElement.style.display = 'none';
        btnElement.innerText = '📷 Buka Kamera Scan QR Meja';
        if (jenis === 'ambil') scannerAmbil = null;
        else scannerKembali = null;
    });
}

// 4. Fungsi Cek Kode Manual
function cariKode(jenis) {
    const inputElement = document.getElementById('input-kode-' + jenis);
    const kode = inputElement ? inputElement.value.trim() : '';
    if (!kode) {
        alert('Silakan scan QR Code meja atau ketik kode verifikasi!');
        return;
    }
    verifikasiKodeMeja(kode, jenis);
}

// 5. Fungsi Verifikasi QR Code Meja Lab via AJAX
function verifikasiKodeMeja(kode, jenis) {
    const feedbackBox = document.getElementById('qr-feedback-' + jenis);
    if (!feedbackBox) return;

    feedbackBox.style.display = 'block';
    feedbackBox.className = 'qr-feedback qr-loading';
    feedbackBox.innerHTML = '⏳ Memeriksa keabsahan QR Code Meja Lab: <strong>' + kode + '</strong>...';

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
        if (res.status === 200 && res.body.success) {
            // Set input hidden kode meja
            const kodeMejaInput = document.getElementById('kode_meja_' + jenis);
            if (kodeMejaInput) kodeMejaInput.value = kode;

            feedbackBox.className = 'qr-feedback qr-success';
            
            const namaMeja = (jenis === 'ambil') ? 'Meja Pengambilan Lab' : 'Meja Pengembalian Lab';
            let messageHtml = '✅ <strong>Lokasi Terverifikasi!</strong><br>' +
                              'Anda terbukti berada di <strong>' + namaMeja + '</strong>.<br>' +
                              '<span style="font-size: 12px; color: #166534;">Silakan lengkapi formulir di bawah dan upload foto bukti barang.</span>';

            // Jika ada pinjaman aktif yang otomatis terdeteksi
            if (res.body.pinjam_aktif) {
                const p = res.body.pinjam_aktif;
                const barangInput = document.getElementById('barang_' + jenis);
                const kodePinjamInput = document.getElementById('kode_pinjam_' + jenis);
                if (barangInput && !barangInput.value) barangInput.value = p.barang;
                if (kodePinjamInput) kodePinjamInput.value = p.kode_pinjam;
            } else if (res.body.data) {
                // Jika yang discan adalah kode pinjam pribadi
                const d = res.body.data;
                const barangInput = document.getElementById('barang_' + jenis);
                const kodePinjamInput = document.getElementById('kode_pinjam_' + jenis);
                if (barangInput) barangInput.value = d.barang;
                if (kodePinjamInput) kodePinjamInput.value = d.kode_pinjam;
            }

            feedbackBox.innerHTML = messageHtml;
        } else {
            feedbackBox.className = 'qr-feedback qr-error';
            feedbackBox.innerHTML = '❌ ' + (res.body.message || 'QR Code meja tidak cocok! Pastikan men-scan QR meja yang sesuai.');
        }
    })
    .catch(err => {
        console.error(err);
        feedbackBox.className = 'qr-feedback qr-error';
        feedbackBox.innerHTML = '❌ Gagal memverifikasi kode. Silakan coba lagi.';
    });
}

// 6. Fungsi Saat Siswa Memilih Peminjaman dari Dropdown
function pilihPeminjaman(jenis, selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const barang = selectedOption.getAttribute('data-barang') || '';
    const kode = selectedOption.getAttribute('data-kode') || '';

    const barangInput = document.getElementById('barang_' + jenis);
    const kodeInput = document.getElementById('kode_pinjam_' + jenis);

    if (barangInput) barangInput.value = barang;
    if (kodeInput) kodeInput.value = kode;
}

// 7. Modal Cetak QR Meja Lab
function bukaModalQR() {
    const modal = document.getElementById('modal-qr-meja');
    if (modal) modal.style.display = 'flex';
}

function tutupModalQR() {
    const modal = document.getElementById('modal-qr-meja');
    if (modal) modal.style.display = 'none';
}

// 8. Label Upload Foto
function tampilkanNamaFile(inputElement, labelId) {
    const label = document.getElementById(labelId);
    if (inputElement.files && inputElement.files[0]) {
        label.innerHTML = "✅<br><strong>Foto terpilih:</strong> " + inputElement.files[0].name;
        label.style.color = "#004E39";
        label.style.borderColor = "#004E39";
        label.style.backgroundColor = "#e6f4ea";
    }
}
