<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Payroll\Entities\OvertimeRequest;
use Modules\Payroll\Entities\PayrollSetting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimeRequestExport implements FromCollection, ShouldAutoSize, WithStyles, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public $employee;
    public $location;
    public $department;
    public $designation;
    public $year;
    public $month;
    public $roleId;

    public function __construct($employee, $location, $department, $designation, $year, $month, $roleId)
    {
        $this->employee = $employee;
        $this->location = $location;
        $this->department = $department;
        $this->designation = $designation;
        $this->year = $year;
        $this->month = $month;
        $this->roleId = $roleId;
    }

    public function collection()
    {
        $roleId = $this->roleId;
        $roles = user_roles();

        $isAdmin = array_filter($roles, fn($r) => $r === "admin");
        $isHRmanager = array_filter($roles, fn($r) => $r === "hr-manager");

        $overtimeRequest = OvertimeRequest::with('actionBy', 'user', 'company', 'policy', 'policy.payCode')
            ->select('overtime_requests.*', 'users.name', 'users.email', 'actionby.email', 'actionby.name as actionByName', 'pay_codes.fixed', 'pay_codes.fixed_amount', 'employee_details.overtime_hourly_rate', 'users.location_id', 'users.department_id', 'users.designation_id', DB::raw('COALESCE(employee_shift_schedules.employee_shift_id, (SELECT default_employee_shift FROM attendance_settings LIMIT 1)) as employee_shift_id'), 'holidays.date as holiday_date')
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
            ->leftJoin('holidays', 'holidays.date', '=', 'overtime_requests.date');

        if (!in_array('admin', $roles)) {
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

        if (!$isAdmin && !$isHRmanager) {
            $overtimeRequest = $overtimeRequest->where('overtime_requests.user_id', auth()->user()->id);
        }

        if ($this->location != 'all' && $this->location != '') {
            $overtimeRequest = $overtimeRequest->where('users.location_id', $this->location);
        }

        if ($this->department != 'all' && $this->department != '') {
            $overtimeRequest = $overtimeRequest->where('users.department_id', $this->department);
        }

        if ($this->designation != 'all' && $this->designation != '') {
            $overtimeRequest = $overtimeRequest->where('users.designation_id', $this->designation);
        }

        if ($this->year != 'all' && $this->year != '') {
            $overtimeRequest = $overtimeRequest->whereYear('overtime_requests.date', $this->year);
        }

        if ($this->month != 'all' && $this->month != '') {
            $overtimeRequest = $overtimeRequest->whereMonth('overtime_requests.date', $this->month);
        }

        if ($this->employee != 'all' && $this->employee != '') {
            $overtimeRequest = $overtimeRequest->whereMonth('overtime_requests.user_id', $this->employee);
        }

        return $overtimeRequest->get();
    }


    public function headings(): array
    {
        return [
            __('app.employee'),
            __('payroll::modules.payroll.requestDate'),
            __('payroll::modules.payroll.overtimeDate'),
            __('payroll::modules.payroll.duration'),
            __('app.reason'),
            __('app.amount'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ["font" => ["bold" => true]]
        ];
    }

    public function map($row): array
    {
        $hours = $row->hours . ' ' . __('app.hour');
        $minutes = ($row->minutes != 0) ? $row->minutes . ' ' . __('app.minute') : '';

        $payrollSetting = PayrollSetting::first();

        $currencySymbol = ($payrollSetting->currency ? $payrollSetting->currency->currency_symbol : company()->currency->currency_symbol);
        $calculation = '';

        if ($row->policy->payCode->fixed == 1) {
            $hourlyRate = $row->fixed_amount;
        } else {
            $hourlyRate = $row->user->employeeDetail->overtime_hourly_rate;
        }

        $minutes = round(((($row->hours * 60) + $row->minutes) / 60), 1);

        $amount = $row->amount;

        if ($row->holiday_date || $row->employee_shift_id == 1) {
            $amount = $hourlyRate * 2;
            $calculation = '( ' . $hourlyRate . ' ( * 2 ' . __('payroll::app.times') . ') * ' . $minutes . ')';
        } elseif ($row->policy->payCode->fixed == 1) {
            $calculation = '( ' . $hourlyRate . ' ( *' . $row->fixed_amount . ' * ' . $minutes . ')';
        } else {
            $calculation = '( ' . $hourlyRate . ' ( *' . $row->policy->payCode->time . ' ' . __('payroll::app.times') . ') * ' . $minutes . ')';
        }


        return [
            $row->name,
            $row->created_at->format(company()->date_format) ?? '--',
            $row->date->format(company()->date_format) ?? '--',
            $hours . ' ' . $minutes,
            $row->overtime_reason,
            $currencySymbol . ' ' . $amount . ' ' . $calculation
        ];
    }
}
