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

    function renderStatusCard(conn, selectedPrinter) {
        var card = document.getElementById('zebra-bp-status-card');
        if (!card) {
            return;
        }
        var st = conn ? conn.state : STATE().CHECKING;
        var title = 'Connecting to Zebra Browser Print…';
        var meta = 'Please wait while we connect to the printer on this computer.';

        if (st === STATE().READY && selectedPrinter) {
            title = selectedPrinter.name + ' Connected';
            meta = 'Connection: ' + escapeHtml(selectedPrinter.connection || 'USB') + ' · Status: Ready';
        } else if (st === STATE().SSL_SETUP_REQUIRED) {
            title = 'Secure Printer Connection Required';
            meta = 'Chrome must trust the local Browser Print certificate before printing.';
        } else if (st === STATE().HOST_AUTHORIZATION_REQUIRED) {
            title = 'Website Authorization Required';
            meta = 'Allow ' + escapeHtml(siteHost()) + ' in Zebra Browser Print Accepted Hosts.';
        } else if (st === STATE().SERVICE_UNAVAILABLE) {
            title = 'Zebra Browser Print is not running';
            meta = 'Install and start Browser Print on this Windows computer.';
        } else if (st === STATE().NO_PRINTERS) {
            title = 'Zebra Printer Not Detected';
            meta = 'Check USB cable, power, and Browser Print printer selection.';
        } else if (conn && conn.message) {
            title = conn.message;
        }

        var spinner = st === STATE().CHECKING ? '<span class="zebra-bp-spinner"></span>' : '';
        card.innerHTML = ''
            + '<div class="status-head">' + spinner
            + '<span class="status-dot ' + statusDotClass(st) + '"></span>'
            + '<span>' + escapeHtml(title) + '</span></div>'
            + '<p class="status-meta">' + meta + '</p>';
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
                + '<h6>Secure Printer Connection Required</h6>'
                + '<div class="wizard-step"><span class="wizard-step-num">1</span><span>Open Browser Print SSL setup and accept the certificate.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">2</span><span>When prompted, allow <strong>' + escapeHtml(host) + '</strong> as an Accepted Host.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">3</span><span>Return here and click <strong>Refresh Printers</strong>.</span></div>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-open-ssl"><i class="fas fa-shield-alt me-1"></i> Open SSL Setup</button>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Retry Connection</button>'
                + '</div>';
        } else if (conn.state === STATE().HOST_AUTHORIZATION_REQUIRED) {
            html = ''
                + '<h6>Zebra Printer Setup Required</h6>'
                + '<p class="small mb-2">Your computer has Zebra Browser Print, but this website has not yet been authorized.</p>'
                + '<div class="wizard-step"><span class="wizard-step-num">1</span><span>Open Browser Print SSL setup.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">2</span><span>Accept the Browser Print certificate.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">3</span><span>When Browser Print asks for an accepted host, allow <strong>' + escapeHtml(host) + '</strong>.</span></div>'
                + '<div class="wizard-step"><span class="wizard-step-num">4</span><span>Click <strong>Refresh Printers</strong>.</span></div>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-open-ssl"><i class="fas fa-external-link-alt me-1"></i> Open Browser Print SSL Setup</button>'
                + '<button type="button" class="btn btn-outline-secondary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Refresh Printers</button>'
                + '</div>';
        } else if (conn.state === STATE().SERVICE_UNAVAILABLE) {
            html = ''
                + '<h6>Zebra Browser Print is not running</h6>'
                + '<p class="small mb-2">Please install and start Zebra Browser Print on this Windows computer.</p>'
                + '<ol class="small ps-3 mb-2">'
                + '<li>Download and install Zebra Browser Print.</li>'
                + '<li>Connect the ZD230 via USB.</li>'
                + '<li>Start Browser Print from the system tray.</li>'
                + '<li>Click Retry Connection below.</li>'
                + '</ol>'
                + '<div class="wizard-actions">'
                + '<button type="button" class="btn btn-primary btn-sm" id="zebra-bp-retry"><i class="fas fa-sync-alt me-1"></i> Retry Connection</button>'
                + '<a class="btn btn-outline-info btn-sm" href="https://www.zebra.com/us/en/support-downloads/software/printer-software/browser-print.html" target="_blank" rel="noopener"><i class="fas fa-book me-1"></i> Printer Setup Guide</a>'
                + '</div>';
        } else if (conn.state === STATE().NO_PRINTERS) {
            html = ''
                + '<h6>Zebra Printer Not Detected</h6>'
                + '<ul class="small ps-3 mb-2">'
                + '<li>ZD230 powered ON</li>'
                + '<li>USB cable connected</li>'
                + '<li>Zebra Browser Print running</li>'
                + '<li>Printer selected in Browser Print Settings</li>'
                + '<li>Windows driver installed</li>'
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
        connection = { state: STATE().CHECKING, message: 'Connecting…' };
        renderStatusCard(connection, null);
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
                renderWizard(conn);
                renderPrinterList(cachedPrinters, selectEl ? selectEl.value : '');
                renderDiagnostics();
                updatePrintButton(conn, selectEl);
                return cachedPrinters;
            })
            .catch(function () {
                connection = { state: STATE().SERVICE_UNAVAILABLE, message: client.userMessage(STATE().SERVICE_UNAVAILABLE) };
                cachedPrinters = [];
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Browser Print unavailable</option>';
                }
                renderStatusCard(connection, null);
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
            if (modalEl.classList.contains('show') && connection
                && (connection.state === STATE().SSL_SETUP_REQUIRED
                    || connection.state === STATE().HOST_AUTHORIZATION_REQUIRED)) {
                refreshConnection(false);
            }
        });
    }

    function init() {
        bindModal();
    }

    global.DeviceQrSticker = { init: init, refreshConnection: refreshConnection };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
