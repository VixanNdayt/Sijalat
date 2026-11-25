(function () {
    'use strict';

    var STORAGE_KEY = 'sijalat-theme';
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function bacaTersimpan() {
        try {
            var nilai = window.localStorage.getItem(STORAGE_KEY);
            return nilai === 'dark' || nilai === 'light' ? nilai : null;
        } catch (e) {
            return null;
        }
    }

    function simpan(nilai) {
        try {
            window.localStorage.setItem(STORAGE_KEY, nilai);
        } catch (e) {
            // Penyimpanan bisa diblokir; tema tetap berlaku untuk sesi ini.
        }
    }

    function temaSistem() {
        return media && media.matches ? 'dark' : 'light';
    }

    function terapkan(tema, simpanNilai) {
        root.setAttribute('data-theme', tema);

        if (simpanNilai) {
            simpan(tema);
        }

        perbaruiTombol(tema);

        try {
            window.dispatchEvent(new CustomEvent('sijalat:themechange', { detail: { theme: tema } }));
        } catch (e) {
            // CustomEvent tidak tersedia di peramban sangat lama.
        }
    }

    function perbaruiTombol(tema) {
        var tombol = document.getElementById('themeToggle');

        if (!tombol) {
            return;
        }

        var gelap = tema === 'dark';
        var label = gelap ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';

        tombol.setAttribute('aria-pressed', gelap ? 'true' : 'false');
        tombol.setAttribute('aria-label', label);
        tombol.setAttribute('title', label);
    }

    // Terapkan tema sebelum halaman digambar.
    var temaAwal = bacaTersimpan() || temaSistem();
    root.setAttribute('data-theme', temaAwal);

    function pasang() {
        var tombol = document.getElementById('themeToggle');

        perbaruiTombol(temaAwal);

        if (tombol) {
            tombol.addEventListener('click', function () {
                var berikutnya = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                terapkan(berikutnya, true);
            });
        }

        if (media) {
            var ikutiSistem = function (event) {
                if (!bacaTersimpan()) {
                    terapkan(event.matches ? 'dark' : 'light', false);
                }
            };

            if (media.addEventListener) {
                media.addEventListener('change', ikutiSistem);
            } else if (media.addListener) {
                media.addListener(ikutiSistem);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', pasang);
    } else {
        pasang();
    }
})();
