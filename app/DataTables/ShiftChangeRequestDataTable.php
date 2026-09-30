<?php

namespace App\DataTables;

use App\Models\AttendanceSetting;
use App\Models\EmployeeShiftChangeRequest;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ShiftChangeRequestDataTable extends BaseDataTable
{
    private string $managerPermission = 'cannot-approve';
    private bool $finalApprover = false;

    public function dataTable($query)
    {
        $this->managerPermission = AttendanceSetting::first()->shift_manager_permission ?? 'cannot-approve';

        // dd(AttendanceSetting::first()->shift_manager_permission);

        $this->finalApprover = $this->isFinalApprover();

        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('check', fn($row) => $this->finalApprover ? $this->checkBox($row) : '')
            ->addColumn('action', function ($row) {
                if ($row->status !== 'waiting') {
                    return null;
                }

                /*
                 * Normal employees only receive their direct reports from query().
                 * Therefore, a visible row means this user is the reporting manager.
                 */
                $isReportingManager = !$this->finalApprover;
                $alreadyPreApproved = $row->manager_status_permission === 'pre-approve';

                $canApprove = $this->finalApprover || ($isReportingManager && !$alreadyPreApproved && $this->managerPermission === 'approved');
                $canDecline = $this->finalApprover || ($isReportingManager && !$alreadyPreApproved && in_array($this->managerPermission, ['approved', 'pre-approve'], true));
                $canPreApprove = $isReportingManager && !$alreadyPreApproved && $this->managerPermission === 'pre-approve';

                // dd($canPreApprove, $isReportingManager, $alreadyPreApproved, $this->managerPermission);

                if (!$canApprove && !$canDecline && !$canPreApprove) {
                    return null;
                }

                $action = '<div class="task_view"><div class="dropdown">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                if ($canApprove) {
                    $action .= '<a href="javascript:;" class="dropdown-item approve-request" data-request-id="' . $row->id . '"><i class="fa fa-check mr-2"></i>' . __('app.approve') . '</a>';
                }

                if ($canPreApprove) {
                    $action .= '<a href="javascript:;" class="dropdown-item preapprove-request" data-request-id="' . $row->id . '"><i class="fa fa-check-circle mr-2"></i>' . __('app.preApprove') . '</a>';
                }

                if ($canDecline) {
                    $action .= '<a href="javascript:;" class="dropdown-item decline-request" data-request-id="' . $row->id . '"><i class="fa fa-times mr-2"></i>' . __('app.decline') . '</a>';
                }

                return $action . '</div></div></div>';
            })
            ->addColumn('employee_name', fn($row) => $row->shiftSchedule->user?->name)
            ->editColumn('name', function ($row) {
                return view('components.employee', ['user' => $row->shiftSchedule->user]);
            })
            ->editColumn('shift_name', function ($row) {
                return $row->shiftSchedule->shift->shift_name . ' ' . __('app.to') . ' ' . $row->shift->shift_name;
            })
            ->editColumn('shift', function ($row) {
                return '<span class="badge badge-info" style="background-color: ' . $row->shiftSchedule->shift->color . '">' . $row->shiftSchedule->shift->shift_name . '</span> ' . __('app.to') . ' <span class="badge badge-info" style="background-color: ' . $row->shift->color . '">' . $row->shift->shift_name . '</span>';
            })
            ->editColumn('date', fn($row) => $row->shiftSchedule->date->translatedFormat(company()->date_format))
            ->editColumn('status', function ($row) {
                $status = ucfirst($row->status);

                if ($row->status === 'waiting' && $row->manager_status_permission === 'pre-approve') {
                    $status .= '<div class="mt-1"><span class="badge badge-success">' . __('modules.attendance.preApproved') . '</span></div>';
                }

                return $status;
            })
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['action', 'name', 'check', 'shift', 'status']);
    }

    public function query(EmployeeShiftChangeRequest $model)
    {
        $request = $this->request();
        $employee = $request->employee;
        $shift = $request->shift_id;
        $status = $request->filterStatus;

        $model = $model->with([
            'shift',
            'shiftSchedule.shift',
            'shiftSchedule.user.employeeDetails'
        ])
            ->join('employee_shift_schedules', 'employee_shift_schedules.id', '=', 'employee_shift_change_requests.shift_schedule_id')
            ->join('users', 'users.id', '=', 'employee_shift_schedules.user_id')
            ->join('employee_shifts', 'employee_shifts.id', '=', 'employee_shift_schedules.employee_shift_id');

        if (!empty($request->startDate) && $request->startDate !== 'null') {
            $startDate = companyToDateString($request->startDate);

            if (!is_null($startDate)) {
                $model->whereDate('employee_shift_change_requests.created_at', '>=', $startDate);
            }
        }

        if (!empty($request->endDate) && $request->endDate !== 'null') {
            $endDate = companyToDateString($request->endDate);

            if (!is_null($endDate)) {
                $model->whereDate('employee_shift_change_requests.created_at', '<=', $endDate);
            }
        }

        if (!is_null($employee) && $employee !== 'all') {
            $model->where('employee_shift_schedules.user_id', $employee);
        }

        if (!is_null($shift) && $shift !== 'all') {
            $model->where('employee_shift_change_requests.employee_shift_id', $shift);
        }

        if (!is_null($status) && $status !== 'all') {
            $model->where('employee_shift_change_requests.status', $status);
        }

        /*
         * Admin and HR Manager see all requests.
         * Normal employees only see requests belonging to their direct reports.
         */
        if (!$this->isFinalApprover()) {
            $model->whereHas('shiftSchedule.user.employeeDetails', function ($query) {
                $query->where('reporting_to', user()->id);
            });
        }

        return $model->select('employee_shift_change_requests.*')->whereHas('shiftSchedule.user');
    }

    private function isFinalApprover(): bool
    {
        return user()->hasRole('admin') || user()->hasRole('hr-manager') || user()->hasRole('hr-officer');
    }

    public function html()
    {
        $dataTable = $this->setBuilder('shift-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["shift-table"].buttons().container().appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function(oSettings) {
                    $(".select-picker").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(
                Button::make([
                    'extend' => 'excel',
                    'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')
                ])
            );
        }

        return $dataTable;
    }

    protected function getColumns()
    {
        return [
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false,
                'visible' => $this->isFinalApprover(),
            ],
            '#' => [
                'data' => 'DT_RowIndex',
                'orderable' => false,
                'searchable' => false,
                'visible' => !showId()
            ],
            __('app.id') => [
                'data' => 'id',
                'name' => 'id',
                'title' => __('#'),
                'visible' => showId()
            ],
            __('app.employee') => [
                'data' => 'name',
                'name' => 'users.name',
                'exportable' => false,
                'title' => __('app.employee')
            ],
            __('app.name') => [
                'data' => 'employee_name',
                'name' => 'name',
                'visible' => false,
                'title' => __('app.name')
            ],
            __('modules.attendance.shiftName') => [
                'data' => 'shift_name',
                'name' => 'shift_name',
                'title' => __('modules.attendance.shift'),
                'visible' => false
            ],
            __('modules.attendance.shift') => [
                'data' => 'shift',
                'name' => 'shift_name',
                'title' => __('modules.attendance.shift'),
                'exportable' => false
            ],
            __('app.date') => [
                'data' => 'date',
                'name' => 'date',
                'title' => __('app.date')
            ],
            __('app.reason') => [
                'data' => 'reason',
                'name' => 'reason',
                'title' => __('app.reason')
            ],
            __('app.status') => [
                'data' => 'status',
                'name' => 'status',
                'title' => __('app.status')
            ],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
    }
}
