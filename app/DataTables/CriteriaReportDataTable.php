<?php

namespace App\DataTables;

use App\Models\CriteriaReport;
use App\Models\EmployeeDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CriteriaReportDataTable extends BaseDataTable
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
            // ->addColumn('check', fn($row) => $this->checkBox($row))
            ->addColumn('rowIndex', function () {
                static $index = 0;
                return ++$index; // Incremental row indexedi
            })
            ->editColumn('criteria', function ($criteria) {
                $employee = new EmployeeDetails();
                $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

                if ($getCustomFieldGroupsWithFields) {
                    $fields = $getCustomFieldGroupsWithFields->fields;
                }

                if (isset($fields) && count($fields) > 0) {
                    foreach ($fields as $field) {
                        if ($field->type == 'select' && $field->name == 'exit-reasons-1') {
                            $options = $field->values;
                            $exitReason = $options[$criteria->exit_reason_id] ?? $criteria->exit_reason_id;
                        }
                    }
                }

                return $exitReason;
            })
            // ->rawColumns()
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(EmployeeDetails $model): QueryBuilder
    {
        $model = $model->select('users.name as employee_name', 'employee_details.notice_period_end_date', 'sub_criterias.action_taken', 'sub_criterias.responsible_person as responsible_person', 'sub_criterias.accountability as accountability', 'sub_criterias.sub_criteria as sub_criteria', 'criterias.exit_reason_id as exit_reason_id', 'teams.id as team_id', 'designations.id as designation_id', 'locations.id as location_id')
            ->join('criterias', 'criterias.id', 'employee_details.criteria_id')
            ->leftJoin('sub_criterias', 'sub_criterias.id', '=', 'employee_details.sub_criteria_id')
            ->leftJoin('users', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
            ->leftJoin('designations', 'designations.id', '=', 'employee_details.designation_id')
            ->leftJoin('locations', 'locations.id', '=', 'teams.location_id')
            ->whereNotNull('employee_details.notice_period_end_date')
            ->whereNotNull('employee_details.criteria_id')
            ->whereNotNull('employee_details.sub_criteria_id');


        if (request()->location != 'all' && request()->location != '') {
            $model->where('locations.id', request()->location);
        }

        if (request()->department != 'all' && request()->department != '') {
            $model->where('teams.id',request()->department);
        }

        if (request()->designation != 'all' && request()->designation != '') {
            $model->where('designations.id', request()->designation);
        }

        if (request()->searchText != 'all' && request()->searchText != '') {
            $model->where('users.name', 'like', '%' . request()->searchText . '%')
                ->orWhere('sub_criterias.action_taken', 'like', '%' . request()->searchText . '%')
                ->orWhere('sub_criterias.responsible_person', 'like', '%' . request()->searchText . '%')
                ->orWhere('sub_criterias.accountability', 'like', '%' . request()->searchText . '%');
        }

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('criteriareport-table')
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
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.menu.employees') => ['data' => 'employee_name', 'name' => 'employee_name', 'title' => __('app.menu.employees')],
            __('app.menu.exitsReason') => ['data' => 'criteria', 'name' => 'criteria', 'title' => __('app.menu.exitsReason')],
            __('app.menu.subCriteria') => ['data' => 'sub_criteria', 'name' => 'sub_criteria', 'title' => __('app.menu.subCriteria')],
            __('app.menu.accountability') => ['data' => 'accountability', 'name' => 'accountability', 'title' => __('app.menu.accountability')],
            __('app.menu.responsiblePerson') => ['data' => 'responsible_person', 'name' => 'responsible_person', 'title' => __('app.menu.responsiblePerson')],
            __('app.menu.actionTaken') => ['data' => 'action_taken', 'name' => 'action_taken', 'title' => __('app.menu.actionTaken')],
            __('app.noticePeriodEndDate') => ['data' => 'notice_period_end_date', 'name' => 'notice_period_end_date', 'title' => __('app.menu.noticePeriodEndDate')],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'CriteriaReport_' . date('YmdHis');
    }
}
