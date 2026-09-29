<?php

namespace Modules\Recruit\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Models\Designation;
use App\Models\Location;
use App\Models\ManagementRank;
use App\Models\Team;
use App\Traits\ImportExcel;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Recruit\DataTables\JobApplicationsDataTable;
use Modules\Recruit\Entities\ApplicationSource;
use Modules\Recruit\Entities\RecruitApplicantBlacklist;
use Modules\Recruit\Entities\RecruitApplicationFile;
use Modules\Recruit\Entities\RecruitApplicationSkill;
use Modules\Recruit\Entities\RecruitApplicationStatus;
use Modules\Recruit\Entities\RecruitApplicationStatusCategory;
use Modules\Recruit\Entities\RecruitCandidateFollowUp;
use Modules\Recruit\Entities\RecruitInterviewSchedule;
use Modules\Recruit\Entities\RecruitJob;
use Modules\Recruit\Entities\RecruitJobAddress;
use Modules\Recruit\Entities\RecruitJobApplication;
use Modules\Recruit\Entities\RecruitJobCustomAnswer;
use Modules\Recruit\Entities\RecruitJobHistory;
use Modules\Recruit\Entities\RecruitSetting;
use Modules\Recruit\Entities\RecruitSkill;
use Modules\Recruit\Events\JobApplicationStatusChangeEvent;
use Modules\Recruit\Http\Requests\JobApplication\ImportProcessRequest;
use Modules\Recruit\Http\Requests\JobApplication\ImportRequest;
use Modules\Recruit\Http\Requests\JobApplication\StoreJobApplication;
use Modules\Recruit\Http\Requests\JobApplication\StoreQuickApplication;
use Modules\Recruit\Http\Requests\JobApplication\UpdateJobApplication;
use Modules\Recruit\Imports\JobApplicationImport;
use Modules\Recruit\Jobs\ImportJobApplicationJob;
use Modules\Recruit\Services\ApplicantWorkflowService;
use PhpParser\Node\Expr\Empty_;

