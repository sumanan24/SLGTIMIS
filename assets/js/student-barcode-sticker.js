/**
 * Bulk roll-number sticker printing for Zebra ZD230 (203 DPI).
 * Dual-column (2-up) text-only labels aligned to parallel sticker stock.
 * Preview first, then print continuously from page data.
 */
(function (global, document) {
    'use strict';

    // Print-ready 2-up strip: 4.0 in × 1.0 in, two 2.0 in labels filling the full width.
    var DPI = 203;
    var STRIP_W_IN = 4.0;
    var STRIP_H_IN = 1.0;
    var SIDE_MARGIN_IN = 0;
    var LABEL_W_IN = 2.0;
    var LABEL_H_IN = 1.0;
    var GAP_IN = 0;
    var MARGIN_IN = 0.18;
    var FONT_PT = 48;
    var LABEL_W = Math.round(LABEL_W_IN * DPI);
    var LABEL_H = Math.round(LABEL_H_IN * DPI);
    var GAP = Math.round(GAP_IN * DPI);
    var SIDE_MARGIN = Math.round(SIDE_MARGIN_IN * DPI);
    var PRINT_WIDTH = Math.round(STRIP_W_IN * DPI);
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

    function cellTextZpl(x, sticker) {
        var lines = stickerLines(sticker).map(escapeZpl).filter(Boolean);
        if (!lines.length) {
            return '';
        }
        var fonts = fontsForStickerLines(sticker);
        var lineGap = 5;
        var blockH = 0;
        fonts.forEach(function (font, idx) {
            blockH += font.h;
            if (idx > 0) {
                blockH += lineGap;
            }
        });
        var top = Math.max(6, Math.floor((LABEL_H - blockH) / 2));
        var zpl = [];
        var y = top;
        lines.forEach(function (line, idx) {
            var font = fonts[idx] || fonts[0];
            zpl.push(
                '^FO' + x + ',' + y
                + '^A0N,' + font.h + ',' + font.w
                + '^FB' + LABEL_W + ',1,0,C^FD' + line + '^FS'
            );
            y += font.h + lineGap;
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
        var dataRoll = (row.getAttribute('data-roll-number') || '').trim();
        if (dataRoll) {
            return dataRoll;
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

    function readStudentIdFromRow(row) {
        if (!row) {
            return '';
        }
        var dataId = (row.getAttribute('data-student-id') || '').trim();
        if (dataId) {
            return dataId;
        }
        var idEl = row.querySelector('.exam-roll-id');
        return idEl ? String(idEl.textContent || '').trim() : '';
    }

    function readStickerFromRow(row) {
        return {
            roll: readRollFromRow(row),
            studentId: readStudentIdFromRow(row)
        };
    }

    function normalizeStickers(list) {
        return (list || []).map(function (item) {
            if (typeof item === 'string') {
                return { roll: String(item).trim(), studentId: '' };
            }
            return {
                roll: String((item && item.roll) || '').trim(),
                studentId: String((item && item.studentId) || '').trim()
            };
        }).filter(function (s) {
            return !!(s.roll || s.studentId);
        });
    }

    function stickerNumber(sticker) {
        if (typeof sticker === 'string') {
            return String(sticker || '').trim();
        }
        var sid = String((sticker && sticker.studentId) || '').trim();
        if (sid) {
            return sid;
        }
        return String((sticker && sticker.roll) || '').trim();
    }

    function stickerLines(sticker) {
        var number = stickerNumber(sticker);
        if (!number) {
            return [];
        }
        var sid = (typeof sticker !== 'string') ? String((sticker && sticker.studentId) || '').trim() : '';
        if (sid) {
            return [sid];
        }
        return rollLinesArray(number);
    }

    function fontsForStickerLines(sticker) {
        var lines = stickerLines(sticker);
        return lines.map(function (line) {
            if (lines.length === 1) {
                var marginDots = Math.round(MARGIN_IN * DPI);
                var h = Math.min(LABEL_H - 10, Math.round(FONT_PT / 72 * DPI));
                var w = Math.max(10, Math.floor((LABEL_W - (2 * marginDots)) / Math.max(1, String(line).length)));
                return { h: h, w: w };
            }
            return { h: 36, w: 26 };
        });
    }

    function visibleRows(root) {
        return Array.prototype.slice.call(root.querySelectorAll('tr.admission-filter-row'))
            .filter(function (tr) {
                return !tr.classList.contains('d-none') && !tr.classList.contains('admission-text-hidden');
            });
    }

    function studentRows(root) {
        if (root && root.classList.contains('exam-roll-stickers-wrap')) {
            return Array.prototype.slice.call(root.querySelectorAll('tr.exam-student-row'));
        }
        return visibleRows(root);
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
            + '.roll-sticker-preview-grid{display:flex;flex-direction:column;gap:12px;align-items:center;}'
            + '.roll-sticker-pair{display:flex;flex-direction:row;align-items:stretch;justify-content:flex-start;gap:0;width:4.0in;box-sizing:border-box;padding:0;background:transparent;border:none;}'
            + '.roll-sticker-card{width:2.00in;height:1.00in;box-sizing:border-box;border:1px solid #a0a0a0;border-radius:0.06in;background:#fff;padding:0.05in 0.18in;display:flex;align-items:center;justify-content:center;overflow:hidden;}'
            + '.roll-sticker-card.is-empty{border-style:dashed;background:#f8f9fa;opacity:.55;}'
            + '.roll-sticker-card .roll{width:100%;font-size:48px;font-weight:700;font-family:Helvetica,Arial,sans-serif;line-height:1;text-align:center;letter-spacing:-0.05em;color:#000;}'
            + '.roll-sticker-card .roll span{display:block;width:100%;white-space:nowrap;overflow:hidden;}'
            + '.roll-sticker-card .roll span.roll-line1{font-size:48px;font-weight:700;}'
            + '.roll-sticker-card .roll span.roll-line2{font-size:22pt;font-weight:700;margin-top:2px;}'
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
                var root = document.querySelector('.exam-roll-stickers-wrap')
                    || document.querySelector('.admission-entries-page-wrap');
                if (root) {
                    refreshPrinterList(root, document.getElementById('roll-sticker-printer-status'));
                }
            });
        }

        var modalPdfBtn = document.getElementById('btn-download-stickers-pdf-modal');
        if (modalPdfBtn) {
            modalPdfBtn.addEventListener('click', function () {
                if (!pendingJob || !pendingJob.stickers.length) {
                    showError('No stickers to download.');
                    return;
                }
                downloadStickersPdf(pendingJob.stickers, pendingJob.copies, [modalPdfBtn], pendingJob.root);
            });
        }

        return document.getElementById('rollStickerPreviewModal');
    }

    function stickerCardHtml(sticker, idx) {
        var number = stickerNumber(sticker);
        if (!number) {
            return '<div class="roll-sticker-card is-empty" title="Empty"><div class="roll text-muted">—</div></div>';
        }
        var lines = stickerLines(sticker);
        var body = '';
        lines.forEach(function (line, lineIdx) {
            body += '<span class="roll-line' + (lineIdx + 1) + '">' + escapeHtml(line) + '</span>';
        });
        return ''
            + '<div class="roll-sticker-card" title="Label ' + (idx + 1) + '">'
            + '  <div class="roll">' + body + '</div>'
            + '</div>';
    }

    function openPreview(root, stickers, copies) {
        stickers = normalizeStickers(stickers);
        var modalEl = ensurePreviewModal();
        var meta = document.getElementById('rollStickerPreviewMeta');
        var grid = document.getElementById('rollStickerPreviewGrid');
        var confirmBtn = document.getElementById('btn-confirm-print-roll-stickers');
        var previewStickers = stickers.slice(0, PREVIEW_LIMIT);
        if (previewStickers.length % 2 === 1) {
            if (stickers.length > PREVIEW_LIMIT) {
                previewStickers = stickers.slice(0, PREVIEW_LIMIT - 1);
            }
        }
        var remaining = Math.max(0, stickers.length - previewStickers.length);
        var rows = pairCount(stickers.length);
        var hasReg = stickers.some(function (s) { return !!s.studentId; });

        pendingJob = {
            root: root,
            stickers: stickers,
            copies: copies
        };

        fillPrinterSelect(
            document.getElementById('roll-sticker-printer-select'),
            cachedPrinters,
            selectedPrinterUid(root)
        );
        refreshPrinterList(root, document.getElementById('roll-sticker-printer-status'));

        if (meta) {
            meta.innerHTML = '<strong>' + stickers.length + '</strong> sticker(s)'
                + ' · <strong>' + rows + '</strong> print row(s) (2 parallel)'
                + ' · <strong>' + copies + '</strong> cop' + (copies === 1 ? 'y' : 'ies') + ' each'
                + ' · total print rows: <strong>' + (rows * copies) + '</strong>'
                + ' · each strip <strong>4″ × 1″</strong> (two 2″ labels, full width) · '
                + (hasReg ? 'student registration no.' : 'roll number only')
                + (remaining > 0 ? '<br><span class="text-muted">Showing first ' + previewStickers.length + ' labels; all ' + stickers.length + ' will print.</span>' : '');
        }

        if (grid) {
            var html = '';
            for (var i = 0; i < previewStickers.length; i += 2) {
                html += '<div class="roll-sticker-pair">'
                    + stickerCardHtml(previewStickers[i], i)
                    + stickerCardHtml(previewStickers[i + 1] || null, i + 1)
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
                printRollNumbers(job.stickers, job.copies, printerUid, [printAllBtn, confirmBtn]);
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

    function drawCenteredSticker(doc, sticker, boxX, boxY, boxW, boxH) {
        var lines = stickerLines(sticker);
        if (!lines.length) {
            return;
        }
        var x = boxX + (boxW / 2);
        var usable = boxW - (2 * MARGIN_IN);
        var single = lines.length === 1;
        var fontSize = single ? FONT_PT : 18;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(fontSize);
        var fontIn = fontSize / 72;
        var y = boxY + (boxH / 2) + (fontIn * 0.35);
        lines.forEach(function (line, idx) {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(fontSize);
            var tw = (typeof doc.getTextWidth === 'function') ? doc.getTextWidth(line) : 0;
            var opts = { align: 'center', baseline: 'alphabetic', lineHeightFactor: 1 };
            if (single && tw > usable && line.length > 1) {
                opts.charSpace = (usable - tw) / (line.length - 1);
            }
            var lineY = y + (idx * (fontIn * 1.15));
            if (!single) {
                var blockH = fontIn * lines.length + 0.04 * Math.max(0, lines.length - 1);
                lineY = boxY + ((boxH - blockH) / 2) + fontIn * 0.8 + (idx * (fontIn + 0.04));
            }
            doc.text(line, x, lineY, opts);
        });
    }

    function buildStickersPdf(JsPDF, stickers, copies) {
        stickers = normalizeStickers(stickers);
        var count = COPY_OPTIONS.indexOf(copies) >= 0 ? copies : 1;
        var labelW = LABEL_W_IN;
        var labelH = LABEL_H_IN;
        var gap = GAP_IN;
        var side = SIDE_MARGIN_IN;
        var pageW = STRIP_W_IN;
        var pageH = STRIP_H_IN;
        var doc = new JsPDF({
            orientation: 'landscape',
            unit: 'in',
            format: [pageW, pageH],
            compress: true
        });
        var firstPage = true;

        for (var i = 0; i < stickers.length; i += 2) {
            var left = stickers[i];
            var right = stickers[i + 1] || null;
            for (var c = 0; c < count; c++) {
                if (!firstPage) {
                    doc.addPage([pageW, pageH], 'landscape');
                }
                firstPage = false;

                doc.setDrawColor(160);
                doc.setLineWidth(0.008);
                doc.roundedRect(side + 0.012, 0.012, labelW - 0.024, labelH - 0.024, 0.06, 0.06, 'S');
                drawCenteredSticker(doc, left, side, 0, labelW, labelH);

                if (right) {
                    var rx = side + labelW + gap;
                    doc.roundedRect(rx + 0.012, 0.012, labelW - 0.024, labelH - 0.024, 0.06, 0.06, 'S');
                    drawCenteredSticker(doc, right, rx, 0, labelW, labelH);
                }
            }
        }

        return doc;
    }

    function serverStickersPdfUrl(root, copies) {
        if (!root) {
            return '';
        }
        var url = (root.getAttribute('data-stickers-pdf-url') || '').trim();
        if (!url) {
            return '';
        }
        var sep = url.indexOf('?') >= 0 ? '&' : '?';
        return url + sep + 'copies=' + encodeURIComponent(copies);
    }

    function downloadStickersPdf(stickers, copies, buttons, root) {
        stickers = normalizeStickers(stickers);
        if (!stickers.length) {
            showError('No student stickers found to download.');
            return;
        }

        var serverUrl = serverStickersPdfUrl(root, copies);
        if (serverUrl) {
            window.open(serverUrl, '_blank');
            return;
        }

        setBusy(buttons, true);
        loadJsPdf()
            .then(function (JsPDF) {
                var doc = buildStickersPdf(JsPDF, stickers, copies);
                var stamp = new Date();
                var name = 'roll-stickers-'
                    + stamp.getFullYear()
                    + String(stamp.getMonth() + 1).padStart(2, '0')
                    + String(stamp.getDate()).padStart(2, '0')
                    + '-' + stickers.length + '.pdf';
                doc.save(name);
            })
            .catch(function (err) {
                showError((err && err.message) ? err.message : 'Could not create stickers PDF.');
            })
            .finally(function () {
                setBusy(buttons, false);
            });
    }

    function printRollNumbers(stickers, copies, printerUid, buttons) {
        var client = global.ZebraBrowserPrintClient;
        if (!client) {
            showError('Roll-number print module failed to load.');
            return;
        }

        stickers = normalizeStickers(stickers);
        if (!stickers.length) {
            showError('No student stickers found to print.');
            return;
        }

        setBusy(buttons, true);
        client.resolvePrinter(printerUid)
            .then(function (printer) {
                var zpl = buildPrintJobZpl(stickers, copies);
                return client.sendToDevice(printer, zpl).then(function () {
                    return printer;
                });
            })
            .then(function (printer) {
                var label = (printer && printer.name) ? printer.name : 'Zebra printer';
                window.alert(
                    'Sent ' + stickers.length + ' sticker(s) in '
                    + pairCount(stickers.length) + ' parallel row(s) × ' + copies
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
                var stickers = studentRows(root).map(readStickerFromRow).filter(function (s) {
                    return !!(s.roll || s.studentId);
                });
                if (!stickers.length) {
                    showError('No visible student stickers to download.');
                    return;
                }
                downloadStickersPdf(stickers, copiesFromUi(root), [downloadPdfBtn], root);
            });
        }

        if (!printAllBtn) {
            return;
        }

        printAllBtn.addEventListener('click', function (ev) {
            ev.preventDefault();
            var stickers = studentRows(root).map(readStickerFromRow).filter(function (s) {
                return !!(s.roll || s.studentId);
            });
            if (!stickers.length) {
                showError('No visible student stickers to print.');
                return;
            }
            openPreview(root, stickers, copiesFromUi(root));
        });
    }

    function init() {
        document.querySelectorAll('.admission-entries-page-wrap, .exam-roll-stickers-wrap').forEach(function (root) {
            bind(root);
        });
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
