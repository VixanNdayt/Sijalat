(function () {
    const canvas = document.getElementById('barangChart');

    // Chart.js dimuat dari CDN; hentikan dengan aman jika belum tersedia.
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const labels = Array.isArray(window.chartLabels) ? window.chartLabels : [];
    const values = Array.isArray(window.chartData) ? window.chartData : [];

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Frekuensi Peminjaman',
                data: values,
                backgroundColor: '#3b82f6',
                hoverBackgroundColor: '#2563eb',
                borderRadius: 6,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.y} kali dipinjam`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#5f6b66',
                        font: { size: 11 }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                        color: '#8a9a93',
                        font: { size: 11 }
                    },
                    grid: { color: '#eef2f0' }
                }
            }
        }
    });
})();
