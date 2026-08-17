<?php
/**
 * Device QR label print modal — 2-up preview like admission roll stickers.
 * Vars: $id, $d, $e, $qrDataUri, $labelPrinterConfig, $defaultLabelSets, $maxLabelSets, $labelsPerSet
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
    'printersUrl' => $baseUrl . '/devices/printers',
    'serverPrintUrl' => $baseUrl . '/devices/qr-print-server',
];
?>
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
                    <dt class="col-sm-3">Printer</dt>
                    <dd class="col-sm-9 mb-1"><?php echo $e($printerModel); ?></dd>
                    <dt class="col-sm-3">Labels per set</dt>
                    <dd class="col-sm-9 mb-0"><?php echo $labelsPerSet; ?> <span class="text-muted">(same QR side-by-side)</span></dd>
                </dl>

                <div class="device-qr-printer-row">
                    <label for="device-qr-printer-select">Printer</label>
                    <select id="device-qr-printer-select" class="form-select form-select-sm" title="Select label printer">
                        <option value="">Loading printers…</option>
                    </select>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="device-qr-refresh-printers" title="Load printers from this PC"><i class="fas fa-sync-alt me-1"></i> Load printers</button>
                    <span class="text-muted small" id="device-qr-printer-status"></span>
                </div>
                <p class="small text-muted mb-3">Printers are loaded from <strong>Windows on this server PC</strong>. Choose <strong>ZDesigner ZD230</strong> for label printing. Zebra Browser Print is optional if installed.</p>

                <div class="mb-3">
                    <label for="deviceQrLabelSets" class="form-label">Number of sets</label>
                    <input type="number" class="form-control form-control-sm" id="deviceQrLabelSets" min="1" max="<?php echo $maxSets; ?>" value="<?php echo $defaultSets; ?>" required style="max-width:8rem;">
                    <div class="form-text">Each set fills one <strong>4″ × 1″</strong> strip (two 2″ × 1″ identical labels side-by-side).</div>
                </div>

                <p class="device-qr-preview-meta mb-2" id="deviceQrPreviewMeta"></p>
                <div class="device-qr-preview-grid" id="deviceQrPreviewGrid"></div>
            </div>
            <div class="modal-footer py-2 flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="device-qr-full-preview"><i class="fas fa-external-link-alt me-1"></i> Full preview</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="device-qr-download-pdf"><i class="fas fa-file-pdf me-1"></i> PDF</button>
                <button type="button" class="btn btn-outline-dark btn-sm" id="device-qr-download-zpl"><i class="fas fa-download me-1"></i> ZPL file</button>
                <button type="button" class="btn btn-dark btn-sm" id="device-qr-confirm-print"><i class="fas fa-print me-1"></i> Print Labels</button>
            </div>
        </div>
    </div>
</div>
