<?php

namespace Modules\Recruit\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Recruit\Services\RecruitmentAnalyticsService;

class RecruitmentAnalyticsExport implements WithMultipleSheets
{
    public function __construct(private Carbon $from, private Carbon $to) {}

    public function sheets(): array
    {
        $service = app(RecruitmentAnalyticsService::class);
        $summary = $service->dashboardSummary(
            $this->from,
            $this->to
        );
        $summaryRows = [['Dashboard Summary Report'], array_merge(['Description'], array_keys($summary))];
        foreach (['total_cv' => 'Total CV IN', 'interviewed' => 'Interviewed', 'hired' => 'Total Hired', 'offer_accepted' => 'Offer Accepted', 'joined' => 'Joined', 'acceptance_rate' => 'Offer Acceptance Rate (%)', 'cv_per_accepted' => 'Average CV Needed for 1 Accepted Candidate', 'cv_per_hire' => 'Average CV Required per Hire'] as $key => $label) {
            $summaryRows[] = array_merge([$label], array_map(fn($month) => $month[$key], $summary));
        }

        $sourceRows = [['Source Effectiveness Analysis'], ['Recruitment Channel', 'Hire by Channel', 'Upper', 'Middle', 'Frontline', 'Total CV IN', 'Effectiveness Rate (%)']];
        foreach ($service->sourceEffectiveness($this->from, $this->to) as $row) $sourceRows[] = [$row['source'], $row['hired'], $row['upper_count'], $row['middle_count'], $row['frontline_count'], $row['total_cv'], $row['effectiveness']];

        $positionRows = [['Position Wise Hiring Report'], ['Level', 'Hire', 'In Progress (Interview)', 'Keep CV', 'Not Started', 'Reject (After Screening)', 'Reject (After Interviewed)']];
        $positions = $service->positionAnalysis($this->from, $this->to);
        foreach (['Upper', 'Middle', 'Frontline'] as $level) {
            $r = $positions[$level] ?? [];
            $positionRows[] = [$level, $r['hired'] ?? 0, $r['interview'] ?? 0, $r['keep_cv'] ?? 0, $r['not_started'] ?? 0, $r['rejected_screening'] ?? 0, $r['rejected_interview'] ?? 0];
        }

        $reasonRows = [['Rejection Reason Analysis'], ['Reject Reason', 'After Screening', 'After Interviewed']];
        foreach ($service->rejectionAnalysis($this->from, $this->to) as $reason => $r) $reasonRows[] = [str_replace('_', ' ', ucwords($reason, '_')), $r['after_screening'], $r['after_interview']];

        $joinRows = [['Join & Not Join Status Summary'], ['Level', 'Join', 'Not Join']];
        $joins = $service->joinAnalysis($this->from, $this->to);
        foreach (['Upper', 'Middle', 'Frontline'] as $level) {
            $r = $joins[$level] ?? [];
            $joinRows[] = [$level, $r['joined'] ?? 0, $r['not_joined'] ?? 0];
        }

        return [
            new RecruitmentReportSheet('Dashboard Summary', $summaryRows),
            new RecruitmentReportSheet('Source Effectiveness', $sourceRows),
            new RecruitmentReportSheet('Position Analysis', $positionRows),
            new RecruitmentReportSheet('Rejection Reasons', $reasonRows),
            new RecruitmentReportSheet('Offer Acceptance KPI', $joinRows),
        ];
    }
}
