(function () {
    'use strict';

    var el = document.getElementById('analisis-data');

    if (!el) {
        return;
    }

    var data = JSON.parse(el.textContent);
    var grafik = [];

    function temaGelap() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function samakanWarnaGrafik() {
        if (!window.Chart) {
            return;
        }

        var gelap = temaGelap();
        Chart.defaults.color = gelap ? '#c5d5ce' : '#666666';
        Chart.defaults.borderColor = gelap ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.1)';

        grafik.forEach(function (chart) {
            chart.update();
        });
    }

    samakanWarnaGrafik();

    // 1. Line Chart (Tren Peminjaman)
    grafik.push(new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: data.trendLabels,
            datasets: [{
                label: 'Transaksi',
                data: data.trendData,
                borderColor: '#3b82f6',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.1)'
            }]
        }
    }));

    // 2. Horizontal Bar Chart (Top 5 Barang)
    grafik.push(new Chart(document.getElementById('topItemChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.topLabels,
            datasets: [{
                label: 'Total Dipinjam',
                data: data.topData,
                backgroundColor: '#10b981'
            }]
        },
        options: { indexAxis: 'y' }
    }));

    // 3. Doughnut Chart (Kondisi Fisik)
    grafik.push(new Chart(document.getElementById('kondisiChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Baik', 'Butuh Pengecekan', 'Rusak / Perlu Service', 'Maintenance'],
            datasets: [{
                data: [
                    data.kondisiBaik,
                    data.kondisiPengecekan,
                    data.kondisiRusak,
                    data.kondisiMtc
                ],
                backgroundColor: ['#10b981', '#3b82f6', '#ef4444', '#f59e0b']
            }]
        }
    }));

    // 4. Pie Chart (Stok Out vs In)
    grafik.push(new Chart(document.getElementById('distribusiChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Stok Tersedia (Gudang)', 'Sedang Dipinjam (Luar)'],
            datasets: [{
                data: [data.stokTersedia, data.sedangDipinjam],
                backgroundColor: ['#3b82f6', '#8b5cf6']
            }]
        }
    }));

    window.addEventListener('sijalat:themechange', samakanWarnaGrafik);

    // Filter periode & tanggal acuan
    var filterPeriode = document.getElementById('filterPeriode');
    var filterTanggal = document.getElementById('filterTanggal');

    function terapkanFilter() {
        var url = new URL(window.location.href);
        var periode = filterPeriode ? filterPeriode.value : 'semua';

        url.searchParams.set('periode', periode);

        if (periode === 'semua') {
            url.searchParams.delete('tanggal');
        } else if (filterTanggal && filterTanggal.value) {
            url.searchParams.set('tanggal', filterTanggal.value);
        }

        window.location.href = url.toString();
    }

    if (filterPeriode) {
        filterPeriode.addEventListener('change', function () {
            if (filterTanggal) {
                filterTanggal.disabled = this.value === 'semua';
            }

            terapkanFilter();
        });
    }

    if (filterTanggal) {
        filterTanggal.addEventListener('change', terapkanFilter);
    }
})();
