<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Payroll\Entities\SalarySlip;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize, WithMapping
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
    }

    public function collection()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $salarySlips = SalarySlip::select('users.name', 'locations.location_name', 'teams.team_name', 'salary_slips.*')
            ->leftJoin('users', 'users.id', '=', 'salary_slips.user_id')
            ->leftJoin('locations', 'users.location_id', '=', 'locations.id')
            ->leftJoin('teams', 'users.department_id', '=', 'teams.id')
            ->where('salary_slips.salary_from', '>=', $startDate)
            ->where('salary_slips.salary_to', '>=', $endDate);

        return $salarySlips->get();
    }

    public function headings(): array
    {
        return [
            '#',
            __('app.name'),
            __('app.location'),
            __('app.menu.teams'),
            __('payroll::modules.payroll.duration'),
            __('payroll::modules.payroll.basicPay'),
            __('payroll::modules.payroll.actualBasicSalary') . ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.technicalAllowance'). ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.livingCostAllowance'). ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.specialAllowance'). ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.otherAllowance'). ' for ' . $this->payrollMonth,
            __('payroll::modules.payroll.depositRefund'). ' for ' . $this->payrollMonth,
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
        static $index = 0;

        $netSalary = $row->gross_salary - $row->total_deductions;

        return [
            ++$index,
            $row->name,
            $row->location_name,
            $row->team_name,
            Carbon::parse($row->salary_from)->format('Y-m-d') . ' to ' . Carbon::parse($row->salary_to)->format('Y-m-d'),
            $row->basic_salary,
            $row->monthly_salary,
            $row->technical_allowance,
            $row->living_cost_allowance,
            $row->special_allowance,
            $row->other_allowance,
            $row->deposit_refund,
            $row->overtime_amount,
            $row->off_day_holiday_salary,
            $row->gazatted_allowance,
            $row->evening_shift_allowance,
            $row->absent,
            $row->leave_without_pay_detection,
            $row->after_late_detection,
            $row->between_late_detection,
            $row->credit_sales,
            $row->deposit,
            $row->loan,
            $row->ssb,
            $row->income_tax,
            $row->other_detection,
            $row->gross_salary,
            $row->total_deductions,
            $netSalary,
        ];
    }
}
