<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LateReportExport implements FromCollection, ShouldAutoSize, WithStyles, WithHeadings, WithMapping
{
    public $location;
    public $month;
    public $year;

    public function __construct($location, $month, $year)
    {
        $this->location = $location;
        $this->month = $month;
        $this->year = $year;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $month = $this->month;
        $year = $this->year;
        $day = 25;
        $location = $this->location;

        $toDate = Carbon::create($year, $month, $day)->format('Y-m-d');
        $fromDate = Carbon::create($year, $month, 26)->subMonth()->format('Y-m-d');

        $leavesSubQuery = DB::table('leaves')
            ->select('user_id', DB::raw('DATE(leave_date) as leave_date'))
            ->where('status', 'approved')
            ->whereBetween(DB::raw('DATE(leave_date)'), [$fromDate, $toDate])
            ->distinct();

        $attendanceSubQuery = Attendance::select([
            'attendances.user_id',
            'u.name',
            DB::raw('DATE(attendances.clock_in_time) as att_date'),
            'attendances.late',
            'attendances.late_between',
            'attendances.break_time_late',
            'attendances.breaktime_late_between',
            'attendances.half_day',
            'attendances.half_day_late',
            'l.leave_date',
            'locations.id as location_id',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY attendances.user_id, DATE(attendances.clock_in_time) ORDER BY attendances.clock_in_time ASC) as row_num')
        ])
            ->join('users as u', 'u.id', '=', 'attendances.user_id')
            ->leftJoinSub($leavesSubQuery, 'l', function ($join) {
                $join->on('attendances.user_id', '=', 'l.user_id')
                    ->on(DB::raw('DATE(attendances.clock_in_time)'), '=', 'l.leave_date');
            })
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'u.id')
            ->leftJoin('teams', 'employee_details.department_id', '=', 'teams.id')
            ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
            ->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$fromDate, $toDate]);

        // dd($attendanceSubQuery->get()->toArray());

        $reportData = DB::table($attendanceSubQuery, 'a2')
            ->select([
                'a2.user_id',
                'a2.name',
                'a2.att_date',
                'a2.location_id',
                DB::raw("SUM(CASE WHEN a2.late = 'yes' AND a2.row_num = 1 THEN 1 ELSE 0 END) AS normal_late"),
                DB::raw("SUM(CASE WHEN a2.late_between = 'yes' AND a2.row_num = 1 THEN 1 ELSE 0 END) AS late_between"),
                DB::raw("SUM(CASE WHEN a2.break_time_late = 'yes' AND a2.row_num = 2 THEN 1 ELSE 0 END) AS breaktime_late"),
                DB::raw("SUM(CASE WHEN a2.breaktime_late_between = 'yes' AND a2.row_num = 2 THEN 1 ELSE 0 END) AS breaktime_late_between"),
                DB::raw("SUM(CASE WHEN a2.leave_date IS NOT NULL AND a2.half_day = 'yes' AND a2.half_day_late = 'yes' THEN 1 ELSE 0 END) AS halfday_late"),
            ])
            ->groupBy('a2.user_id', 'a2.name', 'a2.att_date', 'a2.location_id')
            ->orderBy('a2.att_date')
            ->orderBy('a2.user_id');

        // dd($reportData->get()->toArray(), $location, $month, $year);

        if (isset($location) && $location != '' && $location != 'all') {
            $reportData->where('a2.location_id', $location);
        }

        // dd($reportData->get()->toArray(), $location, $month, $year);

        return $reportData->get();
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
            "#",
            __('app.menu.employees'),
            __('app.menu.attendanceDate'),
            __('app.menu.normalLate'),
            __('app.menu.lateBetween'),
            __('app.menu.breaktimeLate'),
            __('app.menu.breaktimeLateBetween'),
            __('app.menu.halfDayLate'),
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        return [
            ++$index,
            $row->name,
            $row->att_date,
            $row->normal_late,
            $row->late_between,
            $row->breaktime_late,
            $row->breaktime_late_between,
            $row->halfday_late,
        ];
    }
}
