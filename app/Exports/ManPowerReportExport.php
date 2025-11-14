<?php

namespace App\Exports;

use App\Models\ManPowerReport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ManPowerReportExport implements FromCollection, ShouldAutoSize, WithStyles, WithHeadings, WithMapping, WithStrictNullComparison
{
    public $location;
    public $team;
    public $position;
    public $budgetYear;
    public $quarter;

    public function __construct($location, $team, $position, $budgetYear, $quarter)
    {
        $this->location = $location;
        $this->team = $team;
        $this->position = $position;
        $this->budgetYear = $budgetYear;
        $this->quarter = $quarter;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $quarter = $this->quarter;

        // Filter by quarter
        $quarterMonths = [
            1 => [1, 3],
            2 => [4, 6],
            3 => [7, 9],
            4 => [10, 12],
        ];

        $cumulativeRanges = [
            1 => [1, 12],  // Q1: Jan-Dec
            2 => [4, 12],  // Q2: Apr-Dec
            3 => [7, 12],  // Q3: Jul-Dec
            4 => [10, 12], // Q4: Oct-Dec
        ];

        $roles = auth()->user()->roles;
        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        if ($isAdmin || $isHRmanager) {
            $model = ManPowerReport::select(
                'man_power_reports.*',
                'locations.id as location_id',
                'locations.location_name as location',
                'teams.team_name as team',
                'designations.id as designation_id',
                'designations.name as position',
                DB::raw(
                    'COUNT(DISTINCT CASE
            WHEN (YEAR(employee_details.created_at) = man_power_reports.budget_year OR employee_details.created_at IS NULL)
            AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
            AND (
                employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
            )
            THEN employee_details.id
        END) as count_employee'
                ),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.basic_salary
        ELSE 0
    END) as basic_salary'),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.technical_allowance
        ELSE 0
    END) as technical_allowance'),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.living_cost_allowance
        ELSE 0
    END) as living_cost_allowance'),
            )
                ->leftJoin('teams', 'man_power_reports.team_id', '=', 'teams.id')
                ->leftJoin('designations', 'man_power_reports.position_id', '=', 'designations.id')
                ->leftJoin('employee_details', function ($join) use ($quarter, $quarterMonths, $cumulativeRanges) {
                    // position match
                    $join->on('teams.id', '=', 'employee_details.department_id')
                        ->whereColumn('employee_details.designation_id', 'man_power_reports.position_id');

                    if ($quarter != 'all' && $quarter != null && isset($quarterMonths[$quarter])) {
                        // Filter by specific quarter months (Q1=Jan-Mar, Q4=Oct-Dec)
                        [$start, $end] = $quarterMonths[$quarter];
                        $join->where(function ($q) use ($start, $end) {
                            $q->whereRaw("MONTH(employee_details.created_at) BETWEEN ? AND ?", [$start, $end])
                                ->orWhereNull('employee_details.created_at');
                        });
                    }

                    // CRITICAL: Also check if employee falls within the cumulative range
                    $join->where(function ($q) use ($cumulativeRanges) {
                        $q->whereRaw("(
            (man_power_reports.quarter = 1 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[1][0]} AND {$cumulativeRanges[1][1]}) OR
            (man_power_reports.quarter = 2 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[2][0]} AND {$cumulativeRanges[2][1]}) OR
            (man_power_reports.quarter = 3 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[3][0]} AND {$cumulativeRanges[3][1]}) OR
            (man_power_reports.quarter = 4 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[4][0]} AND {$cumulativeRanges[4][1]}) OR
            employee_details.created_at IS NULL
        )");
                    });
                })
                ->leftJoin('users', 'employee_details.user_id', '=', 'users.id')
                ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
                ->leftJoin('allowances', 'users.id', '=', 'allowances.user_id')
                ->groupBy([
                    'man_power_reports.id',
                    'man_power_reports.team_id',
                    'man_power_reports.budget_year',
                    'man_power_reports.man_power_setup',
                    'man_power_reports.man_power_basic_salary',
                    'man_power_reports.quarter',
                    'man_power_reports.position_id',
                    'man_power_reports.created_at',
                    'man_power_reports.updated_at',
                ]);
        } else {
            $model = ManPowerReport::select(
                'man_power_reports.*',
                'locations.id as location_id',
                'locations.location_name as location',
                'teams.team_name as team',
                'designations.id as designation_id',
                'designations.name as position',
                DB::raw(
                    'COUNT(DISTINCT CASE
            WHEN (YEAR(employee_details.created_at) = man_power_reports.budget_year OR employee_details.created_at IS NULL)
            AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
            AND (
                employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
            )
            THEN employee_details.id
        END) as count_employee'
                ),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.basic_salary
        ELSE 0
    END) as basic_salary'),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.technical_allowance
        ELSE 0
    END) as technical_allowance'),
                DB::raw('SUM(CASE
        WHEN (YEAR(allowances.created_at) = man_power_reports.budget_year OR allowances.created_at IS NULL)
        AND (YEAR(users.created_at) = man_power_reports.budget_year OR users.created_at IS NULL)
        AND (
            employee_details.designation_id = man_power_reports.position_id OR users.designation_id = man_power_reports.position_id
        )
        THEN allowances.living_cost_allowance
        ELSE 0
    END) as living_cost_allowance'),
            )
                ->leftJoin('teams', 'man_power_reports.team_id', '=', 'teams.id')
                ->leftJoin('designations', 'man_power_reports.position_id', '=', 'designations.id')
                ->leftJoin('employee_details', function ($join) use ($quarter, $quarterMonths, $cumulativeRanges) {
                    // position match
                    $join->on('teams.id', '=', 'employee_details.department_id')
                        ->whereColumn('employee_details.designation_id', 'man_power_reports.position_id');

                    if ($quarter != 'all' && $quarter != null && isset($quarterMonths[$quarter])) {
                        // Filter by specific quarter months (Q1=Jan-Mar, Q4=Oct-Dec)
                        [$start, $end] = $quarterMonths[$quarter];
                        $join->where(function ($q) use ($start, $end) {
                            $q->whereRaw("MONTH(employee_details.created_at) BETWEEN ? AND ?", [$start, $end])
                                ->orWhereNull('employee_details.created_at');
                        });
                    }

                    // CRITICAL: Also check if employee falls within the cumulative range
                    $join->where(function ($q) use ($cumulativeRanges) {
                        $q->whereRaw("(
            (man_power_reports.quarter = 1 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[1][0]} AND {$cumulativeRanges[1][1]}) OR
            (man_power_reports.quarter = 2 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[2][0]} AND {$cumulativeRanges[2][1]}) OR
            (man_power_reports.quarter = 3 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[3][0]} AND {$cumulativeRanges[3][1]}) OR
            (man_power_reports.quarter = 4 AND MONTH(employee_details.created_at) BETWEEN {$cumulativeRanges[4][0]} AND {$cumulativeRanges[4][1]}) OR
            employee_details.created_at IS NULL
        )");
                    });
                })
                ->leftJoin('users', 'employee_details.user_id', '=', 'users.id')
                ->leftJoin('locations', 'teams.location_id', '=', 'locations.id')
                ->leftJoin('allowances', 'users.id', '=', 'allowances.user_id')
                ->where('created_by', user()->id)
                ->groupBy([
                    'man_power_reports.id',
                    'man_power_reports.team_id',
                    'man_power_reports.budget_year',
                    'man_power_reports.man_power_setup',
                    'man_power_reports.man_power_basic_salary',
                    'man_power_reports.quarter',
                    'man_power_reports.position_id',
                    'man_power_reports.created_at',
                    'man_power_reports.updated_at',
                ]);
        }

        if ($this->team != 'all' && $this->team != null) {
            $model->where('man_power_reports.team_id', $this->team);
        }

        if ($this->location != 'all' && $this->location != null) {
            $model->where('locations.id', $this->location);
        }

        if ($this->position != 'all' && $this->position != null) {
            $model->where('designations.id', $this->position);
        }

        if ($this->budgetYear != 'all' && $this->budgetYear != null) {
            $model->where('man_power_reports.budget_year', request()->budgetYear);
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
            __('app.menu.budgetYear'),
            __('app.menu.quarter'),
            __('app.menu.location'),
            __('app.menu.position'),
            __('app.menu.manPowerSetup'),
            __('app.menu.actualManPower'),
            __('app.menu.maxManPowerBasicSalary'),
            __('app.menu.totalManPowerBasicSalary'),
            __('app.menu.vacancyPercent'),
            __('app.menu.department'),
            __('app.menu.status'),
            __('app.menu.approvedDate'),
            __('app.menu.remarkFrom'),
            __('app.menu.remarkTo')
        ];
    }

    public function map($row): array
    {
        static $index = 0;

        // Quarter mapping
        $quarterMap = [
            1 => 'Q1 (Jan - Mar)',
            2 => 'Q2 (Apr - Jun)',
            3 => 'Q3 (Jul - Sep)',
            4 => 'Q4 (Oct - Dec)',
        ];
        $quarter = $quarterMap[$row['quarter']] ?? '';

        // Force numeric values
        $manPowerSetup       = (int) ($row['man_power_setup'] ?? 0);
        $actualManPower      = (int) ($row['count_employee'] ?? 0);
        $basicSalary         = (int) ($row['basic_salary'] ?? 0);
        $technicalAllowance  = (int) ($row['technical_allowance'] ?? 0);
        $livingCostAllowance = (int) ($row['living_cost_allowance'] ?? 0);
        $manPowerBasicSalary = (int) ($row['man_power_basic_salary'] ?? 0);

        $totalManPowerBasicSalary = $basicSalary + $technicalAllowance + $livingCostAllowance;

        // Vacancy %
        if ($manPowerSetup <= 0) {
            $vacancy = 100;
        } elseif ($manPowerSetup <= $actualManPower) {
            $vacancy = 0;
        } else {
            $vacancy = 100 - ($actualManPower / $manPowerSetup) * 100;
        }
        $vacancy = round($vacancy, 0);

        return [
            ++$index,
            $row['budget_year'] ?? '',
            $quarter,
            $row['location'] ?? '',
            $row['position'] ?? '',
            $manPowerSetup,
            $actualManPower,
            $manPowerBasicSalary,
            $totalManPowerBasicSalary,
            $vacancy,
            $row['team'] ?? '',
            $row['status'] ?? '',
            $row['approved_date'] ?? '',
            $row['remark_from'] ?? '',
            $row['remark_to'] ?? '',
        ];
    }



}
