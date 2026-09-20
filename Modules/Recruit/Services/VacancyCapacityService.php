<?php

namespace Modules\Recruit\Services;

use App\Models\EmployeeDetails;
use App\Models\ManPowerReport;
use Illuminate\Validation\ValidationException;
use Modules\Recruit\Entities\RecruitJob;

class VacancyCapacityService
{
    public function available(ManPowerReport $plan, ?RecruitJob $excludingJob = null): int
    {
        $activeWithoutExit = EmployeeDetails::query()
            ->join('users', 'employee_details.user_id', '=', 'users.id')
            ->where('employee_details.department_id', $plan->team_id)
            ->where('employee_details.designation_id', $plan->position_id)
            ->where('users.status', 'active')
            ->whereNull('employee_details.last_date')
            ->distinct('employee_details.id')
            ->count('employee_details.id');

        $committed = RecruitJob::query()
            ->where('man_power_report_id', $plan->id)
            ->where('status', 'open')
            ->when($excludingJob, fn ($query) => $query->where('id', '!=', $excludingJob->id))
            ->sum('total_positions');

        return max(0, (int) $plan->man_power_setup - $activeWithoutExit - $committed);
    }

    public function validate(ManPowerReport $plan, int $requested, ?RecruitJob $excludingJob = null): void
    {
        if ($plan->status !== 'approved') {
            throw ValidationException::withMessages(['man_power_report_id' => 'Only approved manpower plans can be used for vacancies.']);
        }

        $available = $this->available($plan, $excludingJob);
        if ($requested > $available) {
            throw ValidationException::withMessages([
                'total_positions' => "Only {$available} approved vacancy position(s) are available for this manpower plan.",
            ]);
        }
    }
}
