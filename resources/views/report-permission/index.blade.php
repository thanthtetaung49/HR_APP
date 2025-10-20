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
    $addDepartmentPermission = user()->permission('add_report_permission');
@endphp


@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ">
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
                    {{-- @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->team_name }}</option>
                    @endforeach --}}
                </select>
            </div>
        </div>

        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3 me-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.position')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="position" id="position" data-live-search="true"
                    data-size="8">
                    <option value="all">@lang('app.all')</option>
                    {{-- @foreach ($designations as $position)
                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                    @endforeach --}}
                </select>
            </div>
        </div>

        <!-- DATE END -->

        <!-- SEARCH BY TASK START -->
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

        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <x-forms.link-primary :link="route('report-permission.create')" class="mr-3 float-left" icon="plus">
                    @lang('app.menu.reportPermission')
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
        $('#reportpermission-table').on('preXhr.dt', function(e, settings, data) {
            $searchText = $('#search-text-field').val();
            $location = $('#location_id').val();
            $department = $('#team_id').val();
            $designation = $('#position').val();

            data['searchText'] = $searchText;
            data['location'] = $location;
            data['department'] = $department;
            data['designation'] = $designation;
        });

        const showTable = () => {
            window.LaravelDataTables["reportpermission-table"].draw(true);
        }

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $(`#location_id, #team_id, #position`)
            .on(
                'change keyup',
                function() {
                    if ($('#location_id').val() != "all") {
                        $('#reset-filters').removeClass('d-none');
                    } else if ($('#team_id').val() != "all") {
                        $('#reset-filters').removeClass('d-none');
                    } else if ($('#position').val() != "all") {
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
            var id = $(this).data('reportpermission-id');
            console.log(id);
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
                    var url = "{{ route('report-permission.destroy', ':id') }}";
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
            const rowdIds = $("#reportpermission-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();


            const url = "{{ route('reportPermission.apply_quick_action') }}?row_ids=" + rowdIds;

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

        $("#location_id").on('change', function() {
            let location_id = $(this).val();
            let designation_id = $("#position").val();
            let department_id = $("#team_id").val();

            let department_html = `<option value="all">--</option>`;
            let designation_html = `<option value="all">--</option>`;

            let url = "{{ route('location.select') }}";

            if (department_id != "all" && designation_id != "all") {
                $("#team_id").html(department_html);
                $("#team_id").selectpicker('refresh');

                $("#position").html(designation_html);
                $("#position").selectpicker('refresh');

            } else if (designation_id != "all") {
                $("#position").html(designation_html);
                $("#position").selectpicker('refresh');
            } else {
                $("#team_id").html(department_html);
                $("#team_id").selectpicker('refresh');
            }


            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': location_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let teams = response.data;
                    let html = department_html;

                    teams.forEach((team) => {
                        html += `
                        <option value="${team.id}">${team.team_name}</option>
                    `
                    });

                    $("#team_id").html(html);
                    $("#team_id").selectpicker(
                        'refresh'); // refresh the bootstrap select ui
                }

            });

            showTable();
        });

        $("#team_id").on('change', function() {
            let department_id = $(this).val();
            let location_id = $("#location").val();
            let designation_id = $("#position").val();

            let url = "{{ route('department.select') }}";

            let designation_html = `<option value="all">--</option>`;

            if (designation_id != "all") {
                $("#position").html(designation_html);
                $("#position").selectpicker('refresh');
            }

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': department_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let designations = response.data;
                    let html = designation_html;

                    designations.forEach((designation) => {
                        html += `
                        <option value="${designation.id}">${designation.name}</option>
                    `
                    });

                    $("#position").html(html);
                    $("#position").selectpicker('refresh'); // refresh the bootstrap select ui
                }
            });

            showTable();
        });
    </script>
@endpush
