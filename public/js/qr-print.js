(function () {
    'use strict';

    var target = document.getElementById('qr-print-code');

    if (!target || typeof qrcode !== 'function') {
        return;
    }

    var qr = qrcode(0, 'M');
    qr.addData(target.dataset.kode);
    qr.make();
    target.innerHTML = qr.createSvgTag(6, 8);
})();
