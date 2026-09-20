<?php

namespace Modules\Payroll\DataTables;

use App\DataTables\BaseDataTable;
use App\Models\AttendanceSetting;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Html\Column;
use Modules\Payroll\Entities\OvertimeRequest;
use Modules\Payroll\Entities\OvertimeSetting;
use Modules\Payroll\Entities\PayrollSetting;

class OvertimeRequestDataTable extends BaseDataTable
{
    private $roleId;
    private $payrollSetting;
    private $payCode;
    private $managerPermission;

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->roleId = self::getUserSecondRole();
        $this->payrollSetting = PayrollSetting::first();
        $this->managerPermission = OvertimeSetting::current()->manager_permission;

        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                $allowRoles =
                    $row->policy->allow_roles ?? [];

                $this->payCode =
                    $row->policy->payCode;

                $isAdminOrHr =
                    user()->hasRole('admin') ||
                    user()->hasRole('hr-officer') ||
                    user()->hasRole('hr-manager');

                $isAllowedRole = in_array(
                    $this->roleId,
                    $allowRoles
                );

                $isReportingManager =
                    (int) $row->reporting_to ===
                    (int) user()->id &&
                    (int) $row->user_id !==
                    (int) user()->id;

                $isFinalApprover =
                    $isAdminOrHr ||
                    $isAllowedRole;


                if ($row->status !== 'pending') {
                    if ($row->status === 'reject') {
                        $statusColor = 'danger';
                        $status = __('app.rejected');
                    } else {
                        $statusColor = 'success';
                        $status = __('app.accepted');
                    }

                    $actionBy =
                        $row->actionByName ?? '--';

                    return
                        '<i class="mr-1 fa fa-circle text-' .
                        $statusColor .
                        '"></i>' .
                        $status .
                        '<br>' .
                        '<p>' .
                        __('payroll::modules.payroll.actionBy') .
                        ' : ' .
                        e($actionBy) .
                        '</p>';
                }

                $actions = '';

                if (
                    $row->manager_status_permission ===
                    'pre-approve'
                ) {
                    $actions .=
                        '<div class="mb-2">' .
                        '<i class="mr-1 fa fa-circle ' .
                        'text-warning"></i>' .
                        __('app.pending') .
                        '<br>' .
                        '<span class="badge badge-success">' .
                        __('payroll::messages.preApproved') .
                        '</span>' .
                        '</div>';
                }

                /*
     * Admin, HR and allowed policy roles perform
     * the final action.
     */
                if ($isFinalApprover) {
                    $actions .=
                        '<button type="button" ' .
                        'data-request-id="' . $row->id . '" ' .
                        'data-type="edit" ' .
                        'class="btn-primary btn-sm rounded ' .
                        'f-14 p-2 editRequest mr-1">' .
                        '<i class="fa fa-edit"></i>' .
                        '</button>';

                    $actions .=
                        '<button type="button" ' .
                        'data-request-id="' . $row->id . '" ' .
                        'data-type="reject" ' .
                        'class="btn-danger btn-sm rounded ' .
                        'f-14 p-2 acceptButton mr-1">' .
                        '<i class="fa fa-times mr-1"></i>' .
                        __('app.reject') .
                        '</button>';

                    $actions .=
                        '<button type="button" ' .
                        'data-request-id="' . $row->id . '" ' .
                        'data-type="accept" ' .
                        'class="btn-primary btn-sm rounded ' .
                        'f-14 p-2 acceptButton">' .
                        '<i class="fa fa-check mr-1"></i>' .
                        __('app.accept') .
                        '</button>';

                    return $actions;
                }

                /*
     * Reporting manager actions
     */
                if (!$isReportingManager) {
                    return $actions;
                }

                if (
                    $this->managerPermission ===
                    'cannot-approve'
                ) {
                    return $actions;
                }

                /*
     * Do not allow the manager to act twice.
     */
                if (
                    $row->manager_status_permission ===
                    'pre-approve'
                ) {
                    return $actions;
                }

                /*
     * Reporting manager can reject under both
     * Approve and Pre-approve settings.
     */
                $actions .=
                    '<button type="button" ' .
                    'data-request-id="' . $row->id . '" ' .
                    'data-type="reject" ' .
                    'class="btn-danger btn-sm rounded ' .
                    'f-14 p-2 acceptButton mr-1">' .
                    '<i class="fa fa-times mr-1"></i>' .
                    __('app.reject') .
                    '</button>';

