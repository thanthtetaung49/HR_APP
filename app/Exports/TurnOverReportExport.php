<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TurnOverReportExport implements FromView, WithStyles, ShouldAutoSize
{

    public $months;
    public $turnOverReports;
    public $employeeTotal;
    public $shortFormatYear;
    public $locationName;

    public function __construct($months = null, $turnOverReports = null, $employeeTotal = null, $shortFormatYear = null, $locationName = null) {
       $this->months = $months;
       $this->turnOverReports = $turnOverReports;
       $this->employeeTotal = $employeeTotal;
       $this->shortFormatYear = $shortFormatYear;
       $this->locationName = $locationName;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('turn-over-reports.export.table', [
            'months' => $this->months,
            'turnOverReports' => $this->turnOverReports,
            'employeeTotal' => $this->employeeTotal,
            'shortFormatYear' => $this->shortFormatYear,
            'locationName' => $this->locationName,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1')
            ->getFont()
            ->setSize(16)
            ->setBold(true);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
              ->getBorders()
              ->getAllBorders()
              ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle("A2")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("B2:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        return[];
    }
}
