<?php

namespace Modules\Recruit\Http\Controllers;

use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Recruit\Entities\RecruitInterviewSchedule;
use Modules\Recruit\Entities\RecruitJob;
use Modules\Recruit\Entities\RecruitJobApplication;
use Modules\Recruit\Entities\RecruitSetting;
use Modules\Recruit\Exports\RecruitmentAnalyticsExport;
use Modules\Recruit\Services\RecruitmentAnalyticsService;

class ReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('recruit::app.menu.report');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(RecruitSetting::MODULE_NAME, $this->user->modules));
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $this->authorizeReport();
        [$from, $to] = $this->dateRange($request);
        $locationId = $this->locationId($request);

        $this->locations = Location::orderBy('location_name')->get();
        $this->selectedLocation = $locationId ?? 'all';
        $this->setSummaryCounts($from, $to, $locationId);

        $analytics = app(RecruitmentAnalyticsService::class);
        $this->summaryReport = $analytics->dashboardSummary($from, $to, $locationId);
        $this->sourceReport = $analytics->sourceEffectiveness($from, $to, $locationId);
        $this->positionReport = $analytics->positionAnalysis($from, $to, $locationId);
        $this->rejectionReport = $analytics->rejectionAnalysis($from, $to, $locationId);
        $this->joinReport = $analytics->joinAnalysis($from, $to, $locationId);

        return view('recruit::report.index', $this->data);
    }

    public function reportChartData(Request $request)
    {
        $this->authorizeReport();
        [$from, $to] = $this->dateRange($request);
        $locationId = $this->locationId($request);
        $this->setSummaryCounts($from, $to, $locationId);

        $this->chart = [
            'labels' => [__('recruit::app.menu.jobApplication'), __('recruit::app.report.jobposted'), __('recruit::app.report.candidatehired'), __('recruit::app.report.interviews')],
            'colors' => ['orange', 'grey', 'green', 'blue'],
            'chart_data' => [$this->jobApplication, $this->job, $this->candidatesHired, $this->interviewScheduled],
        ];

        $html = view('recruit::report.chart', $this->data)->render();

        return Reply::dataOnly([
            'status' => 'success',
            'html' => $html,
            'jobApp' => $this->jobApplication,
            'jobPosted' => $this->job,
            'candidateHired' => $this->candidatesHired,
            'interview' => $this->interviewScheduled,
            'title' => $this->pageTitle,
        ]);
    }

    public function exportAnalytics(Request $request)
    {
        $this->authorizeReport();

        $from = $request->startDate
            ? Carbon::createFromFormat($this->company->date_format, $request->startDate, $this->company->timezone)->startOfDay()
            : now($this->company->timezone)->startOfYear();
        $to = $request->endDate
            ? Carbon::createFromFormat($this->company->date_format, $request->endDate, $this->company->timezone)->endOfDay()
            : now($this->company->timezone)->endOfMonth();
        $locationId = $this->locationId($request);

        return Excel::download(
            new RecruitmentAnalyticsExport($from, $to, $locationId),
            'Recruitment_Analytics_' . $to->format('Ym') . '.xlsx'
        );
    }

    private function authorizeReport(): void
    {
        abort_403(user()->permission('view_report') !== 'all');
    }

    private function dateRange(Request $request): array
    {
        $from = $request->startDate
            ? Carbon::createFromFormat($this->company->date_format, $request->startDate, $this->company->timezone)->startOfDay()
            : now($this->company->timezone)->startOfMonth();
        $to = $request->endDate
            ? Carbon::createFromFormat($this->company->date_format, $request->endDate, $this->company->timezone)->endOfDay()
            : now($this->company->timezone)->endOfDay();

        return [$from, $to];
    }

    private function locationId(Request $request): ?int
    {
        $locationId = $request->input('location');
        return $locationId && $locationId !== 'all' ? (int) $locationId : null;
    }

    private function setSummaryCounts(Carbon $from, Carbon $to, ?int $locationId): void
    {
        $applicationQuery = RecruitJobApplication::query()
            ->whereBetween('recruit_job_applications.created_at', [$from, $to])
            ->when($locationId, function (Builder $query, int $locationId) {
                $query->whereHas('job', function (Builder $jobQuery) use ($locationId) {
                    $jobQuery->where('hr_location_id', $locationId);
                });
            });

        $this->jobApplication = (clone $applicationQuery)->count();
        $this->candidatesHired = (clone $applicationQuery)
            ->where('recruit_job_applications.overall_status', 'hired')
            ->count();
        $this->job = RecruitJob::query()
            ->whereBetween('recruit_jobs.created_at', [$from, $to])
            ->when($locationId, fn (Builder $query, int $locationId) => $query->where('hr_location_id', $locationId))
            ->count();
        $this->interviewScheduled = RecruitInterviewSchedule::query()
            ->whereBetween('recruit_interview_schedules.created_at', [$from, $to])
            ->when($locationId, function (Builder $query, int $locationId) {
                $query->whereHas('jobApplication.job', function (Builder $jobQuery) use ($locationId) {
                    $jobQuery->where('hr_location_id', $locationId);
                });
            })
            ->count();
    }
}
