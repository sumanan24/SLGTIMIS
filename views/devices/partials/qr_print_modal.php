<?php
/**
 * Device QR label print modal — production Browser Print integration.
 */
if (!class_exists('DeviceAssetHelper', false)) {
    require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
}
$printerModel = (string) ($labelPrinterConfig['printer_model'] ?? 'Zebra ZD230');
$defaultSets = (int) ($defaultLabelSets ?? DeviceAssetHelper::defaultLabelSets());
$maxSets = (int) ($maxLabelSets ?? DeviceAssetHelper::maxLabelSets());
$labelsPerSet = (int) ($labelsPerSet ?? DeviceAssetHelper::labelsPerSet());
$labelWmm = round(DeviceAssetHelper::labelWidthIn() * 25.4, 1);
$labelHmm = round(DeviceAssetHelper::labelHeightIn() * 25.4, 1);
$baseUrl = rtrim(APP_URL, '/');
$siteHost = parse_url($baseUrl, PHP_URL_HOST) ?: 'sis.slgti.ac.lk';
$sslSupportUrl = DeviceAssetHelper::browserPrintSslSupportUrl();
$deviceViewUrl = DeviceAssetHelper::deviceViewUrl((int) $id);
$printPayload = [
    'deviceId' => (int) $id,
    'assetId' => (string) ($d['asset_id'] ?? ''),
    'serialNumber' => (string) ($d['serial_number'] ?? ''),
    'qrDataUri' => (string) ($qrDataUri ?? ''),
    'defaultSets' => $defaultSets,
    'maxSets' => $maxSets,
    'labelsPerSet' => $labelsPerSet,
    'printerModel' => $printerModel,
    'labelWidthMm' => $labelWmm,
    'labelHeightMm' => $labelHmm,
    'showSlgti' => false,
    'zplUrl' => $baseUrl . '/devices/qr-zpl',
    'pdfUrl' => $baseUrl . '/devices/qr-pdf',
    'previewUrl' => $baseUrl . '/devices/qr-print',
    'deviceViewUrl' => $deviceViewUrl,
    'siteHost' => $siteHost,
    'sslSupportUrl' => $sslSupportUrl,
    'assetsBase' => $baseUrl,
];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl . '/assets/css/device-qr-printer.css', ENT_QUOTES, 'UTF-8'); ?>">
<script type="application/json" id="device-qr-print-data"><?php echo json_encode($printPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="modal fade" id="deviceQrPrintModal" tabindex="-1" aria-labelledby="deviceQrPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="deviceQrPrintModalLabel"><i class="fas fa-qrcode me-2"></i>Print Device QR Labels</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row small mb-3 bg-white border rounded p-2 mx-0">
                    <dt class="col-sm-3">Device</dt>
                    <dd class="col-sm-9 mb-1"><strong><?php echo $e($d['asset_id'] ?? '—'); ?></strong></dd>
                    <dt class="col-sm-3">Configured printer</dt>
                    <dd class="col-sm-9 mb-1"><?php echo $e($printerModel); ?> <span class="text-muted">· USB · Zebra</span></dd>
                    <dt class="col-sm-3">QR links to</dt>
                    <dd class="col-sm-9 mb-1"><a href="<?php echo $e($deviceViewUrl); ?>" target="_blank" rel="noopener" class="small"><?php echo $e($deviceViewUrl); ?></a></dd>
                    <dt class="col-sm-3">Labels per set</dt>
                    <dd class="col-sm-9 mb-0"><?php echo $labelsPerSet; ?> <span class="text-muted">(same QR side-by-side)</span></dd>
                </dl>

                <div id="zebra-bp-status-card" class="zebra-bp-status-card">
                    <div class="status-head"><span class="zebra-bp-spinner"></span><span class="status-dot checking"></span><span>Connecting to Zebra Browser Print…</span></div>
                    <p class="status-meta">Please wait while we connect to the printer on this computer.</p>
                </div>

                <div id="zebra-bp-setup-wizard" class="zebra-bp-wizard d-none" aria-live="polite"></div>

                <div class="device-qr-printer-row mb-2">
                    <label for="device-qr-printer-select">Printer</label>
                    <select id="device-qr-printer-select" class="form-select form-select-sm" title="Zebra printer on this computer">
                        <option value="">Connecting…</option>
                    </select>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="device-qr-refresh-printers">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Printers
                    </button>
                    <a href="<?php echo $e($sslSupportUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm" id="device-qr-chrome-setup">
                        <i class="fas fa-shield-alt me-1"></i> Open SSL Setup
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="device-qr-test-print" disabled>
                        <i class="fas fa-vial me-1"></i> Test Print
                    </button>
                </div>

                <div id="zebra-bp-printer-list" class="d-none mb-2"></div>
                <div id="zebra-bp-diagnostics"></div>
                <div id="device-qr-print-result" class="device-qr-print-result" role="status" aria-live="polite"></div>

                <div class="mb-3">
                    <label for="deviceQrLabelSets" class="form-label">Number of sets</label>
                    <input type="number" class="form-control form-control-sm" id="deviceQrLabelSets" min="1" max="<?php echo $maxSets; ?>" value="<?php echo $defaultSets; ?>" required style="max-width:8rem;">
                    <div class="form-text">Each set = one <?php echo $labelWmm; ?> mm × 2 strip with <?php echo $labelsPerSet; ?> identical QR labels.</div>
                </div>

                <p class="device-qr-preview-meta mb-2" id="deviceQrPreviewMeta"></p>
                <div class="device-qr-preview-grid" id="deviceQrPreviewGrid"></div>
            </div>
            <div class="modal-footer py-2 flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="device-qr-full-preview"><i class="fas fa-external-link-alt me-1"></i> Full Preview</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="device-qr-download-pdf"><i class="fas fa-file-pdf me-1"></i> PDF</button>
                <button type="button" class="btn btn-outline-dark btn-sm" id="device-qr-download-zpl"><i class="fas fa-download me-1"></i> ZPL File</button>
                <button type="button" class="btn btn-dark btn-sm" id="device-qr-confirm-print" disabled><i class="fas fa-print me-1"></i> Print Labels</button>
            </div>
        </div>
    </div>
</div>

<style>
.device-qr-preview-grid{display:flex;flex-direction:column;gap:10px;align-items:center;}
.device-qr-sticker-pair{display:flex;gap:0;padding:8px;background:#e9ecef;border:1px dashed #adb5bd;border-radius:4px;width:408px;}
.device-qr-sticker-card{width:200px;height:100px;border:1px solid #212529;border-radius:6px;background:#fff;padding:5px 6px;display:flex;flex-direction:row;align-items:center;gap:10px;overflow:hidden;box-sizing:border-box;}
.device-qr-sticker-card .qr-img{width:58px;height:58px;flex:0 0 58px;object-fit:contain;}
.device-qr-sticker-card .qr-text{flex:1;font-family:Arial,sans-serif;}
.device-qr-sticker-card .asset-no{font-size:15px;font-weight:600;color:#666;}
.device-qr-sticker-card .serial-no{font-size:15px;font-weight:800;color:#111;word-break:break-all;}
.device-qr-printer-row{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;padding:.55rem .75rem;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;}
.device-qr-printer-row label{margin:0;font-size:.8125rem;font-weight:600;}
.device-qr-printer-row select{min-width:16rem;max-width:100%;}
#deviceQrPrintModal .modal-body{max-height:70vh;overflow:auto;background:#f1f3f5;}
</style>
