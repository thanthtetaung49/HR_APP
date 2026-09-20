@php
    $editPermission = user()->permission('edit_job');
    $deletePermission = user()->permission('delete_job');

    $rankLevel = $job->designation?->rank_id ?? $job->rank_level;
    $managementRank = \App\Models\ManagementRank::all()->first(function ($item) use ($rankLevel) {
        $ranks = is_array($item->rank) ? $item->rank : json_decode($item->rank ?: '[]', true);

        return is_array($ranks)
            && in_array((string) $rankLevel, array_map('strval', $ranks), true);
    });

    $skillNames = $job->skills
        ->map(fn ($jobSkill) => $jobSkill->skill?->name)
        ->filter()
        ->implode(', ');

    $companyNames = $job->address->pluck('location')->filter()->implode(', ');
    $stageNames = $job->stages->pluck('name')->filter()->implode(', ');
    $requiredQuestions = $job->question->pluck('question')->filter()->implode(', ');

    $yesNo = fn ($value) => in_array($value, [1, '1', true, 'yes'], true) ? __('app.yes') : __('app.no');
@endphp

@push('styles')
    <style>
        .job-detail-section-title {
            color: #1d82f5;
            font-size: 15px;
            font-weight: 600;
            margin: 6px 0 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e8eef5;
        }

        .job-description-content p {
            margin: 0 !important;
        }
    </style>
@endpush

<div class="row">
    <div class="col-xl-7 col-lg-7 col-md-12">
        <div class="row">
            <div class="col-xl-6 col-sm-12 mb-4">
                <x-cards.widget :title="__('recruit::app.job.openings')" :value="$openingsCount" icon="tasks" />
            </div>
            <div class="col-xl-6 col-sm-12 mb-4">
                <x-cards.widget :title="__('recruit::app.job.inProgress')" :value="$inProgressCount" icon="clock" />
            </div>
            <div class="col-xl-6 col-sm-12 mb-4">
                <x-cards.widget :title="__('recruit::modules.email.subject')" :value="$scheduledCount" icon="calendar" />
            </div>
            <div class="col-xl-6 col-sm-12 mb-4">
                <x-cards.widget :title="__('recruit::app.job.offerReleased')" :value="$offerReleasedCount" icon="layer-group" />
            </div>
        </div>
    </div>

    <div class="col-xl-5 col-lg-5 col-md-12 mb-4">
        <div class="bg-white h-100 d-flex align-items-center justify-content-center">
            <x-pie-chart id="task-chart" :labels="$applicationStatus['labels']"
                :values="$applicationStatus['values']" :colors="$applicationStatus['colors']"
                height="200" width="250" />
        </div>
    </div>
</div>

