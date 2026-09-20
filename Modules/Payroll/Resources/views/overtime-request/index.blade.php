@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')

    {{-- overtime-action-ui: keep status and approval controls tidy without changing their behaviour --}}
    <style>
        #overtime-request th:last-child,
        #overtime-request td:last-child {
            width: 250px;
            min-width: 250px;
            padding-right: 24px !important;
            box-sizing: border-box;
            white-space: normal;
            vertical-align: middle;
        }

        #overtime-request td:last-child p {
            max-width: 100%;
            margin-right: 0;
            overflow-wrap: anywhere;
        }

        #overtime-request td:last-child .btn-sm {
            display: inline-flex;
            min-height: 34px;
            margin: 4px 4px 0 0 !important;
            padding: 6px 10px !important;
            align-items: center;
            justify-content: center;
            gap: 5px;
            line-height: 1.2;
            white-space: nowrap;
            border: 0;
            box-shadow: none;
        }

        #overtime-request td:last-child .editRequest {
            width: 34px;
            padding-right: 6px !important;
            padding-left: 6px !important;
        }

        #overtime-request td:last-child .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 5px 8px;
            font-weight: 500;
        }

        @media (max-width: 767.98px) {
            #overtime-request th:last-child,
            #overtime-request td:last-child {
                width: 210px;
                min-width: 210px;
            }
        }
    </style>
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex py-2 px-lg-3 px-md-3 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center" id="select-label">@lang('payroll::app.employee')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="employee_id" id="selectEmployee" data-live-search="true">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employees as $item)
                        <x-user-option :user="$item" :pill="true" />
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location" id="location">
                    <option value="" selected>@lang('app.all')</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- LOCATION END -->

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.department')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="department" id="department" data-live-search="true"
                    data-size="8">
                    <option value="">@lang('app.all')</option>
                </select>
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.designation')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="designation" id="designation" data-live-search="true"
                    data-size="8">
                    <option value="">@lang('app.all')</option>
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 pr-lg-3 pr-md-3 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.select') @lang('app.year')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="year" id="year">
                    @for ($i = $year; $i >= $year - 4; $i--)
                        <option @if ($i == $year) selected @endif value="{{ $i }}">
                            {{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-3 px-md-3 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center" id="select-label">@lang('app.select')
                @lang('app.month')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="month" id="month">
                    @foreach ($months as $key => $monthName)
                        <option value="{{ $key + 1 }}" @if ($month == $key + 1) selected @endif>
                            {{ __('app.months.' . ucfirst($monthName)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- SEARCH BY TASK END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

    </x-filters.filter-box>
@endsection

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->

        <div class="row">
            <div class="col-md-6 mb-3">
                <div
                    class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                    <div class="d-block ">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> @lang('payroll::modules.payroll.approvedStatus') </h5>
                        <div class="d-flex">
                            <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                <span id="requested">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('payroll::modules.payroll.requested')</span>
                            </p>

                            <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                <span id="approved">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('app.approved')</span>
                            </p>

                            <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                <span id="rejected">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('app.rejected')</span>
                            </p>
                            <p class="mb-0 f-21 font-weight-bold text-warning d-grid mr-5">
                                <span id="pending">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('app.pending')</span>
                            </p>
                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-thumbs-up text-lightest f-27"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div
                    class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                    <div class="d-block ">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> @lang('payroll::modules.payroll.overtimeHoursSummery') </h5>
                        <div class="d-flex">
                            <p class="mb-0 f-21 font-weight-bold  d-grid mr-5">
                                <span id="overtimeHours">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('payroll::modules.payroll.overtimeHours')</span>
                            </p>

                            <p class="mb-0 f-21 font-weight-bold d-grid mr-5">
                                <span id="compensation">0</span>
                                <span class="f-12 font-weight-normal text-lightest">@lang('payroll::modules.payroll.compensation')</span>
                            </p>

                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-hourglass text-lightest f-27"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex" id="table-actions">
            @if (!is_null($userPolicy) || user()->hasRole('admin'))
                <x-forms.link-primary link="javascript:;" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0 add-request"
                    icon="plus">
                    @lang('payroll::modules.payroll.addRequest')
                </x-forms.link-primary>
            @endif

            @if (canDataTableExport())
                <x-forms.button-secondary id="export-all" class="mr-3 mb-2 mb-lg-0" icon="file-export">
                    @lang('app.exportExcel')
                </x-forms.button-secondary>
            @endif
        </div>
        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        getOvertimeData();

        $('#overtime-request').on('preXhr.dt', function(e, settings, data) {
            const designation = $('#designation').val();
            const department = $('#department').val();
            const year = $('#year').val();
            const month = $('#month').val();
            const location = $('#location').val();
            const employee = $('#selectEmployee').val();

            data['designation'] = designation;
            data['department'] = department;
            data['year'] = year;
            data['month'] = month;
            data['location'] = location;
            data['employee'] = employee;
        });

        const showTable = () => {
            window.LaravelDataTables["overtime-request"].draw(false);
            getOvertimeData();
        }

        function getOvertimeData() {
            const location = $('#location').val();
            const designation = $('#designation').val();
            const department = $('#department').val();
            const year = $('#year').val();
            const month = $('#month').val();
            const employee = $('#selectEmployee').val();

            var url = "{{ route('overtime-request-data') }}?location=" + location + "&designation=" + designation +
                "&department=" + department +
                "&year=" + year + "&month=" + month + "&employee=" + employee;

            $.easyAjax({
                type: 'GET',
                url: url,
                success: function(response) {
                    $('#requested').html(response.overtimeData.requested);
                    $('#approved').html(response.overtimeData.approved);
                    $('#rejected').html(response.overtimeData.rejected);
                    $('#pending').html(response.overtimeData.pending);
                    $('#overtimeHours').html(response.overtimeData.overtimeHours);
                    $('#compensation').html(response.overtimeData.compensation);
                }
            });
        }

        $('#designation, #department, #location, #selectEmployee, #year, #month').on('change keyup',
            function() {
                if ($('#designation').val() !== "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#department').val() !== "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#location').val() !== "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#search-text-field').val() != "") {
                    $('#reset-filters').removeClass('d-none');
                } else {
                    $('#reset-filters').addClass('d-none');
                }

                showTable();
            });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '.add-request', function() {
            let url = '{{ route('overtime-requests.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });


        $('body').on('click', '.editRequest', function() {
            const requestId = $(this).data('request-id');
            let url = '{{ route('overtime-requests.edit', ':id') }}';
            url = url.replace(':id', requestId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.showRequest', function() {
            const requestId = $(this).data('request-id');
            let url = '{{ route('overtime-requests.show', ':id') }}';
            url = url.replace(':id', requestId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        /* delete overtime request */
        $('#overtime-request').on('click', '.delete-request-table-row', function() {
            let obj = $(this).closest('tr');
            var id = $(this).data('request-id');
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

                    var url = "{{ route('overtime-requests.destroy', ':id') }}";
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
                                obj.remove();
                                showTable();
                            }
                        }
                    });
                }
            });
        });
        /* PAYROLL SALARY SCRIPTS */



        /* Accept or reject overtime request */
        $('#overtime-request')
            .off('click.overtimeAction', '.acceptButton')
            .on('click.overtimeAction', '.acceptButton', function() {
                const id = $(this).data('request-id');
                const type = $(this).data('type');

            var butonText = "@lang('payroll::messages.confirmAccept')";
            if (type != 'accept') {
                butonText = "@lang('payroll::messages.confirmReject')";
            }
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('payroll::messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: butonText,
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

                    let url = "{{ route('overtime-request-accept', ':id') }}";
                    url = url.replace(':id', id);

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            type: type,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.status === "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });
        /* PAYROLL SALARY SCRIPTS */

        $('#overtime-request').on('change', '.change-status', function(e) {
            e.preventDefault();
            const id = $(this).data('request-id');
            const status = $(this).val();
            const token = "{{ csrf_token() }}";
            if (id !== undefined && id != '') {
                $.easyAjax({
                    url: '{{ route('overtime-change-status') }}',
                    type: "POST",
                    data: {
                        request_id: id,
                        status: status,
                        _token: token
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            showTable();
                        }
                    }
                })
            }
        });

        $("#location").on('change', function() {
            let location_id = $(this).val();
            let department_id = $("#department").val();

            let url = "{{ route('location.select') }}";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': location_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let teams = response.data;
                    let html = `<option value="">--</option>`;

                    teams.forEach((team) => {
                        html += `
                            <option value="${team.id}">${team.team_name}</option>
                        `
                    });

                    $("#department").html(html);
                    $("#department").selectpicker('refresh');
                    $("#designation").html(`<option value="">--</option>`);
                    $("#designation").selectpicker('refresh');

                    showTable();
                }

            });

        });

        $("#department").on('change', function() {
            let department_id = $(this).val();
            let location_id = $("#location").val();
            let designation_id = $("#designation").val();

            let url = "{{ route('department.select') }}";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': department_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let designations = response.data;
                    let html = `<option value="">--</option>`;

                    if (designations) {
                        designations.forEach((designation) => {
                            html += `
                            <option value="${designation.id}">${designation.name}</option>
                        `
                        });
                    }

                    $("#designation").html(html);
                    $("#designation").selectpicker('refresh');

                    showTable();
                }
            });
        });

        $("#designation").on('change', function() {
            showTable();
        });


        @if (canDataTableExport())
            $('#export-all').click(function(e) {
                e.preventDefault();

                var data = {
                    employee: $('#selectEmployee').val(),
                    location: $('#location').val(),
                    department: $('#department').val(),
                    designation: $('#designation').val(),
                    year: $('#year').val(),
                    month: $('#month').val()
                };

                var queryString = $.param(data);
                var baseUrl = "{{ route('overtime-requests.export_overtime_requests') }}";

                window.location.href = baseUrl + '?' + queryString;
            });
        @endif

        $('#overtime-request').off('click.overtimePreApprove','.preApproveButton')
            .on('click.overtimePreApprove','.preApproveButton', function() {
                    const requestId = $(this).data('request-id');

                    Swal.fire({
                        title: "@lang('messages.sweetAlertTitle')",
                        text: "{{ __('payroll::messages.confirmPreApproveOvertime') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: "{{ __('payroll::messages.preApproveOvertime') }}" ,
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
                    }).then(function(result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        let url ="{{ route('overtime-request-pre-approve', ':id') }}";

                        url = url.replace(
                            ':id',
                            requestId
                        );

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            blockUI: true,
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(
                                response
                            ) {
                                if (
                                    response.status ===
                                    'success'
                                ) {
                                    showTable();
                                }
                            }
                        });
                    });
                }
            );
    </script>
@endpush
