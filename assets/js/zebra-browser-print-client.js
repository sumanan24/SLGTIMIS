/**
 * Thin client for the Zebra Browser Print local service (ports 9100 / 9101).
 * Compatible with the Browser Print SDK device discovery + write flow.
 */
(function (global) {
    'use strict';

    var DEFAULT_TIMEOUT_MS = 8000;
    var UNAVAILABLE_MSG =
        'Zebra Browser Print is not available. Install and start Zebra Browser Print, connect the ZD230 printer (USB), then allow this site in Browser Print.';

    function serviceBaseUrl() {
        return (global.location && global.location.protocol === 'https:')
            ? 'https://127.0.0.1:9101'
            : 'http://127.0.0.1:9100';
    }

    function request(method, path, body, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var timer = setTimeout(function () {
                xhr.abort();
                reject(new Error(UNAVAILABLE_MSG));
            }, timeoutMs || DEFAULT_TIMEOUT_MS);

            xhr.open(method, serviceBaseUrl() + path, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                clearTimeout(timer);
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.responseText || '');
                    return;
                }
                if (xhr.status === 0) {
                    reject(new Error(UNAVAILABLE_MSG));
                    return;
                }
                reject(new Error('Zebra Browser Print error (HTTP ' + xhr.status + ').'));
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
                reject(new Error(UNAVAILABLE_MSG));
            }
        });
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

    function getDefaultDevice() {
        if (typeof global.BrowserPrint === 'object' && typeof global.BrowserPrint.getDefaultDevice === 'function') {
            return new Promise(function (resolve, reject) {
                try {
                    global.BrowserPrint.getDefaultDevice(
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

    function fetchPcPrinters(apiUrl) {
        if (!apiUrl) {
            return Promise.resolve([]);
        }
        return fetch(apiUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Could not load PC printers (HTTP ' + res.status + ').');
                }
                return res.json();
            })
            .then(function (data) {
                var rows = (data && data.printers) ? data.printers : [];
                return rows.map(normalizePcPrinter).filter(Boolean);
            });
    }

    function discoverAllPrinters(options) {
        options = options || {};
        var zebraPromise = getLocalPrinters().catch(function () {
            return getDefaultDevice().then(function (device) {
                return device ? [device] : [];
            }).catch(function () {
                return [];
            });
        });

        var pcPromise = fetchPcPrinters(options.pcPrintersUrl).catch(function () {
            return [];
        });

        return Promise.all([zebraPromise, pcPromise]).then(function (results) {
            return mergePrinterLists(results[0], results[1]);
        });
    }

    function getLocalPrinters() {
        if (typeof global.BrowserPrint === 'object' && typeof global.BrowserPrint.getLocalDevices === 'function') {
            return new Promise(function (resolve, reject) {
                try {
                    global.BrowserPrint.getLocalDevices(
                        function (devices) {
                            resolve(normalizeDeviceList(devices));
                        },
                        function () { reject(new Error(UNAVAILABLE_MSG)); },
                        'printer'
                    );
                } catch (err) {
                    reject(new Error(UNAVAILABLE_MSG));
                }
            });
        }

        return request('GET', '/available').then(function (text) {
            return normalizeDeviceList(parseJson(text, {}));
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

    function resolvePrinter(preferredUid) {
        return getLocalPrinters().then(function (list) {
            if (!list.length) {
                throw new Error(
                    'No Zebra printer detected. Connect the ZD230 via USB, open Zebra Browser Print, and allow this site.'
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
        resolvePrinter: resolvePrinter,
        sendToDevice: sendToDevice,
        getLocalPrinters: getLocalPrinters,
        getDefaultDevice: getDefaultDevice,
        discoverAllPrinters: discoverAllPrinters,
        fetchPcPrinters: fetchPcPrinters,
        mergePrinterLists: mergePrinterLists
    };
})(window);
