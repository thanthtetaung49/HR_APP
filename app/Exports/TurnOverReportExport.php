<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TurnOverReportExport implements FromView, WithStyles, ShouldAutoSize
{
    public $months;
    public $reportRows;
    public $reportData;
    public $shortFormatYear;
    public $locationName;

    public function __construct(
        $months,
        $reportRows,
        $reportData,
        $shortFormatYear,
        $locationName
    ) {
        $this->months = $months;
        $this->reportRows = $reportRows;
        $this->reportData = $reportData;
        $this->shortFormatYear = $shortFormatYear;
        $this->locationName = $locationName;
    }

    public function view(): View
    {
        return view('turn-over-reports.export.table', [
            'months' => $this->months,
            'reportRows' => $this->reportRows,
            'reportData' => $this->reportData,
            'shortFormatYear' => $this->shortFormatYear,
            'locationName' => $this->locationName,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $tableRange = "A1:{$highestColumn}{$highestRow}";

        $sheet->getStyle('A1')
            ->getFont()
            ->setSize(16)
            ->setBold(true);

        $sheet->getStyle($tableRange)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle($tableRange)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("A1:{$highestColumn}3")
            ->getFont()
            ->setBold(true);

        return [];
    }
}
