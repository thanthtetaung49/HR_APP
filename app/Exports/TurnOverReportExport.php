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
    public $probation;
    public $resigned;
    public $permanent;
    public $employeeTotal;
    public $shortFormatYear;

    public function __construct($months = null, $probation = null, $resigned = null, $permanent= null, $employeeTotal = null, $shortFormatYear = null) {
       $this->months = $months;
       $this->probation = $probation;
       $this->resigned = $resigned;
       $this->permanent = $permanent;
       $this->employeeTotal = $employeeTotal;
       $this->shortFormatYear = $shortFormatYear;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {

        return view('turn-over-reports.export.table', [
            'months' => $this->months,
            'probation' => $this->probation,
            'resigned' => $this->resigned,
            'permanent' => $this->permanent,
            'employeeTotal' => $this->employeeTotal,
            'shortFormatYear' => $this->shortFormatYear
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
              ->getBorders()
              ->getAllBorders()
              ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle("A1")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("B1:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        return[];
    }
}
