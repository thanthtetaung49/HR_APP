<?php

namespace App\DataTables;

use App\Models\SubCriteria;
use App\Models\SubCriterion;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class SubCriteriaDataTable extends BaseDataTable
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
            ->editColumn('sub_criteria', function ($subCriteria) {
                return $subCriteria->sub_criteria;
            })
            ->addColumn('action', function ($subCriteria) {
                $action = '<div class="task_view">
<a href="' . route('sub-criteria.show', [$subCriteria->id]) . '" class="taskView text-darkest-grey f-w-500 openRightModal">' . __('app.view') . '</a>
<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $subCriteria->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="dropdownMenuLink-' . $subCriteria->id . '" tabindex="0">
                                <a class="dropdown-item" href="' . route('sub-criteria.edit', [$subCriteria->id]) . '">
                                    <i class="fa fa-edit mr-2"></i>
                                    ' . trans('app.edit') . '
                                </a>
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-subcriteria-id="' . $subCriteria->id . '">
                                    <i class="fa fa-trash mr-2"></i>
                                    ' . trans('app.delete') . '
                                </a>
                            </div>
                        </div>
                    </div>';

                return $action;
            })
            ->rawColumns(['action', 'check'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SubCriteria $model): QueryBuilder
    {
        $model = $model->select('*');
        $searchText = request()->searchText;

        if (request()->has('searchText') && request()->searchText != '') {

            $model = $model->where('sub_criteria', 'like', '%' . $searchText . '%');
        }

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('subcriteria-table')
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
            'sub_criteria' => ['data' => 'sub_criteria', 'name' => 'sub_criteria', 'title' => __('app.menu.subCriteria')],
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
        return 'SubCriteria_' . date('YmdHis');
    }
}
