<?php

namespace App\DataTables;

use App\Models\ManPowerReportHistory;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ManPowerReportHistoryDataTable extends BaseDataTable
{
    protected $id;

    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            // ->addColumn('check', fn($row) => $this->checkBox($row))
            ->addColumn('rowIndex', function () {
                static $index = 0;
                return ++$index; // Incremental row indexedi
            })
            ->editColumn('budget_year', function ($manPower) {
                return $manPower->budget_year;
            })
            ->editColumn('quarter', function ($manPower) {
                return 'Q1 (Apr - Mar)';
            })
            ->editColumn('location', function ($manPower) {
                return $manPower->location ?: '---';
            })
            ->editColumn('team', function ($manPower) {
                return $manPower->team ?: '---';
            })
            ->editColumn('position', function ($manPower) {
                return $manPower->position ?: '---';
            })
            ->editColumn('man_power_setup', function ($manPower) {
                return $manPower->man_power_setup;
            })
            ->editColumn('actual_man_power', function ($manPower) {
                $count =  ($manPower->count_employee > 0) ? $manPower->count_employee : 0;

                if ($manPower->man_power_setup <= $count) {
                    $icon = '<i class="fa fa-check text-success"></i>';
                } else {
                    $icon = '<i class="fa fa-exclamation-triangle text-danger"></i>';
                }

                return '<div>
                    <span>' . $count . '</span>
                    <span class="ml-2">' . $icon . '</span>
                </div>';
            })
            ->editColumn('max_man_power_basic_salary', function ($manPower) {
                return $manPower->man_power_basic_salary;
            })
            ->editColumn('total_man_power_basic_salary', function ($manPower) {
                $salaries = ($manPower->salary_actual > 0) ? $manPower->salary_actual : 0;

                if ($manPower->man_power_basic_salary > $salaries) {
                    $icon = '<i class="fa fa-check text-success"></i>';
                } else {
                    $icon = '<i class="fa fa-exclamation-triangle text-danger"></i>';
                }

                return '<div>
                    <span>' . $salaries . '</span>
                    <span class="ml-2">' . $icon . '</span>
                </div>';
            })
            ->editColumn('status', function ($manPower) {
                if ($manPower->status == 'approved') {
                    return '<span class="bg-success p-1 rounded-sm text-white">' . $manPower->status . '</span>';
                } elseif ($manPower->status == 'pending') {
                    return '<span class="bg-info p-1 rounded-sm text-white">' . $manPower->status . '</span>';
                } else {
                    return '<span class="bg-warning p-1 rounded-sm text-white">' . $manPower->status . '</span>';
                }
            })
            ->editColumn('remark_from', function ($manPower) {
                return $manPower->remark_from ? $manPower->remark_from : '---';
            })
            ->editColumn('remark_to', function ($manPower) {
                return $manPower->remark_to ? $manPower->remark_to : '---';
            })
            ->editColumn('approved_date', function ($manPower) {
                return $manPower->approved_date ? $manPower->approved_date : '---';
            })
            ->editColumn('updated_date', function ($manPower) {
                return $manPower->updated_date ? $manPower->updated_date  : '---';
            })
            ->editColumn('created_at', function ($manPower) {
                return $manPower->created_at->format('Y-m-d');
            })
            ->editColumn('updated_at', function ($manPower) {
                return $manPower->updated_at->format('Y-m-d');
            })
            ->editColumn('vacancy_percent', function ($manPower) {
                $vacancy = (float) $manPower->vacancy_count;

                if ($vacancy < 50) {
                    $icon = '<i class="fa fa-check text-success"></i>';
                    $color = 'text-success';
                } else {
                    $icon = '<i class="fa fa-exclamation-triangle text-danger"></i>';
                    $color = 'text-danger';
                }

                return '<div>
                    <span class="' . $color . '">' . round($vacancy, 0) . ' %</span>
                    <span class="ml-2">' . $icon . '</span>
                </div>';
            })
            ->rawColumns(['actual_man_power', 'total_man_power_basic_salary', 'action', 'check', 'vacancy_percent', 'status'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ManPowerReportHistory $model): QueryBuilder
    {
        $latestAllowances = DB::table('allowances')
            ->select('user_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('user_id');

        $activeEmployeeCondition = "users.status = 'active'
            AND employee_details.notice_period_start_date IS NULL
            AND (YEAR(employee_details.joining_date) <= CAST(man_power_report_histories.budget_year AS UNSIGNED)
                OR employee_details.joining_date IS NULL)
            AND employee_details.designation_id = man_power_report_histories.position_id";

        $employeeCount = "COUNT(DISTINCT CASE WHEN {$activeEmployeeCondition}
            THEN employee_details.user_id END)";

        $basicSalary = "COALESCE(SUM(CASE WHEN {$activeEmployeeCondition}
            THEN COALESCE(allowances.basic_salary, 0) ELSE 0 END), 0)";

        $technicalAllowance = "COALESCE(SUM(CASE WHEN {$activeEmployeeCondition}
            THEN COALESCE(allowances.technical_allowance, 0) ELSE 0 END), 0)";

        $livingCostAllowance = "COALESCE(SUM(CASE WHEN {$activeEmployeeCondition}
            THEN COALESCE(allowances.living_cost_allowance, 0) ELSE 0 END), 0)";

        $salaryActual = "({$basicSalary} + {$technicalAllowance} + {$livingCostAllowance})";

        $vacancyCount = "CASE
            WHEN man_power_report_histories.man_power_setup IS NULL
                OR man_power_report_histories.man_power_setup <= 0 THEN 0
            ELSE GREATEST(
                0,
                100 - (({$employeeCount} / man_power_report_histories.man_power_setup) * 100)
            )
        END";

        $query = $model->newQuery()
            ->join('teams', 'man_power_report_histories.team_id', '=', 'teams.id')
            ->join('locations', 'teams.location_id', '=', 'locations.id')
            ->join('designations', 'man_power_report_histories.position_id', '=', 'designations.id')
            ->leftJoin('employee_details', 'employee_details.department_id', '=', 'man_power_report_histories.team_id')
            ->leftJoin('users', 'users.id', '=', 'employee_details.user_id')
            ->leftJoinSub($latestAllowances, 'latest_allowances', function ($join) {
                $join->on('latest_allowances.user_id', '=', 'users.id');
            })
            ->leftJoin('allowances', 'allowances.id', '=', 'latest_allowances.latest_id')
            ->select(
                'man_power_report_histories.*',
                'locations.location_name as location',
                'teams.team_name as team',
                'designations.name as position',
                DB::raw($employeeCount . ' as count_employee'),
                DB::raw($basicSalary . ' as basic_salary'),
                DB::raw($technicalAllowance . ' as technical_allowance'),
                DB::raw($livingCostAllowance . ' as living_cost_allowance'),
                DB::raw($salaryActual . ' as salary_actual'),
                DB::raw($vacancyCount . ' as vacancy_count')
            )
            ->groupBy(
                'man_power_report_histories.id',
                'man_power_report_histories.man_power_report_id',
                'man_power_report_histories.man_power_setup',
                'man_power_report_histories.man_power_basic_salary',
                'man_power_report_histories.team_id',
                'man_power_report_histories.position_id',
                'man_power_report_histories.budget_year',
                'man_power_report_histories.quarter',
                'man_power_report_histories.status',
                'man_power_report_histories.remark_from',
                'man_power_report_histories.remark_to',
                'man_power_report_histories.created_by',
                'man_power_report_histories.approved_date',
                'man_power_report_histories.updated_date',
                'man_power_report_histories.created_at',
                'man_power_report_histories.updated_at',
                'locations.location_name',
                'teams.team_name',
                'designations.name'
            );

        if ($this->id) {
            $query->where('man_power_report_histories.man_power_report_id', $this->id);
        }

        return $query->orderByDesc('man_power_report_histories.created_at');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('manpowerreporthistory-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
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
            'budget_year' => ['data' => 'budget_year', 'name' => 'budget_year', 'title' => 'Year'],
            'quarter' => ['data' => 'quarter', 'name' => 'quarter', 'title' => __('app.menu.quarter')],
            'location' => ['data' => 'location', 'name' => 'location', 'title' => 'Location'],
            'position' => ['data' => 'position', 'name' => 'position', 'title' => 'Position'],
            'man_power_setup' => ['data' => 'man_power_setup', 'name' => 'man_power_setup', 'title' => 'Man Power Budget'],
            'actual_man_power' => ['data' => 'actual_man_power', 'name' => 'actual_man_power', 'title' => 'Man Power Actual'],
            'max_man_power_basic_salary' => ['data' => 'max_man_power_basic_salary', 'name' => 'max_man_power_basic_salary', 'title' => 'Max Salary Budget'],
            'total_man_power_basic_salary' => ['data' => 'total_man_power_basic_salary', 'name' => 'total_man_power_basic_salary', 'title' => 'Salary Actual'],
            'vacancy_percent' => ['data' => 'vacancy_percent', 'name' => 'vacancy_percent', 'title' => 'Vacancy %'],
            'team' => ['data' => 'team', 'name' => 'team', 'title' => __('app.menu.department')],
            'status' => ['data' => 'status', 'name' => 'status', 'title' => __('app.menu.status')],
            __('app.menu.approvedDate') => ['data' => 'approved_date', 'name' => 'approved_date', 'title' => __('app.menu.approvedDate')],
            __('app.menu.updatedDate') => ['data' => 'updated_date', 'name' => 'updated_date', 'title' => __('app.menu.updatedDate')],
            __('app.menu.remarkFrom') => ['data' => 'remark_from', 'name' => 'remark_from', 'title' => __('app.menu.remarkFrom')],
            __('app.menu.remarkTo') => ['data' => 'remark_to', 'name' => 'remark_to', 'title' => __('app.menu.remarkTo')],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ManPowerReportHistory_' . date('YmdHis');
    }
}