                /*
     * Reporting manager gives final acceptance.
     */
                if (
                    $this->managerPermission ===
                    'approved'
                ) {
                    $actions .=
                        '<button type="button" ' .
                        'data-request-id="' . $row->id . '" ' .
                        'data-type="accept" ' .
                        'class="btn-primary btn-sm rounded ' .
                        'f-14 p-2 acceptButton">' .
                        '<i class="fa fa-check mr-1"></i>' .
                        __('app.accept') .
                        '</button>';
                }

                /*
     * Reporting manager only pre-approves.
     */
                if (
                    $this->managerPermission ===
                    'pre-approve'
                ) {
                    $actions .=
                        '<button type="button" ' .
                        'data-request-id="' . $row->id . '" ' .
                        'data-type="pre-approve" ' .
                        'class="btn-success btn-sm rounded ' .
                        'f-14 p-2 preApproveButton">' .
                        '<i class="fa fa-check mr-1"></i>' .
                        __('app.preApprove') .
                        '</button>';
                }

                return $actions;
            })

            ->editColumn('user_id', function ($row) {
                return view('components.employee', [
                    'user' => $row->user
                ]);
            })

            ->editColumn('date', function ($row) {
                return $row->date->format(company()->date_format) ?? '--';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at->format(company()->date_format) ?? '--';
            })

            ->editColumn('overtime_reason', function ($row) {
                return $row->overtime_reason ? '<p data-toggle="tooltip" data-original-title="' . $row->overtime_reason . '">' . mb_strimwidth($row->overtime_reason, 0, 40, '...') . '</p>' : '--';
            })

            ->editColumn('hours', function ($row) {
                $hours = $row->hours . ' ' . __('app.hour');
                $minutes = ($row->minutes != 0) ? $row->minutes . ' ' . __('app.minute') : '';
                return $hours . ' ' . $minutes;
            })

            ->editColumn('amount', function ($row) {
                $currencySymbol = ($this->payrollSetting->currency ? $this->payrollSetting->currency->currency_symbol : company()->currency->currency_symbol);
                $calculation = '';

                if ($row->policy->payCode->fixed == 1) {
                    $hourlyRate = $row->fixed_amount;
                } else {
                    $hourlyRate = $row->user->employeeDetail->overtime_hourly_rate;
                }

                $minutes = round(((($row->hours * 60) + $row->minutes) / 60), 1);

                $amount = $row->amount;
                // $hours = $row->hours;

                // if ($row->holiday_date || $row->employee_shift_id == 1) {
                //     // $amount = $hours * $hourlyRate * 2;
                //     $calculation = '( ' . $hourlyRate . ' ( * 2 ' . __('payroll::app.times') . ') * ' . $minutes . ')';
                // }

                if ($row->policy->payCode->fixed == 1) {
                    $calculation = '( ' . $hourlyRate . ' ( ' . $row->overtime_rate . ' * ' . $row->fixed_amount . ' * ' . $minutes . ')';
                } else {
                    $calculation = '( ' . $hourlyRate . ' ( ' . $row->overtime_rate . ' * ' . $row->policy->payCode->time . ' ' . __('payroll::app.times') . ') * ' . $minutes . ')';
                }

                return $currencySymbol . ' ' . $amount . ' ' . $calculation;
            })

            ->addIndexColumn()
            ->rawColumns(['action', 'overtime_reason', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    //phpcs:ignore
    public function query(OvertimeRequest $model)
    {
        $request = $this->request();

        $roleId = self::getUserSecondRole();

        $overtimeRequest = $model->with('actionBy', 'user', 'company', 'policy', 'policy.payCode')
            ->select('overtime_requests.*', 'users.name', 'users.email', 'actionby.email', 'actionby.name as actionByName', 'pay_codes.fixed', 'pay_codes.fixed_amount', 'employee_details.overtime_hourly_rate', 'employee_details.reporting_to', 'users.location_id', 'users.department_id', 'users.designation_id', DB::raw('COALESCE(employee_shift_schedules.employee_shift_id, (SELECT default_employee_shift FROM attendance_settings LIMIT 1)) as employee_shift_id'), 'holidays.date as holiday_date')
            ->leftJoin('users', 'users.id', '=', 'overtime_requests.user_id')
            ->leftJoin('overtime_policy_employees', 'users.id', '=', 'overtime_policy_employees.user_id')
            ->leftJoin('overtime_policies', 'overtime_policies.id', '=', 'overtime_policy_employees.overtime_policy_id')
            ->leftJoin('pay_codes', 'pay_codes.id', '=', 'overtime_policies.pay_code_id')
            ->leftJoin('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('users as actionby', 'actionby.id', '=', 'overtime_requests.action_by')
            ->leftJoin('employee_shift_schedules', function ($join) {
                $join->on('employee_shift_schedules.user_id', '=', 'users.id')
                    ->whereRaw('employee_shift_schedules.date = overtime_requests.date');
            })
            ->leftJoin('holidays', 'holidays.date', '=', 'overtime_requests.date')
            ->where('users.status', 'active');

        if (!in_array('admin', user_roles()) && !in_array('hr-officer', user_roles()) && !in_array('hr-manager', user_roles())) {
            $overtimeRequest = $overtimeRequest->where(function ($query) use ($roleId) {
                $query->where('overtime_requests.user_id', user()->id)
                    ->orWhereHas('policy', function ($query) use ($roleId) {
                        $query->where(function ($q) use ($roleId) {
                            // Allow roles
                            $q->where('allow_roles', 'like', '%"' . $roleId . '"%');
                        })->orWhere(function ($q) {
                            // Reporting manager
                            $q->where('allow_reporting_manager', 0)
                                ->orWhere(function ($qm) {
                                    $qm->where('allow_reporting_manager', 1)
                                        ->where('employee_details.reporting_to', user()->id);
                                });
                        });
                    });
            });
        }

        if ($request->location != 'all' && $request->location != '') {
            $overtimeRequest = $overtimeRequest->where('users.location_id', $request->location);
        }

        if ($request->department != 'all' && $request->department != '') {
            $overtimeRequest = $overtimeRequest->where('users.department_id', $request->department);
        }

        if ($request->designation != 'all' && $request->designation != '') {
            $overtimeRequest = $overtimeRequest->where('users.designation_id', $request->designation);
        }

        if ($request->year != 'all' && $request->year != '') {
            $overtimeRequest = $overtimeRequest->whereYear('overtime_requests.date', $request->year);
        }

        if ($request->month != 'all' && $request->month != '') {
            $overtimeRequest = $overtimeRequest->whereMonth('overtime_requests.date', $request->month);
        }

        // dd($request->employee);

        if ($request->employee != 'all' && $request->employee != '') {
            $overtimeRequest = $overtimeRequest->where('overtime_requests.user_id', $request->employee);
        }

        return $overtimeRequest;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return parent::setBuilder('overtime-request')
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["overtime-request"].buttons().container()
                     .appendTo( "#table-actions")
                 }',
                'fnDrawCallback' => 'function( oSettings ) {
                   //
                   $(".select-picker").selectpicker();
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                 }',
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            __('app.employee') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.employee')],
            __('payroll::modules.payroll.requestDate') => ['data' => 'created_at', 'name' => 'created_at', 'title' => __('payroll::modules.payroll.requestDate')],
            __('payroll::modules.payroll.overtimeDate') => ['data' => 'date', 'name' => 'date', 'title' => __('payroll::modules.payroll.overtimeDate')],
            __('payroll::modules.payroll.duration') => ['data' => 'hours', 'name' => 'hours', 'title' => __('payroll::modules.payroll.duration')],
            __('app.reason') => ['data' => 'overtime_reason', 'name' => 'overtime_reason', 'title' => __('app.reason')],
            __('app.amount') => ['data' => 'amount', 'name' => 'amount', 'title' => __('app.amount')],

            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
    }

    static public function getUserSecondRole()
    {
        $roles = Role::all();

        $roleIds = user()->roles()->pluck('role_id')->toArray();

        if (count($roleIds) > 1) {
            if (isset($roleIds[1])) {
                $userRole = $roles->filter(function ($value, $key) use ($roleIds) {
                    return $value->id == $roleIds[1];
                })->first();

                $userSecondRole = ($userRole->name != 'employee') ? $roleIds[1] : $roleIds[0];
            } else {
                $userSecondRole = $roleIds[0];
            }
        } else {
            $userSecondRole = $roleIds[0];
        }

        return $userSecondRole;
    }
}
