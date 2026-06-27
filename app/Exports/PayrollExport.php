<?php

namespace App\Exports;

use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize, WithMapping, WithStrictNullComparison
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public $year;
    public $payrollCycle;
    public $month;
    public $searchText;
    public $payrollMonth;
    public $startDate;
    public $endDate;
    // public $monthKey;

    public $viewPayrollPermission;

    public function __construct($year = null, $payrollCycle = null, $month = null, $searchText = null)
    {
        $this->year = $year;
        $this->payrollCycle = $payrollCycle;
        $this->month = $month;
        $this->searchText = $searchText;

        if (!is_null($this->month) && $this->month != 'null' && $this->month != '') {
            $month = explode(' ', $this->month);

            $this->startDate = Carbon::parse($month[0])->subMonth()->setDay(26);
            $this->endDate = Carbon::parse($month[1])->setDay(25);
        }

        $this->payrollMonth = Carbon::parse($this->endDate)->format('M');
        $this->viewPayrollPermission = user()->permission('view_payroll');
    }

    public function collection()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $payrollMonth = Carbon::createFromFormat('M', $this->payrollMonth)->month;

        // dd($month, $this->payrollMonth);

        $isAdmin = false;
        $isHRManager = false;
        $isHROfficer = false;

        if (in_array('admin', user_roles())) {
            $isAdmin = true;
        }

        if (in_array('hr-manager', user_roles())) {
            $isHRManager = true;
        }

        if (in_array('hr-officer', user_roles())) {
            $isHROfficer = true;
        }

        $salarySlips = User::withoutGlobalScope(ActiveScope::class)
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id')
            ->leftJoin('locations', 'users.location_id', '=', 'locations.id')
            ->leftJoin('teams', 'users.department_id', '=', 'teams.id')
            ->join('salary_slips', 'salary_slips.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
            ->join('payroll_cycles', 'payroll_cycles.id', '=', 'employee_payroll_cycles.payroll_cycle_id')
            ->select('users.id', 'users.name', 'users.email', 'users.image', 'designations.name as designation_name', 'salary_slips.*', 'designations.rank_id', 'locations.location_name', 'teams.team_name')
            ->where('roles.name', '<>', 'client')
            ->where('salary_slips.payroll_cycle_id', $this->payrollCycle)
            ->where('salary_slips.year', $this->year)
            ->where('users.status', 'active')
            ->when(in_array('employee', user_roles()) && count(user_roles()) === 1, function ($query) {
                $query->where('salary_slips.status', '<>', 'generated')
                    ->where('users.id', user()->id);
            })
            ->when(($isHRManager || $isHROfficer) && !$isAdmin, function ($query) {
                $query->where(function ($q) {
                    $q->where('users.id', user()->id)
                        ->orWhere(function ($q) {
                            $q->whereNotNull('designations.rank_id')
                                ->where('designations.rank_id', '<=', 4);
                        });
                });
            });

        if (!is_null($payrollMonth) && $payrollMonth != 'null' && $payrollMonth != '') {
            $salarySlips = $salarySlips->whereRaw('MONTH(salary_slips.salary_to) = ?', [$payrollMonth]);
        }

        if ($this->viewPayrollPermission == 'owned') {
            $salarySlips = $salarySlips->where('users.id', user()->id);
        }

        if ($this->viewPayrollPermission == 'both') {
            $salarySlips = $salarySlips->where(function ($query) {
                $query->where('users.id', user()->id)
                    ->orWhere('salary_slips.added_by', user()->id);
            });
        }

        $salarySlips->groupBy('users.id');

        return $salarySlips->get();
    }

    public function headings(): array
    {
        return [
            '#',
            __('app.name'),
            __('payroll::modules.payroll.rank'),
            __('app.location'),
            __('app.menu.teams'),
            __('app.status'),
            __('payroll::modules.payroll.duration'),
            __('payroll::modules.payroll.basicPay'),
            __('payroll::modules.payroll.actualBasicSalary') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.technicalAllowance') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.livingCostAllowance') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.specialAllowance') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.otherAllowance') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.depositRefund') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.Overtime'),
            __('payroll::modules.payroll.offDayHolidaySalary'),
            __('payroll::modules.payroll.gazattedAllowance'),
            __('payroll::modules.payroll.eveningShiftAllowance'),
            __('payroll::modules.payroll.absent'),
            __('payroll::modules.payroll.leaveWithoutPay'),
            __('payroll::modules.payroll.afterLateDetection'),
            __('payroll::modules.payroll.betweenLateDetection'),
            __('payroll::modules.payroll.creditSales'),
            __('payroll::modules.payroll.deposit'),
            __('payroll::modules.payroll.loan'),
            __('payroll::modules.payroll.ssb'),
            __('payroll::modules.payroll.incomeTax'),
            __('payroll::modules.payroll.otherDetection'),
            __('payroll::modules.payroll.totalAllowance'),
            __('payroll::modules.payroll.totalDeductions'),
            __('payroll::modules.payroll.netSalary'),
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
        // dd($row->toArray());
        static $index = 0;

        $netSalary = $row->gross_salary - $row->total_deductions;

        return [
            ++$index,
            $row->name,
            __('payroll::modules.payroll.rank') . ' - ' . $row->rank_id ,
            $row->location_name,
            $row->team_name,
            $row->status,
            Carbon::parse($row->salary_from)->format('Y-m-d') . ' to ' . Carbon::parse($row->salary_to)->format('Y-m-d'),
            (float) ($row->basic_salary ?? 0),
            (float) ($row->monthly_salary ?? 0),
            (float) ($row->technical_allowance ?? 0),
            (float) ($row->living_cost_allowance ?? 0),
            (float) ($row->special_allowance ?? 0),
            (float) ($row->other_allowance ?? 0),
            (float) ($row->deposit_refund ?? 0),
            (float) ($row->overtime_amount ?? 0),
            (float) ($row->off_day_holiday_salary ?? 0),
            (float) ($row->gazatted_allowance ?? 0),
            (float) ($row->evening_shift_allowance ?? 0),
            (float) ($row->absent ?? 0),
            (float) ($row->leave_without_pay_detection ?? 0),
            (float) ($row->after_late_detection ?? 0),
            (float) ($row->between_late_detection ?? 0),
            (float) ($row->credit_sales ?? 0),
            (float) ($row->deposit ?? 0),
            (float) ($row->loan ?? 0),
            (float) ($row->ssb ?? 0),
            (float) ($row->income_tax ?? 0),
            (float) ($row->other_detection ?? 0),
            (float) ($row->gross_salary ?? 0),
            (float) ($row->total_deductions ?? 0),
            (float) ($netSalary ?? 0),
        ];
    }
}
