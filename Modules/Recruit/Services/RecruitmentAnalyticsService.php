<?php

namespace Modules\Recruit\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Recruit\Entities\RecruitJobApplication;

class RecruitmentAnalyticsService
{
    public function dashboardSummary(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        $summary = [];
        $month = $from->copy()->startOfMonth();
        $lastMonth = $to->copy()->startOfMonth();

        while ($month->lte($lastMonth)) {
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            if ($periodStart->lt($from)) $periodStart = $from->copy();
            if ($periodEnd->gt($to)) $periodEnd = $to->copy();

            $query = RecruitJobApplication::query()
                ->whereBetween('recruit_job_applications.created_at', [$periodStart, $periodEnd])
                ->when($locationId, function (Builder $query, int $locationId) {
                    $query->whereHas('job', function (Builder $jobQuery) use ($locationId) {
                        $jobQuery->where('hr_location_id', $locationId);
                    });
                });

            $totalCv = (clone $query)->count();
            $interviewed = (clone $query)->whereIn('selection_phase', ['first_interview', 'second_interview', 'job_offer', 'hiring'])->count();
            $hired = (clone $query)->where('overall_status', 'hired')->count();
            $accepted = (clone $query)->where('overall_status', 'hired')->where('job_offer_decision', 'accepted')->count();
            $joined = (clone $query)->whereNotNull('employee_user_id')->count();

            $summary[$month->format('M-y')] = [
                'total_cv' => $totalCv,
                'interviewed' => $interviewed,
                'hired' => $hired,
                'offer_accepted' => $accepted,
                'joined' => $joined,
                'acceptance_rate' => $hired > 0 ? round(($joined / $hired) * 100, 1) : 0,
                'cv_per_accepted' => $accepted > 0 ? round($totalCv / $accepted, 2) : 0,
                'cv_per_hire' => $hired > 0 ? round($totalCv / $hired, 2) : 0,
            ];
            $month->addMonth();
        }

        return $summary;
    }

    public function sourceEffectiveness(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        $rows = RecruitJobApplication::query()
            ->leftJoin('application_sources', 'application_sources.id', '=', 'recruit_job_applications.application_source_id')
            ->leftJoin('recruit_jobs', 'recruit_jobs.id', '=', 'recruit_job_applications.recruit_job_id')
            ->leftJoin('designations', 'designations.id', '=', 'recruit_jobs.designation_id')
            ->whereBetween(DB::raw('DATE(recruit_job_applications.created_at)'), [$from->toDateString(), $to->toDateString()])
            ->when($locationId, fn ($query, int $locationId) => $query->where('recruit_jobs.hr_location_id', $locationId))
            ->selectRaw("COALESCE(application_sources.application_source, 'Other') source,
                SUM(CASE WHEN designations.rank_id >= 6 THEN 1 ELSE 0 END) upper_count,
                SUM(CASE WHEN designations.rank_id BETWEEN 4 AND 5 THEN 1 ELSE 0 END) middle_count,
                SUM(CASE WHEN designations.rank_id BETWEEN 1 AND 3 THEN 1 ELSE 0 END) frontline_count,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'hired' THEN 1 ELSE 0 END) hired,
                COUNT(*) total_cv")
            ->groupBy('source')->orderByDesc('total_cv')->get();
        $total = max(1, $rows->sum('total_cv'));

        return $rows->map(fn ($row) => array_merge($row->toArray(), ['effectiveness' => round(($row->total_cv / $total) * 100, 1)]))->all();
    }

    public function positionAnalysis(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        return RecruitJobApplication::query()
            ->leftJoin('recruit_jobs', 'recruit_jobs.id', '=', 'recruit_job_applications.recruit_job_id')
            ->leftJoin('designations', 'designations.id', '=', 'recruit_jobs.designation_id')
            ->whereBetween(DB::raw('DATE(recruit_job_applications.created_at)'), [$from->toDateString(), $to->toDateString()])
            ->when($locationId, fn ($query, int $locationId) => $query->where('recruit_jobs.hr_location_id', $locationId))
            ->selectRaw("CASE WHEN designations.rank_id >= 6 THEN 'Upper' WHEN designations.rank_id BETWEEN 4 AND 5 THEN 'Middle' ELSE 'Frontline' END level,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'hired' THEN 1 ELSE 0 END) hired,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'in_progress' AND recruit_job_applications.selection_phase IN ('first_interview','second_interview') THEN 1 ELSE 0 END) interview,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'keep_cv' THEN 1 ELSE 0 END) keep_cv,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'not_started' THEN 1 ELSE 0 END) not_started,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'rejected' AND recruit_job_applications.selection_phase = 'cv_screening' THEN 1 ELSE 0 END) rejected_screening,
                SUM(CASE WHEN recruit_job_applications.overall_status = 'rejected' AND recruit_job_applications.selection_phase <> 'cv_screening' THEN 1 ELSE 0 END) rejected_interview")
            ->groupBy('level')->get()->keyBy('level')->toArray();
    }

    public function rejectionAnalysis(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        return RecruitJobApplication::query()
            ->whereBetween(DB::raw('DATE(recruit_job_applications.created_at)'), [$from->toDateString(), $to->toDateString()])
            ->when($locationId, function (Builder $query, int $locationId) {
                $query->whereHas('job', function (Builder $jobQuery) use ($locationId) {
                    $jobQuery->where('hr_location_id', $locationId);
                });
            })
            ->where('recruit_job_applications.overall_status', 'rejected')
            ->whereNotNull('recruit_job_applications.rejection_reason')
            ->selectRaw("recruit_job_applications.rejection_reason,
                SUM(CASE WHEN recruit_job_applications.selection_phase = 'cv_screening' THEN 1 ELSE 0 END) after_screening,
                SUM(CASE WHEN recruit_job_applications.selection_phase <> 'cv_screening' THEN 1 ELSE 0 END) after_interview")
            ->groupBy('recruit_job_applications.rejection_reason')->get()->keyBy('rejection_reason')->toArray();
    }

    public function joinAnalysis(Carbon $from, Carbon $to, ?int $locationId = null): array
    {
        return RecruitJobApplication::query()
            ->leftJoin('recruit_jobs', 'recruit_jobs.id', '=', 'recruit_job_applications.recruit_job_id')
            ->leftJoin('designations', 'designations.id', '=', 'recruit_jobs.designation_id')
            ->whereBetween(DB::raw('DATE(recruit_job_applications.created_at)'), [$from->toDateString(), $to->toDateString()])
            ->when($locationId, fn ($query, int $locationId) => $query->where('recruit_jobs.hr_location_id', $locationId))
            ->where('recruit_job_applications.job_offer_decision', 'accepted')
            ->selectRaw("CASE WHEN designations.rank_id >= 6 THEN 'Upper' WHEN designations.rank_id BETWEEN 4 AND 5 THEN 'Middle' ELSE 'Frontline' END level,
                SUM(CASE WHEN recruit_job_applications.employee_user_id IS NOT NULL THEN 1 ELSE 0 END) joined,
                SUM(CASE WHEN recruit_job_applications.employee_user_id IS NULL THEN 1 ELSE 0 END) not_joined")
            ->groupBy('level')->get()->keyBy('level')->toArray();
    }
}
