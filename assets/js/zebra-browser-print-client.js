/**
 * Zebra Browser Print client — local service only (ports 9100 / 9101).
 * Browser → Browser Print → USB/network Zebra printer. No server-side USB access.
 */
(function (global) {
    'use strict';

    var DEFAULT_TIMEOUT_MS = 10000;
    var cachedBaseUrl = null;
    var cachedRawDevices = [];
    var lastProbe = null;

    /** @typedef {'ready'|'service_unavailable'|'ssl_setup_required'|'no_printers'|'loading'} ProbeStatus */

    var STATUS = {
        LOADING: 'loading',
        READY: 'ready',
        SERVICE_UNAVAILABLE: 'service_unavailable',
        SSL_SETUP_REQUIRED: 'ssl_setup_required',
        NO_PRINTERS: 'no_printers'
    };

    var ZEBRA_NAME_RE = /zd230|zdesigner|zebra/i;

    function isHttpsPage() {
        return !!(global.location && global.location.protocol === 'https:');
    }

    function serviceBaseCandidates() {
        var bases = isHttpsPage()
            ? [
                'https://127.0.0.1:9101',
                'https://localhost:9101',
                'http://127.0.0.1:9100',
                'http://localhost:9100'
            ]
            : [
                'http://127.0.0.1:9100',
                'http://localhost:9100',
                'https://127.0.0.1:9101',
                'https://localhost:9101'
            ];
        if (cachedBaseUrl) {
            bases = [cachedBaseUrl].concat(bases.filter(function (b) { return b !== cachedBaseUrl; }));
        }
        return bases;
    }

    function sslSupportUrl() {
        return isHttpsPage()
            ? 'https://localhost:9101/ssl_support'
            : 'http://localhost:9100/ssl_support';
    }

    function statusMessage(status) {
        switch (status) {
            case STATUS.READY:
                return 'Printer ready';
            case STATUS.SERVICE_UNAVAILABLE:
                return 'Zebra Browser Print is not running on this computer.';
            case STATUS.SSL_SETUP_REQUIRED:
                return 'SSL certificate requires setup before Chrome can reach Browser Print.';
            case STATUS.NO_PRINTERS:
                return 'Browser Print is running, but no Zebra printer was detected. Check the USB cable and power.';
            default:
                return 'Detecting printers…';
        }
    }

    function getChromeSetupSteps() {
        var host = global.location ? global.location.hostname : 'this site';
        return [
            'Install Zebra Browser Print on this Windows PC (the computer connected to the ZD230).',
            'Connect the ZD230 via USB and turn it on.',
            'Open Browser Print → Settings → enable Broadcast search and Driver search → select your printer.',
            'Open ' + sslSupportUrl() + ', accept the certificate, and click Yes for localhost.',
            'Allow ' + host + ' as an Accepted Host when Browser Print prompts you.',
            'Click Refresh Printers in this modal.'
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
        if (cachedBaseUrl) {
            return requestOnBase(cachedBaseUrl, method, path, body, timeoutMs)
                .then(function (result) { return result.text; })
                .catch(function () {
                    cachedBaseUrl = null;
                    return request(method, path, body, timeoutMs);
                });
        }
        var bases = serviceBaseCandidates();
        var attempt = function (index) {
            if (index >= bases.length) {
                return Promise.reject(new Error('unreachable'));
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
        var device = {
            name: raw.name || raw.deviceName || 'Zebra Printer',
            deviceType: raw.deviceType || 'printer',
            connection: raw.connection || raw.connectionType || '',
            uid: raw.uid || raw.uniqueId || raw.id || '',
            provider: raw.provider || 'com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider',
            manufacturer: raw.manufacturer || 'Zebra Technologies',
            version: raw.version != null ? raw.version : 3,
            source: 'zebra',
            send: typeof raw.send === 'function' ? raw.send.bind(raw) : null,
            _raw: raw
        };
        return device;
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

    function wrapSdkDevices(devices) {
        if (!devices) {
            return [];
        }
        if (typeof global.BrowserPrint === 'object' && global.BrowserPrint.Device) {
            var list = Array.isArray(devices) ? devices : normalizeDeviceList(devices);
            return list.map(function (d) {
                if (d instanceof global.BrowserPrint.Device) {
                    return normalizeDevice(d);
                }
                return normalizeDevice(d);
            }).filter(Boolean);
        }
        return normalizeDeviceList(devices);
    }

    function getLocalPrintersViaSdk() {
        if (typeof global.BrowserPrint !== 'object' || typeof global.BrowserPrint.getLocalDevices !== 'function') {
            return Promise.resolve(null);
        }
        return new Promise(function (resolve) {
            try {
                global.BrowserPrint.getLocalDevices(
                    function (devices) {
                        resolve(wrapSdkDevices(devices));
                    },
                    function () {
                        resolve(null);
                    },
                    'printer'
                );
            } catch (e) {
                resolve(null);
            }
        });
    }

    function getDefaultDeviceViaSdk() {
        if (typeof global.BrowserPrint !== 'object' || typeof global.BrowserPrint.getDefaultDevice !== 'function') {
            return Promise.resolve(null);
        }
        return new Promise(function (resolve) {
            try {
                global.BrowserPrint.getDefaultDevice(
                    'printer',
                    function (device) {
                        resolve(device ? normalizeDevice(device) : null);
                    },
                    function () {
                        resolve(null);
                    }
                );
            } catch (e) {
                resolve(null);
            }
        });
    }

    function fetchAvailableAtBase(base) {
        return requestOnBase(base, 'GET', '/available', null, DEFAULT_TIMEOUT_MS)
            .then(function (result) {
                return {
                    base: result.base,
                    reachable: true,
                    sslBlocked: false,
                    printers: normalizeDeviceList(parseJson(result.text, {}))
                };
            })
            .catch(function (err) {
                return {
                    base: base,
                    reachable: false,
                    sslBlocked: String(err && err.message) === 'blocked',
                    printers: []
                };
            });
    }

    function fetchDefaultAtBase(base) {
        return requestOnBase(base, 'GET', '/default?type=printer', null, DEFAULT_TIMEOUT_MS)
            .then(function (result) {
                if (!result.text || !String(result.text).trim()) {
                    return null;
                }
                return normalizeDevice(parseJson(result.text, null));
            })
            .catch(function () {
                return null;
            });
    }

    /**
     * Score printer names for auto-selecting ZD230 / ZDesigner variants.
     */
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
        return (printers || []).filter(function (p) {
            return printerMatchScore(p.name, 'zd230') > 0
                || ZEBRA_NAME_RE.test(String(p.name || ''));
        });
    }

    /**
     * Probe Browser Print and classify the situation for UI messaging.
     */
    function probeBrowserPrint(options) {
        options = options || {};
        var preferredModel = options.preferredModel || 'Zebra ZD230';

        return Promise.all(serviceBaseCandidates().map(fetchAvailableAtBase))
            .then(function (attempts) {
                var reachable = attempts.filter(function (a) { return a.reachable; });
                var anySslBlocked = isHttpsPage() && attempts.some(function (a) {
                    return a.sslBlocked;
                });

                if (!reachable.length) {
                    var status = anySslBlocked ? STATUS.SSL_SETUP_REQUIRED : STATUS.SERVICE_UNAVAILABLE;
                    lastProbe = {
                        status: status,
                        printers: [],
                        defaultPrinter: null,
                        preferredModel: preferredModel,
                        message: statusMessage(status),
                        serviceReachable: false,
                        sslBlocked: anySslBlocked
                    };
                    return lastProbe;
                }

                var best = reachable.slice().sort(function (a, b) {
                    return b.printers.length - a.printers.length;
                })[0];
                cachedBaseUrl = best.base;

                var finish = function (printers, defaultPrinter) {
                    var zebraOnly = filterZebraPrinters(printers);
                    var list = zebraOnly.length ? zebraOnly : printers;
                    cachedRawDevices = list.slice();

                    var status = list.length ? STATUS.READY : STATUS.NO_PRINTERS;
                    lastProbe = {
                        status: status,
                        printers: list,
                        defaultPrinter: defaultPrinter || pickBestPrinter(list, preferredModel, null),
                        preferredModel: preferredModel,
                        message: list.length
                            ? statusMessage(STATUS.READY)
                            : statusMessage(STATUS.NO_PRINTERS),
                        serviceReachable: true,
                        sslBlocked: false,
                        activeBaseUrl: cachedBaseUrl
                    };
                    return lastProbe;
                };

                if (best.printers.length) {
                    return getDefaultDeviceViaSdk().then(function (def) {
                        return finish(best.printers, def || pickBestPrinter(best.printers, preferredModel, null));
                    });
                }

                return fetchDefaultAtBase(best.base).then(function (def) {
                    var list = def ? [def] : [];
                    return getDefaultDeviceViaSdk().then(function (sdkDef) {
                        if (sdkDef) {
                            list = [sdkDef];
                        }
                        return finish(list, sdkDef || def);
                    });
                });
            });
    }

    function discoverPrinters(options) {
        return probeBrowserPrint(options).then(function (probe) {
            return probe.printers || [];
        });
    }

    function getLocalPrinters() {
        return discoverPrinters();
    }

    function getDefaultDevice() {
        return probeBrowserPrint().then(function (probe) {
            return probe.defaultPrinter || (probe.printers.length ? probe.printers[0] : null);
        });
    }

    function sendToDevice(device, data) {
        if (!device) {
            return Promise.reject(new Error('No Zebra printer selected.'));
        }

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

        return request('POST', '/write', payload).then(function () {
            return true;
        }).catch(function () {
            return Promise.reject(new Error(
                'Unable to print. Zebra Browser Print is running, but the selected printer is unavailable.'
            ));
        });
    }

    function resolvePrinter(preferredUid, cachedList, preferredModel) {
        var list = (cachedList && cachedList.length) ? cachedList : cachedRawDevices;
        if (list && list.length) {
            var picked = pickBestPrinter(list, preferredModel, preferredUid);
            if (picked) {
                return Promise.resolve(picked);
            }
        }
        return probeBrowserPrint({ preferredModel: preferredModel }).then(function (probe) {
            if (!probe.printers.length) {
                if (probe.status === STATUS.SERVICE_UNAVAILABLE) {
                    throw new Error(statusMessage(STATUS.SERVICE_UNAVAILABLE));
                }
                if (probe.status === STATUS.SSL_SETUP_REQUIRED) {
                    throw new Error(statusMessage(STATUS.SSL_SETUP_REQUIRED));
                }
                throw new Error(statusMessage(STATUS.NO_PRINTERS));
            }
            var match = pickBestPrinter(probe.printers, preferredModel, preferredUid);
            if (!match) {
                throw new Error('Select a Zebra printer before printing.');
            }
            return match;
        });
    }

    global.ZebraBrowserPrintClient = {
        STATUS: STATUS,
        statusMessage: statusMessage,
        sslSupportUrl: sslSupportUrl,
        getChromeSetupSteps: getChromeSetupSteps,
        probeBrowserPrint: probeBrowserPrint,
        discoverPrinters: discoverPrinters,
        discoverAllPrinters: discoverPrinters,
        getLocalPrinters: getLocalPrinters,
        getDefaultDevice: getDefaultDevice,
        resolvePrinter: resolvePrinter,
        sendToDevice: sendToDevice,
        printerMatchScore: printerMatchScore,
        pickBestPrinter: pickBestPrinter,
        getLastProbe: function () { return lastProbe; }
    };
})(window);
