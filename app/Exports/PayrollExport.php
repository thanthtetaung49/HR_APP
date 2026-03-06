<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use MacsiDigital\OAuth2\Support\Token\DB;
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

    public function __construct($year = null, $payrollCycle = null, $month = null, $searchText = null)
    {
        $this->year = $year;
        $this->payrollCycle = $payrollCycle;
        $this->month = $month;
        $this->searchText = $searchText;
    }

    public function collection()
    {
        $startDate = null;
        $endDate = null;

        if (!is_null($this->month) && $this->month != 'null' && $this->month != '') {
            $month = explode(' ', $this->month);

            $startDate = Carbon::parse($month[0])->subMonth()->setDay(26);
            $endDate = Carbon::parse($month[1])->setDay(25);
        }

        $salarySlips = SalarySlip::select('users.name', 'salary_slips.*')
            ->leftJoin('users', 'users.id', '=', 'salary_slips.user_id')
            ->where('salary_slips.salary_from', '>=', $startDate)
            ->where('salary_slips.salary_to', '>=', $endDate);

        return $salarySlips->get();
    }

    public function headings(): array
    {
        return [
            '#',
            __('app.name'),
            __('payroll::modules.payroll.duration'),
            __('payroll::modules.payroll.basicPay'),
            __('payroll::modules.payroll.actualBasicSalary'),
            __('payroll::modules.payroll.technicalAllowance'),
            __('payroll::modules.payroll.livingCostAllowance'),
            __('payroll::modules.payroll.specialAllowance'),
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
            Carbon::parse($row->salary_from)->format('Y-m-d') . ' to ' . Carbon::parse($row->salary_to)->format('Y-m-d'),
            $row->basic_salary ? $row->basic_salary : 0,
            $row->monthly_salary ? $row->monthly_salary : 0,
            $row->technical_allowance ? $row->technical_allowance : 0,
            $row->living_cost_allowance ? $row->living_cost_allowance : 0,
            $row->special_allowance ? $row->special_allowance : 0,
            $row->overtime_amount ? $row->overtime_amount : 0,
            $row->off_day_holiday_salary ? $row->off_day_holiday_salary : 0,
            $row->gazatted_allowance ? $row->gazatted_allowance : 0,
            $row->evening_shift_allowance ? $row->evening_shift_allowance : 0,
            $row->absent ? $row->absent : 0,
            $row->leave_without_pay_detection ? $row->leave_without_pay_detection : 0,
            $row->after_late_detection ? $row->after_late_detection : 0,
            $row->between_late_detection ? $row->between_late_detection : 0,
            $row->credit_sales ? $row->credit_sales : 0,
            $row->deposit ? $row->deposit : 0,
            $row->loan ? $row->loan : 0,
            $row->ssb ? $row->ssb : 0,
            $row->other_detection ? $row->other_detection : 0,
            $row->gross_salary ? $row->gross_salary : 0,
            $row->total_deductions ? $row->total_deductions : 0,
            $netSalary ? $netSalary : 0,
        ];
    }
}
