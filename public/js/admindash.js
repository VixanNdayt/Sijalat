const ctx = document.getElementById('barangChart').getContext('2d');
    
// Menerima array dari Laravel dan mengubahnya ke format JavaScript
new Chart(ctx, {
    type: 'bar', // Bisa diubah jadi 'pie' atau 'doughnut' jika ingin bentuk bulat
    data: {
        labels: window.chartLabels, // Variabel nama barang
        datasets: [{
            label: 'Frekuensi Peminjaman',
            data: window.chartData, // Variabel jumlah data
            backgroundColor: '#3b82f6', // Warna biru (sesuai kotak Masuk)
            borderRadius: 4 // Sedikit melengkungkan ujung balok diagram
        }]
    },
    options: { 
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1 // Memaksa sumbu Y berupa angka bulat (1, 2, 3...)
                }
            }
        }
    }
});