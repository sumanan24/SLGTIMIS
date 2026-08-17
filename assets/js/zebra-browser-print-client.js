/**
 * Client for Zebra Browser Print (localhost ports 9100 / 9101).
 * Tries multiple localhost URLs for Chrome on HTTPS production sites.
 */
(function (global) {
    'use strict';

    var DEFAULT_TIMEOUT_MS = 10000;
    var cachedBaseUrl = null;
    var sdkLoadPromise = null;

    var UNAVAILABLE_MSG =
        'Zebra Browser Print is not running on this computer. Install it, connect the ZD230 via USB, then click "Set up Chrome" below.';

    var SDK_SCRIPT_PATHS = [
        '/browserprint/BrowserPrint-3.1.250.min.js',
        '/browserprint/BrowserPrint-3.0.216.min.js',
        '/browserprint/BrowserPrint.min.js',
        '/BrowserPrint.js'
    ];

    function isHttpsPage() {
        return !!(global.location && global.location.protocol === 'https:');
    }

    function serviceBaseCandidates() {
        var bases = [
            'https://127.0.0.1:9101',
            'https://localhost:9101',
            'http://127.0.0.1:9100',
            'http://localhost:9100'
        ];
        if (!isHttpsPage()) {
            bases = [
                'http://127.0.0.1:9100',
                'http://localhost:9100',
                'https://127.0.0.1:9101',
                'https://localhost:9101'
            ];
        }
        if (cachedBaseUrl) {
            bases = [cachedBaseUrl].concat(bases.filter(function (b) { return b !== cachedBaseUrl; }));
        }
        return bases;
    }

    function sslSupportUrl() {
        return isHttpsPage() ? 'https://localhost:9101/ssl_support' : 'http://localhost:9100/ssl_support';
    }

    function getChromeSetupSteps() {
        return [
            'Install Zebra Browser Print on this Windows PC (not on the web server).',
            'Connect the ZD230 label printer via USB and turn it on.',
            'Open Browser Print → Settings → enable Broadcast search and Driver search → select your printer.',
            'Open ' + sslSupportUrl() + ' in a new tab, accept the certificate, and click Yes to add localhost.',
            'When prompted, allow ' + (global.location ? global.location.hostname : 'this site') + ' as an Accepted Host in Browser Print.',
            'Return here and click Load printers.'
        ];
    }

    function requestOnBase(base, method, path, body, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var timer = setTimeout(function () {
                xhr.abort();
                reject(new Error('timeout'));
            }, timeoutMs || DEFAULT_TIMEOUT_MS);

            xhr.open(method, base + path, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                clearTimeout(timer);
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve({ base: base, text: xhr.responseText || '' });
                    return;
                }
                if (xhr.status === 0) {
                    reject(new Error('blocked'));
                    return;
                }
                reject(new Error('HTTP ' + xhr.status));
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
                reject(err);
            }
        });
    }

    function request(method, path, body, timeoutMs) {
        var bases = serviceBaseCandidates();
        var attempt = function (index) {
            if (index >= bases.length) {
                return Promise.reject(new Error(UNAVAILABLE_MSG));
            }
            return requestOnBase(bases[index], method, path, body, timeoutMs)
                .then(function (result) {
                    cachedBaseUrl = result.base;
                    return result.text;
                })
                .catch(function () {
                    return attempt(index + 1);
                });
        };
        return attempt(0);
    }

    function loadScriptFromBase(base, scriptPath) {
        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = base + scriptPath;
            script.async = true;
            script.onload = function () {
                if (typeof global.BrowserPrint === 'object') {
                    cachedBaseUrl = base;
                    resolve(global.BrowserPrint);
                } else {
                    reject(new Error('SDK missing'));
                }
            };
            script.onerror = function () {
                reject(new Error('SDK load failed'));
            };
            document.head.appendChild(script);
        });
    }

    function ensureBrowserPrintSdk() {
        if (typeof global.BrowserPrint === 'object'
            && typeof global.BrowserPrint.getLocalDevices === 'function') {
            return Promise.resolve(global.BrowserPrint);
        }
        if (sdkLoadPromise) {
            return sdkLoadPromise;
        }

        var bases = serviceBaseCandidates();
        var tryBase = function (baseIndex) {
            if (baseIndex >= bases.length) {
                return Promise.resolve(null);
            }
            var base = bases[baseIndex];
            var tryPath = function (pathIndex) {
                if (pathIndex >= SDK_SCRIPT_PATHS.length) {
                    return tryBase(baseIndex + 1);
                }
                return loadScriptFromBase(base, SDK_SCRIPT_PATHS[pathIndex]).catch(function () {
                    return tryPath(pathIndex + 1);
                });
            };
            return tryPath(0);
        };

        sdkLoadPromise = tryBase(0).catch(function () {
            return null;
        });
        return sdkLoadPromise;
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
        return {
            name: raw.name || raw.deviceName || 'Zebra Printer',
            deviceType: raw.deviceType || 'printer',
            connection: raw.connection || raw.connectionType || '',
            uid: raw.uid || raw.uniqueId || raw.id || '',
            provider: raw.provider || 'com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider',
            manufacturer: raw.manufacturer || 'Zebra Technologies',
            version: raw.version != null ? raw.version : 3
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

    function normalizePcPrinter(raw) {
        if (!raw || typeof raw !== 'object') {
            return null;
        }
        var name = raw.name || raw.Name || 'Printer';
        return {
            name: name,
            deviceType: 'printer',
            connection: raw.port || raw.PortName || 'Windows',
            uid: 'pc:' + encodeURIComponent(name),
            source: 'pc',
            provider: 'windows-spooler',
            manufacturer: raw.driver || raw.DriverName || 'Windows',
            version: 1
        };
    }

    function mergePrinterLists(zebraList, pcList) {
        var merged = [];
        var seen = Object.create(null);

        (zebraList || []).forEach(function (p) {
            var copy = Object.assign({}, p, { source: p.source || 'zebra' });
            var key = String(copy.name || copy.uid || '').toLowerCase();
            if (key && !seen[key]) {
                seen[key] = true;
                merged.push(copy);
            }
        });

        (pcList || []).forEach(function (p) {
            var copy = Object.assign({}, p, { source: p.source || 'pc' });
            var key = String(copy.name || copy.uid || '').toLowerCase();
            if (key && !seen[key]) {
                seen[key] = true;
                merged.push(copy);
            } else if (key && seen[key]) {
                for (var i = 0; i < merged.length; i++) {
                    if (String(merged[i].name || '').toLowerCase() === key && merged[i].source === 'pc') {
                        merged[i] = Object.assign({}, merged[i], copy, { source: 'zebra+pc' });
                        break;
                    }
                }
            }
        });

        return merged;
    }

    function fetchPrinterContext(apiUrl) {
        if (!apiUrl) {
            return Promise.resolve({ platform: '', printers: [], serverPrintAvailable: false });
        }
        return fetch(apiUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                var platform = String((data && data.platform) || '');
                var rows = (data && data.printers) ? data.printers : [];
                var serverPrintAvailable = platform === 'Windows' && rows.length > 0;
                return {
                    platform: platform,
                    printers: rows.map(normalizePcPrinter).filter(Boolean),
                    serverPrintAvailable: serverPrintAvailable
                };
            })
            .catch(function () {
                return { platform: '', printers: [], serverPrintAvailable: false };
            });
    }

    function getLocalPrintersViaSdk() {
        return ensureBrowserPrintSdk().then(function (sdk) {
            if (!sdk || typeof sdk.getLocalDevices !== 'function') {
                return null;
            }
            return new Promise(function (resolve, reject) {
                try {
                    sdk.getLocalDevices(
                        function (devices) {
                            resolve(normalizeDeviceList(devices));
                        },
                        function () {
                            reject(new Error(UNAVAILABLE_MSG));
                        },
                        'printer'
                    );
                } catch (err) {
                    reject(new Error(UNAVAILABLE_MSG));
                }
            });
        });
    }

    function getDefaultDevice() {
        return ensureBrowserPrintSdk().then(function (sdk) {
            if (sdk && typeof sdk.getDefaultDevice === 'function') {
                return new Promise(function (resolve, reject) {
                    try {
                        sdk.getDefaultDevice(
                            'printer',
                            function (device) { resolve(normalizeDevice(device)); },
                            function () {
                                getLocalPrinters().then(function (list) {
                                    resolve(list.length ? list[0] : null);
                                }).catch(reject);
                            }
                        );
                    } catch (err) {
                        reject(new Error(UNAVAILABLE_MSG));
                    }
                });
            }
            return request('GET', '/default?type=printer').then(function (text) {
                if (!text || !String(text).trim()) {
                    return null;
                }
                return normalizeDevice(parseJson(text, null));
            }).catch(function () {
                return getLocalPrinters().then(function (list) {
                    return list.length ? list[0] : null;
                });
            });
        });
    }

    function getLocalPrinters() {
        return getLocalPrintersViaSdk().then(function (sdkList) {
            if (sdkList && sdkList.length) {
                return sdkList;
            }
            return request('GET', '/available').then(function (text) {
                return normalizeDeviceList(parseJson(text, {}));
            });
        }).catch(function () {
            return request('GET', '/available').then(function (text) {
                return normalizeDeviceList(parseJson(text, {}));
            });
        });
    }

    function fetchPcPrinters(apiUrl) {
        return fetchPrinterContext(apiUrl).then(function (ctx) {
            return ctx.serverPrintAvailable ? ctx.printers : [];
        });
    }

    function discoverAllPrinters(options) {
        options = options || {};

        var zebraPromise = ensureBrowserPrintSdk()
            .then(function () {
                return getLocalPrinters();
            })
            .catch(function () {
                return getLocalPrinters().catch(function () {
                    return getDefaultDevice().then(function (device) {
                        return device ? [device] : [];
                    }).catch(function () {
                        return [];
                    });
                });
            });

        var contextPromise = fetchPrinterContext(options.pcPrintersUrl);

        return Promise.all([zebraPromise, contextPromise]).then(function (results) {
            var merged = mergePrinterLists(results[0], results[1].printers);
            merged._context = results[1];
            return merged;
        });
    }

    function sendToDevice(device, data) {
        if (!device) {
            return Promise.reject(new Error('No Zebra printer selected.'));
        }
        if (device.send && typeof device.send === 'function') {
            return new Promise(function (resolve, reject) {
                device.send(
                    data,
                    function () { resolve(true); },
                    function (err) { reject(new Error(err || 'Failed to send label to printer.')); }
                );
            });
        }

        var payload = JSON.stringify({
            device: {
                name: device.name,
                deviceType: device.deviceType || 'printer',
                connection: device.connection || '',
                uid: device.uid || '',
                provider: device.provider,
                manufacturer: device.manufacturer || 'Zebra Technologies',
                version: device.version != null ? device.version : 3
            },
            data: data
        });

        return request('POST', '/write', payload).then(function () {
            return true;
        });
    }

    function resolvePrinter(preferredUid, cachedList) {
        var listPromise = (cachedList && cachedList.length)
            ? Promise.resolve(cachedList.filter(function (p) {
                return p.source !== 'pc' && String(p.uid || '').indexOf('pc:') !== 0;
            }))
            : getLocalPrinters();

        return listPromise.then(function (list) {
            if (!list.length) {
                throw new Error(
                    'No Zebra printer detected on this PC. Install Zebra Browser Print, accept the certificate, and click Load printers.'
                );
            }
            var uid = preferredUid != null ? String(preferredUid) : '';
            if (uid) {
                for (var i = 0; i < list.length; i++) {
                    if (String(list[i].uid || '') === uid) {
                        return list[i];
                    }
                }
            }
            return getDefaultDevice().then(function (device) {
                if (device && device.uid) {
                    for (var j = 0; j < list.length; j++) {
                        if (String(list[j].uid || '') === String(device.uid)) {
                            return list[j];
                        }
                    }
                    return device;
                }
                return list[0];
            });
        });
    }

    global.ZebraBrowserPrintClient = {
        UNAVAILABLE_MSG: UNAVAILABLE_MSG,
        sslSupportUrl: sslSupportUrl,
        getChromeSetupSteps: getChromeSetupSteps,
        ensureBrowserPrintSdk: ensureBrowserPrintSdk,
        resolvePrinter: resolvePrinter,
        sendToDevice: sendToDevice,
        getLocalPrinters: getLocalPrinters,
        getDefaultDevice: getDefaultDevice,
        discoverAllPrinters: discoverAllPrinters,
        fetchPcPrinters: fetchPcPrinters,
        fetchPrinterContext: fetchPrinterContext,
        mergePrinterLists: mergePrinterLists
    };
})(window);
