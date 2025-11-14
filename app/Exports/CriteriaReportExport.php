<?php

namespace App\Exports;

use App\Models\EmployeeDetails;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CriteriaReportExport implements FromCollection, ShouldAutoSize, WithStyles, WithHeadings, WithMapping
{
    public $location;
    public $department;
    public $designation;

    public function __construct($location, $department, $designation)
    {
        $this->location = $location;
        $this->department = $department;
        $this->designation = $designation;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $model = EmployeeDetails::select('users.name as employee_name', 'employee_details.notice_period_end_date', 'sub_criterias.action_taken', 'sub_criterias.responsible_person as responsible_person', 'sub_criterias.accountability as accountability', 'sub_criterias.sub_criteria as sub_criteria', 'criterias.exit_reason_id as exit_reason_id', 'teams.id as team_id', 'designations.id as designation_id', 'locations.id as location_id')
            ->join('criterias', 'criterias.id', 'employee_details.criteria_id')
            ->leftJoin('sub_criterias', 'sub_criterias.id', '=', 'employee_details.sub_criteria_id')
            ->leftJoin('users', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('teams', 'teams.id', '=', 'employee_details.department_id')
            ->leftJoin('designations', 'designations.id', '=', 'employee_details.designation_id')
            ->leftJoin('locations', 'locations.id', '=', 'teams.location_id')
            ->whereNotNull('employee_details.notice_period_end_date')
            ->whereNotNull('employee_details.criteria_id')
            ->whereNotNull('employee_details.sub_criteria_id');

        if ($this->location != 'all' && $this->location != '') {
            $model->where('locations.id', $this->location);
        }

        if ($this->department != 'all' && $this->department != '') {
            $model->where('teams.id', $this->department);
        }

        if ($this->designation != 'all' && $this->designation != '') {
            $model->where('designations.id', $this->designation);
        }

        // dd($model->get()->toArray());

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
            __('app.menu.exitsReason'),
            __('app.menu.subCriteria'),
            __('app.menu.accountability'),
            __('app.menu.responsiblePerson'),
            __('app.menu.actionTaken'),
            __('app.menu.noticePeriodEndDate')
        ];
    }

    public function map($row): array
    {
        static $index = 0;

        $employee = new EmployeeDetails();
        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

        $exitReason = $row['exit_reason_id']; // fallback if nothing matches

        if ($getCustomFieldGroupsWithFields) {
            $fields = $getCustomFieldGroupsWithFields->fields;

            foreach ($fields as $field) {
                if ($field->type == 'select' && $field->name == 'exit-reasons-1') {
                    $options = $field->values;
                    $exitReason = $options[$row['exit_reason_id']] ?? $row['exit_reason_id'];
                }
            }
        }

        return [
            ++$index,
            $row['employee_name'],
            $exitReason,
            $row['sub_criteria'],
            $row['accountability'],
            $row['responsible_person'],
            $row['action_taken'],
            $row['notice_period_end_date']
        ];
    }
}
