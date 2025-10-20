<?php

namespace App\DataTables;

use App\Models\ReportPermission;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ReportPermissionDataTable extends BaseDataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return  datatables()
            ->eloquent($query)
            ->addColumn('check', fn($row) => $this->checkBox($row))
            ->addColumn('rowIndex', function () {
                static $index = 0;
                return ++$index; // Incremental row indexedi
            })
            ->editColumn('report', function () {
                return "Man Power Report";
            })
            ->editColumn('permission', function ($report) {
                if ($report->permission == 'yes') {
                    $icon = '<i class="fa fa-check text-success "></i>';
                } else {
                    $icon = '<i class="fa fa-exclamation-triangle text-danger"></i>';
                }
                return '<div>
                    <span class="mr-1">' . $report->permission . '</span> ' . $icon . '
                </div>';
            })
            ->addColumn('action', function ($report) {
                $action = '<div class="task_view">
<a href="' . route('report-permission.show', [$report->id]) . '" class="taskView text-darkest-grey f-w-500 openRightModal">' . __('app.view') . '</a>
<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $report->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="dropdownMenuLink-' . $report->id . '" tabindex="0">
                                <a class="dropdown-item" href="' . route('report-permission.edit', [$report->id]) . '">
                                    <i class="fa fa-edit mr-2"></i>
                                    ' . trans('app.edit') . '
                                </a>
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-reportpermission-id="' . $report->id . '">
                                    <i class="fa fa-trash mr-2"></i>
                                    ' . trans('app.delete') . '
                                </a>
                            </div>
                        </div>
                    </div>';

                return $action;
            })
            ->rawColumns(['action', 'check', 'permission'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ReportPermission $model): QueryBuilder
    {
        $model = $model->select('locations.location_name as location', 'teams.team_name as department', 'designations.name as designation', 'users.name as user', 'report_permissions.permission as permission', 'report_permissions.id as id')
            ->leftJoin('locations', 'locations.id', '=', 'report_permissions.location_id', 'report_permissions.location_id', 'report_permissions.team_id', 'report_permissions.designation_id', 'report_permissions.user_id')
            ->leftJoin('teams', 'teams.id', '=', 'report_permissions.team_id')
            ->leftJoin('designations', 'designations.id', '=', 'report_permissions.designation_id')
            ->leftJoin('users', 'users.id', '=', 'report_permissions.user_id');

        if (request()->location != 'all' && request()->location != '') {
            $model->where('report_permissions.location_id', request()->location);
        }

        if (request()->department != 'all' && request()->department != '') {
            $model->where('report_permissions.team_id', request()->department);
        }

        if (request()->designation != 'all' && request()->designation != '') {
            $model->where('report_permissions.designation_id', request()->designation);
        }

        if (request()->searchText != 'all' && request()->searchText != '') {
            $model->where('users.name', 'like', '%' . request()->searchText . '%');
        }

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('reportpermission-table')
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
            __('app.menu.location') => ['data' => 'location', 'name' => 'location', 'title' => __('app.menu.location')],
            __('app.menu.department') => ['data' => 'department', 'name' => 'department', 'title' => __('app.menu.department')],
            __('app.menu.designation') => ['data' => 'designation', 'name' => 'designation', 'title' => __('app.menu.designation')],
            __('app.menu.employees') => ['data' => 'user', 'name' => 'user', 'title' => __('app.menu.employees')],
            __('app.menu.reportName') => ['data' => 'report', 'name' => 'report', 'title' => __('app.menu.reportName')],
            __('app.menu.permission') => ['data' => 'permission', 'name' => 'permission', 'title' => __('app.menu.permission')],
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
        return 'ReportPermission_' . date('YmdHis');
    }
}
