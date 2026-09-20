@php
    $blue = 'background:#245782;color:white;font-weight:600;text-align:center;';
    $reasonLabels = [
        'location_unfit'=>'Location Unfit','experience_gap'=>'Experience & Requirement Gap','culture_unfit'=>'Culture Unfit',
        'salary_range_benefit'=>'Salary Range (Compensation & Benefit)','other_competitor_join'=>'Other Competitor Join',
        'interview_absent'=>'Interview Absent','not_contact'=>'Not Contact','failed_assessment_or_interview'=>'Failed Test, Assessment or Interview',
        'failed_reference_check'=>'Failed Reference Check','position_closed'=>'Position Closed','blacklist'=>'Blacklist'
    ];
@endphp
<style>.hr-report-table th{ {!! $blue !!} } .hr-report-table td,.hr-report-table th{border:2px solid #222!important;vertical-align:middle!important}</style>

<x-cards.data title="Dashboard Summary Report" otherClasses="mt-4">
    <div class="table-responsive"><table class="table hr-report-table"><thead><tr><th>Description</th>@foreach(array_keys($summaryReport) as $month)<th>{{ $month }}</th>@endforeach</tr></thead><tbody>
    @foreach(['total_cv'=>'Total CV IN','interviewed'=>'Interviewed','hired'=>'Total Hired','offer_accepted'=>'Offer Accepted','joined'=>'Joined','acceptance_rate'=>'Offer Acceptance Rate by Total Applicant','cv_per_accepted'=>'Average CV Needed for 1 Accepted Candidate','cv_per_hire'=>'Average CV Required per Hire'] as $key=>$label)
        <tr><td>{{ $label }}</td>@foreach($summaryReport as $values)<td class="text-center">{{ $values[$key] }}@if($key==='acceptance_rate')%@endif</td>@endforeach</tr>
    @endforeach
    </tbody></table></div>
</x-cards.data>

<x-cards.data title="Source Effectiveness Analysis (CV In Report - By Channel)" otherClasses="mt-4">
    <div class="table-responsive"><table class="table hr-report-table"><thead><tr><th>Recruitment Channel</th><th>Hire by Channel</th><th>Upper</th><th>Middle</th><th>Frontline</th><th>Total CV IN</th><th>Effectiveness Rate on Total CV In</th></tr></thead><tbody>
    @foreach($sourceReport as $row)<tr><td>{{ $row['source'] }}</td><td class="text-center">{{ $row['hired'] }}</td><td class="text-center">{{ $row['upper_count'] }}</td><td class="text-center">{{ $row['middle_count'] }}</td><td class="text-center">{{ $row['frontline_count'] }}</td><td class="text-center">{{ $row['total_cv'] }}</td><td class="text-center">{{ $row['effectiveness'] }}%</td></tr>@endforeach
    </tbody></table></div>
</x-cards.data>

<x-cards.data title="Position Wise Hiring Report (Recruitment Status By Level)" otherClasses="mt-4">
    <div class="table-responsive"><table class="table hr-report-table"><thead><tr><th>Level</th><th>Hire</th><th>In Progress (Interview)</th><th>Keep CV</th><th>Not Started</th><th>Reject (After Screening)</th><th>Reject (After Interviewed)</th></tr></thead><tbody>
    @foreach(['Upper','Middle','Frontline'] as $level) @php($row=$positionReport[$level]??[]) <tr><td>{{ $level }}</td>@foreach(['hired','interview','keep_cv','not_started','rejected_screening','rejected_interview'] as $key)<td class="text-center">{{ $row[$key]??0 }}</td>@endforeach</tr>@endforeach
    </tbody></table></div>
</x-cards.data>

<x-cards.data title="Rejection Reason Analysis" otherClasses="mt-4">
    <div class="table-responsive"><table class="table hr-report-table"><thead><tr><th>Reject Reason</th><th>After Screening</th><th>After Interviewed</th></tr></thead><tbody>
    @foreach($reasonLabels as $key=>$label) @php($row=$rejectionReport[$key]??[]) <tr><td>{{ $label }}</td><td class="text-center">{{ $row['after_screening']??0 }}</td><td class="text-center">{{ $row['after_interview']??0 }}</td></tr>@endforeach
    </tbody></table></div>
</x-cards.data>

<x-cards.data title="Join & Not Join Status Summary Report" otherClasses="mt-4">
    <div class="table-responsive"><table class="table hr-report-table"><thead><tr><th>Level</th><th>Join</th><th>Not Join</th></tr></thead><tbody>
    @foreach(['Upper','Middle','Frontline'] as $level) @php($row=$joinReport[$level]??[]) <tr><td>{{ $level }}</td><td class="text-center">{{ $row['joined']??0 }}</td><td class="text-center">{{ $row['not_joined']??0 }}</td></tr>@endforeach
    </tbody></table></div>
</x-cards.data>
