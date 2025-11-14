<?php

namespace App\Exports;

use App\Models\User;
use App\Models\EmployeeDetails;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BankReportExport implements FromCollection, ShouldAutoSize, WithStyles, WithHeadings, WithMapping
{
    public $location;
    public $month;
    public $year;

    public function __construct($location, $month, $year)
    {
        $this->location = $location;
        $this->month = $month;
        $this->year = $year;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $model = User::select('users.id as id', 'users.name as name', 'users.bank_account_number as bank_account_number', 'salary_slips.net_salary as net_salary', 'locations.location_name as location_name', 'locations.id as location_id', 'salary_slips.year', 'salary_slips.month', DB::raw('CAST(salary_slips.month AS SIGNED) + 1 as test'))
            ->leftJoin('salary_slips', 'salary_slips.user_id', 'users.id')
            ->leftJoin('employee_details', 'employee_details.user_id', 'users.id')
            ->leftJoin('teams', 'employee_details.department_id', 'teams.id')
            ->leftJoin('locations', 'teams.location_id', 'locations.id')
            ->where('salary_slips.year', $this->year)
            ->where(DB::raw('CAST(salary_slips.month AS SIGNED) + 1'), $this->month);


        if (isset($this->location) && $this->location != 'all') {
            $model->where('locations.id', $this->location);
        }

        return $model->get();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ["font" => ["bold" => true]]
        ];
    }

    public function headings(): array
    {
        return [
            "#",
            __('app.menu.employees'),
            __('app.menu.nrc'),
            __('app.menu.bankaccountNumber'),
            __('app.menu.amount')
        ];
    }

    public function map($row): array
    {
        static $index = 0;

        $employee = EmployeeDetails::where('user_id', $row['id'])->first();

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

        $netSalary = $row['net_salary'] ? $netSalary = $row['net_salary'] . ' MMK' : 0 . ' MMK';

        return [
            ++$index,
            $row['name'],
            $nrc,
            $row['bank_account_number'],
            $netSalary,
        ];
    }
}
