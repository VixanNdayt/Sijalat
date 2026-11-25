(function () {
    /* ---------------- Grafik alat sering dipinjam ---------------- */

    const canvas = document.getElementById('barangChart');

    if (canvas && typeof Chart !== 'undefined') {
        const payloadEl = document.getElementById('dashboard-chart-data');
        const payload = payloadEl ? JSON.parse(payloadEl.textContent) : {};
        const labels = Array.isArray(payload.labels) ? payload.labels : [];
        const values = Array.isArray(payload.values) ? payload.values : [];

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
    }

    /* ---------------- Modal detail transaksi ---------------- */

    const modal = document.getElementById('detailModal');
    const detailBody = document.getElementById('detailBody');
    const detailKode = document.getElementById('detailKode');
    const detailQr = document.getElementById('detailQr');

    if (!modal || !detailBody) {
        return;
    }

    let fokusTerakhir = null;

    function el(tag, className, text) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }

        return node;
    }

    function nilai(value) {
        return value === null || value === undefined || value === '' ? '-' : value;
    }

    function tambahBagian(judul, baris) {
        const section = el('section', 'detail-section');
        section.appendChild(el('h4', '', judul));

        const grid = el('div', 'detail-grid');

        baris.forEach(function (pasangan) {
            const item = el('div', 'detail-item');
            item.appendChild(el('span', 'detail-label', pasangan[0]));

            const isi = nilai(pasangan[1]);
            const teks = el('span', 'detail-value' + (isi === '-' ? ' is-empty' : ''), isi);
            item.appendChild(teks);

            grid.appendChild(item);
        });

        section.appendChild(grid);
        detailBody.appendChild(section);
    }

    function tambahBukti(bukti) {
        const section = el('section', 'detail-section');
        section.appendChild(el('h4', '', 'Bukti transaksi'));

        const kotak = el('div', 'detail-bukti');

        [
            ['ambil', 'Bukti pengambilan'],
            ['kembali', 'Bukti pengembalian']
        ].forEach(function (pasangan) {
            const url = bukti ? bukti[pasangan[0]] : null;

            if (url) {
                const tautan = el('a', '', pasangan[1]);
                tautan.href = url;
                tautan.target = '_blank';
                tautan.rel = 'noopener';
                kotak.appendChild(tautan);
            } else {
                kotak.appendChild(el('span', 'is-empty', pasangan[1] + ': belum ada'));
            }
        });

        section.appendChild(kotak);
        detailBody.appendChild(section);
    }

    function renderDetail(data) {
        detailBody.replaceChildren();

        if (!data) {
            detailBody.appendChild(el('p', 'detail-error', 'Detail transaksi tidak tersedia.'));
            return;
        }

        const status = el('div', 'detail-status');
        status.appendChild(el(
            'span',
            'status-badge status-' + (data.status_tone || 'netral'),
            data.status_label || '-'
        ));
        status.appendChild(el('span', 'detail-status-label', 'Kode: ' + nilai(data.kode_pinjam)));
        detailBody.appendChild(status);

        const siswa = data.siswa || {};
        const alat = data.alat || {};
        const waktu = data.waktu || {};
        const keterangan = data.keterangan || {};

        tambahBagian('Data siswa', [
            ['Nama', siswa.nama],
            ['NIS', siswa.nis],
            ['Kelas', siswa.kelas],
            ['Email', siswa.email]
        ]);

        tambahBagian('Alat dan kelas', [
            ['Alat', alat.nama],
            ['Kode alat', alat.kode],
            ['Lokasi', alat.lokasi],
            ['Guru', alat.guru],
            ['Mata pelajaran', alat.mapel],
            ['Jumlah', alat.jumlah]
        ]);

        tambahBagian('Waktu', [
            ['Diajukan', waktu.diajukan],
            ['Mulai pinjam', waktu.mulai_pinjam],
            ['Tenggat kembali', waktu.tenggat_kembali],
            ['Dikembalikan', waktu.dikembalikan],
            ['Kondisi kembali', waktu.kondisi_kembali],
            ['Keterlambatan', waktu.keterlambatan]
        ]);

        tambahBagian('Keterangan', [
            ['Keperluan', keterangan.keperluan],
            ['Ruangan', keterangan.ruangan],
            ['Catatan', keterangan.catatan],
            ['Catatan kembali', keterangan.catatan_kembali]
        ]);

        tambahBukti(data.bukti);

        if (detailQr) {
            if (data.qr_url) {
                detailQr.href = data.qr_url;
                detailQr.hidden = false;
            } else {
                detailQr.removeAttribute('href');
                detailQr.hidden = true;
            }
        }
    }

    function bukaDetail(url) {
        fokusTerakhir = document.activeElement;
        modal.hidden = false;

        if (detailKode) {
            detailKode.textContent = 'Memuat...';
        }

        detailBody.replaceChildren(el('p', 'detail-loading', 'Memuat detail...'));

        if (detailQr) {
            detailQr.hidden = true;
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Detail transaksi gagal dimuat.');
                }

                return response.json();
            })
            .then(function (data) {
                if (detailKode) {
                    detailKode.textContent = data.kode_pinjam || 'Detail transaksi';
                }

                renderDetail(data);
            })
            .catch(function (error) {
                detailBody.replaceChildren(el('p', 'detail-error', error.message));
            });
    }

    function tutupDetail() {
        modal.hidden = true;

        if (fokusTerakhir && typeof fokusTerakhir.focus === 'function') {
            fokusTerakhir.focus();
        }
    }

    document.addEventListener('click', function (event) {
        const tombol = event.target.closest('[data-detail-url]');

        if (tombol) {
            event.preventDefault();
            bukaDetail(tombol.dataset.detailUrl);
            return;
        }

        if (event.target.closest('[data-close-detail]')) {
            event.preventDefault();
            tutupDetail();
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            tutupDetail();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            tutupDetail();
        }
    });
})();
