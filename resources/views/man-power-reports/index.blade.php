@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
    <style>
        .filter-box {
            z-index: 2;
        }
    </style>
@endpush

@php
    $addDepartmentPermission = user()->permission('add_department');
@endphp


@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location_id" id="location_id" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.teams')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="team_id" id="team_id" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->team_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.position')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="position" id="position" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($designations as $position)
                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.quarter')</p>
            <div class="select-status">
                <select class="form-control select-picker mt" name="quarter" id="quarter" data-live-search="true">
                    <option value="all">@lang('app.all')</option>
                    <option value="1">Q1 (Jan to Mar)</option>
                    <option value="2">Q2 (Apr to Jun)</option>
                    <option value="3">Q3 (Jul to Sept)</option>
                    <option value="4">Q4 (Oct to Dec)</option>
                </select>
            </div>
        </div>

        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.budgetYear')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="budget_year" id="budget_year" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($budgetYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- DATE START -->
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text"
                    class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange" placeholder="@lang('placeholders.dateRange')"
                    value="{{ request('start') && request('end') ? request('start') . ' ' . __('app.to') . ' ' . request('end') : '' }}">
            </div>
        </div>
        <!-- DATE END -->

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

        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <x-forms.link-primary :link="route('man-power-reports.create')" class="mr-3 float-left" icon="plus">
                    @lang('app.menu.manPower')
                </x-forms.link-primary>
            </div>
        </div>

        <x-datatable.actions>
            <div class="select-status mr-3 pl-3">
                <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                    <option value="">@lang('app.selectAction')</option>
                    <option value="delete">@lang('app.delete')</option>
                </select>
            </div>
        </x-datatable.actions>

        <!-- leave table Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- leave table End -->

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('#manpowerreport-table').on('preXhr.dt', function(e, settings, data) {
            @if (request('start') && request('end'))
                $('#datatableRange').data('daterangepicker').setStartDate("{{ request('start') }}");
                $('#datatableRange').data('daterangepicker').setEndDate("{{ request('end') }}");
            @endif

            var dateRangePicker = $('#datatableRange').data('daterangepicker');

            let startDate = $('#datatableRange').val();

            let endDate;

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            const teamId = $('#team_id').val();
            const locationId = $('#location_id').val();
            const budgetYear = $('#budget_year').val();
            const position = $('#position').val();
            const quarter = $('#quarter').val();

            data['teamId'] = teamId;
            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['locationId'] = locationId;
            data['budgetYear'] = budgetYear;
            data['position'] = position;
            data['quarter'] = quarter;
        });

        const showTable = () => {
            window.LaravelDataTables["manpowerreport-table"].draw(true);
        }

        $('#team_id, #location_id, #budget_year, #position, #quarter').on('change keyup',
            function() {
                if ($('#team_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($("#datatableRange").val() != "") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($("#location_id").val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($("#budget_year").val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($("#position").val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($("#quarter").val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else {
                    $('#reset-filters').addClass('d-none');
                }

                showTable();
            });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            $('.filter-box #status').val('not finished');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('manpower-id');
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
                    var url = "{{ route('man-power-reports.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    console.log(url);

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
                            console.log(response.redirectUrl);
                            window.location.href = response.redirectUrl;
                            // if (response.message == "success") {
                            //     // showTable();
                            // }
                        }
                    });
                }
            });
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();

            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue === 'delete') {
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
            const rowdIds = $("#manpowerreport-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();


            const url = "{{ route('manPowerReports.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };
    </script>
@endpush
