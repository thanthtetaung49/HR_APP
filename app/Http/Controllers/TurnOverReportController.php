<?php

namespace App\Http\Controllers;

use App\Exports\TurnOverReportExport;
use App\Models\EmployeeDetails;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TurnOverReportController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.turnOverReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $viewPermission = user()->permission('view_turn_over_reports');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->months = $this->months();
        $this->turnOverReports = $this->turnOverReports();
        $this->employeeTotal = $this->employeeTotal();
        $this->locations = Location::get();

        return view('turn-over-reports.index', $this->data);
    }


    public function exportTurnOverReport(Request $request)
    {
        $locationId = $request->locationId;
        $locationName = "";

        if (empty($locationId) || $locationId == "") {
            $locationName = "All Location";
        } else {
            $location = Location::findOrFail($locationId);
            $locationName = $location->location_name;
        }

        $filterYear = $request->year;

        $now = Carbon::now();
        $year = $filterYear ? $filterYear : $now->format('Y');
        $shortFormatYear = (int)$year % 100;

        $this->months = $this->months();
        $this->turnOverReports = $this->turnOverReports($filterYear, $locationId);
        $this->employeeTotal = $this->employeeTotal($filterYear, $locationId);

        return Excel::download(new TurnOverReportExport(
            $this->months,
            $this->turnOverReports,
            $this->employeeTotal,
            $shortFormatYear,
            $locationName
        ), 'turn-over-reports_' . $year . '_' . $locationName . '.xlsx');
    }

    public function filterTurnOverReport(Request $request)
    {
        $year = $request->year;
        $locationId = $request->locationId;

        $this->filterYear = $year;

        $this->months = $this->months();
        $this->turnOverReports = $this->turnOverReports($year, $locationId);

        $this->employeeTotal = $this->employeeTotal($year, $locationId);

        return response()->json([
            'months' => $this->months,
            'turnOverReports' => $this->turnOverReports,
            'employeeTotal' => $this->employeeTotal
        ]);
    }

    protected function months()
    {
        return  [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "May",
            6 => "Jun",
            7 => "Jul",
            8 => "Aug",
            9 => "Sep",
            10 => "Oct",
            11 => "Nov",
            12 => "Dec"
        ];
    }

    // protected function probation($year = null)
    // {
    //     $probation = EmployeeDetails::select(
    //         DB::raw('MONTH(employee_details.probation_end_date) as month'),
    //         'employee_details.user_id',
    //         DB::raw('CASE
    //             WHEN employee_details.probation_end_date IS NOT NULL
    //             THEN "Yes"
    //             ELSE "No"
    //         END AS has_probation
    //         '),
    //         DB::raw('COUNT(*) as total'),
    //         'teams.department_type'
    //     )
    //         ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
    //         ->whereNotNull('employee_details.probation_end_date')
    //         ->whereYear('employee_details.created_at', now()->year)
    //         ->when($year, function ($query) use ($year) {
    //             $query->whereYear('employee_details.created_at', $year);
    //         })
    //         ->groupBy([
    //             DB::raw('MONTH(employee_details.probation_end_date)'),
    //             'teams.department_type'
    //         ])
    //         ->get();

    //     return $probation;
    // }

    public function turnOverReports($year = null, $locationId = null)
    {
        $turnOverReports = EmployeeDetails::select(
            DB::raw(
                '
                MONTH(employee_details.notice_period_end_date) as month',
                'CASE WHEN employee_details.notice_period_end_date IS NOT NULL THEN MONTH(employee_details.notice_period_end_date) ELSE MONTH(employee_details.probation_end_date) END as month'
            ),
            DB::raw('COUNT(*) as total'),
            DB::raw('CAST(SUM(CASE WHEN employee_details.notice_period_end_date IS NOT NULL THEN 1 ELSE 0 END) AS SIGNED) as resigned_total'),
            DB::raw('CAST(SUM(CASE WHEN employee_details.probation_end_date IS NOT NULL THEN 1 ELSE 0 END) AS SIGNED) as probation_total'),
            DB::raw('CAST(SUM(CASE WHEN employee_details.notice_period_end_date IS NOT NULL THEN 1 ELSE 0 END) AS SIGNED) - CAST(SUM(CASE WHEN employee_details.probation_end_date IS NOT NULL THEN 1 ELSE 0 END) AS SIGNED) as permanent_total'),
            'teams.department_type',
            'locations.id as location_id',
            'locations.location_name as location_name'
        )
            ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
            ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
            ->where(function ($query) {
                $query->whereNotNull('employee_details.notice_period_end_date')
                    ->orWhereNotNull('employee_details.probation_end_date');
            })
            ->whereYear('employee_details.created_at', now()->year)
            ->when($year, function ($query) use ($year) {
                $query->whereYear('employee_details.created_at', $year);
            })
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('locations.id', '=', $locationId);
            })
            ->groupBy(
                DB::raw('MONTH(COALESCE(employee_details.notice_period_end_date, employee_details.probation_end_date))'),
                'teams.department_type'
            )
            ->get();

        // dd($locationId, $turnOverReports->toArray());

        return $turnOverReports;
    }


    // public function permanent($year = null)
    // {
    //     $permanent = EmployeeDetails::select(
    //         DB::raw('MONTH(employee_details.created_at) as month'),
    //         'employee_details.user_id',
    //         DB::raw('CASE
    //             WHEN employee_details.notice_period_end_date IS NULL AND employee_details.probation_end_date IS NULL
    //             THEN "Yes"
    //             ELSE "No"
    //         END AS has_permanent
    //         '),
    //         DB::raw('COUNT(*) as total'),
    //         'teams.department_type'
    //     )
    //         ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
    //         ->whereNull('employee_details.notice_period_end_date')
    //         ->whereNull('employee_details.probation_end_date')
    //         ->whereYear('employee_details.created_at', now()->year)
    //         ->when($year, function ($query) use ($year) {
    //             $query->whereYear('employee_details.created_at', $year);
    //         })
    //         ->groupBy([
    //             DB::raw('MONTH(employee_details.created_at)'),
    //             'teams.department_type'
    //         ])
    //         ->get();

    //     return $permanent;
    // }

    public function employeeTotal($year = null, $locationId = null)
    {
        $employeeTotal = EmployeeDetails::select(
            DB::raw('
            MONTH(employee_details.created_at) as month,
            SUM(CASE WHEN teams.department_type = "operation" THEN 1 ELSE 0 END) as operation_employee_count,
            SUM(CASE WHEN teams.department_type = "supporting" THEN 1 ELSE 0 END) as supporting_employee_count,
            locations.id as location_id,
            locations.location_name as location_name
        ')
        )
            ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
            ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
            ->whereYear('employee_details.created_at', now()->year)
            ->when($year, function ($query) use ($year) {
                $query->whereYear('employee_details.created_at', $year);
            })
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('locations.id', '=', $locationId);
            })
            ->groupBy(DB::raw('MONTH(employee_details.created_at)'))
            ->get();

        return $employeeTotal;
    }
}
