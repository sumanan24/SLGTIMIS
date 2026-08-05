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

    function getLocalPrinters() {
        if (typeof global.BrowserPrint === 'object' && typeof global.BrowserPrint.getLocalDevices === 'function') {
            return new Promise(function (resolve, reject) {
                try {
                    global.BrowserPrint.getLocalDevices(
                        function (devices) {
                            var list = Array.isArray(devices) ? devices : (devices && devices.printer) || [];
                            resolve(list.map(normalizeDevice).filter(Boolean));
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
            var data = parseJson(text, {});
            var list = Array.isArray(data) ? data : (data.printer || data.printers || []);
            return list.map(normalizeDevice).filter(Boolean);
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
        getDefaultDevice: getDefaultDevice
    };
})(window);
