<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\EmployeeDetails;
use App\Scopes\ActiveScope;
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
                return $user->net_salary ? round($user->net_salary, 0) : 0;
            })
            ->editColumn('notice_period_end_date', function ($user) {
                return $user->notice_period_end_date ? $user->notice_period_end_date : '-';
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
        $month = request('month');
        $year = request('year');
        $locationId = request('locationId');
        $searchText = request('searchText');

        return $model
            ->withoutGlobalScope(ActiveScope::class)
            ->select(
                'users.id',
                'users.name',
                'users.bank_account_number',
                'salary_slips.net_salary',
                'locations.location_name',
                'locations.id as location_id',
                'salary_slips.year',
                'salary_slips.month',
                'employee_details.notice_period_end_date'
            )
            ->join('salary_slips', 'salary_slips.user_id', '=', 'users.id')
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('teams', 'employee_details.department_id', '=', 'teams.id')
            ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
            ->whereNotNull('users.bank_account_number')
            ->whereIn('users.status', ['active', 'deactive'])
            ->when($year !== null && $year !== '', function ($query) use ($year) {
                $query->where('salary_slips.year', $year);
            })
            ->when($month !== null && $month !== '', function ($query) use ($month) {
                $query->whereRaw('CAST(salary_slips.month AS SIGNED) = ?', [(int) $month]);
            })
            ->when($locationId !== null && $locationId !== '' && $locationId !== 'all', function ($query) use ($locationId) {
                $query->where('locations.id', $locationId);
            })
            ->when($searchText !== null && $searchText !== '', function ($query) use ($searchText) {
                $query->where('users.name', 'like', '%' . $searchText . '%');
            });
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
            __('app.menu.employees') => ['data' => 'name', 'name' => 'name', 'title' => __('app.menu.employees'), 'className' => 'text-left'],
            __('app.menu.nrc') => ['data' => 'nrc', 'name' => 'nrc', 'title' => __('app.menu.nrc'), 'className' => 'text-left'],
            __('app.menu.bankaccountNumber') => ['data' => 'bank_account_number', 'name' => 'bank_account_number', 'title' => __('app.menu.bankaccountNumber'), 'className' => 'text-left'],
            __('app.onNoticePeriod') => ['data' => 'notice_period_end_date', 'name' => 'notice_period_end_date', 'title' => __('app.onNoticePeriod'), 'className' => 'text-left'],
            __('app.menu.amount') => ['data' => 'net_salary', 'name' => 'net_salary', 'title' => __('app.menu.amount'), 'className' => 'text-left'],
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
