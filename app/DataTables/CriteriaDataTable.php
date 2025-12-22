<?php

namespace App\DataTables;

use App\Models\Criteria;
use App\Models\Criterion;
use App\Models\EmployeeDetails;
use App\Models\SubCriteria;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CriteriaDataTable extends BaseDataTable
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
            ->editColumn('criteria', function ($criteria) {
                $employee = new EmployeeDetails();
                $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

                if ($getCustomFieldGroupsWithFields) {
                    $fields = $getCustomFieldGroupsWithFields->fields->where('id', 18);
                }

                if (isset($fields) && count($fields) > 0) {
                    foreach ($fields as $field) {
                        $options = $field->values;
                        $exitReason = $options[$criteria->exit_reason_id] ?? $criteria->exit_reason_id;
                    }
                }

                return $exitReason;
            })
            // ->editColumn('sub_criteria', function ($criteria) {
            //     $subCriteriaIds = $criteria->sub_criteria_ids;
            //     $subCriterias = SubCriteria::whereIn('id', json_decode($subCriteriaIds))->get();

            //     $li = '';

            //     collect($subCriterias)->map(function ($subCriteria) use (&$li) {
            //         $li .= '<li>' . $subCriteria->sub_criteria . '</li>';
            //         return $li;
            //     });

            //     return '<ul>'
            //         . $li .
            //         '</ul>';
            // })
            // ->editColumn('responsible_person', function ($criteria) {
            //     $subCriteriaIds = $criteria->sub_criteria_ids;
            //     $subCriterias = SubCriteria::whereIn('id', json_decode($subCriteriaIds))->get();

            //     $li = '';

            //     collect($subCriterias)->map(function ($subCriteria) use (&$li) {
            //         $li .= '<li>' . $subCriteria->responsible_person . '</li>';
            //         return $li;
            //     });

            //     return '<ul>'
            //         . $li .
            //         '</ul>';
            // })
            // ->editColumn('accountability', function ($criteria) {
            //     $subCriteriaIds = $criteria->sub_criteria_ids;
            //     $subCriterias = SubCriteria::whereIn('id', json_decode($subCriteriaIds))->get();

            //     $li = '';

            //     collect($subCriterias)->map(function ($subCriteria) use (&$li) {
            //         $li .= '<li>' . $subCriteria->accountability . '</li>';
            //         return $li;
            //     });

            //     return '<ul>'
            //         . $li .
            //         '</ul>';
            // })
            // ->editColumn('action_taken', function ($criteria) {

            //     $subCriteriaIds = $criteria->sub_criteria_ids;
            //     $subCriterias = SubCriteria::whereIn('id', json_decode($subCriteriaIds))->get();

            //     $li = '';

            //     collect($subCriterias)->map(function ($subCriteria) use (&$li) {
            //         $li .= '<li>' . $subCriteria->action_taken . '</li>';
            //         return $li;
            //     });

            //     return '<ul>'
            //         . $li .
            //         '</ul>';
            // })
            ->addColumn('action', function ($criteria) {
                $action = '<div class="task_view">
<a href="' . route('criteria.show', [$criteria->id]) . '" class="taskView text-darkest-grey f-w-500 openRightModal">' . __('app.view') . '</a>
<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $criteria->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="dropdownMenuLink-' . $criteria->id . '" tabindex="0">
                                <a class="dropdown-item" href="' . route('criteria.edit', [$criteria->id]) . '">
                                    <i class="fa fa-edit mr-2"></i>
                                    ' . trans('app.edit') . '
                                </a>
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-criteria-id="' . $criteria->id . '">
                                    <i class="fa fa-trash mr-2"></i>
                                    ' . trans('app.delete') . '
                                </a>
                            </div>
                        </div>
                    </div>';

                return $action;
            })
            ->rawColumns(['action', 'check', 'sub_criteria', 'responsible_person', 'accountability', 'action_taken'])
            ->setRowId('id')
            ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Criteria $model): QueryBuilder
    {
        $model = $model->select(
            'criterias.id',
            'criterias.exit_reason_id',
            'sub_criterias.sub_criteria',
            'sub_criterias.responsible_person',
            'sub_criterias.accountability',
            'sub_criterias.action_taken'
        )
            ->leftJoin('sub_criterias', function ($join) {
                $join->on(DB::raw("JSON_CONTAINS(criterias.sub_criteria_ids, CONCAT('\"', sub_criterias.id, '\"'))"), '=', DB::raw('1'));
            });

        $searchText = request()->searchText;

        if (!empty($searchText)) {
            $employee = new EmployeeDetails();
            $fields = optional($employee->getCustomFieldGroupsWithFields())->fields ?? [];
            $exitReasonIds = [];

            foreach ($fields as $field) {
                if ($field->type === 'select' && $field->name === 'exit-reasons-1') {
                    foreach ($field->values as $id => $label) {
                        if (stripos($label, $searchText) !== false) {
                            $exitReasonIds[] = $id;
                        }
                    }
                }
            }

            if (!empty($exitReasonIds)) {
                $model->whereIn('criteria.exit_reason_id', $exitReasonIds);
            } else {
                $model->where('criteria.exit_reason_id', 'like', '%' . $searchText . '%');
            }
        }

        return $model;
    }


    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('criteria-table')
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
            __('app.menu.exitsReason') => ['data' => 'criteria', 'name' => 'criteria', 'title' => __('app.menu.exitsReason')],
            __('app.menu.subCriteria') => ['data' => 'sub_criteria', 'name' => 'sub_criteria', 'title' => __('app.menu.subCriteria')],
            __('app.menu.responsiblePerson') => ['data' => 'responsible_person', 'name' => 'responsible_person', 'title' => __('app.menu.responsiblePerson')],
            __('app.menu.accountability') => ['data' => 'accountability', 'name' => 'accountability', 'title' => __('app.menu.accountability')],
            __('app.menu.actionTaken') => ['data' => 'action_taken', 'name' => 'action_taken', 'title' => __('app.menu.actionTaken')],
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
        return 'Criteria_' . date('YmdHis');
    }
}
