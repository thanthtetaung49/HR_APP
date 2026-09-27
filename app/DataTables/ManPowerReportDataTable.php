<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Designation;
use App\Models\ManPowerReport;
use App\Models\EmployeeDetails;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ManPowerReportDataTable extends BaseDataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('check', fn($row) => $this->checkBox($row))
            ->addColumn('rowIndex', function () {
                static $index = 0;
                return ++$index; // Incremental row indexedi
            })
            ->editColumn('budget_year', function ($manPower) {
                return $manPower->budget_year;
            })
            ->editColumn('quarter', function ($manPower) {
                // if ($manPower->quarter == 1) {
                //     return 'Q1 (Apr - Mar)';
                // } elseif ($manPower->quarter == 2) {
                //     return 'Q2 (Apr - Dec)';
                // } elseif ($manPower->quarter == 3) {
                //     return 'Q3 (Jul - Dec)';
                // } else {
                //     return 'Q4 (Oct - Dec)';
                // }
                return 'Q1 (Apr - Mar)';
            })
            ->editColumn('location', function ($manPower) {
                return $manPower->location;
            })
            ->editColumn('team', function ($manPower) {
                return $manPower->team;
            })
            ->editColumn('position', function ($manPower) {
                return $manPower->position;
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
                $salaries = $manPower->salary_actual > 0 ? $manPower->salary_actual : 0;

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
            // ->editColumn('joining_date', function ($manPower) {
            //     return $manPower->joining_date->format('Y-m-d');
            // })
            // ->editColumn('updated_at', function ($manPower) {
            //     return $manPower->updated_at->format('Y-m-d');
            // })
            ->editColumn('vacancy_percent', function ($manPower) {
                $vacancy = (float) ($manPower->vacancy_count ?? 0);

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
            ->addColumn('action', function ($manPower) {

                $findLastBudgetReport = ManPowerReport::select('budget_year', 'id', 'quarter')
                    ->where('status', '!=', 'approved')
                    ->groupBy('budget_year', 'quarter')
                    ->orderBy('quarter', 'desc')
                    ->first();

                $action = '<div class="task_view">
<a href="' . route('man-power-reports.show', [$manPower->id]) . '" class="taskView text-darkest-grey f-w-500 openRightModal">' . __('app.view') . '</a>
<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $manPower->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="dropdownMenuLink-' . $manPower->id . '" tabindex="0">
                                <a class="dropdown-item" href="' . route('man-power-reports.edit', [$manPower->id]) . '">
                                    <i class="fa fa-edit mr-2"></i>
                                    ' . trans('app.edit') . '
                                </a>
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-manpower-id="' . $manPower->id . '">
                                    <i class="fa fa-trash mr-2"></i>
                                    ' . trans('app.delete') . '
                                </a>

                                 <a class="dropdown-item" href="' . route('manPowerReports.history', [$manPower->id]) . '">
                                    <i class="fas fa-history mr-2"></i>
                                   History
                                </a>

                            </div>
                        </div>
                    </div>';

                return $action;
            })
            ->rawColumns(['actual_man_power', 'total_man_power_basic_salary', 'action', 'check', 'vacancy_percent', 'status'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ManPowerReport $model)
    {
        $quarter = request()->quarter;
        $roles = auth()->user()->roles;
        $isAdmin = $roles->contains(fn($role) => $role->name === 'admin');
        $isHRmanager = $roles->contains(fn($role) => $role->name === 'hr-manager');

        $latestAllowanceIds = DB::table('allowances')
            ->selectRaw('MAX(id) as id, user_id')
            ->groupBy('user_id');

        $activeEmployeeCondition = 'u.status = "active"
            AND employee_details.notice_period_start_date IS NULL
            AND (YEAR(employee_details.joining_date) <= man_power_reports.budget_year
                OR employee_details.joining_date IS NULL)
            AND employee_details.designation_id = man_power_reports.position_id';

        $employeeCount = 'COUNT(DISTINCT CASE WHEN ' . $activeEmployeeCondition . '
            THEN employee_details.user_id END)';

        $basicSalary = 'COALESCE(SUM(CASE WHEN ' . $activeEmployeeCondition . '
            THEN COALESCE(allowances.basic_salary, 0) ELSE 0 END), 0)';
        $technicalAllowance = 'COALESCE(SUM(CASE WHEN ' . $activeEmployeeCondition . '
            THEN COALESCE(allowances.technical_allowance, 0) ELSE 0 END), 0)';
        $livingCostAllowance = 'COALESCE(SUM(CASE WHEN ' . $activeEmployeeCondition . '
            THEN COALESCE(allowances.living_cost_allowance, 0) ELSE 0 END), 0)';
        $salaryActual = '(' . $basicSalary . ' + ' . $technicalAllowance . ' + ' . $livingCostAllowance . ')';

        $model = $model->select(
            'man_power_reports.*',
            'locations.id as location_id',
            'locations.location_name as location',
            'teams.team_name as team',
            'designations.id as designation_id',
            'designations.name as position',
            DB::raw($employeeCount . ' as count_employee'),
            DB::raw($basicSalary . ' as basic_salary'),
            DB::raw($technicalAllowance . ' as technical_allowance'),
            DB::raw($livingCostAllowance . ' as living_cost_allowance'),
            DB::raw($salaryActual . ' as salary_actual'),
            DB::raw('CASE
                WHEN man_power_reports.man_power_setup IS NULL OR man_power_reports.man_power_setup <= 0 THEN 0
                ELSE GREATEST(0, 100 - ((' . $employeeCount . ' / man_power_reports.man_power_setup) * 100))
            END as vacancy_count')
        )
            ->join('teams', 'man_power_reports.team_id', '=', 'teams.id')
            ->join('locations', 'teams.location_id', '=', 'locations.id')
            ->join('designations', 'man_power_reports.position_id', '=', 'designations.id')
            ->leftJoin('employee_details', function ($join) {
                $join->on('employee_details.department_id', '=', 'teams.id')
                    ->on('employee_details.designation_id', '=', 'man_power_reports.position_id');
            })
            ->leftJoin('users as u', 'u.id', '=', 'employee_details.user_id')
            ->leftJoinSub($latestAllowanceIds, 'latest_allowance_ids', function ($join) {
                $join->on('latest_allowance_ids.user_id', '=', 'u.id');
            })
            ->leftJoin('allowances', 'allowances.id', '=', 'latest_allowance_ids.id')
            ->when(!$isAdmin && !$isHRmanager, function ($query) {
                $query->where('man_power_reports.created_by', user()->id);
            })
            ->groupBy([
                'man_power_reports.id',
                'locations.id',
                'locations.location_name',
                'teams.team_name',
                'designations.id',
                'designations.name',
            ])
            ->orderBy('man_power_reports.budget_year', 'desc')
            ->orderBy('man_power_reports.created_at', 'desc');

        if ($quarter != 'all' && $quarter != null) {
            $model->where('man_power_reports.quarter', $quarter);
        }

        if (request()->teamId != 'all' && request()->teamId != null) {
            $model->where('man_power_reports.team_id', request()->teamId);
        }

        if (request()->locationId != 'all' && request()->locationId != null) {
            $model->where('locations.id', request()->locationId);
        }

        if (request()->position != 'all' && request()->position != null) {
            $model->where('designations.id', request()->position);
        }

        if (request()->budgetYear != 'all' && request()->budgetYear != null) {
            $model->where('man_power_reports.budget_year', request()->budgetYear);
        }

        if (request('startDate') != '' && request()->startDate != null) {
            $startDate = Carbon::createFromFormat($this->company->date_format, request()->startDate)->toDateString();

            $model->whereRaw('Date(employee_details.joining_date) >= ?', [$startDate]);
        }

        if (request()->endDate != '' && request()->endDate != null) {
            $endDate = Carbon::createFromFormat($this->company->date_format, request()->endDate)->toDateString();

            $model->whereRaw('Date(employee_details.joining_date) <= ?', [$endDate]);
        }

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('manpowerreport-table')
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
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false,
                'visible' => !in_array('client', user_roles())
            ],

            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
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
            __('app.menu.remarkFrom') => ['data' => 'remark_from', 'name' => 'remark_from', 'title' => __('app.menu.remarkFrom')],
            __('app.menu.remarkTo') => ['data' => 'remark_to', 'name' => 'remark_to', 'title' => __('app.menu.remarkTo')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ManPowerReport_' . date('YmdHis');
    }
}
