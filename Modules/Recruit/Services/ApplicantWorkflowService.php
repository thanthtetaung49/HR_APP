<?php

namespace Modules\Recruit\Services;

use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\Models\EmployeeDetails;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Recruit\Entities\RecruitApplicantBlacklist;
use Modules\Recruit\Entities\RecruitJobApplication;

class ApplicantWorkflowService
{
    public const PHASES = ['cv_screening', 'first_interview', 'second_interview', 'job_offer', 'hiring'];
    public const STATUSES = ['not_started', 'in_progress', 'keep_cv', 'rejected', 'passed', 'hired'];
    public const OFFER_DECISIONS = ['accepted', 'declined'];

    public const REJECTION_REASONS = [
        'location_unfit',
        'experience_gap',
        'culture_unfit',
        'salary_range_benefit',
        'other_competitor_join',
        'interview_absent',
        'not_contact',
        'failed_assessment_or_interview',
        'failed_reference_check',
        'position_closed',
        'blacklist',
    ];

    public function findBlacklistMatch(RecruitJobApplication $applicant): ?RecruitApplicantBlacklist
    {
        if (!$applicant->nrc) {
            return null;
        }

        $blacklistUser = RecruitApplicantBlacklist::query()
            ->where('company_id', 1)
            ->where(function ($query) use ($applicant) {
                if ($applicant->nrc) {
                    $query->orWhere('nrc', trim($applicant->nrc));
                }
            })
            ->first();

        // dd($blacklistUser);


        return $blacklistUser;
    }

    public function applyBlacklistDecision(RecruitJobApplication $applicant): ?RecruitApplicantBlacklist
    {
        $match = $this->findBlacklistMatch($applicant);

        if ($match) {
            $applicant->forceFill([
                'is_blacklisted' => true,
                'blacklist_reason' => $match->reason,
                'overall_status' => 'rejected',
                'rejection_reason' => 'blacklist',
                'rejection_reason_details' => $match->reason,
            ]);
        }

        return $match;
    }

    public function validateTransition(RecruitJobApplication $applicant): void
    {
        $actualRank = (int) $applicant->job?->designation?->rank_id;

        if (
            !in_array($applicant->selection_phase, self::PHASES, true)
            || !in_array($applicant->overall_status, self::STATUSES, true)
        ) {
            throw ValidationException::withMessages(['overall_status' => 'Invalid recruitment workflow value.']);
        }

        if (!$actualRank) {
            throw ValidationException::withMessages([
                'selection_phase' =>
                'The actual designation rank is not configured.',
            ]);
        }



        if ($actualRank < 4 && $applicant->selection_phase === 'second_interview') {
            throw ValidationException::withMessages(['selection_phase' => 'Rank levels below 4 require only one interview round.']);
        }

        if ($applicant->overall_status === 'rejected' && !$applicant->rejection_reason) {
            throw ValidationException::withMessages(['rejection_reason' => 'A rejection reason is required.']);
        }

        if (
            $applicant->selection_phase === 'job_offer' &&
            $applicant->overall_status === 'hired' &&
            !in_array(
                $applicant->job_offer_decision,
                ['accepted', 'declined'],
                true
            )
        ) {
            throw ValidationException::withMessages(['job_offer_decision' =>  'Please select Accepted or Declined before continuing.']);
        }

        if ($applicant->overall_status === 'hired' && !$applicant->email) {
            throw ValidationException::withMessages(['email' => 'Email is required to create the employee account.']);
        }

        if ($applicant->job_offer_decision === 'declined' && !$applicant->job_offer_decision_reason) {
            throw ValidationException::withMessages(['job_offer_decision_reason' => 'A reason is required when an offer is declined.']);
        }

        if ($applicant->selection_phase === 'hiring' && $applicant->overall_status === 'hired') {
            $applicant->selection_phase = 'job_offer';
            $applicant->overall_status = 'hired';
        }

        if (
            $applicant->selection_phase === 'job_offer' &&
            $applicant->overall_status === 'hired' &&
            in_array(
                $applicant->job_offer_decision,
                ['declined'],
                true
            )
        ) {
            $applicant->overall_status = 'rejected';
        }
    }

