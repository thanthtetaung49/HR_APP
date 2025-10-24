<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\EmployeeDetails;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class BankReportDataTable extends BaseDataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('rowIndex', function () {
                static $index = 0;
                return ++$index;
            })
            ->editColumn('net_salary', function ($user) {
                return $user->net_salary ? $user->net_salary . ' MMK' : 0 . ' MMK';
            })
            ->editColumn('nrc', function ($user) {
                $employee = EmployeeDetails::where('user_id', $user->id)->first();

                $employeeDetail = $employee->withCustomFields();
                $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

                if ($getCustomFieldGroupsWithFields) {
                    $fields = $getCustomFieldGroupsWithFields->fields;
                }

                if (isset($fields) && count($fields) > 0) {
                    foreach ($fields as $field) {
                        if ($field->type == 'text' && $field->name == 'nrc-1') {

                            $nrc = $employeeDetail->custom_fields_data['field_' . $field->id];

                        }
                    }
                }

                return $nrc;
            })
            // ->rawColumns([])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        $month = request()->month;
        $year = request()->year;

        $model = $model->select('users.id as id', 'users.name as name', 'users.bank_account_number as bank_account_number', 'salary_slips.net_salary as net_salary', 'locations.location_name as location_name', 'locations.id as location_id', 'salary_slips.year', 'salary_slips.month', DB::raw('CAST(salary_slips.month AS SIGNED) + 1 as test'))
            ->leftJoin('salary_slips', 'salary_slips.user_id', 'users.id')
            ->leftJoin('employee_details', 'employee_details.user_id', 'users.id')
            ->leftJoin('teams', 'employee_details.department_id', 'teams.id')
            ->leftJoin('locations', 'teams.location_id', 'locations.id')
            ->where('salary_slips.year', $year)
            ->where(DB::raw('CAST(salary_slips.month AS SIGNED) + 1'), $month);

        if (isset(request()->locationId) && request()->locationId != '') {
            $model->where('locations.id', request()->locationId);
        }

        if (isset(request()->searchText) && request()->searchText != '') {
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
            ->setTableId('bankreport-table')
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
            __('app.menu.employees') => ['data' => 'name', 'name' => 'name', 'title' => __('app.menu.employees')],
            __('app.menu.nrc') => ['data' => 'nrc', 'name' => 'nrc', 'title' => __('app.menu.nrc')],
            __('app.menu.bankaccountNumber') => ['data' => 'bank_account_number', 'name' => 'bank_account_number', 'title' => __('app.menu.bankaccountNumber')],
            __('app.menu.amount') => ['data' => 'net_salary', 'name' => 'net_salary', 'title' => __('app.menu.amount')],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'BankReport_' . date('YmdHis');
    }
}
