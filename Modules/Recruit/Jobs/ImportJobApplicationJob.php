<?php

namespace Modules\Recruit\Jobs;

use App\Models\CompanyAddress;
use App\Models\Designation;
use App\Models\ManagementRank;
use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Recruit\Entities\ApplicationSource;
use Modules\Recruit\Entities\RecruitApplicantBlacklist;
use Modules\Recruit\Entities\RecruitApplicationStatus;
use Modules\Recruit\Entities\RecruitJob;
use Modules\Recruit\Entities\RecruitJobApplication;
use Modules\Recruit\Services\ApplicantWorkflowService;
use Throwable;

class ImportJobApplicationJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;
    private $recruitJobId;

    public function __construct(
        $row,
        $columns,
        $company = null,
        $recruitJobId = null
    ) {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
        $this->recruitJobId = $recruitJobId
            ?: request()->input('recruit_job_id');
    }

    public function handle(): void
    {
        if (
            !$this->isColumnExists('full_name') ||
            $this->getColumnValue('full_name') === 'Full Name'
        ) {
            $this->failJob(__('messages.invalidData'));
            return;
        }

        try {
            $companyId = $this->company?->id;

            if (!$companyId) {
                $this->failJob('Company information was not found.');
                return;
            }

            $fullName = trim(
                (string) $this->columnValue('full_name')
            );

            if ($fullName === '') {
                $this->failJob(
                    __('recruit::messages.fullNameRequired')
                );

                return;
            }

            $fatherName = trim(
                (string) $this->columnValue('father_name')
            );

            if ($fatherName === '') {
                $this->failJob(
                    __('recruit::messages.fatherNameRequired')
                );

                return;
            }


            $email = trim(
                (string) $this->columnValue('email')
            );

            if ($email === '') {
                $this->failJob('Email is required.');
                return;
            }

            $emailExists = RecruitJobApplication::where(
                'company_id',
                $companyId
            )
                ->where('email', $email)
                ->exists();

            if ($emailExists) {
                $this->failJob(
                    __('recruit::messages.emailAlreadyExists', [
                        'email' => $email,
                    ])
                );

                return;
            }
            if (!$this->recruitJobId) {
                $this->failJob(
                    'Please select a recruitment job before importing.'
                );

                return;
            }

            $recruitJob = RecruitJob::where(
                'company_id',
                $companyId
            )
                ->find($this->recruitJobId);

            if (!$recruitJob) {
                $this->failJob(
                    'The selected recruitment job was not found.'
                );

                return;
            }

            $designation = Designation::find(
                $recruitJob->designation_id
            );

            if (!$designation) {
                $this->failJob(
                    'The designation assigned to the selected job was not found.'
                );

                return;
            }

            if (empty($designation->rank_id)) {
                $this->failJob(
                    'Rank level is not configured for designation: '
                        . $designation->name
                );

                return;
            }

            $source = null;

            $sourceName = trim(
                (string) $this->columnValue('source')
            );

            if ($sourceName !== '') {
                $source = ApplicationSource::where(
                    'company_id',
                    $companyId
                )
                    ->where('application_source', $sourceName)
                    ->first();

                if (!$source) {
                    $this->failJob(
                        'Application source was not found: '
                            . $sourceName
                    );

                    return;
                }
            }

            $status = RecruitApplicationStatus::where(
                'company_id',
                $companyId
            )
                ->where('slug', 'applied')
                ->first();

            if (!$status) {
                $this->failJob(
                    'The default application status "applied" was not found.'
                );

                return;
            }

            $dateOfBirth = null;
            $dateOfBirthValue = $this->columnValue('date_of_birth');

            if (!empty($dateOfBirthValue)) {
                $dateOfBirth = Carbon::parse(
                    $dateOfBirthValue
                )->toDateString();
            }

            $maritalStatus = strtolower(
                trim((string) $this->columnValue('marital_status'))
            );

            if (
                $maritalStatus !== '' &&
                !in_array(
                    $maritalStatus,
                    ['single', 'married'],
                    true
                )
            ) {
                $this->failJob(
                    'Marital status must be Single or Married.'
                );

                return;
            }

            $isBlacklisted = in_array(
                strtolower(
                    trim((string) $this->columnValue('blacklist'))
                ),
                ['yes', 'y', '1', 'true'],
                true
            );

            DB::beginTransaction();

            $rankId = (string) $designation->rank_id;

            $managementRankId = ManagementRank::query()
                ->whereJsonContains('rank', (string) $rankId)
                ->value('id');

            if (!$managementRankId) {
                $this->failJob(
                    'Management rank is not configured for designation rank ' .
                        $rankId . '.'
                );

                return;
            }


            $jobApp = new RecruitJobApplication();
            $jobApp->company_id = $companyId;
            $jobApp->recruit_job_id = $recruitJob->id;
            $jobApp->management_rank_level = $managementRankId;
            $jobApp->recruit_application_status_id = $status->id;
            $jobApp->selection_phase = 'cv_screening';
            $jobApp->overall_status = 'not_started';


            $jobApp->full_name = $fullName;
            $jobApp->father_name = $fatherName;
            $jobApp->email = $email;
            $jobApp->phone = $this->columnValue('phone');

            $jobApp->gender = strtolower(
                trim((string) $this->columnValue('gender'))
            );

            $jobApp->location_id = 1;
            $jobApp->current_location =
                $this->columnValue('current_location')
                ?: $this->columnValue('current_location');
            $jobApp->application_source_id = $source?->id;

            $jobApp->current_ctc =
                $this->columnValue('current_ctc');

            $jobApp->expected_ctc =
                $this->columnValue('expected_ctc');

            $jobApp->total_experience =
                $this->columnValue('total_experience');

            $jobApp->date_of_birth = $dateOfBirth;
            $jobApp->marital_status =
                $maritalStatus !== '' ? $maritalStatus : null;

            $jobApp->education =
                $this->columnValue('education');

            $jobApp->certifications_qualifications =
                $this->columnValue(
                    'certifications_qualifications'
                );

            $jobApp->work_experience_details = [[
                'company' => $this->columnValue(
                    'work_experience_company'
                ),
                'position' => $this->columnValue(
                    'work_experience_position'
                ),
                'years' => $this->columnValue(
                    'work_experience_years'
                ),
            ]];

            $jobApp->last_salary_minimum =
                $this->columnValue('last_salary_minimum');

            $jobApp->expected_salary_minimum =
                $this->columnValue('expected_salary_minimum');

            $jobApp->nrc = $this->columnValue('nrc');


            $jobApp->rejection_reason =
                $this->columnValue('rejection_reason');

            $jobApp->keep_cv_reason =
                $this->columnValue('keep_cv_reason');

            $jobApp->job_offer_decision =
                $this->columnValue('job_offer_decision');

            $jobApp->is_blacklisted = $isBlacklisted ? 1 : 0;
            $jobApp->blacklist_reason = $this->columnValue('blacklist_reason');

            $jobApp->save();


            if ($isBlacklisted) {
                RecruitApplicantBlacklist::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'email' => $jobApp->email,
                    ],
                    [
                        'full_name' => $jobApp->full_name,
                        'phone' => $jobApp->phone,
                        'nrc' => $jobApp->nrc,
                        'reason' =>
                        $this->columnValue('blacklist_reason')
                            ?: 'Imported blacklist entry',
                    ]
                );
            }


            $workflow = app(ApplicantWorkflowService::class);

            $workflow->applyBlacklistDecision($jobApp);
            $workflow->validateTransition($jobApp);

            $jobApp->save();

            $workflow->createEmployeeForHiredApplicant($jobApp);

            DB::commit();
        } catch (InvalidFormatException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->failJob(__('messages.invalidDate'));
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->failJobWithMessage($e->getMessage());
        }
    }

    private function columnValue(string $column, $default = null)
    {
        if (!$this->isColumnExists($column)) {
            return $default;
        }

        $value = $this->getColumnValue($column);

        if ($value === '') {
            return $default;
        }

        return $value;
    }
}
