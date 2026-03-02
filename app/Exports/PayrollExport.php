<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
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

        $users = User::join('role_user', 'role_user.user_id', '=', 'users.id')
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id')
            ->join('salary_slips', 'salary_slips.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
            ->join('payroll_cycles', 'payroll_cycles.id', '=', 'employee_payroll_cycles.payroll_cycle_id')
            ->select('users.id', 'users.name', 'users.email', 'users.image', 'designations.name as designation_name', 'salary_slips.net_salary', 'salary_slips.gross_salary', 'salary_slips.paid_on', 'salary_slips.status as salary_status', 'salary_slips.id as salary_slip_id', 'salary_slips.added_by', 'salary_slips.month', 'salary_slips.year', 'salary_slips.currency_id', 'salary_slips.salary_from', 'salary_slips.salary_to', 'salary_slips.total_deductions')
            ->where('roles.name', '<>', 'client')
            ->where('salary_slips.payroll_cycle_id', $this->payrollCycle)
            ->where('salary_slips.year', $this->year);


        if (!is_null($startDate) && !is_null($endDate)) {
            $users = $users->whereRaw('Date(salary_slips.salary_from) = ?', [$startDate]);
            $users = $users->whereRaw('Date(salary_slips.salary_to) = ?', [$endDate]);
        }

        if ($this->searchText != '') {
            $users = $users->where(function ($query) {
                $query->where('users.name', 'like', '%' . $this->searchText . '%')
                    ->orWhere('users.email', 'like', '%' . $this->searchText . '%');
            });
        }


        $users->groupBy('users.id');

        return $users->get();
    }

    public function headings(): array
    {
        return [
            '#',
            __('app.name'),
            __('payroll::modules.payroll.netSalary'),
            __('payroll::modules.payroll.earning'),
            __('payroll::modules.payroll.totalDeductions'),
            __('payroll::modules.payroll.duration'),
            __('modules.payments.paidOn'),
            __('app.status'),
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

        return [
            ++$index,
            $row->name,
            $row->net_salary,
            $row->gross_salary,
            $row->total_deductions,
            Carbon::parse($row->salary_from)->format('d M Y') . ' to ' . Carbon::parse($row->salary_to)->format('d M Y'),
            Carbon::parse($row->paid_on)->format('d M Y'),
            ucfirst($row->salary_status)
        ];
    }
}
