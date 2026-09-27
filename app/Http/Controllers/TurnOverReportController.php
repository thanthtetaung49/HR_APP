<?php

namespace App\Http\Controllers;

use App\Exports\TurnOverReportExport;
use App\Models\EmployeeDetails;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TurnOverReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.turnOverReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    public function index()
    {
        $this->authorize('viewAny', User::class);

        $year = (int) request('year', now()->year);
        $locationId = request('locationId');
        $report = $this->getReport($year, $locationId);

        $this->months = $this->months();
        $this->reportRows = $this->reportRows();
        $this->reportData = $report['reportData'];
        $this->turnOverReports = $report['turnOverReports'];
        $this->employeeTotal = $report['employeeTotal'];
        $this->selectedYear = $year;
        $this->locations = Location::orderBy('location_name')->get();

        return view('turn-over-reports.index', $this->data);
    }

    public function exportTurnOverReport(Request $request)
    {
        $year = (int) ($request->year ?: now()->year);
        $locationId = $request->locationId;

        $locationName = $locationId
            ? Location::findOrFail($locationId)->location_name
            : 'All Location';

        $report = $this->getReport($year, $locationId);

        return Excel::download(
            new TurnOverReportExport(
                $this->months(),
                $this->reportRows(),
                $report['reportData'],
                $year % 100,
                $locationName
            ),
            'turn-over-reports_' . $year . '_' . $locationName . '.xlsx'
        );
    }

    public function filterTurnOverReport(Request $request)
    {
        $year = (int) ($request->year ?: now()->year);
        $locationId = $request->locationId;
        $report = $this->getReport($year, $locationId);

        return response()->json([
            'months' => $this->months(),
            'reportRows' => $this->reportRows(),
            'reportData' => $report['reportData'],
            'turnOverReports' => $report['turnOverReports'],
            'employeeTotal' => $report['employeeTotal'],
        ]);
    }

    protected function months(): array
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];
    }

    protected function reportRows(): array
    {
        return [
            ['label' => 'Total MP', 'key' => 'manpower', 'percentage' => false],
            ['label' => 'Resign', 'key' => 'resign', 'percentage' => false],
            ['label' => 'Turnover %', 'key' => 'resign_percentage', 'percentage' => true],
            ['label' => 'Probation', 'key' => 'probation', 'percentage' => false],
            ['label' => 'Turnover %', 'key' => 'probation_percentage', 'percentage' => true],
            ['label' => 'Permanent', 'key' => 'permanent', 'percentage' => false],
            ['label' => 'Turnover %', 'key' => 'permanent_percentage', 'percentage' => true],
        ];
    }

    protected function getReport(int $year, $locationId = null): array
    {
        $employeeTotal = $this->employeeTotal($year, $locationId);
        $turnOverReports = $this->turnOverReports($year, $locationId);

        return [
            'employeeTotal' => $employeeTotal,
            'turnOverReports' => $turnOverReports,
            'reportData' => $this->buildMonthlyReport($employeeTotal, $turnOverReports),
        ];
    }

    public function turnOverReports($year = null, $locationId = null): Collection
    {
        $year = (int) ($year ?: now()->year);

        return EmployeeDetails::query()
            ->join('teams', 'teams.id', '=', 'employee_details.department_id')
            ->join('locations', 'locations.id', '=', 'teams.location_id')
            ->whereNotNull('employee_details.last_date')
            ->whereYear('employee_details.last_date', $year)
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            })
            ->whereIn('teams.department_type', ['operation', 'supporting'])
            ->selectRaw('MONTH(employee_details.last_date) AS month')
            ->selectRaw('teams.department_type')
            ->selectRaw('COUNT(DISTINCT employee_details.user_id) AS resigned_total')
            ->selectRaw("COUNT(DISTINCT CASE
                WHEN employee_details.probation_end_date IS NOT NULL
                AND employee_details.last_date <= employee_details.probation_end_date
                THEN employee_details.user_id END) AS probation_total")
            ->selectRaw("COUNT(DISTINCT CASE
                WHEN employee_details.probation_end_date IS NOT NULL
                AND employee_details.last_date > employee_details.probation_end_date
                THEN employee_details.user_id END) AS permanent_total")
            ->groupByRaw('MONTH(employee_details.last_date), teams.department_type')
            ->orderByRaw('MONTH(employee_details.last_date), teams.department_type')
            ->get();
    }

    public function employeeTotal($year = null, $locationId = null): Collection
    {
        $year = (int) ($year ?: now()->year);
        $monthsQuery = null;

        foreach (range(1, 12) as $month) {
            $monthQuery = DB::query()->selectRaw('? AS cutoff_date', [
                Carbon::create($year, $month, 28)->toDateString(),
            ]);

            $monthsQuery = $monthsQuery
                ? $monthsQuery->unionAll($monthQuery)
                : $monthQuery;
        }

        return DB::query()
            ->fromSub($monthsQuery, 'rm')
            ->crossJoin('locations')
            ->leftJoin('teams', 'teams.location_id', '=', 'locations.id')
            ->leftJoin('employee_details', 'employee_details.department_id', '=', 'teams.id')
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            })
            ->selectRaw('MONTH(rm.cutoff_date) AS month, rm.cutoff_date')
            ->selectRaw("COUNT(DISTINCT CASE
                WHEN teams.department_type = 'operation'
                AND DATE(employee_details.joining_date) <= rm.cutoff_date
                AND (employee_details.last_date IS NULL OR DATE(employee_details.last_date) >= rm.cutoff_date)
                THEN employee_details.user_id END) AS operation_employee_count")
            ->selectRaw("COUNT(DISTINCT CASE
                WHEN teams.department_type = 'supporting'
                AND DATE(employee_details.joining_date) <= rm.cutoff_date
                AND (employee_details.last_date IS NULL OR DATE(employee_details.last_date) >= rm.cutoff_date)
                THEN employee_details.user_id END) AS supporting_employee_count")
            ->groupByRaw('MONTH(rm.cutoff_date), rm.cutoff_date')
            ->orderByRaw('MONTH(rm.cutoff_date)')
            ->get();
    }

    protected function buildMonthlyReport(Collection $employeeTotal, Collection $turnOverReports): array
    {
        $manpowerByMonth = $employeeTotal->keyBy(fn($item) => (int) $item->month);
        $turnoverByMonth = $turnOverReports->groupBy(fn($item) => (int) $item->month);
        $report = [];

        foreach ($this->months() as $month => $label) {
            $manpowerRow = $manpowerByMonth->get($month);
            $turnoverRows = $turnoverByMonth->get($month, collect())->keyBy('department_type');
            $operation = $turnoverRows->get('operation');
            $supporting = $turnoverRows->get('supporting');

            $manpower = $this->metric(
                (int) ($manpowerRow->operation_employee_count ?? 0),
                (int) ($manpowerRow->supporting_employee_count ?? 0)
            );
            $resign = $this->metric(
                (int) ($operation->resigned_total ?? 0),
                (int) ($supporting->resigned_total ?? 0)
            );
            $probation = $this->metric(
                (int) ($operation->probation_total ?? 0),
                (int) ($supporting->probation_total ?? 0)
            );
            $permanent = $this->metric(
                (int) ($operation->permanent_total ?? 0),
                (int) ($supporting->permanent_total ?? 0)
            );

            $report[$month] = [
                'month' => $month,
                'label' => $label,
                'manpower' => $manpower,
                'resign' => $resign,
                'resign_percentage' => $this->percentageMetric($resign, $manpower),
                'probation' => $probation,
                'probation_percentage' => $this->percentageMetric($probation, $manpower),
                'permanent' => $permanent,
                'permanent_percentage' => $this->percentageMetric($permanent, $manpower),
            ];
        }

        return $report;
    }

    protected function metric(int $operation, int $supporting): array
    {
        return [
            'operation' => $operation,
            'supporting' => $supporting,
            'total' => $operation + $supporting,
        ];
    }

    protected function percentageMetric(array $numerator, array $denominator): array
    {
        return [
            'operation' => $this->percentage($numerator['operation'], $denominator['operation']),
            'supporting' => $this->percentage($numerator['supporting'], $denominator['supporting']),
            'total' => $this->percentage($numerator['total'], $denominator['total']),
        ];
    }

    protected function percentage(int $value, int $total): int
    {
        return $total > 0 ? (int) round(($value / $total) * 100) : 0;
    }
}