class JobApplicationController extends AccountBaseController
{
    use ImportExcel;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('recruit::app.menu.jobApplication');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(RecruitSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    public function index(JobApplicationsDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_job_application');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->applicationStatus = RecruitApplicationStatus::select('id', 'status', 'position', 'color')->orderBy('position')->get();
        $this->applicationSources = ApplicationSource::all();
        $this->jobs = RecruitJob::where('status', 'open')->get();
        $this->locations = CompanyAddress::all();
        $this->jobLocations = RecruitJobAddress::with('location')->where('recruit_job_id', request()->id)->get();
        $this->jobApp = RecruitJob::where('id', request()->id)->first();
        $this->hrLocations = Location::orderBy('location_name')->get();

        // $designation = Designation::findOrFail($request->designation_id);
        // $this->job->designation_id = $designation->id;
        // $this->job->rank_level = $designation->rank_id;

        $this->locations = CompanyAddress::all();
        $this->currentLocations = RecruitJobApplication::select('current_location')->where('current_location', '!=', null)->distinct()->get();

        $settings = RecruitSetting::select('form_settings')->first();
        $this->formSettings = collect([]);

        if ($settings) {
            $formSettings = $settings->form_settings;

            foreach ($formSettings as $form) {
                if ($form['status'] == true) {
                    $this->formSettings->push($form);
                }
            }
        }

        $this->formFields = $this->formSettings->pluck('name')->toArray();

        return $dataTable->render('recruit::job-applications.table', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $addPermission = user()->permission('add_job_application');
        abort_403(!in_array($addPermission, ['all', 'added']));
        $this->jobId = request()->id ?? null;

        $this->pageTitle = __('recruit::modules.jobApplication.addJobApplications');

        $this->applicationStatus = RecruitApplicationStatus::select('id', 'status', 'position', 'color')->orderBy('position')->get();
        $this->applicationSources = ApplicationSource::where('company_id', company()->id)->get();
        $this->jobs = RecruitJob::where('status', 'open')->get();
        $this->locations = CompanyAddress::all();
        $this->jobLocations = RecruitJobAddress::with('location')->where('recruit_job_id', request()->id)->get();
        $this->jobApp = RecruitJob::where('id', request()->id)->first();
        $this->statusId = request()->column_id;

        // if (request()->ajax()) {
        //     $html = view('recruit::job-applications.ajax.create', $this->data)->render();

        //     return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        // }

        $this->view = 'recruit::job-applications.ajax.create';

        return view('recruit::job-applications.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(StoreJobApplication $request)
    {
        $addPermission = user()->permission('add_job_application');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $existApplicant = RecruitJobApplication::where('recruit_job_id', $request->job_id)
            ->where(function ($query) use ($request) {
                $query->where('email', $request->email)
                    ->orWhere('nrc', $request->nrc);
            })
            ->first();

        if ($existApplicant) {
            return Reply::dataOnly([
                'status' => 'success',
                'applicationExists' => true,
                'message' => 'You have already applied for this job with the provided email or NRC.',
                'redirectUrl' => route('job-applications.index'),
            ]);
        }

        [$jobApp, $blacklistMatch] = DB::transaction(function () use ($request) {
            $job = RecruitJob::findOrFail($request->job_id);
            $jobApp = new RecruitJobApplication();

            $this->fillApplicantFields($jobApp, $request, $job);
            $this->storeApplicantPhoto(
                $jobApp,
                $request
            );

            $jobApp->location_id = 1;
            $jobApp->application_sources = 'addedByUser';
            $jobApp->column_priority = 0;
            $jobApp->send_email = $request->boolean('send_email');

            $workflow = app(ApplicantWorkflowService::class);
            $blacklistMatch = $workflow->findBlacklistMatch($jobApp);

            if ($blacklistMatch) {
                return [null, $blacklistMatch];
            }

            $workflow->validateTransition($jobApp);

            $jobApp->save();

            $this->storeResume($jobApp, $request);
            $this->syncApplicantBlacklist($jobApp, $request);
            $workflow->createEmployeeForHiredApplicant($jobApp);


            return [$jobApp, null];
        });

        // dd($blacklistMatch);

        if ($blacklistMatch) {
            return Reply::dataOnly([
                'status' => 'success',
                'blacklistMatch' => true,
                'message' => 'This applicant is already blacklisted.',
                'blacklistReason' => $blacklistMatch->reason,
                'redirectUrl' => route('job-applications.index'),
            ]);
        }


        if (request()->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('recruit::messages.applicationAdded'), ['html' => $html, 'add_more' => true]);
        }

        $redirectUrl = $request->filled('redirect_url')
            ? urldecode($request->redirect_url)
            : route('job-applications.index');

        return Reply::dataOnly([
            'redirectUrl' => $redirectUrl,
            'application_id' => $jobApp->id,
            'blacklistMatch' => (bool) $blacklistMatch,
            'message' => __('recruit::messages.applicationAdded'),
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $interviewer = [];
        $this->application = RecruitJobApplication::with('job', 'applicationStatus', 'location', 'source', 'comments', 'comments.user', 'files')->findOrFail($id);
        $scheduleData = RecruitInterviewSchedule::with('employees')->where('recruit_job_application_id', $id)->first();

        $this->currencySymbol = Currency::where('id', '=', $this->application->job->currency_id)->first();

        if ($scheduleData) {
            $interviewer = $scheduleData->employees->pluck('id')->toArray();
        }

        $this->viewPermission = user()->permission('view_job_application');
        $this->interviewViewPermission = user()->permission('view_interview_schedule');
        abort_403(!($this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->application->added_by == user()->id)
            || ($this->viewPermission == 'owned' && user()->id == $this->application->job->recruiter_id)
            || ($this->viewPermission == 'owned' && in_array(user()->id, $interviewer))
            || ($this->viewPermission == 'both' && user()->id == $this->application->job->recruiter_id
                || $this->application->added_by == user()->id) || (in_array(user()->id, $interviewer))
            || ($this->interviewViewPermission == 'owned')));
        $this->departments = Team::all();
        $this->recruit_skills = RecruitApplicationSkill::where('recruit_job_application_id', $id)->get();
        $this->selected_skills = $this->recruit_skills->pluck('recruit_skill_id')->toArray();
        $this->skills = RecruitSkill::select('id', 'name')->get();
        $this->allAnswers = RecruitJobCustomAnswer::where('recruit_job_application_id', $this->application->id)->get();
        $this->followUps = RecruitCandidateFollowUp::where('recruit_job_application_id', $id)->get();
        $this->applicationStatus = RecruitApplicationStatus::select('id', 'status', 'position', 'color', 'slug')->orderBy('position')->get();
        $this->applicationStatusHistory = RecruitJobHistory::where('details', 'updateJobapplicationStatus')
            ->where('recruit_job_application_id', $id)
            ->get()->pluck('recruit_job_application_status_id')->toArray();

        $tab = request('view');

        switch ($tab) {
            case 'applicant_notes':
                $this->tab = 'recruit::job-applications.notes.notes';
                break;
            case 'skill':
                $this->tab = 'recruit::job-applications.ajax.skill';
                break;
            case 'custom':
                $this->tab = 'recruit::job-applications.ajax.custom-question';
                break;
            case 'follow-up':
                $this->tab = 'recruit::job-applications.ajax.follow-up';
                break;
            case 'resume':
                $this->tab = 'recruit::job-applications.ajax.resume';
                break;

            default:
                $this->editInterviewSchedulePermission = user()->permission('edit_interview_schedule');
                $this->deleteInterviewSchedulePermission = user()->permission('delete_interview_schedule');
                $this->viewInterviewSchedulePermission = user()->permission('view_interview_schedule');
                $this->reschedulePermission = user()->permission('reschedule_interview');
                $this->applicationStatuses = ['pending', 'hired', 'canceled', 'completed', 'rejected'];

                $this->interviewSchedule = RecruitInterviewSchedule::with(['employeesData', 'employeesData.user'])
                    ->select('recruit_interview_schedules.id', 'recruit_interview_schedules.recruit_job_application_id', 'recruit_interview_schedules.interview_type', 'recruit_interview_schedules.recruit_interview_stage_id', 'recruit_interview_employees.user_id as employee_id', 'recruit_interview_employees.user_accept_status', 'recruit_interview_employees.id as emp_id', 'recruit_job_applications.full_name', 'recruit_interview_schedules.status', 'recruit_interview_schedules.schedule_date', 'recruit_interview_stages.name')
                    ->where('recruit_job_application_id', $id)
                    ->leftjoin('recruit_job_applications', 'recruit_job_applications.id', 'recruit_interview_schedules.recruit_job_application_id')
                    ->leftjoin('recruit_interview_stages', 'recruit_interview_stages.id', 'recruit_interview_schedules.recruit_interview_stage_id')
                    ->leftjoin('recruit_interview_employees', 'recruit_interview_employees.recruit_interview_schedule_id', 'recruit_interview_schedules.id')
                    ->groupBy('recruit_interview_schedules.id')->get();
                $this->tab = 'recruit::job-applications.ajax.interview-schedule';
                break;
        }

        if (request()->ajax()) {
            if (request('json') == true) {
                $html = view($this->tab, $this->data)->render();

                return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
            }

            $html = view('recruit::job-applications.ajax.show', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'recruit::job-applications.ajax.show';

        return view('recruit::job-applications.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->jobApplication = RecruitJobApplication::with('job')->findOrFail($id);
        $this->jobId = null;
        $this->job = $this->jobApplication->job;
        $this->management_rank_level = $this->jobApplication->job->management_rank_level;
        $this->rank_level = $this->jobApplication->job->rank_level;

        $this->managementRanks = ManagementRank::all();
        $this->currency = $this->job && $this->job->currency_id
            ? Currency::find($this->job->currency_id)
            : null;
        $this->editPermission = user()->permission('edit_job_application');
        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->jobApplication->added_by == user()->id)
            || ($this->editPermission == 'owned' && user()->id == $this->job?->recruiter_id)
            || ($this->editPermission == 'both' && user()->id == $this->job?->recruiter_id)
            || $this->jobApplication->added_by == user()->id));

        $this->jobApplictionFile = RecruitApplicationFile::where('recruit_job_application_id', $id)->first();
        $this->jobs = RecruitJob::all();
        $this->applicationSources = ApplicationSource::where('company_id', company()->id)->get();
        $this->locations = CompanyAddress::all();
        $this->applicationStatus = RecruitApplicationStatus::select('id', 'status', 'position', 'color')->orderBy('position')->get();
        $this->statusId = $this->jobApplication->recruit_application_status_id;

        if (request()->ajax()) {
            $html = view('recruit::job-applications.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'recruit::job-applications.ajax.edit';

        return view('recruit::job-applications.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(UpdateJobApplication $request, $id)
    {
        $this->editPermission = user()->permission('edit_job_application');
        $jobApp = RecruitJobApplication::with('job')->findOrFail($id);

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $jobApp->added_by == user()->id)
            || ($this->editPermission == 'owned' && user()->id == $jobApp->job->recruiter_id)
            || ($this->editPermission == 'both' && user()->id == $jobApp->job->recruiter_id)
            || $jobApp->added_by == user()->id));

        $oldStatusId = $jobApp->recruit_application_status_id;

        $blacklistMatch = DB::transaction(function () use ($jobApp, $request) {
            $job = RecruitJob::findOrFail($request->job_id);

            $this->fillApplicantFields($jobApp, $request, $job);
            $jobApp->send_email = $request->boolean('send_email');

            $workflow = app(ApplicantWorkflowService::class);
            $blacklistMatch = $workflow->applyBlacklistDecision($jobApp);
            $workflow->validateTransition($jobApp);

            $jobApp->save();

            $this->storeResume($jobApp, $request);
            $this->syncApplicantBlacklist($jobApp, $request);
            $workflow->createEmployeeForHiredApplicant($jobApp);

            return $blacklistMatch;
        });

        $newStatusId = $jobApp->recruit_application_status_id;

        if ($oldStatusId != $newStatusId) {
            $send = $this->statusForMailSend($newStatusId);

            if ($send == true) {
                event(new JobApplicationStatusChangeEvent($jobApp));
            }
        }

        return Reply::successWithData(
            $blacklistMatch ? 'Blacklist match found. Applicant was automatically rejected.' : __('recruit::modules.message.updateSuccess'),
            ['redirectUrl' => route('job-applications.index'), 'application_id' => $jobApp->id, 'blacklistMatch' => (bool) $blacklistMatch]
        );
    }

    private function fillApplicantFields(
        RecruitJobApplication $jobApp,
        Request $request,
        RecruitJob $job
    ): void {

        $jobApp->recruit_job_id = $job->id;
        $jobApp->unsetRelation('job');

        $jobApp->full_name = trim($request->full_name);
        $jobApp->father_name = trim($request->father_name);
        $jobApp->email = $request->filled('email')
            ? strtolower(trim($request->email))
            : null;

        $jobApp->phone = $request->phone;
        $jobApp->gender = $request->gender;
        $jobApp->current_location = $request->current_location;
        $jobApp->total_experience = $request->total_experience;
        $jobApp->notice_period = $request->notice_period;
        // $jobApp->start_date = $request->start_date;
        // $jobApp->end_date = $request->end_date;

        $jobApp->recruit_application_status_id =
            $request->status_id;

        $jobApp->application_source_id =
            $request->source;

        $jobApp->cover_letter =
            $request->cover_letter;

        $jobApp->date_of_birth = $request->filled('date_of_birth')
            ? Carbon::createFromFormat(
                $this->company->date_format,
                $request->date_of_birth
            )->format('Y-m-d')
            : null;

        $jobApp->rank_level = $job->rank_level;
        $jobApp->management_rank_level = $job->management_rank_level;
        $jobApp->marital_status =
            $request->marital_status;

        $jobApp->nrc = $request->filled('nrc')
            ? trim($request->nrc)
            : null;

        $jobApp->education =
            $request->education;

        $jobApp->certifications_qualifications =
            $request->certifications_qualifications;

        $jobApp->work_experience_details =
            $this->cleanWorkExperience(
                $request->input('work_experience_details', [])
            );

        // dd($jobApp->work_experience_details);

        $jobApp->last_salary_minimum =
            $request->filled('last_salary_minimum')
            ? $request->last_salary_minimum
            : null;

        $jobApp->expected_salary_minimum =
            $request->filled('expected_salary_minimum')
            ? $request->expected_salary_minimum
            : null;

        $jobApp->selection_phase = $request->input(
            'selection_phase',
            'cv_screening'
        );

        $jobApp->overall_status = $request->input(
            'overall_status',
            'not_started'
        );

        $jobApp->keep_cv_reason =
            $request->overall_status === 'keep_cv'
            ? $request->keep_cv_reason
            : null;

        $jobApp->rejection_reason =
            $request->overall_status === 'rejected'
            ? $request->rejection_reason
            : null;

        $jobApp->rejection_reason_details =
            $request->overall_status === 'rejected'
            ? $request->rejection_reason_details
            : null;

        $jobApp->job_offer_decision =
            $request->job_offer_decision ?: null;

        $jobApp->job_offer_decision_reason =
            $request->job_offer_decision === 'declined'
            ? $request->job_offer_decision_reason
            : null;

        // dd($request->all());

        $jobApp->is_blacklisted = $request->blacklist == 1 ? 1 : 0;
        $jobApp->blacklist_reason = $request->blacklist_reason;
    }

    private function cleanWorkExperience(array $rows): array
    {
        return array_values(
            array_filter(
                array_map(function ($row) {
                    if (!is_array($row)) {
                        return null;
                    }

                    $cleanRow = [
                        'company' => trim(
                            (string) ($row['company'] ?? '')
                        ),

                        'position' => trim(
                            (string) ($row['position'] ?? '')
                        ),

                        'years' => trim(
                            (string) ($row['years'] ?? '')
                        )
                    ];

                    $hasValue = array_filter(
                        $cleanRow,
                        fn($value) =>
                        $value !== null &&
                            $value !== ''
                    );

                    return $hasValue ? $cleanRow : null;
                }, $rows)
            )
        );
    }

    private function storeApplicantPhoto(
        RecruitJobApplication $jobApp,
        Request $request
    ): void {
        if (!$request->hasFile('photo')) {
            return;
        }

        if ($jobApp->photo) {
            Files::deleteFile(
                $jobApp->photo,
                'avatar'
            );
        }

        $jobApp->photo = Files::uploadLocalOrS3(
            $request->file('photo'),
            'avatar',
            300
        );
    }

    private function syncApplicantBlacklist(
        RecruitJobApplication $jobApp,
        Request $request
    ): void {
        $sourceBlacklist = RecruitApplicantBlacklist::where('company_id', company()->id)
            ->where('source_application_id', $jobApp->id);

        if (!$request->boolean('blacklist')) {
            $sourceBlacklist->delete();
            $jobApp->is_blacklisted = false;
            $jobApp->blacklist_reason = null;

            return;
        }

        RecruitApplicantBlacklist::updateOrCreate(
            [
                'company_id' => company()->id,
                'source_application_id' => $jobApp->id,
            ],
            [
                'full_name' => $jobApp->full_name,
                'email' => $jobApp->email,
                'phone' => $jobApp->phone,
                'nrc' => $jobApp->nrc,
                'reason' => $request->blacklist_reason,
                'added_by' => user()->id,
            ]
        );
    }

    private function storeResume(
        RecruitJobApplication $jobApp,
        Request $request
    ): void {
        if (!$request->hasFile('resume')) {
            return;
        }

        $uploadedResume = $request->file('resume');
        $file = RecruitApplicationFile::where(
            'recruit_job_application_id',
            $jobApp->id
        )->first();

        if ($file && $file->hashname) {
            Files::deleteFile(
                $file->hashname,
                'application-files/' . $jobApp->id
            );
        }

        $file ??= new RecruitApplicationFile();
        $file->recruit_job_application_id = $jobApp->id;
        $file->filename = $uploadedResume->getClientOriginalName();
        $file->hashname = Files::uploadLocalOrS3(
            $uploadedResume,
            'application-files/' . $jobApp->id
        );
        $file->size = $uploadedResume->getSize();
        $file->save();
    }

    private function deleteApplicantFiles(RecruitJobApplication $jobApp): void
    {
        $jobApp->loadMissing('files');

        foreach ($jobApp->files as $file) {
            if ($file->hashname) {
                Files::deleteFile(
                    $file->hashname,
                    'application-files/' . $jobApp->id
                );
            }

            $file->delete();
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $jobApp = RecruitJobApplication::with('job')->findOrFail($id);

        $this->deletePermission = user()->permission('delete_job_application');
        abort_403(!($this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $jobApp->added_by == user()->id)
            || ($this->deletePermission == 'owned' && user()->id == $jobApp->job->recruiter_id)
            || ($this->deletePermission == 'both' && user()->id == $jobApp->job->recruiter_id)
            || $jobApp->added_by == user()->id));

        DB::transaction(function () use ($jobApp) {
            $this->deleteApplicantFiles($jobApp);

            RecruitApplicantBlacklist::where('company_id', company()->id)
                ->where('source_application_id', $jobApp->id)
                ->delete();

            $jobApp->forceDelete();
        });

        return Reply::successWithData(__('recruit::modules.message.deleteSuccess'), ['redirectUrl' => route('job-applications.index')]);
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
            case 'delete':
                $this->deleteRecords($request);

                return Reply::success(__('messages.deleteSuccess'));
            case 'change-status':
                $this->changeStatus($request);

                return Reply::success(__('messages.updateSuccess'));
            default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_job_application') != 'all');

        $ids = array_values(array_filter(
            explode(',', $request->row_ids),
            fn($id) => ctype_digit((string) $id)
        ));

        DB::transaction(function () use ($ids) {
            RecruitJobApplication::whereIn('id', $ids)->get()->each(function ($jobApp) {
                $this->deleteApplicantFiles($jobApp);

                RecruitApplicantBlacklist::where('company_id', company()->id)
                    ->where('source_application_id', $jobApp->id)
                    ->delete();

                $jobApp->forceDelete();
            });
        });

        return true;
    }

    public function changeStatus(Request $request)
    {
        abort_403(user()->permission('edit_job_application') != 'all');
        $interviewPermission = user()->permission('add_interview_schedule');
        $offerLetterPermission = user()->permission('add_offer_letter');

        $item = explode(',', $request->row_ids);

        if (($key = array_search('on', $item)) !== false) {
            unset($item[$key]);
        }

        $statusId = $request->status;
        $status = RecruitApplicationStatus::with('category')->where('id', $statusId)->first();
        $send = $this->statusForMailSend($statusId);

        foreach ($item as $id) {
            $mail = RecruitJobApplication::findOrFail($id);

            $mail->recruit_application_status_id = $request->status;
            $mail->save();

            if ($send == true) {
                event(new JobApplicationStatusChangeEvent($mail, $status));
            }
        }


        return Reply::dataOnly(['status' => 'success', 'status' => $status, 'interviewPermission' => $interviewPermission, 'offerLetterPermission' => $offerLetterPermission]);
    }

    public function updateWorkflow(Request $request, $id)
    {
        abort_403(user()->permission('edit_job_application') == 'none');

        $validated = $request->validate([
            'selection_phase' => 'nullable|in:cv_screening,first_interview,second_interview,job_offer,hiring',
            'overall_status' =>   'nullable|in:not_started,in_progress,keep_cv,rejected,passed,hired',
            'rejection_reason' => 'required_if:overall_status,rejected|nullable|string',
        ]);

        $applicant = RecruitJobApplication::with('job')->findOrFail($id);
        $applicant->forceFill(array_filter($validated, fn($value) => $value !== null));

        $workflow = app(ApplicantWorkflowService::class);
        $workflow->applyBlacklistDecision($applicant);
        $workflow->validateTransition($applicant);
        $applicant->save();
        $workflow->createEmployeeForHiredApplicant($applicant);

        return Reply::success('Applicant workflow updated.');
    }

    public function statusForMailSend($id)
    {
        $settings = RecruitSetting::first();
        $mail = $settings->mail_setting;

        foreach ($mail as $mailDetails) {
            if ($mailDetails['id'] == $id && $mailDetails['status'] == true) {
                return true;
            }
        }
    }

    public function getRankLevel(Request $request)
    {
        $this->data = RecruitJob::with('address')->findOrFail($request->job_id);
        $designationId = $this->data->designation_id;

        if (!$designationId) {
            return Reply::dataOnly(['status' => 'error', 'message' => 'Designation not found']);
        }

        $designation = Designation::where('id', $designationId)->first();
        $rankLevel = $designation?->rank_id;
        $managementRank  = ManagementRank::where('id', $this->data->management_rank_level)->first();;

        $managementName = '';
        $managementId = '';

        if (!empty($managementRank)) {
            $managementName = $managementRank->name;
            $managementId = $managementRank->id;
        }

        return Reply::dataOnly(['status' => 'success', 'name' => $managementName, 'id' => $managementId, 'rankLevel' => $rankLevel, 'designationId' => $designationId]);
    }

    public function emailValidation($request)
    {
        $jobApps = RecruitJobApplication::where('recruit_job_id', $request->job_id)->get();

        if (count($jobApps) > 0) {
            foreach ($jobApps as $jobApp) {
                $mail = $jobApp->where('recruit_job_id', $request->job_id)->whereNotNull('email')->pluck('email')->toArray();
            }

            if (in_array($request->email, $mail)) {
                $this->validate($request, [
                    'email' => 'unique:recruit_job_applications|email'
                ]);
            } else {
                return $request->email;
            }
        } else {
            return $request->email;
        }
    }

    public function quickAddFormStore(StoreQuickApplication $request)
    {
        $addPermission = user()->permission('add_job_application');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $jobApp = new RecruitJobApplication();
        $jobApp->recruit_job_id = $request->job_id;
        $jobApp->full_name = $request->full_name;
        $jobApp->email = $this->emailValidation($request);
        $jobApp->phone = $request->phone;

        if ($request->has('gender')) {
            $jobApp->gender = $request->gender;
        }

        $jobApp->application_source_id = $request->source;
        $jobApp->cover_letter = $request->cover_letter;
        $jobApp->location_id = 1;
        $jobApp->total_experience = $request->total_experience;
        $jobApp->current_location = $request->current_location;
        $jobApp->current_ctc = $request->current_ctc;
        $jobApp->expected_ctc = $request->expected_ctc;
        $jobApp->notice_period = $request->notice_period;
        $jobApp->recruit_application_status_id = $request->status_id ?? 1;
        $jobApp->application_sources = 'addedByUser';
        $jobApp->column_priority = 0;

        $jobApp->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('job-applications.index');
        }

        return Reply::dataOnly(['redirectUrl' => $redirectUrl, 'application_id' => $jobApp->id]);
    }

    public function importJobApplication()
    {
        $this->pageTitle = __('recruit::modules.jobApplication.importJobCandidates');

        $this->addPermission = user()->permission('add_job_application');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->view = 'recruit::job-applications.ajax.import';


        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('recruit::job-applications.create', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $this->importFileProcess($request, JobApplicationImport::class);

        $this->jobs = RecruitJob::all();

        $view = view('recruit::job-applications.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importSalaryJobProcess($request, JobApplicationImport::class, ImportJobApplicationJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function downloadSampleCsv()
    {
        return response()->download(storage_path('csv/job_applicants_sample.csv'));
    }
}
