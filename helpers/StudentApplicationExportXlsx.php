<?php
/**
 * Styled XLSX export for online student applications (staff).
 */

class StudentApplicationExportXlsx {
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $cols
     * @param array<string, string> $labels
     */
    public static function buildSpreadsheet(array $rows, array $cols, array $labels, string $title, string $filterSummary): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $title), 0, 31) ?: 'Applications';
        $sheet->setTitle($sheetTitle);

        $sheet->setCellValue('A1', 'SLGTI — Online Student Applications');
        $sheet->setCellValue('A2', $filterSummary);
        $sheet->setCellValue('A3', 'Exported: ' . date('Y-m-d H:i'));

        $headerRow = 5;
        $colCount = count($cols);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $sheet->mergeCells('A1:' . $lastColLetter . '1');
        $sheet->mergeCells('A2:' . $lastColLetter . '2');
        $sheet->mergeCells('A3:' . $lastColLetter . '3');

        $c = 1;
        foreach ($cols as $colName) {
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $headerRow;
            $label = $labels[$colName] ?? ucwords(str_replace('_', ' ', $colName));
            $sheet->setCellValue($coord, $label);
            $c++;
        }

        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $c = 1;
            foreach ($cols as $colName) {
                $val = isset($row[$colName]) ? (string) $row[$colName] : '';
                $val = str_replace(["\r\n", "\r", "\n"], ' | ', $val);
                if ($colName === 'student_full_name' && $val !== '') {
                    require_once BASE_PATH . '/helpers/FormatHelper.php';
                    $val = FormatHelper::personName($val);
                }
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $sheet->setCellValueExplicit(
                    $coord,
                    $val,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $c++;
            }
            $r++;
        }

        $lastDataRow = max($headerRow, $r - 1);
        $dim = 'A' . $headerRow . ':' . $lastColLetter . $lastDataRow;

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('444444');
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        $headerStyle = $sheet->getStyle($headerRow . ':' . $headerRow);
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1F4E79');
        $headerStyle->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        if ($rows !== []) {
            $sheet->setAutoFilter($dim);
            $sheet->freezePane('A' . ($headerRow + 1));

            $dataStyle = $sheet->getStyle('A' . ($headerRow + 1) . ':' . $lastColLetter . $lastDataRow);
            $dataStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)->setWrapText(false);
            $dataStyle->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD0D0D0'));

            for ($ri = $headerRow + 1; $ri <= $lastDataRow; $ri++) {
                if (($ri - $headerRow) % 2 === 0) {
                    $sheet->getStyle('A' . $ri . ':' . $lastColLetter . $ri)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F5F8FC');
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        foreach (range(1, $colCount) as $colIdx) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $width = $sheet->getColumnDimension($letter)->getWidth();
            if ($width > 42) {
                $sheet->getColumnDimension($letter)->setWidth(42);
            }
        }

        return $spreadsheet;
    }
}