    public function createEmployeeForHiredApplicant(RecruitJobApplication $applicant): ?User
    {
        if ($applicant->selection_phase !== 'job_offer' || $applicant->overall_status !== 'hired' || $applicant->job_offer_decision !== 'accepted' || $applicant->employee_user_id) {
            return $applicant->employee_user_id ? User::find($applicant->employee_user_id) : null;
        }

        return DB::transaction(function () use ($applicant) {
            $existing = User::where('company_id', $applicant->company_id)
                ->where('email', $applicant->email)
                ->first();

            if ($existing) {
                $applicant->employee_user_id = $existing->id;
                $applicant->saveQuietly();
                return $existing;
            }

            $user = new User();
            $user->company_id = $applicant->company_id;
            $user->name = $applicant->full_name;
            $user->email = $applicant->email;
            $user->mobile = $applicant->phone;
            $user->gender = $applicant->gender;
            $user->location_id = $applicant->job?->hr_location_id;
            $user->department_id = $applicant->job?->department_id;
            $user->designation_id = $applicant->job?->designation_id;
            $user->password = bcrypt(Str::random(32));
            $user->status = 'active';
            $user->save();

            $this->syncEmployeeDetails($user, $applicant);

            if ($employeeRole = Role::where('name', 'employee')->first()) {
                $user->attachRole($employeeRole);
                $user->assignUserRolePermission($employeeRole->id);
            }

            $applicant->employee_user_id = $user->id;
            $applicant->saveQuietly();

            return $user;
        });
    }

    private function syncEmployeeDetails(User $user, RecruitJobApplication $applicant): EmployeeDetails
    {
        $employee = EmployeeDetails::firstOrNew([
            'company_id' => $applicant->company_id,
            'user_id' => $user->id,
        ]);

        $lastEmployeeID = EmployeeDetails::count();

        $checkifExistEmployeeId = EmployeeDetails::select('id')
            ->where('employee_id', $lastEmployeeID + 1)
            ->first();

        $newEmployeeID = !$checkifExistEmployeeId ? $lastEmployeeID + 1 : '';

        if (!$employee->exists) {
            $employee->employee_id = $newEmployeeID;
            $employee->joining_date = now()->toDateString();
        }

        $employee->department_id = $applicant->job?->department_id;
        $employee->designation_id = $applicant->job?->designation_id;
        $employee->rank = $applicant->job?->designation?->rank_id;
        $employee->date_of_birth = $applicant->date_of_birth;
        $employee->marital_status = $applicant->marital_status;
        $employee->save();

        $this->syncEmployeeNrc($employee, $applicant->nrc, $applicant->company_id);
        $this->syncEmployeeFatherName($employee, $applicant->father_name, $applicant->company_id);

        return $employee;
    }

    private function syncEmployeeFatherName(EmployeeDetails $employee, ?string $fatherName, int $companyId): void
    {
        $fatherName = trim((string) $fatherName);
        if ($fatherName === '') {
            return;
        }

        $employeeFieldGroupId = CustomFieldGroup::query()
            ->where('company_id', $companyId)
            ->where('model', EmployeeDetails::CUSTOM_FIELD_MODEL)
            ->value('id');

        if (!$employeeFieldGroupId) {
            return;
        }

        $fatherNameField = CustomField::query()
            ->where('company_id', $companyId)
            ->where('custom_field_group_id', $employeeFieldGroupId)
            ->where('name', 'father-name-1')
            ->first();

        // dd($fatherNameField);

        if (!$fatherNameField) {
            return;
        }

        $employee->updateCustomFieldData([
            'field_' . $fatherNameField->id => $fatherName,
        ], $companyId);
    }


    private function syncEmployeeNrc(EmployeeDetails $employee, ?string $nrc, int $companyId): void
    {
        $nrc = trim((string) $nrc);
        if ($nrc === '') {
            return;
        }

        $employeeFieldGroupId = CustomFieldGroup::query()
            ->where('company_id', $companyId)
            ->where('model', EmployeeDetails::CUSTOM_FIELD_MODEL)
            ->value('id');

        if (!$employeeFieldGroupId) {
            return;
        }

        $nrcField = CustomField::query()
            ->where('company_id', $companyId)
            ->where('custom_field_group_id', $employeeFieldGroupId)
            ->where('name', 'nrc-1')
            ->first();

        if (!$nrcField) {
            return;
        }

        $employee->updateCustomFieldData([
            'field_' . $nrcField->id => $nrc,
        ], $companyId);
    }
}
