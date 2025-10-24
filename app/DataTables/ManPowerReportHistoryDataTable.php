<?php

namespace App\DataTables;

use App\Models\Designation;
use App\Models\Location;
use App\Models\ManPowerReportHistory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

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
                if ($manPower->quarter == 1) {
                    return 'Q1 (Jan - Dec)';
                } elseif ($manPower->quarter == 2) {
                    return 'Q2 (Apr - Dec)';
                } elseif ($manPower->quarter == 3) {
                    return 'Q3 (Jul - Dec)';
                } else {
                    return 'Q4 (Oct - Dec)';
                }
            })
            ->editColumn('location', function ($manPower) {
                $department = Team::where('id', $manPower->team_id)->first();
                $location = Location::where('id', $department->location_id)->first();

                return $location->location_name;
            })
            ->editColumn('team', function ($manPower) {
                $department = Team::where('id', $manPower->team_id)->first();

                return $department ? $department->team_name : '---';
            })
            ->editColumn('position', function ($manPower) {
                $designation = Designation::where('id', $manPower->position_id)->first();

                return $designation ? $designation->name : '---';
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
                $count =  ($manPower->count_employee > 0) ? $manPower->count_employee : 0;

                $vacancy = 100;

                if ($manPower->man_power_setup <= $count) {
                    $vacancy = 0;
                } else if ($count > 0) {
                    $vacancy = 100 - ($count / $manPower->man_power_setup) * 100;
                } else {
                    $vacancy = 100;
                }

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
        $query = $model->newQuery();

        if ($this->id) {
            $query->where('man_power_report_id', $this->id);
        }

        return $query;
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
