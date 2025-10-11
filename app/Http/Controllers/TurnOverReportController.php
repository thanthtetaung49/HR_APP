<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDetails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $viewPermission = user()->permission('view_department');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->months = [
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

         $this->probation = EmployeeDetails::select(
            DB::raw('MONTH(employee_details.probation_end_date) as month'),
            'employee_details.user_id',
            DB::raw('CASE
                WHEN employee_details.probation_end_date IS NOT NULL
                THEN "Yes"
                ELSE "No"
            END AS has_probation
            '),
            DB::raw('COUNT(*) as total'),
            'teams.department_type'
        )
        ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
        ->whereNotNull('employee_details.probation_end_date')
        ->whereYear('employee_details.created_at', now()->year)
        ->groupBy([
            DB::raw('MONTH(employee_details.probation_end_date)'),
            'teams.department_type'
        ])
        ->get();

        // dd($this->probation->toArray());

        $this->resigned = EmployeeDetails::select(
            DB::raw('MONTH(employee_details.notice_period_end_date) as month'),
            'employee_details.user_id',
            DB::raw('CASE
                WHEN employee_details.notice_period_end_date IS NOT NULL
                THEN "Yes"
                ELSE "No"
            END AS has_resigned
            '),
            DB::raw('COUNT(*) as total'),
            'teams.department_type'
        )
        ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
        ->whereNotNull('employee_details.notice_period_end_date')
        ->whereYear('employee_details.created_at', now()->year)
        ->groupBy([
            DB::raw('MONTH(employee_details.notice_period_end_date)'),
            'teams.department_type'
        ])
        ->get();


        $this->employeeTotal = EmployeeDetails::select(
            DB::raw('
            MONTH(employee_details.created_at) as month,
            SUM(CASE WHEN teams.department_type = "operation" THEN 1 ELSE 0 END) as operation_employee_count,
            SUM(CASE WHEN teams.department_type = "supporting" THEN 1 ELSE 0 END) as supporting_employee_count
        ')
        )
        ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
        ->whereYear('employee_details.created_at', now()->year)
        ->groupBy(DB::raw('MONTH(employee_details.created_at)'))
        ->get();

        // dd($this->total->toArray());

        // dd($resigned->toArray());
        // dd($probation->toArray());

        return view('turn-over-reports.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
