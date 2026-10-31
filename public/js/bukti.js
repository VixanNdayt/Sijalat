document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Fungsi jika barcode sukses terbaca kamera
        function onScanSuccess(decodedText, decodedResult) {
            
            // Masukkan teks barcode (misal: AMBIL-15) ke dalam kolom input
            document.getElementById('kode_barcode').value = decodedText;
            
            // Tampilkan pop-up notifikasi ke user
            alert('Barcode berhasil di-scan: ' + decodedText);
            
            // Hentikan kamera agar tidak terus-menerus scan secara looping
            html5QrcodeScanner.clear();
        }

        // 2. Fungsi jika sedang mencari/gagal (Dibiarkan kosong agar tidak spam error di console)
        function onScanFailure(error) {
            // Abaikan error saat proses pencarian kotak barcode
        }

        // 3. Konfigurasi Mesin Scanner Kamera
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", // Wajib sama dengan id <div id="reader"> pembungkus kameramu
            { 
                fps: 10,                           // Kecepatan scan per detik
                qrbox: {width: 250, height: 250},  // Ukuran kotak hijau target scan
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA] // Fokus kamera saja
            },
            false // verbose mode off
        );
        
        // 4. Jalankan Kamera
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });