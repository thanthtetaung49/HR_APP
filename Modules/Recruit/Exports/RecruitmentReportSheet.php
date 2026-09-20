<?php

namespace Modules\Recruit\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RecruitmentReportSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private string $title, private array $rows) {}

    public function array(): array { return $this->rows; }
    public function title(): string { return mb_substr($this->title, 0, 31); }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:Z2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('245782');
        $sheet->getStyle('A1:Z2')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->freezePane('B3');
        return [];
    }
}
