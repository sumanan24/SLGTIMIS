/**
 * Device QR sticker printing — Zebra ZD230 via ZebraBrowserPrintService.
 */
(function (global, document) {
    'use strict';

    var STORAGE_KEY = 'slgti_zebra_printer_uid';
    var cachedPrinters = [];
    var connection = null;
    var printData = null;
    var isBusy = false;

    function svc() {
        return global.ZebraBrowserPrintService || global.ZebraBrowserPrintClient;
    }

    function STATE() {
        var s = svc();
        return s ? s.STATE : {};
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

    function siteHost() {
        return global.location ? global.location.hostname : 'sis.slgti.ac.lk';
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
            case STATE().READY:
            case STATE().PRINT_SUCCESS:
                return 'ready';
            case STATE().SSL_SETUP_REQUIRED:
            case STATE().HOST_AUTHORIZATION_REQUIRED:
                return 'warning';
            case STATE().NO_PRINTERS:
            case STATE().PRINT_FAILED:
            case STATE().PRINTER_OFFLINE:
                return 'error';
            case STATE().SERVICE_UNAVAILABLE:
                return 'info';
            default:
                return 'checking';
        }
    }

    function renderStatusCard(conn, selectedPrinter, cardId) {
        var card = document.getElementById(cardId || 'zebra-bp-status-card');
        if (!card) {
            return;
        }
        var st = conn ? conn.state : STATE().CHECKING;
        var title = (conn && conn.title) ? conn.title : 'Connecting to Zebra Browser Print…';
        var meta = (conn && conn.message) ? conn.message : 'Checking connection to your local Zebra printer…';

        var spinner = st === STATE().CHECKING ? '<span class="zebra-bp-spinner"></span>' : '';
        var html = ''
            + '<div class="status-head">' + spinner
            + '<span class="status-dot ' + statusDotClass(st) + '"></span>'
            + '<span>' + escapeHtml(title) + '</span></div>'
            + '<p class="status-meta">' + escapeHtml(meta) + '</p>';

        if (st === STATE().READY && selectedPrinter) {
            html += ''
                + '<dl class="zebra-bp-ready-details small mb-0 mt-2">'
                + '<dt>Printer</dt><dd>' + escapeHtml(selectedPrinter.name) + '</dd>'
                + '<dt>Connection</dt><dd>' + escapeHtml(selectedPrinter.connection || 'USB') + '</dd>'
                + '<dt>Status</dt><dd>Ready</dd>'
                + '<dt>Browser Print</dt><dd>Connected</dd>'
                + '</dl>';
        }

        card.innerHTML = html;
    }

    function renderPageStatus(conn, selectedPrinter) {
        renderStatusCard(conn, selectedPrinter, 'device-page-zebra-status');
    }

    function renderWizard(conn) {
        var wizard = document.getElementById('zebra-bp-setup-wizard');
        var client = svc();
        if (!wizard) {
            return;
        }
        if (!conn || conn.state === STATE().READY) {
            wizard.classList.add('d-none');
            wizard.innerHTML = '';
            return;
        }

        var sslUrl = client && client.sslSupportUrl ? client.sslSupportUrl() : 'https://localhost:9101/ssl_support';
        var host = siteHost();
        var html = '';

        if (conn.state === STATE().SSL_SETUP_REQUIRED) {
            html = ''
                + '<h6>Zebra Printer Connection Requires Setup</h6>'
                + '<p class="small mb-2">Your Zebra ZD230 is already installed in Windows. Chrome needs a one-time permission to connect this website to Zebra Browser Print.</p>'
                + '<div class="wizard-step"><span class="wizard-step-num">1</span><span>Click <strong>Open SSL Setup</strong> below.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">2</span><span>Accept the Browser Print certificate.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">3</span><span>Allow <strong>' + escapeHtml(host) + '</strong> as an Accepted Host when prompted.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">4</span><span>Return here and click <strong>Refresh Printers</strong>.</span></div>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-open-ssl"><i class="fas fa-shield-alt me-1"></i> Open SSL Setup</button>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Refresh Printers</button>'
                + '</div>';
        } else if (conn.state === STATE().HOST_AUTHORIZATION_REQUIRED) {
            html = ''
                + '<h6>Website Authorization Required</h6>'
                + '<p class="small mb-2">Browser Print is running on this laptop, but <strong>' + escapeHtml(host) + '</strong> has not been authorized yet.</p>'
                + '<div class="wizard-step"><span class="wizard-step-num">1</span><span>Open Browser Print SSL setup.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">2</span><span>When asked, allow <strong>' + escapeHtml(host) + '</strong> as an Accepted Host.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">3</span><span>Click <strong>Refresh Printers</strong>.</span></div>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-open-ssl"><i class="fas fa-external-link-alt me-1"></i> Open Browser Print Setup</button>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Refresh Printers</button>'
                + '</div>';
        } else if (conn.state === STATE().SERVICE_UNAVAILABLE) {
            html = ''
                + '<h6>Browser Print Not Running</h6>'
                + '<p class="small mb-2">Start Zebra Browser Print on this laptop. Your Zebra ZD230 is already installed in Windows — no new printer setup is needed.</p>'
                + '<ul class="small ps-3 mb-2">'
                + '<li>Look for the Zebra icon in the system tray (bottom-right).</li>'
                + '<li>If not running, start <strong>Zebra Browser Print</strong> from the Start menu.</li>'
                + '<li>Click <strong>Retry Connection</strong> below.</li>'
                + '</ul>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Retry Connection</button>'
                + '</div>';
        } else if (conn.state === STATE().NO_PRINTERS) {
            html = ''
                + '<h6>Zebra ZD230 Not Found</h6>'
                + '<p class="small mb-2">Browser Print is connected, but your existing ZD230 was not detected. Please verify:</p>'
                + '<ul class="small ps-3 mb-2">'
                + '<li>ZD230 is powered ON</li>'
                + '<li>USB cable is connected</li>'
                + '<li>Printer appears in <strong>Windows Settings → Printers &amp; scanners</strong></li>'
                + '<li>Printer is selected in Browser Print → Settings (Broadcast + Driver search)</li>'
                + '</ul>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Refresh Printers</button>'
                + '</div>';
        }

        wizard.innerHTML = html;
        wizard.classList.toggle('d-none', !html);

        var sslBtn = document.getElementById('zebra-bp-open-ssl');
        if (sslBtn) {
            sslBtn.addEventListener('click', function () {
                global.open(sslUrl, '_blank', 'noopener');
            });
        }
        var retryBtn = document.getElementById('zebra-bp-retry');
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                refreshConnection(true);
            });
        }
    }

    function renderDiagnostics() {
        var box = document.getElementById('zebra-bp-diagnostics');
        var client = svc();
        if (!box || !client || !client.getDiagnostics) {
            return;
        }
        var d = client.getDiagnostics();
        if (!d) {
            box.innerHTML = '';
            return;
        }
        var lines = [
            'Website: ' + (d.website || '—'),
            'Secure Context: ' + (d.secureContext ? 'YES' : 'NO'),
            'Browser: ' + (d.browser || '—'),
            'Browser Print: ' + (d.browserPrintDetected ? 'Detected' : 'Not detected'),
            'Browser Print HTTPS: ' + (d.browserPrintHttps ? 'Available' : 'Unavailable'),
            'SSL Certificate: ' + (d.sslCertificateTrusted ? 'Trusted' : 'Not trusted'),
            'Accepted Host: ' + (d.acceptedHost === true ? 'YES' : (d.acceptedHost === false ? 'NO' : 'Unknown')),
            'Printers found: ' + (d.printerCount != null ? d.printerCount : 0),
            'Selected: ' + (d.selectedPrinter || '—')
        ].join('\n');

        box.innerHTML = ''
            + '<details class="zebra-bp-diagnostics"><summary>Connection diagnostics</summary>'
            + '<pre>' + escapeHtml(lines) + '</pre></details>';
    }

    function renderPrinterList(printers, selectedUid) {
        var listEl = document.getElementById('zebra-bp-printer-list');
        if (!listEl) {
            return;
        }
        if (!printers.length) {
            listEl.innerHTML = '';
            listEl.classList.add('d-none');
            return;
        }
        var html = '<p class="small fw-semibold mb-1">Available Printers</p><ul class="zebra-bp-printer-list">';
        printers.forEach(function (p) {
            var uid = String(p.uid || '');
            var active = uid === String(selectedUid || '');
            html += '<li>'
                + '<span>' + (active ? '●' : '○') + '</span>'
                + '<span><span class="pl-name">' + escapeHtml(p.name) + '</span><br>'
                + '<span class="pl-sub">' + escapeHtml(p.connection || 'USB') + ' · '
                + (active ? 'Selected' : 'Ready') + '</span></span></li>';
        });
        html += '</ul>';
        listEl.innerHTML = html;
        listEl.classList.remove('d-none');
    }

    function fillPrinterSelect(selectEl, printers, preferredUid) {
        if (!selectEl) {
            return null;
        }
        if (!printers.length) {
            selectEl.innerHTML = '<option value="">— No Zebra printer detected —</option>';
            return null;
        }
        var client = svc();
        var preferred = preferredUid || rememberedPrinterUid() || '';
        var best = client && client.pickBestPrinter
            ? client.pickBestPrinter(printers, configuredModel(), preferred)
            : printers[0];
        var bestUid = best ? String(best.uid || '') : '';

        var html = '';
        printers.forEach(function (p, idx) {
            var uid = String(p.uid || ('printer-' + idx));
            var selected = (preferred && uid === preferred) || (!preferred && uid === bestUid);
            html += '<option value="' + escapeHtml(uid) + '"' + (selected ? ' selected' : '') + '>'
                + escapeHtml(p.name) + (selected ? ' — Connected' : '') + '</option>';
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
        var ready = conn && conn.state === STATE().READY && selectEl && String(selectEl.value || '') !== '' && !isBusy;
        if (printBtn) {
            printBtn.disabled = !ready;
        }
        if (testBtn) {
            testBtn.disabled = !ready;
        }
    }

    function refreshConnection(useRetry) {
        var client = svc();
        var selectEl = document.getElementById('device-qr-printer-select');
        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (!client) {
            return Promise.resolve([]);
        }

        hidePrintResult();
        if (selectEl) {
            selectEl.innerHTML = '<option value="">Connecting…</option>';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        connection = { state: STATE().CHECKING, message: 'Checking connection…' };
        renderStatusCard(connection, null);
        renderPageStatus(connection, null);
        renderWizard(null);
        updatePrintButton(null, selectEl);

        var connectFn = useRetry !== false ? client.connectWithRetry : client.connectBrowserPrint;
        return connectFn({
            preferredModel: configuredModel(),
            preferredUid: rememberedPrinterUid()
        })
            .then(function (conn) {
                connection = conn;
                cachedPrinters = conn.printers || [];
                var selected = fillPrinterSelect(selectEl, cachedPrinters, rememberedPrinterUid());
                renderStatusCard(conn, selected);
                renderPageStatus(conn, selected);
                renderWizard(conn);
                renderPrinterList(cachedPrinters, selectEl ? selectEl.value : '');
                renderDiagnostics();
                updatePrintButton(conn, selectEl);
                return cachedPrinters;
            })
            .catch(function () {
                connection = {
                    state: STATE().SERVICE_UNAVAILABLE,
                    title: client.userTitle(STATE().SERVICE_UNAVAILABLE),
                    message: client.userMessage(STATE().SERVICE_UNAVAILABLE)
                };
                cachedPrinters = [];
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Browser Print unavailable</option>';
                }
                renderStatusCard(connection, null);
                renderPageStatus(connection, null);
                renderWizard(connection);
                updatePrintButton(connection, selectEl);
                return [];
            })
            .finally(function () {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                }
            });
    }

    function ensurePreviewStyles() {
        if (!document.getElementById('device-qr-preview-styles')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = (printData && printData.assetsBase ? printData.assetsBase : '') + '/assets/css/device-qr-printer.css';
            link.id = 'device-qr-printer-css';
            document.head.appendChild(link);
        }
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
                + ' · <strong>' + (sets * perSet) + '</strong> sticker(s)'
                + ' · <strong>' + perSet + '</strong> identical QR labels side-by-side per set';
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

    function setPrintingUi(active, message) {
        isBusy = !!active;
        var printBtn = document.getElementById('device-qr-confirm-print');
        var testBtn = document.getElementById('device-qr-test-print');
        var selectEl = document.getElementById('device-qr-printer-select');
        if (printBtn) {
            printBtn.disabled = active || !(connection && connection.state === STATE().READY);
            printBtn.innerHTML = active
                ? '<span class="zebra-bp-spinner"></span> Printing…'
                : '<i class="fas fa-print me-1"></i> Print Labels';
        }
        if (testBtn) {
            testBtn.disabled = active;
        }
        if (active && message) {
            showPrintResult('info', message);
            renderStatusCard({ state: STATE().PRINTING, message: message }, selectedPrinterFromUi());
        }
        updatePrintButton(connection, selectEl);
    }

    function runTestPrint() {
        var client = svc();
        var printer = selectedPrinterFromUi();
        if (!client || !printer || isBusy) {
            return;
        }
        setPrintingUi(true, 'Sending test label… Please wait.');
        client.testPrint(printer, { deviceId: printData && printData.deviceId })
            .then(function () {
                showPrintResult('success', '✓ Test label sent successfully to ' + printer.name + '.');
                renderStatusCard(connection, printer);
            })
            .catch(function (err) {
                showPrintResult('error', (err && err.message) ? err.message : 'Test print failed.');
                renderStatusCard(connection, printer);
            })
            .finally(function () {
                setPrintingUi(false);
            });
    }

    function printToZebra() {
        var client = svc();
        if (!client || isBusy) {
            return;
        }
        var selectEl = document.getElementById('device-qr-printer-select');
        var printerUid = selectEl ? String(selectEl.value || '') : '';
        if (!printerUid) {
            renderWizard(connection);
            return;
        }

        var sets = setsFromUi();
        var perSet = printData.labelsPerSet || 2;
        rememberPrinterUid(printerUid);
        setPrintingUi(true, 'Printing label… Please wait.');

        fetchZpl(sets)
            .then(function (zpl) {
                return client.resolvePrinter(printerUid, cachedPrinters, configuredModel())
                    .then(function (printer) {
                        return client.printZpl(printer, zpl).then(function () {
                            return printer;
                        });
                    });
            })
            .then(function (printer) {
                var total = sets * perSet;
                var name = (printer && printer.name) ? printer.name : configuredModel();
                showPrintResult('success', '✓ ' + total + ' QR label(s) printed successfully to ' + name + '.');
                renderStatusCard(connection, printer);
            })
            .catch(function (err) {
                showPrintResult('error', (err && err.message) ? err.message : 'Print failed.');
                renderStatusCard(connection, selectedPrinterFromUi());
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

    function bindModal() {
        var modalEl = document.getElementById('deviceQrPrintModal');
        if (!modalEl || modalEl.getAttribute('data-device-qr-bound') === '1') {
            return;
        }
        modalEl.setAttribute('data-device-qr-bound', '1');
        printData = readPrintData();

        var setsInput = document.getElementById('deviceQrLabelSets');
        if (setsInput) {
            setsInput.addEventListener('input', renderPreviewGrid);
            setsInput.addEventListener('change', renderPreviewGrid);
        }

        var refreshBtn = document.getElementById('device-qr-refresh-printers');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshConnection(true);
            });
        }

        var setupBtn = document.getElementById('device-qr-chrome-setup');
        if (setupBtn) {
            setupBtn.addEventListener('click', function () {
                var client = svc();
                global.open(client && client.sslSupportUrl ? client.sslSupportUrl() : 'https://localhost:9101/ssl_support', '_blank', 'noopener');
            });
        }

        var printerSelect = document.getElementById('device-qr-printer-select');
        if (printerSelect) {
            printerSelect.addEventListener('change', function () {
                rememberPrinterUid(printerSelect.value);
                var selected = selectedPrinterFromUi();
                renderStatusCard(connection, selected);
                renderPrinterList(cachedPrinters, printerSelect.value);
                updatePrintButton(connection, printerSelect);
            });
        }

        document.getElementById('device-qr-confirm-print')
            && document.getElementById('device-qr-confirm-print').addEventListener('click', printToZebra);

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
            refreshConnection(true);
        });

        global.addEventListener('focus', function () {
            var needsRecheck = connection
                && (connection.state === STATE().SSL_SETUP_REQUIRED
                    || connection.state === STATE().HOST_AUTHORIZATION_REQUIRED);
            if (needsRecheck) {
                refreshConnection(false);
            }
        });

        var pageRetry = document.getElementById('device-page-zebra-retry');
        if (pageRetry) {
            pageRetry.addEventListener('click', function () {
                refreshConnection(true);
            });
        }
    }

    function init() {
        printData = readPrintData();
        ensurePreviewStyles();
        bindModal();
        if (document.getElementById('device-page-zebra-status')) {
            refreshConnection(true);
        }
    }

    global.DeviceQrSticker = { init: init, refreshConnection: refreshConnection };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