<div id="notice-detail-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white border-bottom-grey justify-content-between p-20">
                    <div class="row align-items-center">
                        <div class="col-lg-10 col-10">
                            <h3 class="heading-h1 mb-0">{{ ucwords($job->title) ?: '--' }}</h3>
                        </div>

                        <div class="col-lg-2 col-2 text-right">
                            @if ($editPermission != 'none' || $deletePermission != 'none')
                                <div class="dropdown">
                                    <button class="btn btn-lg f-14 px-2 py-1 text-dark-grey rounded dropdown-toggle"
                                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0">
                                        @if ($editPermission == 'all'
                                            || ($editPermission == 'added' && $job->added_by == user()->id)
                                            || ($editPermission == 'owned' && $job->recruiter_id == user()->id)
                                            || ($editPermission == 'both' && ($job->recruiter_id == user()->id || $job->added_by == user()->id)))
                                            <a class="dropdown-item openRightModal"
                                                href="{{ route('jobs.edit', $job->id) }}">@lang('app.edit')</a>
                                        @endif

                                        @if ($deletePermission == 'all'
                                            || ($deletePermission == 'added' && $job->added_by == user()->id)
                                            || ($deletePermission == 'owned' && $job->recruiter_id == user()->id)
                                            || ($deletePermission == 'both' && ($job->recruiter_id == user()->id || $job->added_by == user()->id)))
                                            <a class="dropdown-item delete-table-row" href="javascript:;">@lang('app.delete')</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h5 class="job-detail-section-title">Position and Organization</h5>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Job Title" :value="$job->title ?: '--'" />
                            <x-cards.data-row label="Location" :value="$job->hrLocation?->location_name ?: '--'" />
                            <x-cards.data-row :label="__('app.department')" :value="$job->team?->team_name ?: '--'" />
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Designation" :value="$job->designation?->name ?: '--'" />
                            <x-cards.data-row label="Rank" :value="$rankLevel ? 'Rank ' . $rankLevel : '--'" />
                            <x-cards.data-row label="Management Rank" :value="$managementRank?->name ?: '--'" />
                        </div>

                        {{-- <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Vacancy" :value="$job->total_positions ?? '--'" />
                            <x-cards.data-row label="Remaining Openings" :value="$job->remaining_openings ?? '--'" />
                            <x-cards.data-row label="Approved Manpower" :value="$job->manPowerReport?->man_power_setup ?? '--'" />
                        </div> --}}
                    </div>

                    <h5 class="job-detail-section-title mt-3">Job Configuration</h5>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Skills" :value="$skillNames ?: '--'" />
                            <x-cards.data-row label="Company Name" :value="$companyNames ?: '--'" />
                            <x-cards.data-row label="Interview Stages" :value="$stageNames ?: '--'" />
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row :label="__('recruit::modules.job.startDate')"
                                :value="$job->start_date?->format($company->date_format) ?: '--'" />
                            <x-cards.data-row :label="__('recruit::modules.job.endDate')"
                                :value="$job->end_date ? $job->end_date->format($company->date_format) : __('recruit::modules.job.noEndDate')" />
                            <x-cards.data-row label="Status" :value="ucwords($job->status ?: '--')" />
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Recruiter" :value="$job->employee?->name ?: '--'" />
                            <x-cards.data-row :label="__('recruit::app.job.jobtype')"
                                :value="$job->jobType?->job_type ?: '--'" />
                            <x-cards.data-row :label="__('recruit::app.job.workexperience')"
                                :value="$job->workExperience?->work_experience ?: '--'" />
                        </div>
                    </div>

                    <h5 class="job-detail-section-title mt-3">Payment and Work Arrangement</h5>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Currency" :value="$job->currency?->currency_code ?: '--'" />
                            {{-- <x-cards.data-row label="Pay Type" :value="$job->pay_type ?: '--'" /> --}}
                            {{-- <x-cards.data-row label="Pay According" :value="$job->pay_according ? ucwords($job->pay_according) : '--'" /> --}}
                        </div>

                        {{-- <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Starting/Exact Amount"
                                :value="$job->start_amount !== null ? number_format($job->start_amount, 2) : '--'" />
                            <x-cards.data-row label="Maximum Amount"
                                :value="$job->end_amount !== null ? number_format($job->end_amount, 2) : '--'" />
                        </div> --}}

                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Remote Job" :value="$yesNo($job->remote_job)" />
                            {{-- <x-cards.data-row label="Disclose Salary" :value="$yesNo($job->disclose_salary)" /> --}}
                        </div>
                    </div>

                    <h5 class="job-detail-section-title mt-3">Required Applicant Fields</h5>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Photo Required" :value="$yesNo($job->is_photo_require)" />
                            <x-cards.data-row label="Resume Required" :value="$yesNo($job->is_resume_require)" />
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Date of Birth Required" :value="$yesNo($job->is_dob_require)" />
                            <x-cards.data-row label="Gender Required" :value="$yesNo($job->is_gender_require)" />
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <x-cards.data-row label="Current Salary Required" :value="$yesNo($job->is_currentctc_require)" />
                            <x-cards.data-row label="Expected Salary Required" :value="$yesNo($job->is_expectedctc_require)" />
                        </div>
                    </div>

                    {{-- <h5 class="job-detail-section-title mt-3">Additional Information</h5>
                    <div class="row">
                        <div class="col-lg-6 col-md-12">
                            <x-cards.data-row :label="__('app.category')"
                                :value="$job->category?->category_name ?: '--'" />
                            <x-cards.data-row :label="__('recruit::modules.job.subCategory')"
                                :value="$job->subcategory?->sub_category_name ?: '--'" />
                            <x-cards.data-row label="Meta Title"
                                :value="data_get($job->meta_details, 'title', '--') ?: '--'" />
                            <x-cards.data-row label="Meta Description"
                                :value="data_get($job->meta_details, 'description', '--') ?: '--'" />
                            <x-cards.data-row label="Additional Required Questions"
                                :value="$requiredQuestions ?: '--'" />
                        </div>

                        <div class="col-lg-6 col-md-12">
                            <div class="col-12 px-0 pb-3">
                                <p class="mb-1 text-lightest f-14">@lang('app.description')</p>
                                <div class="text-dark-grey f-14 text-wrap ql-editor job-description-content p-0">
                                    {!! $job->job_description ?: '--' !!}
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('body').off('click.jobProfileDelete', '.delete-table-row')
            .on('click.jobProfileDelete', '.delete-table-row', function () {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.easyAjax({
                        type: 'POST',
                        url: "{{ route('jobs.destroy', $job->id) }}",
                        blockUI: true,
                        data: {
                            '_token': "{{ csrf_token() }}",
                            '_method': 'DELETE'
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                window.location.href = response.redirectUrl;
                            }
                        }
                    });
                });
            });
    });
</script>
