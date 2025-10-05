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

                if ($manPower->man_power_setup > $count) {
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
                $salaries =  ($manPower->total_allowance > 0) ? $manPower->total_allowance : 0;

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
            ->editColumn('created_at', function ($manPower) {
                return $manPower->created_at->format('Y-m-d');
            })
            ->editColumn('updated_at', function ($manPower) {
                return $manPower->updated_at->format('Y-m-d');
            })
            ->addColumn('action', function ($manPower) {
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
                            </div>
                        </div>
                    </div>';

                return $action;
            })
            ->rawColumns(['actual_man_power', 'total_man_power_basic_salary', 'action', 'check'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ManPowerReport $model)
    {
        $model = $model->select([
            'man_power_reports.*',
            'teams.team_name as team',
            'employee_details.department_id',
            'designations.name as position',
            'locations.location_name as location',
            'designations.id as designation_id',
            'locations.id as location_id',
            DB::raw('SUM(CASE
    WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
    AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
    THEN allowances.basic_salary
    ELSE 0
END) as total_allowance'),

            DB::raw('COUNT(CASE
    WHEN (YEAR(employee_details.created_at) = man_power_reports.budget_year OR employee_details.created_at IS NULL)
    AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
    THEN employee_details.id
END) as count_employee')

        ])
            ->leftJoin('teams', 'man_power_reports.team_id', '=', 'teams.id')
            ->leftJoin('employee_details', 'teams.id', '=', 'employee_details.department_id')
            ->leftJoin('users', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'users.designation_id', '=', 'designations.id')
            ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
            ->leftJoin('allowances', 'users.id', '=', 'allowances.user_id')
            // ->whereRaw('(employee_details.created_at IS NULL OR YEAR(employee_details.created_at) = man_power_reports.budget_year)')
            // ->whereRaw('(users.created_at IS NULL OR YEAR(users.created_at) = man_power_reports.budget_year)')
            // ->whereRaw('(allowances.created_at IS NULL OR YEAR(allowances.created_at) = man_power_reports.budget_year)')
            ->groupBy([
                'man_power_reports.id',
                'man_power_reports.team_id',
                'man_power_reports.budget_year',
                'man_power_reports.man_power_setup',
                'man_power_reports.man_power_basic_salary',
                'man_power_reports.created_at',
                'man_power_reports.updated_at',
                'designations.name',
                'designations.id',
                'teams.team_name',
                'locations.location_name',
                'locations.id',
                'employee_details.department_id'
            ]);

        if (request()->teamId != 'all' && request()->teamId != null) {
            $model->where('man_power_reports.team_id', request()->teamId);
        }

        if (request()->locationId != 'all' && request()->locationId != null) {
            $model->where('locations.id', request()->locationId);
        }

        if (request()->budgetYear != 'all' && request()->budgetYear != null) {
            $model->where('man_power_reports.budget_year', request()->budgetYear);
        }

        if (request('startDate') != '' && request()->startDate != null) {
            $startDate = Carbon::createFromFormat($this->company->date_format, request()->startDate)->toDateString();

            $model->whereRaw('Date(man_power_reports.created_at) >= ?', [$startDate]);
        }

        if (request()->endDate != '' && request()->endDate != null) {
            $endDate = Carbon::createFromFormat($this->company->date_format, request()->endDate)->toDateString();

            $model->whereRaw('Date(man_power_reports.created_at) <= ?', [$endDate]);
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

    // public function html()
    // {
    //     $dataTable = $this->setBuilder('manpowerreport-table', 2)
    //         ->parameters([
    //             'initComplete' => 'function () {
    //                window.LaravelDataTables["departments-table"].buttons().container()
    //                 .appendTo("#table-actions")
    //             }',
    //             'fnDrawCallback' => 'function( oSettings ) {
    //                 $("body").tooltip({
    //                     selector: \'[data-toggle="tooltip"]\'
    //                 })
    //             }',
    //         ]);

    //     if (canDataTableExport()) {
    //         $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
    //     }

    //     return $dataTable;
    // }

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
            'location' => ['data' => 'location', 'name' => 'location', 'title' => 'Location'],
            'position' => ['data' => 'position', 'name' => 'position', 'title' => 'Position'],
            'man_power_setup' => ['data' => 'man_power_setup', 'name' => 'man_power_setup', 'title' => 'Man Power Budget'],
            'actual_man_power' => ['data' => 'actual_man_power', 'name' => 'actual_man_power', 'title' => 'Man Power Actual'],
            'max_man_power_basic_salary' => ['data' => 'max_man_power_basic_salary', 'name' => 'max_man_power_basic_salary', 'title' => 'Max Salary Budget'],
            'total_man_power_basic_salary' => ['data' => 'total_man_power_basic_salary', 'name' => 'total_man_power_basic_salary', 'title' => 'Salary Actual'],
            'team' => ['data' => 'team', 'name' => 'team', 'title' => __('app.menu.department')],
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
