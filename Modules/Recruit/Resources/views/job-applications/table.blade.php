@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')

    <x-filters.filter-box>
        <!-- DATE START -->
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text"
                    class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>
        <!-- DATE END -->

        <!-- status start -->
        {{-- <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.status')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="status" id="status" data-live-search="true"
                        data-size="8">
                    <option {{ request('status') == 'all' ? 'selected' : '' }} value="all">@lang('app.all')</option>
                    @foreach ($applicationStatus as $status)
                        <option value="{{$status->id}}"
                                data-content="<i class='fa fa-circle mr-2' style='color: {{$status->color}}'></i> {{ $status->status }}"></option>
                    @endforeach
                </select>
            </div>
        </div> --}}
        <!-- status end -->

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('recruit::modules.job.job')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="job" id="job" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($jobs as $job)
                        <option data-content="" value="{{ $job->id }}">{{ $job->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Selection Phase</p>
            <div class="select-status">
                <select class="form-control select-picker" name="selection_phase" id="selection_phase"
                    data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    <option value="cv_screening">CV Screening</option>
                    <option value="first_interview">First Interview</option>
                    <option value="second_interview">Second Interview</option>
                    <option value="job_offer">Job Offer</option>
                    <option value="hiring">Hiring</option>
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Overall Status</p>
            <div class="select-status">
                <select class="form-control select-picker" name="overall_status" id="overall_status" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    <option value="not_started">Not Started</option>
                    <option value="in_progress">In Progress</option>
                    <option value="keep_cv">Keep CV</option>
                    <option value="rejected">Rejected</option>
                    <option value="hired">Hired</option>
                    <option value="job_offer">Job Offer</option>
                    <option value="hiring">Hiring</option>
                </select>
            </div>
        </div>


        <!-- SEARCH BY APPLICATION START -->
        <div class="task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                        placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <!-- SEARCH BY APPLICATION END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

        <!-- MORE FILTERS START -->
        <x-filters.more-filter-box>
            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('recruit::modules.jobApplication.gender')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="gender" data-container="body" id="gender">
                            <option value="all">@lang('app.all')</option>
                            <option value="male">@lang('app.male')</option>
                            <option value="female">@lang('app.female')</option>
                            <option value="others">@lang('app.others')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">Blacklist</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="blacklist" data-container="body" id="blacklist">
                            <option value="all">@lang('app.all')</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('recruit::modules.jobApplication.experience')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" id="total_experience" data-live-search="true"
                            data-container="body" data-size="8">
                            <option value="all">@lang('app.all')</option>
                            <option value="fresher">@lang('recruit::modules.jobApplication.fresher')</option>
                            <option value="1-2">1-2 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="3-4">3-4 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="5-6">5-6 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="7-8">7-8 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="9-10">9-10 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="11-12">11-12 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="13-14">13-14 @lang('recruit::modules.jobApplication.years')</option>
                            <option value="over-15">@lang('recruit::modules.jobApplication.over15')</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('recruit::modules.jobApplication.currentLocation')</label>
                <div class="select-filter">
                    <div class="select-others">
                        <select class="form-control select-picker" id="current_location" data-live-search="true"
                            data-container="body" data-size="8">
                            <option value="all">@lang('app.all')</option>
                            @if (count($currentLocations) > 0)
                                @foreach ($currentLocations as $currentLocation)
                                    <option value="{{ $currentLocation->current_location }}">
                                        {{ $currentLocation->current_location }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>


        </x-filters.more-filter-box>
        <!-- MORE FILTERS END -->
    </x-filters.filter-box>

@endsection

@php
    $addJobApplicationPermission = user()->permission('add_job_application');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar dd">

            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addJobApplicationPermission == 'all' || $addJobApplicationPermission == 'added')
                    <x-forms.link-primary :link="route('job-applications.create')" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0" icon="plus">
                        @lang('recruit::modules.jobApplication.addJobApplications')
                    </x-forms.link-primary>
                @endif

                <x-forms.button-secondary
                    class="btn-secondary rounded f-14 p-2 mr-3 float-left mb-2 mb-lg-0 mb-md-0 quick-add" icon="plus">
                    @lang('recruit::modules.jobApplication.quickAdd')
                </x-forms.button-secondary>

                @if ($addJobApplicationPermission == 'all' || $addJobApplicationPermission == 'added')
                    <x-forms.link-secondary :link="route('job-applications.import')"
                        class="mr-3 float-left mb-2 mb-lg-0 mb-md-0 d-none d-lg-block" icon="file-upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
            </div>

            <div class="d-flex">
                <x-datatable.actions>
                    <div class="select-status mr-3 pl-3">
                        <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                            <option value="">@lang('app.selectAction')</option>
                            <option value="change-status">@lang('modules.tasks.changeStatus')</option>
                            <option value="delete">@lang('app.delete')</option>
                        </select>
                    </div>
                    <div class="select-status mr-3 d-none quick-action-field" id="change-status-action">
                        <select name="status" class="form-control select-picker">
                            @foreach ($applicationStatus as $status)
                                <option value="{{ $status->id }}">
                                    {{ $status->slug == 'app.' . 'applied' || $status->slug == 'app.' . 'hired' ? __('app.' . $status->slug) : $status->status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </x-datatable.actions>

                <div class="btn-group mt-3 mt-lg-0 mt-md-0 ml-lg-3" role="group">
                    <a href="{{ route('job-applications.index') }}" class="btn btn-secondary f-14 btn-active"
                        data-toggle="tooltip" data-original-title="@lang('recruit::app.menu.tableView')"><i
                            class="side-icon bi bi-list-ul"></i></a>

                    <a href="{{ route('job-appboard.index') }}" class="btn btn-secondary f-14" data-toggle="tooltip"
                        data-original-title="@lang('recruit::app.menu.boardView')"><i class="side-icon bi bi-kanban"></i></a>

                </div>
            </div>
        </div>
        <div class="mt-3" id="quick-add-form">
            @include('recruit::job-applications.ajax.quick_add_form')
        </div>

        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('body').on('click', '#save-application', function() {
            const url = "{{ route('job-applications.quick_add_form_store') }}";

            $.easyAjax({
                url: url,
                container: '#save-application-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: "#save-application",
                data: $('#save-application-data-form').serialize(),
                success: function(response) {
                    $('#save-application-data-form')[0].reset();
                    $('#save-application-data-form .select-picker').selectpicker("refresh");
                    showTable();
                }
            });
        });

        $('#quick-add-form').hide();

        $('body').on('click', '.quick-add', function() {
            $('#quick-add-form').toggle();
            $('body').addClass('sidebar-toggled');
        });

        $('#job-applications-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = $('#datatableRange').val();

            let endDate;

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ $company->moment_format }}');
                endDate = dateRangePicker.endDate.format('{{ $company->moment_format }}');
            }

            const searchText = $('#search-text-field').val();
            const status = $('#status').val();
            const location = $('#location').val();
            const job = $('#job').val();
            const gender = $('#gender').val();
            const total_experience = $('#total_experience').val();
            const current_location = $('#current_location').val();
            const current_ctc_min = $('#current_ctc_min').val();
            const current_ctc_max = $('#current_ctc_max').val();
            const expected_ctc_min = $('#expected_ctc_min').val();
            const expected_ctc_max = $('#expected_ctc_max').val();
            const date_filter_on = $('#date_filter_on').val();
            const selection_phase = $('#selection_phase').val();
            const overall_status = $('#overall_status').val();
            const blacklist = $("#blacklist").val();

            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['status'] = status;
            data['location'] = location;
            data['job'] = job;
            data['gender'] = gender;
            data['total_experience'] = total_experience;
            data['current_location'] = current_location;
            data['current_ctc_min'] = current_ctc_min;
            data['current_ctc_max'] = current_ctc_max;
            data['expected_ctc_min'] = expected_ctc_min;
            data['expected_ctc_max'] = expected_ctc_max;
            data['searchText'] = searchText;
            data['date_filter_on'] = date_filter_on;
            data['selection_phase'] = selection_phase;
            data['overall_status'] = overall_status;
            data['blacklist'] = blacklist;

        });

        const showTable = () => {
            window.LaravelDataTables["job-applications-table"].draw(true);
        }

        $('#search-text-field, #status, #location, #job, #gender, #total_experience, #current_location, #current_ctc_min, #current_ctc_max, #expected_ctc_min, #expected_ctc_max, #selection_phase, #overall_status, #blacklist')
            .on('change keyup', function() {
                if ($('#search-text-field').val() !== "") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#status').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#location').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#job').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#gender').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#total_experience').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#current_location').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#current_ctc_min').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#current_ctc_max').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#expected_ctc_min').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#selection_phase').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#overall_status').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#expected_ctc_max').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#blacklist').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                }
                showTable();
            });

        $('body').on('click', '#reset-filters', function() {
            $('#filter-form')[0].reset();
            $('.filter-box #status').val('not finished');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '#reset-filters-2', function() {
            $('#filter-form')[0].reset();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('body').on('click', '#quick-action-apply', function() {

            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
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
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });
        const applyQuickAction = () => {
            var rowdIds = $("#job-applications-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            const url = "{{ route('job-applications.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };
        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('application-id');
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
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('job-applications.destroy', ':id') }}";
                    url = url.replace(':id', id);
                    var token = "{{ csrf_token() }}";
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        $('#job-applications-table').on('change', '.change-status', function() {
            var url = "{{ route('job-applications.change_status') }}";
            var token = "{{ csrf_token() }}";
            var id = $(this).data('status-id');
            var status = $(this).val();

            if (id != "" && status != "") {
                $.easyAjax({
                    url: url,
                    type: "POST",
                    container: '.content-wrapper',
                    blockUI: true,
                    data: {
                        '_token': token,
                        row_ids: id,
                        status: status,
                        sortBy: 'id'
                    },
                    success: function(response) {
                        let app_id = id;
                        let board = 0;
                        if (app_id && response.status.action == 'yes') {
                            if (response.status.category.name == 'shortlist') {
                                var url =
                                    "{{ route('job-appboard.application_remark', [':id', ':board']) }}";
                                url = url.replace(':id', app_id);
                                url = url.replace(':board', board);

                                $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
                                $.ajaxModal(MODAL_DEFAULT, url);
                            }
                            if (response.status.category.name == 'interview' && response
                                .interviewPermission == 'all') {
                                var url = "{{ route('job-appboard.interview', [':id', ':board']) }}";
                                url = url.replace(':id', app_id);
                                url = url.replace(':board', board);
                                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                                $.ajaxModal(MODAL_LG, url);
                            }
                            if (response.status.category.name == 'hired' && response
                                .offerLetterPermission == 'all') {
                                var url =
                                    "{{ route('job-appboard.offer_letter', [':id', ':board']) }}";
                                url = url.replace(':id', app_id);
                                url = url.replace(':board', board);
                                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                                $.ajaxModal(MODAL_LG, url);
                            }
                            if (response.status.category.name == 'rejected') {
                                var url =
                                    "{{ route('job-appboard.rejected_remark', [':id', ':board']) }}";
                                url = url.replace(':id', app_id);
                                url = url.replace(':board', board);
                                $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
                                $.ajaxModal(MODAL_DEFAULT, url);
                            }
                        }
                    }
                });

            }
        });

        $('#job-applications-table').on('change', '.change-workflow', function() {
            const input = $(this);
            const payload = {};
            payload[input.data('field')] = input.val();
            const url = "{{ route('job-applications.update_workflow', ':id') }}".replace(':id', input.data(
                'application-id'));

            if (input.data('field') === 'overall_status' && input.val() === 'rejected') {
                Swal.fire({
                    title: 'Rejection Reason',
                    input: 'select',
                    inputOptions: {
                        location_unfit: 'Location Unfit',
                        experience_gap: 'Experience Gap (Overqualified or Underqualified)',
                        culture_unfit: 'Culture Unfit',
                        salary_range_benefit: 'Salary Range & Benefit',
                        other_competitor_join: 'Other Competitor Join',
                        interview_absent: 'Interview Absent',
                        not_contact: 'Not Contact',
                        failed_assessment_or_interview: 'Failed Assessment or Interview',
                        failed_reference_check: 'Failed Reference Check',
                        position_closed: 'Position Closed',
                        blacklist: 'Blacklist'
                    },
                    inputPlaceholder: 'Select a reason',
                    showCancelButton: true,
                    inputValidator: value => !value ? 'Rejection reason is required' : undefined
                }).then(result => {
                    if (result.isConfirmed) {
                        payload.rejection_reason = result.value;
                        saveApplicantWorkflow(url, payload);
                    } else {
                        window.LaravelDataTables['job-applications-table'].draw(false);
                    }
                });
                return;
            }

            saveApplicantWorkflow(url, payload);
        });

        function saveApplicantWorkflow(url, payload) {
            $.easyAjax({
                url: url,
                type: 'POST',
                data: payload,
                success: function() {
                    window.LaravelDataTables['job-applications-table'].draw(false);
                }
            });
        }

        $('body').on('click', '.archive-job', function() {
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('recruit::messages.archiveMessage')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('recruit::messages.confirmArchive')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('candidate-database.store') }}";
                    var token = "{{ csrf_token() }}";
                    var rowId = $(this).data('application-id');

                    $.easyAjax({
                        url: url,
                        type: "POST",
                        data: {
                            '_token': token,
                            row_id: rowId
                        },
                        success: function(response) {
                            if (response.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });

        $('body').off('click', ".follow-up").on('click', '.follow-up', function() {
            let applicationId = $(this).data('application-id');
            let datatable = $(this).data('datatable');
            let searchQuery = "?id=" + applicationId + "&datatable=" + datatable;
            let url = "{{ route('candidate-follow-up.create') }}" + searchQuery;

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });
    </script>

    <script>
        function openApplicationFilter() {
            var omf = document.getElementById("application_filter");
            omf.classList.add("in");
        }

        function closeApplicationFilter() {
            var cls = document.getElementById("application_filter");
            cls.classList.remove("in");
        }

        if ($('#application_filter').length > 0) {
            $(document).on('mouseup', function(e) {
                var container = $("#application_filter");
                var searchField = $(".bs-searchbox");

                // if the target of the click isn't the container nor a descendant of the container
                if (container.is(e.target) && container.has(e.target).length === 0 && !searchField.is(e.target) &&
                    searchField.has(e.target).length === 0) {
                    closeApplicationFilter()
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            /*
             * Overall statuses allowed under each selection phase.
             */
            const overallStatusesByPhase = {
                cv_screening: {
                    not_started: 'Not Started',
                    keep_cv: 'Keep CV',
                    rejected: 'Rejected'
                },

                first_interview: {
                    in_progress: 'In Progress',
                    keep_cv: 'Keep CV',
                    rejected: 'Rejected',
                    passed: 'Passed'
                },

                second_interview: {
                    in_progress: 'In Progress',
                    keep_cv: 'Keep CV',
                    rejected: 'Rejected',
                    passed: 'Passed'
                },

                job_offer: {
                    hired: 'Hired'
                },

                hiring: {
                    hired: 'Hired',
                    rejected: 'Rejected'
                }
            };

            const selectionPhaseDropdown =
                $('#selection_phase');

            const overallStatusDropdown =
                $('#overall_status');

            /*
             * Refresh Bootstrap Select safely.
             */
            function refreshFilterSelectPicker(dropdown) {
                if (
                    dropdown.length &&
                    typeof dropdown.selectpicker === 'function'
                ) {
                    dropdown.selectpicker('refresh');
                }
            }

            /*
             * Set the selected value in both the original select
             * and the Bootstrap Select UI.
             */
            function setFilterValue(dropdown, value) {
                dropdown.val(value);

                if (
                    dropdown.length &&
                    typeof dropdown.selectpicker === 'function'
                ) {
                    dropdown.selectpicker('val', value);
                    dropdown.selectpicker('refresh');
                }
            }

            /*
             * Populate overall-status options based on the
             * selected selection phase.
             */
            function populateOverallStatusFilter() {
                const selectedPhase =
                    selectionPhaseDropdown.val() || 'all';

                /*
                 * Remove all previous values, including stale
                 * values such as "hired".
                 */
                overallStatusDropdown.empty();

                overallStatusDropdown.append(
                    new Option(
                        'All',
                        'all',
                        true,
                        true
                    )
                );

                /*
                 * When phase is All, do not add phase-specific
                 * statuses.
                 */
                if (selectedPhase !== 'all') {
                    const allowedStatuses =
                        overallStatusesByPhase[
                            selectedPhase
                        ] || {};

                    Object.entries(allowedStatuses)
                        .forEach(function([value, label]) {
                            overallStatusDropdown.append(
                                new Option(
                                    label,
                                    value,
                                    false,
                                    false
                                )
                            );
                        });
                }

                /*
                 * Always reset overall status to All whenever
                 * the selection phase changes.
                 */
                setFilterValue(
                    overallStatusDropdown,
                    'all'
                );
            }

            /*
             * Redraw the applicant DataTable.
             */
            function redrawApplicantTable() {
                if (
                    window.LaravelDataTables &&
                    window.LaravelDataTables[
                        'job-applications-table'
                    ]
                ) {
                    window.LaravelDataTables[
                        'job-applications-table'
                    ].draw();
                }
            }

            /*
             * Send the filter values with every DataTable
             * AJAX request.
             */
            $('#job-applications-table')
                .off('preXhr.dt.applicantFilters')
                .on(
                    'preXhr.dt.applicantFilters',
                    function(event, settings, data) {
                        data.selection_phase =
                            selectionPhaseDropdown.val() ||
                            'all';

                        data.overall_status =
                            overallStatusDropdown.val() ||
                            'all';
                    }
                );

            /*
             * Selection phase changed:
             * reset overall status and reload the table.
             */
            $(document)
                .off(
                    'change.applicantPhaseFilter',
                    '#selection_phase'
                )
                .on(
                    'change.applicantPhaseFilter',
                    '#selection_phase',
                    function() {
                        populateOverallStatusFilter();
                        redrawApplicantTable();
                    }
                );

            /*
             * Overall status changed:
             * reload the table.
             */
            $(document)
                .off(
                    'change.applicantStatusFilter',
                    '#overall_status'
                )
                .on(
                    'change.applicantStatusFilter',
                    '#overall_status',
                    function() {
                        redrawApplicantTable();
                    }
                );

            /*
             * Initial page setup.
             */
            if (!selectionPhaseDropdown.val()) {
                setFilterValue(
                    selectionPhaseDropdown,
                    'all'
                );
            }

            populateOverallStatusFilter();

            refreshFilterSelectPicker(
                selectionPhaseDropdown
            );

            refreshFilterSelectPicker(
                overallStatusDropdown
            );
        });
    </script>
@endpush
