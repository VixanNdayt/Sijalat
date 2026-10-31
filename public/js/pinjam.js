// 1. Fungsi memilih barang (Tetap seperti milikmu)
// Variabel untuk melacak alat apa saja yang sudah masuk keranjang
let keranjang = []; 

function pilihBarang(id, nama) {
    // 1. Cek apakah barang sudah ada di keranjang
    if (keranjang.includes(id)) {
        alert('Barang ini sudah ada di keranjang!');
        return;
    }

    // 2. Masukkan ID ke memori keranjang
    keranjang.push(id);

    // 3. Hilangkan tulisan "Belum ada alat"
    document.getElementById('keranjang-kosong').style.display = 'none';

    // 4. Gambar kotak barang baru di dalam keranjang
    const container = document.getElementById('keranjang-container');
    const itemHTML = `
        <div class="item-cart" id="item-${id}">
            <div class="flex-grow">
                <p class="font-bold text-sm text-gray-800">${nama}</p>
                <!-- Input hidden mengirimkan ID dalam bentuk Array -->
                <input type="hidden" name="alat_id[]" value="${id}">
            </div>
            
            <div class="cart-value">
                <div class="flex flex-col">
                    <label class="text-[10px] text-gray-500 font-bold uppercase">Jumlah</label>
                    <!-- Input jumlah dalam bentuk Array -->
                    <input type="number" name="jumlah_minta[]" value=1 min=1 class="w-16 h-8 text-sm border-gray-300 rounded px-2">
                </div>
                
                <!-- Tombol Hapus memanggil fungsi hapusBarang -->
                <button type="button" onclick="hapusBarang(${id})" class="text-red-500 hover:text-red-700 p-1 mt-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                </button>
            </div>
        </div>
    `;
    
    // Sisipkan ke paling bawah
    container.insertAdjacentHTML('beforeend', itemHTML);
}

// Fungsi untuk menghapus barang dari keranjang
function hapusBarang(id) {
    // Hapus elemen HTML
    document.getElementById(`item-${id}`).remove();
    
    // Hapus ID dari memori JavaScript
    keranjang = keranjang.filter(itemId => itemId !== id);
    
    // Jika keranjang kosong, tampilkan lagi tulisan "Belum ada alat"
    if(keranjang.length === 0) {
        document.getElementById('keranjang-kosong').style.display = 'block';
    }
}

// 2. Deklarasi elemen pencarian dan filter
const searchInput = document.getElementById('searchInput');
const filterKategori = document.getElementById('filterKategori'); // Pastikan <select> di HTML sudah diberi id ini

// 3. Fungsi gabungan untuk menyaring kartu
function jalankanFilter() {
    let keyword = searchInput.value.toLowerCase();
    
    // Ambil value kategori (kalau elemennya ada)
    let kategori = filterKategori ? filterKategori.value.toLowerCase() : ''; 
    
    let cards = document.querySelectorAll('.item-box'); // Menggunakan class .item-box milikmu

    cards.forEach(function(card) {
        let namaAlat = card.getAttribute('data-nama') || '';
        let kategoriAlat = card.getAttribute('data-kategori') || ''; // Membaca kategori dari kartu

        let cocokNama = namaAlat.includes(keyword);
        let cocokKategori = (kategori === "") || (kategoriAlat.includes(kategori));

        // Tampilkan hanya jika cocok nama DAN cocok kategori
        if (cocokNama / cocokKategori) {
            card.style.display = ''; 
        } else {
            card.style.display = 'none';
        }
    });
}

// 4. Pasang pendeteksi event (Event Listeners)
if (searchInput) {
    searchInput.addEventListener('keyup', jalankanFilter);
}

if (filterKategori) {
    filterKategori.addEventListener('change', jalankanFilter);
}