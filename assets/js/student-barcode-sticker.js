/**
 * Bulk roll-number sticker printing for Zebra ZD230 (203 DPI).
 * Dual-column (2-up) text-only labels aligned to parallel sticker stock.
 * Preview first, then print continuously from page data.
 */
(function (global, document) {
    'use strict';

    // Dual-column landscape stock: 2 stickers side-by-side.
    // Each sticker 50mm wide × 25mm tall; gap ~3mm; side margins ~2mm.
    var DPI = 203;
    var LABEL_W = Math.round(50 * DPI / 25.4);   // ~400 dots
    var LABEL_H = Math.round(25 * DPI / 25.4);   // ~200 dots
    var GAP = Math.round(3 * DPI / 25.4);        // ~24 dots
    var SIDE_MARGIN = Math.round(2 * DPI / 25.4); // ~16 dots
    var PRINT_WIDTH = SIDE_MARGIN + LABEL_W + GAP + LABEL_W + SIDE_MARGIN;
    var LEFT_X = SIDE_MARGIN;
    var RIGHT_X = SIDE_MARGIN + LABEL_W + GAP;

    var COPY_OPTIONS = [1, 2, 5, 10];
    var PREVIEW_LIMIT = 60;
    var STORAGE_KEY = 'slgti_zebra_printer_uid';
    var pendingJob = null;
    var cachedPrinters = [];

    function escapeZpl(value) {
        return String(value || '')
            .replace(/\^/g, ' ')
            .replace(/~/g, ' ')
            .replace(/\r?\n/g, ' ')
            .trim();
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Split roll into 2 bold lines:
     *   line1 = SLGTI/KA/AUT
     *   line2 = balance (04/E/001)
     */
    function splitRollLines(rollNumber) {
        var code = String(rollNumber || '').trim().replace(/^\/+|\/+$/g, '');
        if (!code) {
            return { line1: '', line2: '' };
        }
        var parts = code.split('/').map(function (p) {
            return String(p || '').trim();
        }).filter(Boolean);

        if (parts.length >= 4) {
            return {
                line1: parts.slice(0, 3).join('/'),
                line2: parts.slice(3).join('/')
            };
        }
        if (parts.length === 3) {
            return {
                line1: parts.slice(0, 2).join('/'),
                line2: parts[2]
            };
        }
        return { line1: code, line2: '' };
    }

    function rollLinesArray(rollNumber) {
        var split = splitRollLines(rollNumber);
        var lines = [];
        if (split.line1) {
            lines.push(split.line1);
        }
        if (split.line2) {
            lines.push(split.line2);
        }
        return lines;
    }

    function fontForRollLines(lines) {
        // Bold ~20px on 203 DPI sticker (~40 dots high).
        return { h: 40, w: 28 };
    }

    function cellTextZpl(x, rollNumber) {
        var lines = rollLinesArray(rollNumber).map(escapeZpl).filter(Boolean);
        if (!lines.length) {
            return '';
        }
        var font = fontForRollLines(lines);
        var lineGap = 6;
        var blockH = (font.h * lines.length) + (lineGap * Math.max(0, lines.length - 1));
        var top = Math.max(8, Math.floor((LABEL_H - blockH) / 2));
        var zpl = [];
        lines.forEach(function (line, idx) {
            var y = top + (idx * (font.h + lineGap));
            zpl.push(
                '^FO' + x + ',' + y
                + '^A0N,' + font.h + ',' + font.w
                + '^FB' + LABEL_W + ',1,0,C^FD' + line + '^FS'
            );
        });
        return zpl.join('\n');
    }

    /**
     * One print row = two stickers in parallel (left + right).
     */
    function buildPairLabelZpl(leftRoll, rightRoll) {
        var parts = [
            '^XA',
            '^CI28',
            '^PW' + PRINT_WIDTH,
            '^LL' + LABEL_H,
            '^LH0,0',
            '^LT0',
            '^LS0',
            '^MD30',
            '^PR4'
        ];
        if (leftRoll) {
            parts.push(cellTextZpl(LEFT_X, leftRoll));
        }
        if (rightRoll) {
            parts.push(cellTextZpl(RIGHT_X, rightRoll));
        }
        parts.push('^XZ');
        return parts.join('\n');
    }

    function buildPrintJobZpl(rollNumbers, copies) {
        var count = COPY_OPTIONS.indexOf(copies) >= 0 ? copies : 1;
        var parts = [];
        for (var i = 0; i < rollNumbers.length; i += 2) {
            var left = rollNumbers[i];
            var right = rollNumbers[i + 1] || '';
            var row = buildPairLabelZpl(left, right);
            for (var c = 0; c < count; c++) {
                parts.push(row);
            }
        }
        return parts.join('\n');
    }

    function pairCount(rollCount) {
        return Math.ceil(rollCount / 2);
    }

    function readRollFromRow(row) {
        if (!row) {
            return '';
        }
        var input = row.querySelector('.roll-index-input');
        if (input && String(input.value || '').trim()) {
            return String(input.value).trim();
        }
        var readout = row.querySelector('.roll-index-readout');
        if (readout && String(readout.textContent || '').trim()) {
            return String(readout.textContent).trim();
        }
        return (row.getAttribute('data-enrollment') || '').trim();
    }

    function visibleRows(root) {
        return Array.prototype.slice.call(root.querySelectorAll('tr.admission-filter-row'))
            .filter(function (tr) {
                return !tr.classList.contains('d-none') && !tr.classList.contains('admission-text-hidden');
            });
    }

    function showError(message) {
        window.alert(message);
    }

    function setBusy(buttons, busy) {
        buttons.forEach(function (btn) {
            if (!btn) {
                return;
            }
            btn.disabled = !!busy;
        });
    }

    function copiesFromUi(root) {
        var sel = root.querySelector('#barcode-copies');
        var n = sel ? parseInt(sel.value, 10) : 1;
        return COPY_OPTIONS.indexOf(n) >= 0 ? n : 1;
    }

    function selectedPrinterUid(root) {
        var modalSel = document.getElementById('roll-sticker-printer-select');
        if (modalSel && modalSel.value) {
            return String(modalSel.value);
        }
        var sel = root ? root.querySelector('#zebra-printer-select') : document.getElementById('zebra-printer-select');
        return sel ? String(sel.value || '') : '';
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
        var name = printer.name || 'Zebra Printer';
        var conn = printer.connection ? (' · ' + printer.connection) : '';
        return name + conn;
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
            selectEl.selectedIndex = 0;
        }
        rememberPrinterUid(selectEl.value);
    }

    function refreshPrinterList(root, statusEl) {
        var client = global.ZebraBrowserPrintClient;
        var selectEl = root.querySelector('#zebra-printer-select');
        var refreshBtn = root.querySelector('#btn-refresh-zebra-printers');
        if (!client) {
            if (selectEl) {
                selectEl.innerHTML = '<option value="">Print module missing</option>';
            }
            return Promise.resolve([]);
        }
        if (selectEl) {
            selectEl.innerHTML = '<option value="">Detecting printers…</option>';
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        if (statusEl) {
            statusEl.textContent = 'Detecting printers…';
        }
        return client.getLocalPrinters()
            .then(function (list) {
                cachedPrinters = list || [];
                if (!cachedPrinters.length) {
                    return client.getDefaultDevice().then(function (device) {
                        cachedPrinters = device ? [device] : [];
                        return cachedPrinters;
                    }).catch(function () {
                        return [];
                    });
                }
                return cachedPrinters;
            })
            .then(function (list) {
                fillPrinterSelect(selectEl, list, rememberedPrinterUid());
                var modalSel = document.getElementById('roll-sticker-printer-select');
                if (modalSel) {
                    fillPrinterSelect(modalSel, list, selectEl ? selectEl.value : rememberedPrinterUid());
                }
                if (statusEl) {
                    statusEl.textContent = list.length
                        ? (list.length + ' printer(s) found')
                        : 'No printer found — start Zebra Browser Print';
                }
                return list;
            })
            .catch(function (err) {
                cachedPrinters = [];
                if (selectEl) {
                    selectEl.innerHTML = '<option value="">Browser Print unavailable</option>';
                }
                if (statusEl) {
                    statusEl.textContent = (err && err.message) ? err.message : client.UNAVAILABLE_MSG;
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
        var style = document.getElementById('roll-sticker-preview-styles');
        if (!style) {
            style = document.createElement('style');
            style.id = 'roll-sticker-preview-styles';
            document.head.appendChild(style);
        }
        style.textContent = ''
            + '.roll-sticker-preview-grid{display:flex;flex-direction:column;gap:10px;align-items:center;}'
            + '.roll-sticker-pair{display:flex;gap:10px;padding:8px;background:#e9ecef;border:1px dashed #adb5bd;border-radius:4px;}'
            + '.roll-sticker-card{width:200px;height:100px;border:1px solid #212529;border-radius:6px;background:#fff;padding:8px 10px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 2px rgba(0,0,0,.08);}'
            + '.roll-sticker-card.is-empty{border-style:dashed;background:#f8f9fa;opacity:.55;}'
            + '.roll-sticker-card .roll{width:100%;font-size:20px;font-weight:900;font-family:Consolas,Monaco,monospace;line-height:1.2;text-align:center;letter-spacing:0.02em;}'
            + '.roll-sticker-card .roll span{display:block;width:100%;}'
            + '.roll-sticker-card .roll span.roll-line1{font-size:20px;font-weight:900;}'
            + '.roll-sticker-card .roll span.roll-line2{font-size:20px;font-weight:900;margin-top:2px;}'
            + '.roll-sticker-preview-meta{font-size:.875rem;color:#495057;}'
            + '.roll-sticker-printer-row{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;margin-bottom:1rem;padding:.55rem .75rem;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;}'
            + '.roll-sticker-printer-row label{margin:0;font-size:.8125rem;font-weight:600;}'
            + '.roll-sticker-printer-row select{min-width:14rem;max-width:100%;}'
            + '#rollStickerPreviewModal .modal-body{max-height:65vh;overflow:auto;background:#f1f3f5;}';
    }

    function ensurePreviewModal() {
        ensurePreviewStyles();
        var existing = document.getElementById('rollStickerPreviewModal');
        if (existing) {
            return existing;
        }

        var wrap = document.createElement('div');
        wrap.innerHTML = ''
            + '<div class="modal fade" id="rollStickerPreviewModal" tabindex="-1" aria-labelledby="rollStickerPreviewModalLabel" aria-hidden="true">'
            + '  <div class="modal-dialog modal-lg modal-dialog-scrollable">'
            + '    <div class="modal-content">'
            + '      <div class="modal-header py-2">'
            + '        <h5 class="modal-title" id="rollStickerPreviewModalLabel"><i class="fas fa-eye me-2"></i>Roll number sticker preview (2-up)</h5>'
            + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            + '      </div>'
            + '      <div class="modal-body">'
            + '        <div class="roll-sticker-printer-row">'
            + '          <label for="roll-sticker-printer-select">Printer</label>'
            + '          <select id="roll-sticker-printer-select" class="form-select form-select-sm"></select>'
            + '          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh-modal-printers" title="Refresh printers"><i class="fas fa-sync-alt"></i></button>'
            + '          <span class="text-muted small" id="roll-sticker-printer-status"></span>'
            + '        </div>'
            + '        <p class="roll-sticker-preview-meta mb-3" id="rollStickerPreviewMeta"></p>'
            + '        <div class="roll-sticker-preview-grid" id="rollStickerPreviewGrid"></div>'
            + '      </div>'
            + '      <div class="modal-footer py-2">'
            + '        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>'
            + '        <button type="button" class="btn btn-outline-dark btn-sm" id="btn-download-stickers-pdf-modal"><i class="fas fa-file-pdf me-1"></i> Download PDF</button>'
            + '        <button type="button" class="btn btn-dark btn-sm" id="btn-confirm-print-roll-stickers"><i class="fas fa-print me-1"></i> Print to selected printer</button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';
        document.body.appendChild(wrap.firstElementChild);

        var modalSel = document.getElementById('roll-sticker-printer-select');
        var modalRefresh = document.getElementById('btn-refresh-modal-printers');
        if (modalSel) {
            modalSel.addEventListener('change', function () {
                rememberPrinterUid(modalSel.value);
                var pageSel = document.getElementById('zebra-printer-select');
                if (pageSel) {
                    pageSel.value = modalSel.value;
                }
            });
        }
        if (modalRefresh) {
            modalRefresh.addEventListener('click', function () {
                var root = document.querySelector('.admission-entries-page-wrap');
                if (root) {
                    refreshPrinterList(root, document.getElementById('roll-sticker-printer-status'));
                }
            });
        }

        var modalPdfBtn = document.getElementById('btn-download-stickers-pdf-modal');
        if (modalPdfBtn) {
            modalPdfBtn.addEventListener('click', function () {
                if (!pendingJob || !pendingJob.rolls.length) {
                    showError('No roll numbers to download.');
                    return;
                }
                downloadStickersPdf(pendingJob.rolls, pendingJob.copies, [modalPdfBtn]);
            });
        }

        return document.getElementById('rollStickerPreviewModal');
    }

    function stickerCardHtml(roll, idx) {
        if (!roll) {
            return '<div class="roll-sticker-card is-empty" title="Empty"><div class="roll text-muted">—</div></div>';
        }
        var split = splitRollLines(roll);
        var body = '';
        if (split.line1) {
            body += '<span class="roll-line1">' + escapeHtml(split.line1) + '</span>';
        }
        if (split.line2) {
            body += '<span class="roll-line2">' + escapeHtml(split.line2) + '</span>';
        }
        return ''
            + '<div class="roll-sticker-card" title="Label ' + (idx + 1) + '">'
            + '  <div class="roll">' + body + '</div>'
            + '</div>';
    }

    function openPreview(root, rollNumbers, copies) {
        var modalEl = ensurePreviewModal();
        var meta = document.getElementById('rollStickerPreviewMeta');
        var grid = document.getElementById('rollStickerPreviewGrid');
        var confirmBtn = document.getElementById('btn-confirm-print-roll-stickers');
        var previewRolls = rollNumbers.slice(0, PREVIEW_LIMIT);
        if (previewRolls.length % 2 === 1) {
            // Keep pair rows even in preview when truncating.
            if (rollNumbers.length > PREVIEW_LIMIT) {
                previewRolls = rollNumbers.slice(0, PREVIEW_LIMIT - 1);
            }
        }
        var remaining = Math.max(0, rollNumbers.length - previewRolls.length);
        var rows = pairCount(rollNumbers.length);

        pendingJob = {
            root: root,
            rolls: rollNumbers,
            copies: copies
        };

        fillPrinterSelect(
            document.getElementById('roll-sticker-printer-select'),
            cachedPrinters,
            selectedPrinterUid(root)
        );
        refreshPrinterList(root, document.getElementById('roll-sticker-printer-status'));

        if (meta) {
            meta.innerHTML = '<strong>' + rollNumbers.length + '</strong> sticker(s)'
                + ' · <strong>' + rows + '</strong> print row(s) (2 parallel)'
                + ' · <strong>' + copies + '</strong> cop' + (copies === 1 ? 'y' : 'ies') + ' each'
                + ' · total print rows: <strong>' + (rows * copies) + '</strong>'
                + ' · each sticker 50×25 mm landscape · roll number only'
                + (remaining > 0 ? '<br><span class="text-muted">Showing first ' + previewRolls.length + ' labels; all ' + rollNumbers.length + ' will print.</span>' : '');
        }

        if (grid) {
            var html = '';
            for (var i = 0; i < previewRolls.length; i += 2) {
                html += '<div class="roll-sticker-pair">'
                    + stickerCardHtml(previewRolls[i], i)
                    + stickerCardHtml(previewRolls[i + 1] || '', i + 1)
                    + '</div>';
            }
            grid.innerHTML = html;
        }

        if (confirmBtn && confirmBtn.getAttribute('data-bound') !== '1') {
            confirmBtn.setAttribute('data-bound', '1');
            confirmBtn.addEventListener('click', function () {
                if (!pendingJob) {
                    return;
                }
                var job = pendingJob;
                var printerUid = selectedPrinterUid(job.root);
                if (!printerUid) {
                    showError('Select a Zebra printer before printing.');
                    return;
                }
                rememberPrinterUid(printerUid);
                var printAllBtn = job.root.querySelector('#btn-print-all-roll-numbers');
                hidePreviewModal();
                printRollNumbers(job.rolls, job.copies, printerUid, [printAllBtn, confirmBtn]);
            });
        }

        if (global.bootstrap && typeof global.bootstrap.Modal === 'function') {
            global.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
        }
    }

    function hidePreviewModal() {
        var modalEl = document.getElementById('rollStickerPreviewModal');
        if (!modalEl) {
            return;
        }
        if (global.bootstrap && typeof global.bootstrap.Modal === 'function') {
            var instance = global.bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        } else {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
        }
    }

    var jsPdfLoading = null;

    function loadJsPdf() {
        if (typeof global.jspdf !== 'undefined' && global.jspdf.jsPDF) {
            return Promise.resolve(global.jspdf.jsPDF);
        }
        if (typeof global.jsPDF === 'function') {
            return Promise.resolve(global.jsPDF);
        }
        if (jsPdfLoading) {
            return jsPdfLoading;
        }
        jsPdfLoading = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js';
            script.async = true;
            script.onload = function () {
                if (global.jspdf && global.jspdf.jsPDF) {
                    resolve(global.jspdf.jsPDF);
                } else if (typeof global.jsPDF === 'function') {
                    resolve(global.jsPDF);
                } else {
                    jsPdfLoading = null;
                    reject(new Error('jsPDF loaded but API was not found.'));
                }
            };
            script.onerror = function () {
                jsPdfLoading = null;
                reject(new Error('Could not load PDF library. Check your internet connection.'));
            };
            document.head.appendChild(script);
        });
        return jsPdfLoading;
    }

    function drawCenteredRoll(doc, text, boxX, boxY, boxW, boxH) {
        var lines = rollLinesArray(text);
        if (!lines.length) {
            return;
        }
        // Fixed bold font size 20px for both lines.
        var fontSize = 20;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(fontSize);
        var lineH = 7.5;
        var gap = 1.8;
        var blockH = (lineH * lines.length) + (gap * Math.max(0, lines.length - 1));
        var startY = boxY + ((boxH - blockH) / 2) + 5.2;
        var x = boxX + (boxW / 2);
        lines.forEach(function (line, idx) {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(fontSize);
            doc.text(line, x, startY + (idx * (lineH + gap)), { align: 'center' });
        });
    }

    function buildStickersPdf(JsPDF, rollNumbers, copies) {
        var count = COPY_OPTIONS.indexOf(copies) >= 0 ? copies : 1;
        var labelW = 50;
        var labelH = 25;
        var gap = 3;
        var pageW = labelW + gap + labelW;
        var pageH = labelH;
        var doc = new JsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: [pageW, pageH],
            compress: true
        });
        var firstPage = true;

        for (var i = 0; i < rollNumbers.length; i += 2) {
            var left = rollNumbers[i];
            var right = rollNumbers[i + 1] || '';
            for (var c = 0; c < count; c++) {
                if (!firstPage) {
                    doc.addPage([pageW, pageH], 'landscape');
                }
                firstPage = false;

                doc.setDrawColor(180);
                doc.setLineWidth(0.2);
                doc.roundedRect(0.4, 0.4, labelW - 0.8, labelH - 0.8, 1.5, 1.5, 'S');
                drawCenteredRoll(doc, left, 0, 0, labelW, labelH);

                if (right) {
                    var rx = labelW + gap;
                    doc.roundedRect(rx + 0.4, 0.4, labelW - 0.8, labelH - 0.8, 1.5, 1.5, 'S');
                    drawCenteredRoll(doc, right, rx, 0, labelW, labelH);
                }
            }
        }

        return doc;
    }

    function downloadStickersPdf(rollNumbers, copies, buttons) {
        var codes = (rollNumbers || []).map(function (v) {
            return String(v || '').trim();
        }).filter(Boolean);

        if (!codes.length) {
            showError('No student roll numbers found to download.');
            return;
        }

        setBusy(buttons, true);
        loadJsPdf()
            .then(function (JsPDF) {
                var doc = buildStickersPdf(JsPDF, codes, copies);
                var stamp = new Date();
                var name = 'roll-stickers-'
                    + stamp.getFullYear()
                    + String(stamp.getMonth() + 1).padStart(2, '0')
                    + String(stamp.getDate()).padStart(2, '0')
                    + '-' + codes.length + '.pdf';
                doc.save(name);
            })
            .catch(function (err) {
                showError((err && err.message) ? err.message : 'Could not create stickers PDF.');
            })
            .finally(function () {
                setBusy(buttons, false);
            });
    }

    function printRollNumbers(rollNumbers, copies, printerUid, buttons) {
        var client = global.ZebraBrowserPrintClient;
        if (!client) {
            showError('Roll-number print module failed to load.');
            return;
        }

        var codes = (rollNumbers || []).map(function (v) {
            return String(v || '').trim();
        }).filter(Boolean);

        if (!codes.length) {
            showError('No student roll numbers found to print.');
            return;
        }

        setBusy(buttons, true);
        client.resolvePrinter(printerUid)
            .then(function (printer) {
                var zpl = buildPrintJobZpl(codes, copies);
                return client.sendToDevice(printer, zpl).then(function () {
                    return printer;
                });
            })
            .then(function (printer) {
                var label = (printer && printer.name) ? printer.name : 'Zebra printer';
                window.alert(
                    'Sent ' + codes.length + ' roll-number sticker(s) in '
                    + pairCount(codes.length) + ' parallel row(s) × ' + copies
                    + ' to ' + label + '.'
                );
            })
            .catch(function (err) {
                showError((err && err.message) ? err.message : client.UNAVAILABLE_MSG);
            })
            .finally(function () {
                setBusy(buttons, false);
            });
    }

    function bind(root) {
        if (!root || root.getAttribute('data-barcode-bound') === '1') {
            return;
        }
        root.setAttribute('data-barcode-bound', '1');

        var printAllBtn = root.querySelector('#btn-print-all-roll-numbers');
        var downloadPdfBtn = root.querySelector('#btn-download-stickers-pdf');
        var printerSelect = root.querySelector('#zebra-printer-select');
        var refreshBtn = root.querySelector('#btn-refresh-zebra-printers');

        if (printerSelect) {
            printerSelect.addEventListener('change', function () {
                rememberPrinterUid(printerSelect.value);
            });
        }
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshPrinterList(root, null);
            });
        }

        refreshPrinterList(root, null);

        if (downloadPdfBtn) {
            downloadPdfBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                var rolls = visibleRows(root).map(readRollFromRow).filter(Boolean);
                if (!rolls.length) {
                    showError('No visible student roll numbers to download.');
                    return;
                }
                downloadStickersPdf(rolls, copiesFromUi(root), [downloadPdfBtn]);
            });
        }

        if (!printAllBtn) {
            return;
        }

        printAllBtn.addEventListener('click', function (ev) {
            ev.preventDefault();
            var rolls = visibleRows(root).map(readRollFromRow).filter(Boolean);
            if (!rolls.length) {
                showError('No visible student roll numbers to print.');
                return;
            }
            openPreview(root, rolls, copiesFromUi(root));
        });
    }

    function init() {
        var root = document.querySelector('.admission-entries-page-wrap');
        if (root) {
            bind(root);
        }
    }

    global.StudentBarcodeSticker = {
        buildPairLabelZpl: buildPairLabelZpl,
        buildPrintJobZpl: buildPrintJobZpl,
        downloadStickersPdf: downloadStickersPdf,
        openPreview: openPreview,
        init: init
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
