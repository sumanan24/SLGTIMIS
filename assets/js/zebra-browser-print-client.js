/**
 * ZebraBrowserPrintService — production HTTPS-safe Browser Print integration.
 *
 * Architecture: HTTPS SIS → user's Chrome → localhost Browser Print (9101) → USB ZD230
 * The cloud server never accesses the USB printer.
 */
(function (global) {
    'use strict';

    var DEFAULT_TIMEOUT_MS = 8000;
    var cachedBaseUrl = null;
    var cachedRawDevices = [];
    var lastConnection = null;
    var lastDiagnostics = null;
    var isPrinting = false;
    var debugMode = false;

    var STATE = {
        CHECKING: 'checking',
        READY: 'ready',
        SERVICE_UNAVAILABLE: 'service_unavailable',
        SSL_SETUP_REQUIRED: 'ssl_setup_required',
        HOST_AUTHORIZATION_REQUIRED: 'host_authorization_required',
        NO_PRINTERS: 'no_printers',
        PRINTER_OFFLINE: 'printer_offline',
        PRINTING: 'printing',
        PRINT_SUCCESS: 'print_success',
        PRINT_FAILED: 'print_failed'
    };

    var ZEBRA_NAME_RE = /zd230|zdesigner|zebra/i;

    function siteHostname() {
        return global.location ? global.location.hostname : '';
    }

    function isHttpsPage() {
        return !!(global.location && global.location.protocol === 'https:');
    }

    function isSecureContext() {
        return !!global.isSecureContext;
    }

    function browserName() {
        var ua = global.navigator ? global.navigator.userAgent : '';
        if (/Edg\//.test(ua)) {
            return 'Edge';
        }
        if (/Chrome\//.test(ua) && !/Edg\//.test(ua)) {
            return 'Chrome';
        }
        if (/Firefox\//.test(ua)) {
            return 'Firefox';
        }
        return 'Browser';
    }

    function httpsBases() {
        return ['https://127.0.0.1:9101', 'https://localhost:9101'];
    }

    function httpBases() {
        return ['http://127.0.0.1:9100', 'http://localhost:9100'];
    }

    function serviceBaseCandidates() {
        var bases = isHttpsPage()
            ? httpsBases().concat(httpBases())
            : httpBases().concat(httpsBases());
        if (cachedBaseUrl) {
            bases = [cachedBaseUrl].concat(bases.filter(function (b) { return b !== cachedBaseUrl; }));
        }
        return bases;
    }

    function sslSupportUrl() {
        return 'https://localhost:9101/ssl_support';
    }

    function logDebug() {
        if (!debugMode) {
            return;
        }
        try {
            console.log.apply(console, ['[ZebraBrowserPrint]'].concat(Array.prototype.slice.call(arguments)));
        } catch (e) { /* ignore */ }
    }

    function requestOnBase(base, method, path, body, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var timer = setTimeout(function () {
                xhr.abort();
                reject({ code: 'timeout', message: 'timeout', base: base });
            }, timeoutMs || DEFAULT_TIMEOUT_MS);

            xhr.open(method, base + path, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                clearTimeout(timer);
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve({ base: base, text: xhr.responseText || '', status: xhr.status });
                    return;
                }
                if (xhr.status === 0) {
                    reject({ code: 'blocked', message: 'blocked', base: base });
                    return;
                }
                reject({ code: 'http_' + xhr.status, message: 'HTTP ' + xhr.status, base: base, status: xhr.status });
            };
            try {
                if (body != null) {
                    xhr.setRequestHeader('Content-Type', 'text/plain;charset=UTF-8');
                    xhr.send(body);
                } else {
                    xhr.send();
                }
            } catch (err) {
                clearTimeout(timer);
                reject({ code: 'exception', message: String(err && err.message || err), base: base });
            }
        });
    }

    function request(method, path, body, timeoutMs) {
        if (cachedBaseUrl) {
            return requestOnBase(cachedBaseUrl, method, path, body, timeoutMs)
                .then(function (result) { return result.text; })
                .catch(function (err) {
                    if (err && err.code === 'http_403') {
                        return Promise.reject(err);
                    }
                    cachedBaseUrl = null;
                    return request(method, path, body, timeoutMs);
                });
        }
        var bases = serviceBaseCandidates();
        var attempt = function (index) {
            if (index >= bases.length) {
                return Promise.reject({ code: 'unreachable', message: 'unreachable' });
            }
            return requestOnBase(bases[index], method, path, body, timeoutMs)
                .then(function (result) {
                    cachedBaseUrl = result.base;
                    return result.text;
                })
                .catch(function (err) {
                    if (err && err.code === 'http_403') {
                        return Promise.reject(err);
                    }
                    return attempt(index + 1);
                });
        };
        return attempt(0);
    }

    function parseJson(text, fallback) {
        if (text == null || String(text).trim() === '') {
            return fallback;
        }
        try {
            return JSON.parse(text);
        } catch (e) {
            return fallback;
        }
    }

    function normalizeDevice(raw) {
        if (!raw || typeof raw !== 'object') {
            return null;
        }
        var conn = raw.connection || raw.connectionType || '';
        return {
            name: raw.name || raw.deviceName || 'Zebra Printer',
            deviceType: raw.deviceType || 'printer',
            connection: conn || 'USB',
            uid: raw.uid || raw.uniqueId || raw.id || '',
            provider: raw.provider || 'com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider',
            manufacturer: raw.manufacturer || 'Zebra Technologies',
            version: raw.version != null ? raw.version : 3,
            source: 'zebra',
            _raw: raw
        };
    }

    function normalizeDeviceList(raw) {
        if (raw == null) {
            return [];
        }
        if (Array.isArray(raw)) {
            return raw.map(normalizeDevice).filter(Boolean);
        }
        if (typeof raw === 'object') {
            if (Array.isArray(raw.printer)) {
                return raw.printer.map(normalizeDevice).filter(Boolean);
            }
            if (Array.isArray(raw.printers)) {
                return raw.printers.map(normalizeDevice).filter(Boolean);
            }
            try {
                var asList = Array.from(raw);
                if (asList.length) {
                    return asList.map(normalizeDevice).filter(Boolean);
                }
            } catch (e) { /* ignore */ }
            if (raw[0]) {
                var indexed = [];
                for (var k = 0; raw[k] != null; k++) {
                    var item = normalizeDevice(raw[k]);
                    if (item) {
                        indexed.push(item);
                    }
                }
                if (indexed.length) {
                    return indexed;
                }
            }
            var single = normalizeDevice(raw);
            return single ? [single] : [];
        }
        return [];
    }

    function printerMatchScore(name, preferredModel) {
        var n = String(name || '').toLowerCase();
        var model = String(preferredModel || 'zd230').toLowerCase();
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
        if (model && n.indexOf(model) !== -1) {
            return 60;
        }
        return ZEBRA_NAME_RE.test(n) ? 50 : 0;
    }

    function pickBestPrinter(printers, preferredModel, preferredUid) {
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
        var bestScore = printerMatchScore(best.name, preferredModel);
        for (var j = 1; j < printers.length; j++) {
            var score = printerMatchScore(printers[j].name, preferredModel);
            if (score > bestScore) {
                best = printers[j];
                bestScore = score;
            }
        }
        return best;
    }

    function filterZebraPrinters(printers) {
        var filtered = (printers || []).filter(function (p) {
            return printerMatchScore(p.name, 'zd230') > 0 || ZEBRA_NAME_RE.test(String(p.name || ''));
        });
        return filtered.length ? filtered : (printers || []);
    }

    function extractAcceptedHosts(config) {
        if (!config || typeof config !== 'object') {
            return [];
        }
        var hosts = config.acceptedHosts || config.accepted_hosts || config.AcceptedHosts;
        if (Array.isArray(hosts)) {
            return hosts.map(String);
        }
        if (config.application && Array.isArray(config.application.acceptedHosts)) {
            return config.application.acceptedHosts.map(String);
        }
        return [];
    }

    function hostIsAccepted(config) {
        var host = siteHostname();
        if (!host) {
            return null;
        }
        var accepted = extractAcceptedHosts(config);
        if (!accepted.length) {
            return null;
        }
        var lower = host.toLowerCase();
        return accepted.some(function (h) {
            var a = String(h || '').toLowerCase();
            return a === lower || a === ('https://' + lower) || a.indexOf(lower) !== -1;
        });
    }

    function probeBase(base) {
        var result = {
            base: base,
            reachable: false,
            sslBlocked: false,
            forbidden: false,
            config: null,
            printers: [],
            defaultPrinter: null,
            acceptedHost: null
        };

        return requestOnBase(base, 'GET', '/config', null, 6000)
            .then(function (cfgRes) {
                result.config = parseJson(cfgRes.text, null);
                result.acceptedHost = hostIsAccepted(result.config);
                return requestOnBase(base, 'GET', '/available', null, 6000);
            })
            .catch(function (err) {
                if (err && err.code === 'blocked') {
                    result.sslBlocked = true;
                    return Promise.reject(err);
                }
                if (err && err.code === 'http_403') {
                    result.forbidden = true;
                    result.reachable = true;
                    return Promise.reject(err);
                }
                return requestOnBase(base, 'GET', '/available', null, 6000);
            })
            .then(function (availRes) {
                result.reachable = true;
                result.printers = normalizeDeviceList(parseJson(availRes.text, {}));
                if (result.printers.length) {
                    return result;
                }
                return requestOnBase(base, 'GET', '/default?type=printer', null, 5000)
                    .then(function (defRes) {
                        if (defRes.text && String(defRes.text).trim()) {
                            var def = normalizeDevice(parseJson(defRes.text, null));
                            if (def) {
                                result.defaultPrinter = def;
                                result.printers = [def];
                            }
                        }
                        return result;
                    })
                    .catch(function () {
                        return result;
                    });
            })
            .catch(function (err) {
                if (err && err.code === 'blocked') {
                    result.sslBlocked = true;
                }
                if (err && err.code === 'http_403') {
                    result.forbidden = true;
                    result.reachable = true;
                }
                return result;
            });
    }

    function buildDiagnostics(probeAttempts, connection) {
        var httpsReachable = probeAttempts.some(function (a) {
            return a.reachable && String(a.base || '').indexOf('https://') === 0;
        });
        var httpReachable = probeAttempts.some(function (a) {
            return a.reachable && String(a.base || '').indexOf('http://') === 0;
        });
        var sslBlocked = isHttpsPage() && probeAttempts.some(function (a) {
            return a.sslBlocked && String(a.base || '').indexOf('https://') === 0;
        });

        lastDiagnostics = {
            website: global.location ? global.location.href : '',
            hostname: siteHostname(),
            secureContext: isSecureContext(),
            browser: browserName(),
            browserPrintDetected: !!(connection && connection.serviceReachable),
            browserPrintHttps: httpsReachable,
            browserPrintHttp: httpReachable,
            sslCertificateTrusted: httpsReachable || !isHttpsPage(),
            sslBlocked: sslBlocked,
            acceptedHost: connection ? connection.acceptedHost : null,
            activeBaseUrl: connection ? connection.activeBaseUrl : null,
            state: connection ? connection.state : STATE.CHECKING,
            printerCount: connection && connection.printers ? connection.printers.length : 0,
            selectedPrinter: connection && connection.selectedPrinter
                ? connection.selectedPrinter.name
                : null,
            timestamp: new Date().toISOString()
        };
        return lastDiagnostics;
    }

    function connectionFromProbe(probeAttempts, options) {
        options = options || {};
        var preferredModel = options.preferredModel || 'Zebra ZD230';
        var reachable = probeAttempts.filter(function (a) { return a.reachable; });
        var httpsBlocked = isHttpsPage() && probeAttempts.some(function (a) {
            return a.sslBlocked && String(a.base || '').indexOf('https://') === 0;
        });
        var anyForbidden = probeAttempts.some(function (a) { return a.forbidden; });

        if (!reachable.length) {
            var state = httpsBlocked ? STATE.SSL_SETUP_REQUIRED : STATE.SERVICE_UNAVAILABLE;
            return {
                state: state,
                status: state,
                printers: [],
                selectedPrinter: null,
                defaultPrinter: null,
                preferredModel: preferredModel,
                message: userMessage(state),
                serviceReachable: false,
                sslBlocked: httpsBlocked,
                acceptedHost: false,
                activeBaseUrl: null
            };
        }

        var best = reachable.slice().sort(function (a, b) {
            return b.printers.length - a.printers.length;
        })[0];
        cachedBaseUrl = best.base;

        var accepted = best.acceptedHost;
        if (accepted === false || anyForbidden) {
            return {
                state: STATE.HOST_AUTHORIZATION_REQUIRED,
                status: STATE.HOST_AUTHORIZATION_REQUIRED,
                printers: [],
                selectedPrinter: null,
                defaultPrinter: null,
                preferredModel: preferredModel,
                message: userMessage(STATE.HOST_AUTHORIZATION_REQUIRED),
                serviceReachable: true,
                sslBlocked: false,
                acceptedHost: false,
                activeBaseUrl: cachedBaseUrl
            };
        }

        var list = filterZebraPrinters(best.printers);
        cachedRawDevices = list.slice();

        if (!list.length) {
            return {
                state: STATE.NO_PRINTERS,
                status: STATE.NO_PRINTERS,
                printers: [],
                selectedPrinter: null,
                defaultPrinter: best.defaultPrinter,
                preferredModel: preferredModel,
                message: userMessage(STATE.NO_PRINTERS),
                serviceReachable: true,
                sslBlocked: false,
                acceptedHost: accepted !== false,
                activeBaseUrl: cachedBaseUrl
            };
        }

        var selected = pickBestPrinter(list, preferredModel, options.preferredUid || null);
        return {
            state: STATE.READY,
            status: STATE.READY,
            printers: list,
            selectedPrinter: selected,
            defaultPrinter: best.defaultPrinter || selected,
            preferredModel: preferredModel,
            message: userMessage(STATE.READY),
            serviceReachable: true,
            sslBlocked: false,
            acceptedHost: accepted !== false,
            activeBaseUrl: cachedBaseUrl
        };
    }

    function userMessage(state) {
        switch (state) {
            case STATE.READY:
                return 'Printer ready';
            case STATE.SERVICE_UNAVAILABLE:
                return 'Zebra Browser Print is not running on this computer.';
            case STATE.SSL_SETUP_REQUIRED:
                return 'Secure printer connection required — trust the Browser Print certificate.';
            case STATE.HOST_AUTHORIZATION_REQUIRED:
                return 'This website must be authorized in Zebra Browser Print.';
            case STATE.NO_PRINTERS:
                return 'No Zebra printer detected on this computer.';
            case STATE.PRINTING:
                return 'Printing label…';
            case STATE.PRINT_SUCCESS:
                return 'Label sent successfully.';
            case STATE.PRINT_FAILED:
                return 'Print failed.';
            default:
                return 'Connecting to Zebra Browser Print…';
        }
    }

    function connectBrowserPrint(options) {
        options = options || {};
        return Promise.all(serviceBaseCandidates().map(probeBase))
            .then(function (attempts) {
                var connection = connectionFromProbe(attempts, options);
                lastConnection = connection;
                buildDiagnostics(attempts, connection);
                logDebug('connect', connection.state, lastDiagnostics);
                return connection;
            });
    }

    function connectWithRetry(options) {
        options = options || {};
        var delays = options.delays || [0, 1000, 2000, 4000];
        var finalConn = null;

        function attempt(index) {
            return connectBrowserPrint(options).then(function (conn) {
                finalConn = conn;
                if (conn.state === STATE.READY
                    || conn.state === STATE.NO_PRINTERS
                    || conn.state === STATE.SSL_SETUP_REQUIRED
                    || conn.state === STATE.HOST_AUTHORIZATION_REQUIRED) {
                    return conn;
                }
                if (index >= delays.length - 1) {
                    return conn;
                }
                var waitMs = delays[index + 1] - delays[index];
                return new Promise(function (resolve) {
                    setTimeout(resolve, waitMs > 0 ? waitMs : 500);
                }).then(function () {
                    return attempt(index + 1);
                });
            });
        }

        return attempt(0);
    }

    function discoverPrinters(options) {
        return connectBrowserPrint(options).then(function (conn) {
            return conn.printers || [];
        });
    }

    function selectConfiguredPrinter(printers, preferredModel, preferredUid) {
        return pickBestPrinter(printers || cachedRawDevices, preferredModel, preferredUid);
    }

    function resolvePrinter(preferredUid, cachedList, preferredModel) {
        var list = (cachedList && cachedList.length) ? cachedList : cachedRawDevices;
        var picked = pickBestPrinter(list, preferredModel, preferredUid);
        if (picked) {
            return Promise.resolve(picked);
        }
        return connectBrowserPrint({ preferredModel: preferredModel, preferredUid: preferredUid })
            .then(function (conn) {
                if (conn.state === STATE.SSL_SETUP_REQUIRED) {
                    throw new Error(userMessage(STATE.SSL_SETUP_REQUIRED));
                }
                if (conn.state === STATE.HOST_AUTHORIZATION_REQUIRED) {
                    throw new Error(userMessage(STATE.HOST_AUTHORIZATION_REQUIRED));
                }
                if (conn.state === STATE.SERVICE_UNAVAILABLE) {
                    throw new Error(userMessage(STATE.SERVICE_UNAVAILABLE));
                }
                if (!conn.printers.length) {
                    throw new Error(userMessage(STATE.NO_PRINTERS));
                }
                var match = pickBestPrinter(conn.printers, preferredModel, preferredUid);
                if (!match) {
                    throw new Error('Select a Zebra printer before printing.');
                }
                return match;
            });
    }

    function sendToDevice(device, data) {
        if (isPrinting) {
            return Promise.reject(new Error('A print job is already in progress.'));
        }
        if (!device) {
            return Promise.reject(new Error('No Zebra printer selected.'));
        }

        isPrinting = true;
        var payload = JSON.stringify({
            device: {
                name: device.name,
                deviceType: device.deviceType || 'printer',
                connection: device.connection || '',
                uid: device.uid || '',
                provider: device.provider || 'com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider',
                manufacturer: device.manufacturer || 'Zebra Technologies',
                version: device.version != null ? device.version : 3
            },
            data: data
        });

        return request('POST', '/write', payload)
            .then(function () {
                isPrinting = false;
                return true;
            })
            .catch(function (err) {
                isPrinting = false;
                if (err && err.code === 'http_403') {
                    return Promise.reject(new Error(userMessage(STATE.HOST_AUTHORIZATION_REQUIRED)));
                }
                return Promise.reject(new Error(
                    'Unable to print. Zebra Browser Print is running, but the selected printer is unavailable.'
                ));
            });
    }

    function printZpl(device, zpl) {
        return sendToDevice(device, zpl);
    }

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    /** Minimal test label — not a real device/student label. */
    function buildTestPrintZpl(options) {
        options = options || {};
        var deviceId = options.deviceId != null ? String(options.deviceId) : '—';
        var printerName = options.printerName || 'Zebra ZD230';
        var connection = options.connection || 'USB';
        var now = new Date();
        var stamp = now.getFullYear() + '-'
            + pad2(now.getMonth() + 1) + '-'
            + pad2(now.getDate()) + ' '
            + pad2(now.getHours()) + ':'
            + pad2(now.getMinutes());

        return '^XA'
            + '^PW406^LL203^LH0,0^CI28'
            + '^FO20,20^A0N,28,24^FDSLGTI SIS^FS'
            + '^FO20,55^A0N,22,18^FDZEBRA PRINTER TEST^FS'
            + '^FO20,85^A0N,20,16^FDDevice ID: ' + deviceId + '^FS'
            + '^FO20,110^A0N,20,16^FDPrinter: ' + printerName + '^FS'
            + '^FO20,135^A0N,20,16^FDConnection: ' + connection + '^FS'
            + '^FO20,160^A0N,18,14^FD' + stamp + '^FS'
            + '^FO20,185^A0N,18,14^FDStatus: TEST PRINT^FS'
            + '^XZ\n';
    }

    function testPrint(device, options) {
        var zpl = buildTestPrintZpl({
            deviceId: options && options.deviceId,
            printerName: device ? device.name : 'Zebra',
            connection: device ? device.connection : 'USB'
        });
        return printZpl(device, zpl);
    }

    function getChromeSetupSteps() {
        var host = siteHostname() || 'this site';
        return [
            'Install Zebra Browser Print on this Windows PC.',
            'Connect the ZD230 via USB and turn it on.',
            'In Browser Print → Settings, enable Broadcast search and Driver search, then select your printer.',
            'Open ' + sslSupportUrl() + ' and accept the Browser Print certificate.',
            'When prompted, allow ' + host + ' as an Accepted Host.',
            'Return here and click Refresh Printers.'
        ];
    }

    function getLastConnection() {
        return lastConnection;
    }

    function getDiagnostics() {
        return lastDiagnostics;
    }

    function setDebugMode(enabled) {
        debugMode = !!enabled;
    }

    function isPrintInProgress() {
        return isPrinting;
    }

    var service = {
        STATE: STATE,
        userMessage: userMessage,
        sslSupportUrl: sslSupportUrl,
        getChromeSetupSteps: getChromeSetupSteps,
        connectBrowserPrint: connectBrowserPrint,
        connectWithRetry: connectWithRetry,
        discoverPrinters: discoverPrinters,
        selectConfiguredPrinter: selectConfiguredPrinter,
        resolvePrinter: resolvePrinter,
        sendToDevice: sendToDevice,
        printZpl: printZpl,
        testPrint: testPrint,
        buildTestPrintZpl: buildTestPrintZpl,
        printerMatchScore: printerMatchScore,
        pickBestPrinter: pickBestPrinter,
        getLastConnection: getLastConnection,
        getDiagnostics: getDiagnostics,
        setDebugMode: setDebugMode,
        isPrintInProgress: isPrintInProgress,
        probeBrowserPrint: connectBrowserPrint,
        getLocalPrinters: discoverPrinters,
        getDefaultDevice: function (options) {
            return connectBrowserPrint(options).then(function (c) {
                return c.selectedPrinter || c.defaultPrinter || (c.printers[0] || null);
            });
        },
        getLastProbe: getLastConnection,
        discoverAllPrinters: discoverPrinters
    };

    global.ZebraBrowserPrintService = service;
    global.ZebraBrowserPrintClient = service;
})(window);
