<?php
declare(strict_types=1);
/** @var string $pdfPayloadCurrent */
/** @var string $pdfPayloadAll */
/** @var bool $showMonthPdfCurrentBtn */
/** @var bool $showMonthPdfAllBtn */
if (!($showMonthPdfCurrentBtn || $showMonthPdfAllBtn)) {
    return;
}
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    if (typeof window.jspdf === 'undefined') return;
    var jsPDF = window.jspdf.jsPDF;

    function drawPortraitMonthPdf(doc, payload) {
        var sections = payload.sections || [];
        var marginX = 12;
        var pageW = doc.internal.pageSize.getWidth();
        var firstPage = true;
        sections.forEach(function (sec) {
            if (!firstPage) {
                doc.addPage();
            }
            firstPage = false;
            var y = 14;
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text('Sri Lanka German Training Institute', pageW / 2, y, { align: 'center' });
            y += 6;
            doc.setFontSize(10);
            doc.text('Staff Attendance Summary', pageW / 2, y, { align: 'center' });
            y += 5;
            doc.setFontSize(9);
            if (payload.monthDisplay) {
                doc.text(payload.monthDisplay, pageW / 2, y, { align: 'center' });
                y += 5;
            }
            doc.setTextColor(0, 0, 0);
            doc.text('Employee: ' + String(sec.employeeLabel || ''), pageW / 2, y, { align: 'center' });
            doc.setTextColor(0, 0, 0);
            y += 8;
            var body = (sec.rows || []).map(function (r) {
                return [
                    String(r.date || ''),
                    String(r.day || ''),
                    String(r.check_in || ''),
                    String(r.check_out || ''),
                    String(r.other || '')
                ];
            });
            doc.autoTable({
                startY: y,
                margin: { left: marginX, right: marginX, bottom: 10 },
                head: [['Date', 'Day', 'IN TIME', 'OUT TIME', 'Other times']],
                body: body,
                styles: {
                    fontSize: 12,
                    cellPadding: 1.1,
                    halign: 'center',
                    valign: 'middle',
                    overflow: 'linebreak',
                    textColor: [0, 0, 0],
                    lineColor: [220, 220, 220],
                    lineWidth: 0.1
                },
                headStyles: {
                    fillColor: [13, 110, 253],
                    textColor: [0, 0, 0],
                    halign: 'center',
                    valign: 'middle',
                    fontStyle: 'bold',
                    fontSize: 12
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 24 },
                    1: { halign: 'center', cellWidth: 28 },
                    2: { halign: 'center', cellWidth: 22 },
                    3: { halign: 'center', cellWidth: 22 },
                    4: { halign: 'center', cellWidth: 'auto' }
                }
            });
        });
    }

    function wire(btnId, payload, filenameFn) {
        var btn = document.getElementById(btnId);
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (!payload.sections || payload.sections.length === 0) return;
            var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            drawPortraitMonthPdf(doc, payload);
            doc.save(filenameFn(payload));
        });
    }

    <?php if (!empty($showMonthPdfCurrentBtn)): ?>
    wire('staffMonthReportPdfBtn', <?php echo $pdfPayloadCurrent; ?>, function (p) {
        return 'staff-attendance-' + (p.reportMonth || 'month') + '.pdf';
    });
    <?php endif; ?>

    <?php if (!empty($showMonthPdfAllBtn)): ?>
    wire('staffMonthReportPdfAllBtn', <?php echo $pdfPayloadAll; ?>, function (p) {
        return 'staff-attendance-all-employees-' + (p.reportMonth || 'month') + '.pdf';
    });
    <?php endif; ?>
})();
</script>
