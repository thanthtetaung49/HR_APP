<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShiftRosterHistory implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $startDate;
    public $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $user = User::select('users.name', 'employee_shift_roster_change_histories.date',
            DB::raw(
"
IFNULL(
        GROUP_CONCAT(employee_shifts.shift_name ORDER BY employee_shift_roster_change_histories.created_at ASC SEPARATOR ' -> '),
        (SELECT ess.shift_name
         FROM attendance_settings t
         LEFT JOIN employee_shifts ess ON t.default_employee_shift = ess.id
         LIMIT 1)
    ) AS shift_history
"
            )
        )
        ->leftJoin('employee_shift_roster_change_histories', 'users.id', 'employee_shift_roster_change_histories.user_id')
        ->leftJoin('employee_shifts', 'employee_shift_roster_change_histories.employee_shift_id' ,'employee_shifts.id' )
        ->where('employee_shift_roster_change_histories.date', '>=', $this->startDate)
        ->where('employee_shift_roster_change_histories.date', '<=', $this->endDate)
        ->groupBy('users.name', 'employee_shift_roster_change_histories.date')
        ->get();

        return $user;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ["font" => ["bold" => true]]
        ];
    }

    public function headings(): array
    {
        return [
            "Name",
            "Date",
            "Shift History"
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->date,
            $row->shift_history
        ];
    }
}
