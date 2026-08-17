/**
 * Device QR sticker printing for Zebra ZD230 — 2-up identical labels (same pattern as admission roll stickers).
 */
(function (global, document) {
    'use strict';

    var STORAGE_KEY = 'slgti_zebra_printer_uid';
    var cachedPrinters = [];
    var printData = null;

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

    function printerOptionLabel(printer) {
        var name = printer.name || 'Printer';
        var conn = printer.connection ? (' · ' + printer.connection) : '';
        var tag = '';
        if (printer.source === 'pc') {
            tag = ' [PC]';
        } else if (printer.source === 'zebra') {
            tag = ' [Zebra]';
        }
        return name + conn + tag;
    }

    function isPcPrinterUid(uid) {
        return String(uid || '').indexOf('pc:') === 0;
    }

    function pcPrinterNameFromUid(uid) {
        return decodeURIComponent(String(uid || '').slice(3));
    }

    function renderSetupHelp(show) {
        var box = document.getElementById('device-qr-printer-setup');
        var client = global.ZebraBrowserPrintClient;
        if (!box) {
            return;
        }
        if (!show) {
            box.classList.add('d-none');
            box.innerHTML = '';
            return;
        }
        var steps = client && client.getChromeSetupSteps
            ? client.getChromeSetupSteps()
            : ['Install Zebra Browser Print on this PC and click Load printers.'];
        var sslUrl = client && client.sslSupportUrl ? client.sslSupportUrl() : 'https://localhost:9101/ssl_support';
        var html = '<strong>No printer detected on this computer.</strong> Chrome on '
            + (global.location && global.location.protocol === 'https:' ? 'HTTPS' : 'HTTP')
            + ' needs Zebra Browser Print running locally:<ol class="mb-2 ps-3">';
        steps.forEach(function (step) {
            html += '<li>' + escapeHtml(step) + '</li>';
        });
        html += '</ol><a class="alert-link" href="' + escapeHtml(sslUrl) + '" target="_blank" rel="noopener">Open certificate setup (' + escapeHtml(sslUrl) + ')</a>'
            + ' · Or use <strong>PDF</strong> / <strong>ZPL file</strong> below.';
        box.innerHTML = html;
        box.classList.remove('d-none');
    }

    function fillPrinterSelect(selectEl, printers, preferredUid) {
        if (!selectEl) {
            return;
        }
        if (!printers.length) {
            selectEl.innerHTML = '<option value="">No printer found</option>';
            return;
        }
        var preferred = preferredUid || rememberedPrinterUid() || '';
        var html = '';
        var matched = false;
        printers.forEach(function (p, idx) {
            var uid = String(p.uid || ('printer-' + idx));
            var selected = preferred && uid === preferred;
            if (selected) {
                matched = true;
            }
            html += '<option value="' + escapeHtml(uid) + '"' + (selected ? ' selected' : '') + '>'
                + escapeHtml(printerOptionLabel(p)) + '</option>';
        });
        selectEl.innerHTML = html;
        if (!matched && selectEl.options.length) {
            var preferIdx = -1;
            for (var j = 0; j < selectEl.options.length; j++) {
                var label = (selectEl.options[j].text || '').toLowerCase();
                if (label.indexOf('zdesigner') !== -1 || label.indexOf('zd230') !== -1 || label.indexOf('zebra') !== -1) {
                    preferIdx = j;
                    break;
                }
            }
            selectEl.selectedIndex = preferIdx >= 0 ? preferIdx : 0;
        }
        rememberPrinterUid(selectEl.value);
    }

    function refreshPrinterList(statusEl) {
        var client = global.ZebraBrowserPrintClient;
        var selectEl = document.getElementById('device-qr-printer-select');
        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (!client) {
            if (selectEl) {
                selectEl.innerHTML = '<option value="">Print module missing</option>';
            }
            return Promise.resolve([]);
        }
        if (selectEl) {
            selectEl.innerHTML = '<option value="">Loading printers…</option>';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        if (statusEl) {
            statusEl.textContent = 'Loading printers from this PC…';
        }

        var discover = client.discoverAllPrinters
            ? client.discoverAllPrinters({ pcPrintersUrl: printData && printData.printersUrl })
            : client.getLocalPrinters();

        return discover
            .then(function (list) {
                cachedPrinters = list || [];
                var ctx = cachedPrinters._context || {};
                fillPrinterSelect(selectEl, cachedPrinters, rememberedPrinterUid());
                if (!cachedPrinters.length) {
                    renderSetupHelp(true);
                    if (statusEl) {
                        statusEl.textContent = ctx.serverPrintAvailable
                            ? 'No local printers found. Use Zebra Browser Print on this PC (see steps below).'
                            : 'No printers found on this PC. Install Zebra Browser Print and click Set up Chrome.';
                    }
                } else {
                    renderSetupHelp(false);
                    if (statusEl) {
                        var pcCount = cachedPrinters.filter(function (p) {
                            return p.source === 'pc' || p.source === 'zebra+pc';
                        }).length;
                        var zebraCount = cachedPrinters.filter(function (p) {
                            return p.source === 'zebra' || p.source === 'zebra+pc';
                        }).length;
                        statusEl.textContent = cachedPrinters.length + ' printer(s) on this PC'
                            + (zebraCount ? (' · ' + zebraCount + ' via Browser Print') : '')
                            + (pcCount ? (' · ' + pcCount + ' from server Windows') : '')
                            + '. Select ZDesigner ZD230 if listed.';
                    }
                }
                return cachedPrinters;
            })
            .catch(function (err) {
                cachedPrinters = [];
                renderSetupHelp(true);
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Could not load printers</option>';
                }
                if (statusEl) {
                    statusEl.textContent = (err && err.message) ? err.message : 'Could not load printer list.';
                }
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
            + '.device-qr-printer-row{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;margin-bottom:1rem;padding:.55rem .75rem;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;}'
            + '.device-qr-printer-row label{margin:0;font-size:.8125rem;font-weight:600;}'
            + '.device-qr-printer-row select{min-width:14rem;max-width:100%;}'
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
                + '<br><span class="text-muted">Left and right stickers use the same device QR.</span>';
        }
    }

    function fetchZpl(sets) {
        var url = (printData.zplUrl || '') + '?id=' + encodeURIComponent(printData.deviceId) + '&sets=' + encodeURIComponent(sets);
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Could not load ZPL (HTTP ' + res.status + ').');
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

    function printToZebra(buttons) {
        var client = global.ZebraBrowserPrintClient;
        if (!client) {
            window.alert('Zebra print module failed to load.');
            return;
        }
        var selectEl = document.getElementById('device-qr-printer-select');
        var printerUid = selectEl ? String(selectEl.value || '') : '';
        if (!printerUid) {
            window.alert('Select a printer before printing.');
            return;
        }
        var sets = setsFromUi();
        rememberPrinterUid(printerUid);
        setBusy(buttons, true);

        if (isPcPrinterUid(printerUid)) {
            var printerName = pcPrinterNameFromUid(printerUid);
            var serverUrl = printData && printData.serverPrintUrl;
            if (!serverUrl) {
                window.alert('Server print URL is not configured.');
                setBusy(buttons, false);
                return;
            }
            var body = new URLSearchParams();
            body.set('printer', printerName);
            body.set('device_id', String(printData.deviceId || ''));
            body.set('sets', String(sets));
            fetch(serverUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
                body: body.toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        throw new Error((data && data.error) ? data.error : 'Print failed.');
                    }
                    var perSet = printData.labelsPerSet || 2;
                    window.alert(
                        'Sent ' + sets + ' set(s) (' + (sets * perSet) + ' stickers) to ' + printerName + '.'
                    );
                    var modalEl = document.getElementById('deviceQrPrintModal');
                    if (modalEl && global.bootstrap && global.bootstrap.Modal) {
                        var inst = global.bootstrap.Modal.getInstance(modalEl);
                        if (inst) {
                            inst.hide();
                        }
                    }
                })
                .catch(function (err) {
                    window.alert((err && err.message) ? err.message : 'Print failed.');
                })
                .finally(function () {
                    setBusy(buttons, false);
                });
            return;
        }

        fetchZpl(sets)
            .then(function (zpl) {
                var zebraOnly = (cachedPrinters || []).filter(function (p) {
                    return p.source !== 'pc' && String(p.uid || '').indexOf('pc:') !== 0;
                });
                return client.resolvePrinter(printerUid, zebraOnly).then(function (printer) {
                    return client.sendToDevice(printer, zpl).then(function () {
                        return printer;
                    });
                });
            })
            .then(function (printer) {
                var perSet = printData.labelsPerSet || 2;
                var label = (printer && printer.name) ? printer.name : 'Zebra printer';
                window.alert(
                    'Sent ' + sets + ' set(s) (' + (sets * perSet) + ' identical stickers) to ' + label + '.'
                );
                var modalEl = document.getElementById('deviceQrPrintModal');
                if (modalEl && global.bootstrap && global.bootstrap.Modal) {
                    var inst = global.bootstrap.Modal.getInstance(modalEl);
                    if (inst) {
                        inst.hide();
                    }
                }
            })
            .catch(function (err) {
                window.alert((err && err.message) ? err.message : 'Print failed.');
            })
            .finally(function () {
                setBusy(buttons, false);
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
                window.alert((err && err.message) ? err.message : 'Could not download ZPL.');
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
                renderSetupHelp(true);
            });
        }

        var printerSelect = document.getElementById('device-qr-printer-select');
        if (printerSelect) {
            printerSelect.addEventListener('change', function () {
                rememberPrinterUid(printerSelect.value);
            });
        }

        var printBtn = document.getElementById('device-qr-confirm-print');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                printToZebra([printBtn, document.getElementById('device-qr-download-zpl')]);
            });
        }

        var zplBtn = document.getElementById('device-qr-download-zpl');
        if (zplBtn) {
            zplBtn.addEventListener('click', function () {
                downloadZpl([zplBtn, printBtn]);
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
