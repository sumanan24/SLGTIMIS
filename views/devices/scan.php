<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="h4 mb-3">QR Scanner</h1>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="device-detail-section">
                <p class="small text-muted">Point your camera at a device QR code. You must be logged in with ADM, HOD ICT, ACC, or DIR access.</p>
                <div id="qr-reader" style="width:100%;max-width:480px;"></div>
                <div id="qr-scan-msg" class="alert alert-info mt-3 d-none"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="device-detail-section">
                <h3>Manual token</h3>
                <form class="d-flex gap-2 mt-3" onsubmit="return false;">
                    <input type="text" class="form-control" placeholder="Paste QR token" id="manual-token">
                    <button type="button" class="btn btn-primary" id="manual-go">Open</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Or open a printed QR label — it links directly to the device page.</p>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var base = <?php echo json_encode(rtrim(APP_URL, '/') . '/devices/qr/'); ?>;
    var msg = document.getElementById('qr-scan-msg');
    function go(url) {
        if (msg) { msg.classList.remove('d-none'); msg.textContent = 'Opening device…'; }
        window.location.href = url;
    }
    function onScan(decoded) {
        var text = (decoded || '').trim();
        if (!text) return;
        if (text.indexOf('/devices/qr/') !== -1) {
            go(text);
            return;
        }
        go(base + encodeURIComponent(text));
    }
    if (typeof Html5Qrcode !== 'undefined') {
        var scanner = new Html5Qrcode('qr-reader');
        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || !cameras.length) return;
            scanner.start(cameras[0].id, { fps: 10, qrbox: 220 }, onScan, function () {});
        }).catch(function () {
            if (msg) { msg.classList.remove('d-none'); msg.className = 'alert alert-warning mt-3'; msg.textContent = 'Camera not available. Use manual token entry.'; }
        });
    }
    var goBtn = document.getElementById('manual-go');
    if (goBtn) {
        goBtn.addEventListener('click', function () {
            var t = (document.getElementById('manual-token').value || '').trim();
            if (!t) return;
            if (t.indexOf('/devices/qr/') !== -1) { go(t); return; }
            go(base + encodeURIComponent(t));
        });
    }
})();
</script>
