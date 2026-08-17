/**
 * Device QR sticker printing — Windows PC printers (default) or optional Browser Print.
 */
(function (global, document) {
    'use strict';

    var STORAGE_KEY = 'slgti_pc_printer_uid';
    var cachedPrinters = [];
    var connection = null;
    var printData = null;
    var isBusy = false;

    var PC_STATE = {
        CHECKING: 'checking',
        READY: 'ready',
        NO_PRINTERS: 'no_printers',
        UNAVAILABLE: 'unavailable',
        PRINTING: 'printing'
    };

    function svc() {
        return global.ZebraBrowserPrintService || global.ZebraBrowserPrintClient;
    }

    function usesBrowserPrint() {
        return !!(printData && printData.useBrowserPrint);
    }

    function STATE() {
        if (!usesBrowserPrint()) {
            return PC_STATE;
        }
        var s = svc();
        return s ? s.STATE : PC_STATE;
    }

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

    function isPcUid(uid) {
        return String(uid || '').indexOf('pc:') === 0;
    }

    function pcNameFromUid(uid) {
        return decodeURIComponent(String(uid || '').slice(3));
    }

    function printerMatchScore(name, model) {
        var n = String(name || '').toLowerCase();
        var m = String(model || 'zd230').toLowerCase();
        if (n.indexOf('zd230') !== -1) {
            return 100;
        }
        if (n.indexOf('zdesigner') !== -1 && n.indexOf('zpl') !== -1) {
            return 90;
        }
        if (n.indexOf('zdesigner') !== -1) {
            return 80;
        }
        if (n.indexOf('zebra') !== -1) {
            return 70;
        }
        if (m && n.indexOf(m) !== -1) {
            return 60;
        }
        return 0;
    }

    function pickBestPrinter(printers, preferredUid) {
        if (!printers || !printers.length) {
            return null;
        }
        if (preferredUid) {
            for (var i = 0; i < printers.length; i++) {
                if (String(printers[i].uid || '') === String(preferredUid)) {
                    return printers[i];
                }
            }
        }
        var best = printers[0];
        var bestScore = printerMatchScore(best.name, configuredModel());
        for (var j = 1; j < printers.length; j++) {
            var score = printerMatchScore(printers[j].name, configuredModel());
            if (score > bestScore) {
                best = printers[j];
                bestScore = score;
            }
        }
        return bestScore > 0 ? best : printers[0];
    }

    function normalizePcRow(row) {
        if (!row || typeof row !== 'object') {
            return null;
        }
        var name = row.name || row.Name || '';
        if (!name) {
            return null;
        }
        return {
            name: name,
            connection: row.port || row.PortName || 'USB',
            uid: 'pc:' + encodeURIComponent(name),
            source: 'pc'
        };
    }

    function showPrintResult(type, message) {
        var box = document.getElementById('device-qr-print-result');
        if (!box) {
            return;
        }
        box.className = 'device-qr-print-result show ' + type;
        box.textContent = message;
    }

    function hidePrintResult() {
        var box = document.getElementById('device-qr-print-result');
        if (box) {
            box.className = 'device-qr-print-result';
            box.textContent = '';
        }
    }

    function statusDotClass(state) {
        switch (state) {
            case PC_STATE.READY:
                return 'ready';
            case PC_STATE.NO_PRINTERS:
            case PC_STATE.UNAVAILABLE:
                return 'error';
            case PC_STATE.PRINTING:
                return 'checking';
            default:
                return 'checking';
        }
    }

    function renderStatusCard(conn, selectedPrinter, cardId) {
        var card = document.getElementById(cardId || 'zebra-bp-status-card');
        if (!card) {
            return;
        }
        var st = conn ? conn.state : PC_STATE.CHECKING;
        var title = conn && conn.title ? conn.title : 'Loading printers from this PC…';
        var meta = conn && conn.message ? conn.message : 'Reading Windows installed printers.';

        var spinner = st === PC_STATE.CHECKING ? '<span class="zebra-bp-spinner"></span>' : '';
        var html = ''
            + '<div class="status-head">' + spinner
            + '<span class="status-dot ' + statusDotClass(st) + '"></span>'
            + '<span>' + escapeHtml(title) + '</span></div>'
            + '<p class="status-meta">' + escapeHtml(meta) + '</p>';

        if (st === PC_STATE.READY && selectedPrinter) {
            html += ''
                + '<dl class="zebra-bp-ready-details small mb-0 mt-2">'
                + '<dt>Printer</dt><dd>' + escapeHtml(selectedPrinter.name) + '</dd>'
                + '<dt>Connection</dt><dd>' + escapeHtml(selectedPrinter.connection || 'USB') + '</dd>'
                + '<dt>Status</dt><dd>Ready</dd>'
                + '<dt>Source</dt><dd>Windows PC</dd>'
                + '</dl>';
        }

        card.innerHTML = html;
    }

    function renderPageStatus(conn, selectedPrinter) {
        renderStatusCard(conn, selectedPrinter, 'device-page-zebra-status');
        var retry = document.getElementById('device-page-zebra-retry');
        if (retry && conn && conn.state === PC_STATE.READY) {
            retry.classList.remove('d-none');
        }
    }

    function renderWizard(conn) {
        var wizard = document.getElementById('zebra-bp-setup-wizard');
        if (!wizard || !usesBrowserPrint()) {
            if (wizard) {
                wizard.classList.add('d-none');
                wizard.innerHTML = '';
            }
            return;
        }
        /* Browser Print wizard — only when use_browser_print is enabled in config */
        wizard.classList.add('d-none');
        wizard.innerHTML = '';
    }

    function fillPrinterSelect(selectEl, printers, preferredUid) {
        if (!selectEl) {
            return null;
        }
        if (!printers.length) {
            selectEl.innerHTML = '<option value="">— No printer found on this PC —</option>';
            return null;
        }
        var preferred = preferredUid || rememberedPrinterUid() || '';
        var best = pickBestPrinter(printers, preferred);
        var bestUid = best ? String(best.uid || '') : '';

        var html = '';
        printers.forEach(function (p, idx) {
            var uid = String(p.uid || ('printer-' + idx));
            var selected = (preferred && uid === preferred) || (!preferred && uid === bestUid);
            html += '<option value="' + escapeHtml(uid) + '"' + (selected ? ' selected' : '') + '>'
                + escapeHtml(p.name) + (selected ? ' — Ready' : '') + '</option>';
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

    function updatePrintButton(conn, selectEl) {
        var printBtn = document.getElementById('device-qr-confirm-print');
        var testBtn = document.getElementById('device-qr-test-print');
        var ready = conn && conn.state === PC_STATE.READY && selectEl && String(selectEl.value || '') !== '' && !isBusy;
        if (printBtn) {
            printBtn.disabled = !ready;
        }
        if (testBtn) {
            testBtn.disabled = !ready;
        }
    }

    function fetchPcPrinters() {
        var url = printData && printData.printersUrl;
        if (!url) {
            return Promise.reject(new Error('Printer list URL not configured.'));
        }
        return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Could not load printers (HTTP ' + res.status + ').');
                }
                return res.json();
            })
            .then(function (data) {
                var rows = (data && data.printers) ? data.printers : [];
                var platform = (data && data.platform) ? data.platform : '';
                var all = rows.map(normalizePcRow).filter(Boolean);
                var zebra = all.filter(function (p) {
                    return printerMatchScore(p.name, configuredModel()) > 0;
                });
                return {
                    platform: platform,
                    printers: zebra.length ? zebra : all
                };
            });
    }

    function refreshPcPrinters() {
        var selectEl = document.getElementById('device-qr-printer-select');
        var refreshBtn = document.getElementById('device-qr-refresh-printers');

        hidePrintResult();
        if (selectEl) {
            selectEl.innerHTML = '<option value="">Loading printers…</option>';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }

        connection = {
            state: PC_STATE.CHECKING,
            title: 'Loading printers from this PC…',
            message: 'Reading Windows Settings → Printers & scanners.'
        };
        renderStatusCard(connection, null);
        renderPageStatus(connection, null);
        renderWizard(null);
        updatePrintButton(null, selectEl);

        return fetchPcPrinters()
            .then(function (result) {
                cachedPrinters = result.printers || [];
                if (!cachedPrinters.length) {
                    connection = {
                        state: PC_STATE.NO_PRINTERS,
                        title: 'Zebra ZD230 Not Found',
                        message: result.platform === 'Windows'
                            ? 'No Zebra printer in Windows. Check Printers & scanners — your ZD230 should appear there.'
                            : 'Windows printer list is only available when the app runs on the same Windows PC as the printer (local WAMP).'
                    };
                } else {
                    connection = {
                        state: PC_STATE.READY,
                        title: 'Zebra Printer Connected',
                        message: cachedPrinters.length + ' printer(s) loaded from this PC. No Browser Print required.'
                    };
                }
                var selected = fillPrinterSelect(selectEl, cachedPrinters, rememberedPrinterUid());
                renderStatusCard(connection, selected);
                renderPageStatus(connection, selected);
                updatePrintButton(connection, selectEl);
                return cachedPrinters;
            })
            .catch(function (err) {
                connection = {
                    state: PC_STATE.UNAVAILABLE,
                    title: 'Could Not Load Printers',
                    message: (err && err.message) ? err.message : 'Failed to read Windows printers.'
                };
                cachedPrinters = [];
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Could not load printers</option>';
                }
                renderStatusCard(connection, null);
                renderPageStatus(connection, null);
                updatePrintButton(connection, selectEl);
                return [];
            })
            .finally(function () {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                }
            });
    }

    function refreshConnection() {
        if (usesBrowserPrint() && svc()) {
            return refreshBrowserPrintConnection();
        }
        return refreshPcPrinters();
    }

    function refreshBrowserPrintConnection() {
        var client = svc();
        var selectEl = document.getElementById('device-qr-printer-select');
        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (!client) {
            return refreshPcPrinters();
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        return client.connectWithRetry({
            preferredModel: configuredModel(),
            preferredUid: rememberedPrinterUid()
        }).then(function (conn) {
            connection = conn;
            cachedPrinters = conn.printers || [];
            var selected = fillPrinterSelect(selectEl, cachedPrinters, rememberedPrinterUid());
            renderStatusCard(conn, selected);
            renderPageStatus(conn, selected);
            renderWizard(conn);
            updatePrintButton({ state: conn.state === client.STATE.READY ? PC_STATE.READY : PC_STATE.NO_PRINTERS }, selectEl);
            return cachedPrinters;
        }).finally(function () {
            if (refreshBtn) {
                refreshBtn.disabled = false;
            }
        });
    }

    function buildTestZpl() {
        var deviceId = printData && printData.deviceId ? printData.deviceId : '—';
        return '^XA^PW406^LL203^LH0,0^CI28'
            + '^FO20,20^A0N,28,24^FDSLGTI SIS^FS'
            + '^FO20,55^A0N,22,18^FDZEBRA ZD230^FS'
            + '^FO20,80^A0N,20,16^FDPRINTER TEST^FS'
            + '^FO20,105^A0N,20,16^FDDevice ID: ' + deviceId + '^FS'
            + '^XZ\n';
    }

    function sendZplToServer(printerName, zpl, deviceId, sets) {
        var url = printData && printData.serverPrintUrl;
        if (!url) {
            return Promise.reject(new Error('Server print URL not configured.'));
        }
        var body = new URLSearchParams();
        body.set('printer', printerName);
        body.set('zpl', zpl);
        if (deviceId) {
            body.set('device_id', String(deviceId));
        }
        if (sets != null) {
            body.set('sets', String(sets));
        }
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
            body: body.toString()
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (!data || !data.ok) {
                throw new Error((data && data.error) ? data.error : 'Print failed.');
            }
            return data;
        });
    }

    function selectedPrinterFromUi() {
        var selectEl = document.getElementById('device-qr-printer-select');
        var uid = selectEl ? String(selectEl.value || '') : '';
        for (var i = 0; i < cachedPrinters.length; i++) {
            if (String(cachedPrinters[i].uid || '') === uid) {
                return cachedPrinters[i];
            }
        }
        return null;
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

    function setPrintingUi(active, message) {
        isBusy = !!active;
        var printBtn = document.getElementById('device-qr-confirm-print');
        var testBtn = document.getElementById('device-qr-test-print');
        var selectEl = document.getElementById('device-qr-printer-select');
        if (printBtn) {
            printBtn.disabled = active || !(connection && connection.state === PC_STATE.READY);
            printBtn.innerHTML = active
                ? '<span class="zebra-bp-spinner"></span> Printing…'
                : '<i class="fas fa-print me-1"></i> Print Labels';
        }
        if (testBtn) {
            testBtn.disabled = active;
        }
        if (active && message) {
            showPrintResult('info', message);
        }
        updatePrintButton(connection, selectEl);
    }

    function runTestPrint() {
        var printer = selectedPrinterFromUi();
        if (!printer || isBusy) {
            return;
        }
        setPrintingUi(true, 'Sending test label… Please wait.');

        var name = printer.name;
        var promise;
        if (usesBrowserPrint() && svc() && !isPcUid(printer.uid)) {
            promise = svc().testPrint(printer, { deviceId: printData.deviceId });
        } else {
            promise = sendZplToServer(name, buildTestZpl());
        }

        promise
            .then(function () {
                showPrintResult('success', '✓ Test print sent successfully to ' + name + '.');
            })
            .catch(function (err) {
                showPrintResult('error', (err && err.message) ? err.message : 'Test print failed.');
            })
            .finally(function () {
                setPrintingUi(false);
            });
    }

    function printLabels() {
        if (isBusy) {
            return;
        }
        var printer = selectedPrinterFromUi();
        if (!printer) {
            return;
        }
        var sets = setsFromUi();
        var perSet = printData.labelsPerSet || 2;
        rememberPrinterUid(printer.uid);
        setPrintingUi(true, 'Printing label… Please wait.');

        var name = printer.name;
        var promise;

        if (usesBrowserPrint() && svc() && !isPcUid(printer.uid)) {
            promise = fetchZpl(sets).then(function (zpl) {
                return svc().resolvePrinter(printer.uid, cachedPrinters, configuredModel())
                    .then(function (p) {
                        return svc().printZpl(p, zpl).then(function () {
                            return p;
                        });
                    });
            }).then(function (p) {
                return p.name || name;
            });
        } else {
            promise = fetchZpl(sets).then(function (zpl) {
                return sendZplToServer(name, zpl, printData.deviceId, sets).then(function () {
                    return name;
                });
            });
        }

        promise
            .then(function (printedName) {
                showPrintResult('success', '✓ ' + (sets * perSet) + ' QR label(s) printed successfully to ' + printedName + '.');
            })
            .catch(function (err) {
                showPrintResult('error', (err && err.message) ? err.message : 'Print failed.');
            })
            .finally(function () {
                setPrintingUi(false);
            });
    }

    function downloadZpl() {
        if (isBusy) {
            return;
        }
        var sets = setsFromUi();
        isBusy = true;
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
                showPrintResult('error', (err && err.message) ? err.message : 'Could not download ZPL file.');
            })
            .finally(function () {
                isBusy = false;
            });
    }

    function renderPreviewGrid() {
        var grid = document.getElementById('deviceQrPreviewGrid');
        var meta = document.getElementById('deviceQrPreviewMeta');
        if (!printData || !grid) {
            return;
        }
        var sets = setsFromUi();
        var perSet = printData.labelsPerSet || 2;
        var html = '';
        for (var s = 0; s < sets; s++) {
            html += '<div class="device-qr-sticker-pair">';
            for (var i = 0; i < perSet; i++) {
                html += ''
                    + '<div class="device-qr-sticker-card">'
                    + '  <img class="qr-img" src="' + escapeHtml(printData.qrDataUri || '') + '" alt="QR">'
                    + '  <div class="qr-text">'
                    + '    <div class="asset-no">A/N ' + escapeHtml(printData.assetId || '—') + '</div>'
                    + '    <div class="serial-no">S/N ' + escapeHtml(printData.serialNumber || '—') + '</div>'
                    + '  </div></div>';
            }
            html += '</div>';
        }
        grid.innerHTML = html;
        if (meta) {
            meta.innerHTML = '<strong>' + sets + '</strong> set(s) · <strong>' + (sets * perSet)
                + '</strong> sticker(s) · same QR side-by-side';
        }
    }

    function bindModal() {
        var modalEl = document.getElementById('deviceQrPrintModal');
        if (!modalEl || modalEl.getAttribute('data-device-qr-bound') === '1') {
            return;
        }
        modalEl.setAttribute('data-device-qr-bound', '1');

        var setsInput = document.getElementById('deviceQrLabelSets');
        if (setsInput) {
            setsInput.addEventListener('input', renderPreviewGrid);
            setsInput.addEventListener('change', renderPreviewGrid);
        }

        document.getElementById('device-qr-refresh-printers')
            && document.getElementById('device-qr-refresh-printers').addEventListener('click', refreshConnection);

        var printerSelect = document.getElementById('device-qr-printer-select');
        if (printerSelect) {
            printerSelect.addEventListener('change', function () {
                rememberPrinterUid(printerSelect.value);
                var selected = selectedPrinterFromUi();
                renderStatusCard(connection, selected);
                renderPageStatus(connection, selected);
                updatePrintButton(connection, printerSelect);
            });
        }

        document.getElementById('device-qr-confirm-print')
            && document.getElementById('device-qr-confirm-print').addEventListener('click', printLabels);

        document.getElementById('device-qr-test-print')
            && document.getElementById('device-qr-test-print').addEventListener('click', runTestPrint);

        document.getElementById('device-qr-download-zpl')
            && document.getElementById('device-qr-download-zpl').addEventListener('click', downloadZpl);

        document.getElementById('device-qr-download-pdf')
            && document.getElementById('device-qr-download-pdf').addEventListener('click', function (e) {
                e.preventDefault();
                if (printData) {
                    global.location.href = printData.pdfUrl + '?id=' + encodeURIComponent(printData.deviceId)
                        + '&sets=' + encodeURIComponent(setsFromUi());
                }
            });

        document.getElementById('device-qr-full-preview')
            && document.getElementById('device-qr-full-preview').addEventListener('click', function (e) {
                e.preventDefault();
                if (printData) {
                    global.open(printData.previewUrl + '?id=' + encodeURIComponent(printData.deviceId)
                        + '&sets=' + encodeURIComponent(setsFromUi()), '_blank');
                }
            });

        modalEl.addEventListener('show.bs.modal', function () {
            printData = readPrintData();
            renderPreviewGrid();
            refreshConnection();
        });

        var pageRetry = document.getElementById('device-page-zebra-retry');
        if (pageRetry) {
            pageRetry.addEventListener('click', refreshConnection);
        }
    }

    function init() {
        printData = readPrintData();
        bindModal();
        if (document.getElementById('device-page-zebra-status')) {
            refreshConnection();
        }
    }

    global.DeviceQrSticker = { init: init, refreshConnection: refreshConnection };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
