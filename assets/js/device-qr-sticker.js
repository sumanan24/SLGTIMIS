/**
 * Device QR sticker printing — Zebra ZD230 via Browser Print (client-side only).
 */
(function (global, document) {
    'use strict';

    var STORAGE_KEY = 'slgti_zebra_printer_uid';
    var cachedPrinters = [];
    var printerProbe = null;
    var printData = null;

    var STATUS = {
        LOADING: 'loading',
        READY: 'ready',
        SERVICE_UNAVAILABLE: 'service_unavailable',
        SSL_SETUP_REQUIRED: 'ssl_setup_required',
        NO_PRINTERS: 'no_printers'
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function readPrintData() {
        var el = document.getElementById('device-qr-print-data');
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    function setsFromUi() {
        var input = document.getElementById('deviceQrLabelSets');
        var max = printData && printData.maxSets ? printData.maxSets : 50;
        var n = input ? parseInt(input.value, 10) : 1;
        if (isNaN(n) || n < 1) {
            n = 1;
        }
        return Math.min(max, n);
    }

    function rememberPrinterUid(uid) {
        try {
            if (uid) {
                global.localStorage.setItem(STORAGE_KEY, uid);
            }
        } catch (e) { /* ignore */ }
    }

    function rememberedPrinterUid() {
        try {
            return global.localStorage.getItem(STORAGE_KEY) || '';
        } catch (e) {
            return '';
        }
    }

    function configuredModel() {
        return (printData && printData.printerModel) ? printData.printerModel : 'Zebra ZD230';
    }

    function printerOptionLabel(printer, isSelected) {
        var name = printer.name || 'Zebra Printer';
        if (isSelected) {
            return name + ' — Connected';
        }
        return name;
    }

    function statusIcon(status) {
        switch (status) {
            case STATUS.READY:
                return '🟢';
            case STATUS.SSL_SETUP_REQUIRED:
                return '🟠';
            case STATUS.NO_PRINTERS:
                return '🔴';
            case STATUS.SERVICE_UNAVAILABLE:
                return '🟡';
            default:
                return '⚪';
        }
    }

    function statusLine(probe, selectedPrinter) {
        if (!probe) {
            return statusIcon(STATUS.LOADING) + ' Detecting printers…';
        }
        var icon = statusIcon(probe.status);
        if (probe.status === STATUS.READY && selectedPrinter) {
            return icon + ' ' + escapeHtml(selectedPrinter.name) + ' connected and ready';
        }
        return icon + ' ' + escapeHtml(probe.message || 'Detecting printers…');
    }

    function updatePrintButton(probe, selectEl) {
        var printBtn = document.getElementById('device-qr-confirm-print');
        if (!printBtn) {
            return;
        }
        var ready = probe
            && probe.status === STATUS.READY
            && selectEl
            && String(selectEl.value || '') !== '';
        printBtn.disabled = !ready;
        printBtn.title = ready
            ? 'Send ZPL to the selected Zebra printer'
            : 'Select a detected Zebra printer first';
    }

    function renderSetupHelp(probe) {
        var box = document.getElementById('device-qr-printer-setup');
        var client = global.ZebraBrowserPrintClient;
        if (!box) {
            return;
        }
        if (!probe || probe.status === STATUS.READY) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }

        var sslUrl = client && client.sslSupportUrl
            ? client.sslSupportUrl()
            : 'https://localhost:9101/ssl_support';
        var steps = client && client.getChromeSetupSteps
            ? client.getChromeSetupSteps()
            : ['Install Zebra Browser Print on this PC and click Refresh Printers.'];

        var title = 'Zebra Browser Print is not running on this computer.';
        if (probe.status === STATUS.SSL_SETUP_REQUIRED) {
            title = 'SSL certificate requires setup before Chrome can reach Browser Print.';
        } else if (probe.status === STATUS.NO_PRINTERS) {
            title = 'Browser Print is running, but no Zebra printer was detected.';
        }

        var html = '<strong>' + escapeHtml(title) + '</strong>';
        if (probe.status !== STATUS.NO_PRINTERS) {
            html += '<ol class="mb-2 ps-3 mt-2">';
            steps.forEach(function (step) {
                html += '<li>' + escapeHtml(step) + '</li>';
            });
            html += '</ol>';
        } else {
            html += '<ul class="mb-2 ps-3 mt-2">';
            html += '<li>Check the ZD230 USB cable and power.</li>';
            html += '<li>In Browser Print → Settings, enable Broadcast search and Driver search.</li>';
            html += '<li>Select your ZDesigner ZD230 printer, then click Refresh Printers.</li>';
            html += '</ul>';
        }
        html += '<a class="alert-link" href="' + escapeHtml(sslUrl) + '" target="_blank" rel="noopener">Open certificate setup</a>'
            + ' · You can still use <strong>PDF</strong>, <strong>ZPL file</strong>, or <strong>Full preview</strong>.';
        box.innerHTML = html;
        box.classList.remove('d-none');
    }

    function fillPrinterSelect(selectEl, printers, preferredUid) {
        if (!selectEl) {
            return null;
        }
        if (!printers.length) {
            selectEl.innerHTML = '<option value="">— No Zebra printer detected —</option>';
            return null;
        }

        var client = global.ZebraBrowserPrintClient;
        var preferred = preferredUid || rememberedPrinterUid() || '';
        var best = client && client.pickBestPrinter
            ? client.pickBestPrinter(printers, configuredModel(), preferred)
            : printers[0];
        var bestUid = best ? String(best.uid || '') : '';

        var html = '';
        printers.forEach(function (p, idx) {
            var uid = String(p.uid || ('printer-' + idx));
            var selected = preferred && uid === preferred;
            if (!preferred && uid === bestUid) {
                selected = true;
            }
            html += '<option value="' + escapeHtml(uid) + '"' + (selected ? ' selected' : '') + '>'
                + escapeHtml(printerOptionLabel(p, selected)) + '</option>';
        });
        selectEl.innerHTML = html;

        var selectedPrinter = null;
        for (var i = 0; i < printers.length; i++) {
            if (String(printers[i].uid || '') === String(selectEl.value || '')) {
                selectedPrinter = printers[i];
                break;
            }
        }
        rememberPrinterUid(selectEl.value);
        return selectedPrinter;
    }

    function refreshPrinterList(statusEl) {
        var client = global.ZebraBrowserPrintClient;
        var selectEl = document.getElementById('device-qr-printer-select');
        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (!client) {
            if (selectEl) {
                selectEl.innerHTML = '<option value="">Print module missing</option>';
            }
            if (statusEl) {
                statusEl.textContent = 'Print module failed to load.';
            }
            updatePrintButton(null, selectEl);
            return Promise.resolve([]);
        }

        if (selectEl) {
            selectEl.innerHTML = '<option value="">Detecting printers…</option>';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        if (statusEl) {
            statusEl.innerHTML = statusIcon(STATUS.LOADING) + ' Detecting printers on this computer…';
        }
        updatePrintButton(null, selectEl);

        return client.probeBrowserPrint({ preferredModel: configuredModel() })
            .then(function (probe) {
                printerProbe = probe;
                cachedPrinters = probe.printers || [];
                var selected = fillPrinterSelect(selectEl, cachedPrinters, rememberedPrinterUid());
                renderSetupHelp(probe);
                if (statusEl) {
                    statusEl.innerHTML = statusLine(probe, selected);
                }
                updatePrintButton(probe, selectEl);
                return cachedPrinters;
            })
            .catch(function (err) {
                printerProbe = {
                    status: STATUS.SERVICE_UNAVAILABLE,
                    printers: [],
                    message: (err && err.message) ? err.message : 'Could not reach Zebra Browser Print.'
                };
                cachedPrinters = [];
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Browser Print unavailable</option>';
                }
                renderSetupHelp(printerProbe);
                if (statusEl) {
                    statusEl.innerHTML = statusIcon(STATUS.SERVICE_UNAVAILABLE) + ' '
                        + escapeHtml(printerProbe.message);
                }
                updatePrintButton(printerProbe, selectEl);
                return [];
            })
            .finally(function () {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                }
            });
    }

    function ensurePreviewStyles() {
        var style = document.getElementById('device-qr-preview-styles');
        if (!style) {
            style = document.createElement('style');
            style.id = 'device-qr-preview-styles';
            document.head.appendChild(style);
        }
        style.textContent = ''
            + '.device-qr-preview-grid{display:flex;flex-direction:column;gap:10px;align-items:center;}'
            + '.device-qr-sticker-pair{display:flex;gap:0;padding:8px;background:#e9ecef;border:1px dashed #adb5bd;border-radius:4px;width:408px;}'
            + '.device-qr-sticker-card{width:200px;height:100px;border:1px solid #212529;border-radius:6px;background:#fff;padding:5px 6px;display:flex;flex-direction:row;align-items:center;gap:10px;box-shadow:0 1px 2px rgba(0,0,0,.08);overflow:hidden;box-sizing:border-box;}'
            + '.device-qr-sticker-card .qr-img{width:58px;height:58px;flex:0 0 58px;object-fit:contain;padding:2px;box-sizing:border-box;}'
            + '.device-qr-sticker-card .qr-text{flex:1;min-width:0;font-family:Arial,sans-serif;display:flex;flex-direction:column;justify-content:center;gap:2px;}'
            + '.device-qr-sticker-card .qr-text .asset-no{font-size:15px;font-weight:600;color:#666;line-height:1.15;}'
            + '.device-qr-sticker-card .qr-text .serial-no{font-size:15px;font-weight:800;line-height:1.15;word-break:break-all;color:#111;}'
            + '.device-qr-preview-meta{font-size:.875rem;color:#495057;}'
            + '.device-qr-printer-row{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;margin-bottom:.5rem;padding:.55rem .75rem;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;}'
            + '.device-qr-printer-row label{margin:0;font-size:.8125rem;font-weight:600;}'
            + '.device-qr-printer-row select{min-width:16rem;max-width:100%;}'
            + '.device-qr-printer-status{display:block;width:100%;font-size:.8125rem;margin-top:.25rem;}'
            + '#deviceQrPrintModal .modal-body{max-height:70vh;overflow:auto;background:#f1f3f5;}';
    }

    function stickerCardHtml(qrDataUri, assetId, serialNumber) {
        var body = ''
            + '<div class="asset-no">A/N ' + escapeHtml(assetId || '—') + '</div>'
            + '<div class="serial-no">S/N ' + escapeHtml(serialNumber || '—') + '</div>';
        return ''
            + '<div class="device-qr-sticker-card">'
            + '  <img class="qr-img" src="' + escapeHtml(qrDataUri || '') + '" alt="QR">'
            + '  <div class="qr-text">' + body + '</div>'
            + '</div>';
    }

    function renderPreviewGrid() {
        var grid = document.getElementById('deviceQrPreviewGrid');
        var meta = document.getElementById('deviceQrPreviewMeta');
        if (!printData || !grid) {
            return;
        }
        var sets = setsFromUi();
        var perSet = printData.labelsPerSet || 2;
        var totalStickers = sets * perSet;
        var html = '';
        for (var s = 0; s < sets; s++) {
            html += '<div class="device-qr-sticker-pair">';
            for (var i = 0; i < perSet; i++) {
                html += stickerCardHtml(printData.qrDataUri, printData.assetId, printData.serialNumber);
            }
            html += '</div>';
        }
        grid.innerHTML = html;
        if (meta) {
            meta.innerHTML = '<strong>' + sets + '</strong> set(s)'
                + ' · <strong>' + totalStickers + '</strong> sticker(s) total'
                + ' · <strong>' + perSet + '</strong> identical QR labels side-by-side per set'
                + ' · Asset No. + Serial Number beside QR'
                + '<br><span class="text-muted">Both stickers use the same QR linking to this device page.</span>';
        }
    }

    function fetchZpl(sets) {
        var url = (printData.zplUrl || '') + '?id=' + encodeURIComponent(printData.deviceId) + '&sets=' + encodeURIComponent(sets);
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Could not generate label data (HTTP ' + res.status + ').');
            }
            return res.text();
        });
    }

    function setBusy(buttons, busy) {
        (buttons || []).forEach(function (btn) {
            if (btn) {
                btn.disabled = !!busy;
            }
        });
    }

    function showUserMessage(message) {
        window.alert(message);
    }

    function printToZebra(buttons) {
        var client = global.ZebraBrowserPrintClient;
        if (!client) {
            showUserMessage('Zebra print module failed to load. Refresh the page and try again.');
            return;
        }

        var selectEl = document.getElementById('device-qr-printer-select');
        var printerUid = selectEl ? String(selectEl.value || '') : '';
        if (!printerUid) {
            if (printerProbe && printerProbe.status === STATUS.SSL_SETUP_REQUIRED) {
                showUserMessage('SSL certificate requires setup. Click Set up Chrome, then Refresh Printers.');
            } else if (printerProbe && printerProbe.status === STATUS.SERVICE_UNAVAILABLE) {
                showUserMessage('Zebra Browser Print is not running on this computer. Install and start Browser Print, then click Refresh Printers.');
            } else if (printerProbe && printerProbe.status === STATUS.NO_PRINTERS) {
                showUserMessage('No Zebra printer detected. Check the USB connection and Browser Print settings.');
            } else {
                showUserMessage('Select a Zebra printer before printing.');
            }
            return;
        }

        var sets = setsFromUi();
        var perSet = printData.labelsPerSet || 2;
        rememberPrinterUid(printerUid);
        setBusy(buttons, true);

        var statusEl = document.getElementById('device-qr-printer-status');
        if (statusEl) {
            statusEl.innerHTML = statusIcon(STATUS.LOADING) + ' Sending labels to printer…';
        }

        fetchZpl(sets)
            .then(function (zpl) {
                return client.resolvePrinter(printerUid, cachedPrinters, configuredModel())
                    .then(function (printer) {
                        return client.sendToDevice(printer, zpl).then(function () {
                            return printer;
                        });
                    });
            })
            .then(function (printer) {
                var label = (printer && printer.name) ? printer.name : configuredModel();
                var totalLabels = sets * perSet;
                if (statusEl) {
                    statusEl.innerHTML = statusIcon(STATUS.READY) + ' ' + escapeHtml(label) + ' connected and ready';
                }
                showUserMessage(totalLabels + ' QR label(s) sent successfully to ' + label + '.');
                var modalEl = document.getElementById('deviceQrPrintModal');
                if (modalEl && global.bootstrap && global.bootstrap.Modal) {
                    var inst = global.bootstrap.Modal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                    }
                }
            })
            .catch(function (err) {
                var msg = (err && err.message) ? err.message : 'Print failed.';
                if (statusEl && printerProbe) {
                    statusEl.innerHTML = statusLine(printerProbe, null);
                }
                showUserMessage(msg);
            })
            .finally(function () {
                setBusy(buttons, false);
                updatePrintButton(printerProbe, selectEl);
            });
    }

    function downloadZpl(buttons) {
        var sets = setsFromUi();
        setBusy(buttons, true);
        fetchZpl(sets)
            .then(function (zpl) {
                var blob = new Blob([zpl], { type: 'text/plain;charset=utf-8' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'device-qr-' + (printData.assetId || printData.deviceId) + '-x' + sets + '.zpl';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function () { URL.revokeObjectURL(a.href); }, 500);
            })
            .catch(function (err) {
                showUserMessage((err && err.message) ? err.message : 'Could not download ZPL file.');
            })
            .finally(function () {
                setBusy(buttons, false);
            });
    }

    function bindModal() {
        var modalEl = document.getElementById('deviceQrPrintModal');
        if (!modalEl || modalEl.getAttribute('data-device-qr-bound') === '1') {
            return;
        }
        modalEl.setAttribute('data-device-qr-bound', '1');
        printData = readPrintData();
        ensurePreviewStyles();

        var setsInput = document.getElementById('deviceQrLabelSets');
        if (setsInput) {
            setsInput.addEventListener('input', renderPreviewGrid);
            setsInput.addEventListener('change', renderPreviewGrid);
        }

        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshPrinterList(document.getElementById('device-qr-printer-status'));
            });
        }

        var setupBtn = document.getElementById('device-qr-chrome-setup');
        if (setupBtn) {
            setupBtn.addEventListener('click', function () {
                var client = global.ZebraBrowserPrintClient;
                var url = client && client.sslSupportUrl
                    ? client.sslSupportUrl()
                    : 'https://localhost:9101/ssl_support';
                global.open(url, '_blank', 'noopener');
            });
        }

        var printerSelect = document.getElementById('device-qr-printer-select');
        if (printerSelect) {
            printerSelect.addEventListener('change', function () {
                rememberPrinterUid(printerSelect.value);
                var selected = null;
                for (var i = 0; i < cachedPrinters.length; i++) {
                    if (String(cachedPrinters[i].uid || '') === String(printerSelect.value || '')) {
                        selected = cachedPrinters[i];
                        break;
                    }
                }
                var statusEl = document.getElementById('device-qr-printer-status');
                if (statusEl && printerProbe) {
                    statusEl.innerHTML = statusLine(printerProbe, selected);
                }
                updatePrintButton(printerProbe, printerSelect);
            });
        }

        var printBtn = document.getElementById('device-qr-confirm-print');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                printToZebra([printBtn]);
            });
        }

        var zplBtn = document.getElementById('device-qr-download-zpl');
        if (zplBtn) {
            zplBtn.addEventListener('click', function () {
                downloadZpl([zplBtn]);
            });
        }

        var pdfBtn = document.getElementById('device-qr-download-pdf');
        if (pdfBtn) {
            pdfBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!printData) {
                    return;
                }
                global.location.href = (printData.pdfUrl || '') + '?id=' + encodeURIComponent(printData.deviceId)
                    + '&sets=' + encodeURIComponent(setsFromUi());
            });
        }

        var previewBtn = document.getElementById('device-qr-full-preview');
        if (previewBtn) {
            previewBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!printData) {
                    return;
                }
                global.open(
                    (printData.previewUrl || '') + '?id=' + encodeURIComponent(printData.deviceId)
                        + '&sets=' + encodeURIComponent(setsFromUi()),
                    '_blank'
                );
            });
        }

        modalEl.addEventListener('show.bs.modal', function () {
            printData = readPrintData();
            renderPreviewGrid();
            refreshPrinterList(document.getElementById('device-qr-printer-status'));
        });
    }

    function init() {
        bindModal();
    }

    global.DeviceQrSticker = { init: init, renderPreviewGrid: renderPreviewGrid };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
