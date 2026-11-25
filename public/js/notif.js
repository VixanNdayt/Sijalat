(function () {
    const button = document.getElementById('notifBtn');
    const panel = document.getElementById('notifPanel');
    const badge = document.getElementById('notifBadge');
    const body = document.getElementById('notifBody');
    const foot = document.getElementById('notifFoot');
    const refresh = document.getElementById('notifRefresh');

    if (!button || !panel || !badge || !body) {
        return;
    }

    const url = button.dataset.notifUrl || '';
    const intervalMs = 60000;
    let loading = false;
    let lastLoaded = 0;

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

    function setBadge(total) {
        if (total > 0) {
            badge.hidden = false;
            badge.textContent = total > 99 ? '99+' : String(total);
        } else {
            badge.hidden = true;
            badge.textContent = '';
        }
    }

    function render(data) {
        const total = Number(data && data.total) || 0;
        setBadge(total);

        body.replaceChildren();

        const sections = data && Array.isArray(data.sections) ? data.sections : [];

        if (!sections.length) {
            body.appendChild(el('p', 'notif-empty', 'Tidak ada notifikasi baru.'));
        } else {
            sections.forEach(function (section) {
                const kotak = el('div', 'notif-section');
                const kepala = el('div', 'notif-section-head');

                kepala.appendChild(el('span', '', section.label || 'Notifikasi'));

                if (Number(section.count) > 0) {
                    kepala.appendChild(el('em', '', section.count > 99 ? '99+' : String(section.count)));
                }

                kotak.appendChild(kepala);

                const items = Array.isArray(section.items) ? section.items : [];

                items.forEach(function (item) {
                    const baris = el(item.url ? 'a' : 'div', 'notif-item');

                    if (item.url) {
                        baris.href = item.url;
                    }

                    baris.appendChild(el('span', 'notif-item-title', item.title || ''));

                    if (item.meta) {
                        baris.appendChild(el('span', 'notif-item-meta', item.meta));
                    }

                    kotak.appendChild(baris);
                });

                if (section.url) {
                    const semua = el('a', 'notif-more', 'Lihat semua');
                    semua.href = section.url;
                    kotak.appendChild(semua);
                }

                body.appendChild(kotak);
            });
        }

        if (foot) {
            if (data && data.updated_at) {
                foot.hidden = false;
                foot.textContent = 'Diperbarui pukul ' + data.updated_at;
            } else {
                foot.hidden = true;
                foot.textContent = '';
            }
        }
    }

    function gagal() {
        body.replaceChildren(el('p', 'notif-empty', 'Gagal memuat notifikasi. Coba lagi.'));
    }

    function muat(force) {
        if (!url || loading) {
            return;
        }

        if (!force && Date.now() - lastLoaded < 15000) {
            return;
        }

        loading = true;

        if (refresh) {
            refresh.disabled = true;
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
                    throw new Error('Gagal memuat notifikasi.');
                }

                return response.json();
            })
            .then(function (data) {
                lastLoaded = Date.now();
                render(data);
            })
            .catch(gagal)
            .finally(function () {
                loading = false;

                if (refresh) {
                    refresh.disabled = false;
                }
            });
    }

    function bukaPanel() {
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        muat(true);
    }

    function tutupPanel() {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    }

    button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (panel.hidden) {
            bukaPanel();
        } else {
            tutupPanel();
        }
    });

    if (refresh) {
        refresh.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            muat(true);
        });
    }

    document.addEventListener('click', function (event) {
        if (!panel.hidden && !event.target.closest('.notif-wrap')) {
            tutupPanel();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            tutupPanel();
            button.focus();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            muat(false);
        }
    });

    // Muat jumlah terbaru saat halaman dibuka, lalu perbarui berkala.
    muat(true);

    window.setInterval(function () {
        if (!document.hidden) {
            muat(true);
        }
    }, intervalMs);
})();
