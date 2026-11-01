// 1. Fungsi untuk ganti tab Pengambilan / Pengembalian
function switchTab(tab) {
    const btnAmbil = document.getElementById('btn-ambil');
    const btnKembali = document.getElementById('btn-kembali');
    const formAmbil = document.getElementById('form-ambil');
    const formKembali = document.getElementById('form-kembali');

    if (tab === 'ambil') {
        // Aktifkan tombol ambil
        btnAmbil.classList.add('active');
        btnKembali.classList.remove('active');
        
        // Munculkan form ambil, hilangkan form kembali
        formAmbil.style.display = 'block';
        formKembali.style.display = 'none';
    } else {
        // Aktifkan tombol kembali
        btnKembali.classList.add('active');
        btnAmbil.classList.remove('active');
        
        // Munculkan form kembali, hilangkan form ambil
        formKembali.style.display = 'block';
        formAmbil.style.display = 'none';
    }
}

// 2. Fungsi agar teks dalam kotak foto berubah jadi nama file yang di-upload
function tampilkanNamaFile(inputElement, labelId) {
    const label = document.getElementById(labelId);
    if (inputElement.files && inputElement.files[0]) {
        // Ganti isi kotak menjadi nama file + ikon centang
        label.innerHTML = "✅<br>" + inputElement.files[0].name;
        label.style.color = "#3f9d77";
        label.style.borderColor = "#3f9d77";
    }
}