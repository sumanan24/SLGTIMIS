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
    var pendingJob = null;

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

    function fontForRoll(code) {
        var len = String(code || '').length;
        if (len > 24) {
            return { h: 42, w: 28 };
        }
        if (len > 20) {
            return { h: 48, w: 32 };
        }
        return { h: 54, w: 36 };
    }

    function cellTextZpl(x, rollNumber) {
        var code = escapeZpl(rollNumber);
        if (!code) {
            return '';
        }
        var font = fontForRoll(code);
        // Center text vertically in the landscape sticker.
        var top = Math.max(16, Math.floor((LABEL_H - font.h) / 2) - 4);
        return '^FO' + x + ',' + top
            + '^A0N,' + font.h + ',' + font.w
            + '^FB' + LABEL_W + ',2,2,C^FD' + code + '^FS';
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
            + '.roll-sticker-card .roll{font-size:15px;font-weight:800;font-family:Consolas,Monaco,monospace;line-height:1.15;word-break:break-word;text-align:center;letter-spacing:0.01em;}'
            + '.roll-sticker-preview-meta{font-size:.875rem;color:#495057;}'
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
            + '        <p class="roll-sticker-preview-meta mb-3" id="rollStickerPreviewMeta"></p>'
            + '        <div class="roll-sticker-preview-grid" id="rollStickerPreviewGrid"></div>'
            + '      </div>'
            + '      <div class="modal-footer py-2">'
            + '        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>'
            + '        <button type="button" class="btn btn-dark btn-sm" id="btn-confirm-print-roll-stickers"><i class="fas fa-print me-1"></i> Print to Zebra</button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';
        document.body.appendChild(wrap.firstElementChild);
        return document.getElementById('rollStickerPreviewModal');
    }

    function stickerCardHtml(roll, idx) {
        if (!roll) {
            return '<div class="roll-sticker-card is-empty" title="Empty"><div class="roll text-muted">—</div></div>';
        }
        return ''
            + '<div class="roll-sticker-card" title="Label ' + (idx + 1) + '">'
            + '  <div class="roll">' + escapeHtml(roll) + '</div>'
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
                var printAllBtn = job.root.querySelector('#btn-print-all-roll-numbers');
                hidePreviewModal();
                printRollNumbers(job.rolls, job.copies, [printAllBtn, confirmBtn]);
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

    function printRollNumbers(rollNumbers, copies, buttons) {
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
        client.resolvePrinter()
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
        openPreview: openPreview,
        init: init
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
