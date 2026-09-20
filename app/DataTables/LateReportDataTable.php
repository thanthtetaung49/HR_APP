<?php

namespace App\DataTables;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LateReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param mixed $query Results from query() method.
     */
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(): QueryBuilder
    {
        $month = request()->month ?? date('m');
        $year = request()->year ?? date('Y');
        $day = 25;

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

        // dd($reportData->get()->toArray());

        if (isset(request()->locationId) && request()->locationId != '') {
            $reportData->where('a2.location_id', request()->locationId);
        }

        if (isset(request()->searchText) && request()->searchText != '') {
            $reportData->where('a2.name', 'like', '%' . request()->searchText . '%');
        }

        return $reportData;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('latereport-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('create'),
                Button::make('export'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => true, 'title' => '#'],
            __('app.menu.employees') => ['data' => 'name', 'name' => 'a2.name', 'title' => __('app.menu.employees')],
            __('app.menu.attendanceDate') => ['data' => 'att_date', 'name' => 'a2.att_date', 'title' => __('app.menu.attendanceDate')],
            __('app.menu.normalLate') => ['data' => 'normal_late', 'name' => 'normal_late', 'title' => __('app.menu.normalLate')],
            __('app.menu.lateBetween') => ['data' => 'late_between', 'name' => 'late_between', 'title' => __('app.menu.lateBetween')],
            __('app.menu.breaktimeLate') => ['data' => 'breaktime_late', 'name' => 'breaktime_late', 'title' => __('app.menu.breaktimeLate')],
            __('app.menu.breaktimeLateBetween') => ['data' => 'breaktime_late_between', 'name' => 'breaktime_late_between', 'title' => __('app.menu.breaktimeLateBetween')],
            __('app.menu.halfDayLate') => ['data' => 'halfday_late', 'name' => 'halfday_late', 'title' => __('app.menu.halfDayLate')],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'LateReport_' . date('YmdHis');
    }
}
