@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>

        <!-- LOCATION START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location" id="locationSearch">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- LOCATION END -->

        <!-- DEPARTMENT START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.departments')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="department" id="department">
                    <option value="all">@lang('app.all')</option>
                    {{-- department display here --}}
                </select>
            </div>
        </div>
        <!-- DEPARTMENT END -->

        <!-- DESIGNATION START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.designation')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="designation" id="designation">
                    <option value="all">@lang('app.all')</option>
                    {{-- designation display here  --}}
                </select>
            </div>
        </div>
        <!-- DESIGNATION END -->


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
        <div class="select-box d-flex py-2 px-lg-3 px-md-3 px-0">
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
        <div class="d-flex" id="table-actions">

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
        $('#employee-salary-table').on('preXhr.dt', function(e, settings, data) {

            const designation = $('#designation').val();
            const location = $("#locationSearch").val();
            const department = $('#department').val();
            // const designation = $("#designation").val();
            const searchText = $('#search-text-field').val();

            data['designation'] = designation;
            data['location'] = location;
            data['department'] = department;
            data['searchText'] = searchText;

            console.log(location);
        });

        const showTable = () => {
            window.LaravelDataTables["employee-salary-table"].draw(true);
        }

        $("#locationSearch").on('change', function() {
            console.log('hello');
            let location_id = $(this).val();
            let designation_id = $("#designation").val();
            let department_id = $("#department").val();

            let department_html = `<option value="all">--</option>`;
            let designation_html = `<option value="all">--</option>`;

            let url = "{{ route('location.select') }}";

            if (department_id != "all" && designation_id != "all") {
                $("#department").html(department_html);
                $("#department").selectpicker('refresh');

                $("#designation").html(designation_html);
                $("#designation").selectpicker('refresh');

            } else if (designation_id != "all") {
                $("#designation").html(designation_html);
                $("#designation").selectpicker('refresh');
            } else {
                $("#department").html(department_html);
                $("#department").selectpicker('refresh');
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

                    $("#department").html(html);
                    $("#department").selectpicker('refresh'); // refresh the bootstrap select ui
                }

            });

            showTable();
        });

        $("#department").on('change', function() {
            let department_id = $(this).val();
            let location_id = $("#locationSearch").val();
            let designation_id = $("#designation").val();

            let url = "{{ route('department.select') }}";

            let designation_html = `<option value="all">--</option>`;

            if (designation_id != "all") {
                $("#designation").html(designation_html);
                $("#designation").selectpicker('refresh');
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

                    $("#designation").html(html);
                    $("#designation").selectpicker('refresh'); // refresh the bootstrap select ui
                }
            });

            showTable();
        });

        $('#locationSearch, #designation, #department, #search-text-field').on('change keyup',
            function() {
                if ($('#designation').val() !== "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#locationSearch').val() !== "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#department').val() !== "all") {
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

        $('body').on('click', '.save-initial-salary', function() {
            const id = $(this).data('user-id');
            const amount = $('#initial-salary-' + id).val();
            const token = "{{ csrf_token() }}";

            $.easyAjax({
                url: "{{ route('employee-salary.store') }}",
                container: '#employee-salary-table',
                type: "POST",
                blockUI: true,
                disableButton: true,
                buttonSelector: "#save-initial-salary",
                data: {
                    user_id: id,
                    amount: amount,
                    _token: token,
                    type: 'initial'
                },
                success: function(response) {
                    if (response.status === "success") {
                        showTable();
                    }
                }
            });

        });

        $('body').on('click', '.salary-history', function() {
            const userId = $(this).data('user-id');
            let url = '{{ route('employee-salary.show', ':id') }}';
            url = url.replace(':id', userId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.update-salary', function() {
            const userId = $(this).data('user-id');
            let url = '{{ route('employee-salary.edit', ':id') }}';
            url = url.replace(':id', userId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('change', '.salary-cycle', function() {
            const id = $(this).data('user-id');
            const cycle = $(this).val();
            const token = "{{ csrf_token() }}";
            if (id !== undefined && id !== '') {
                $.easyAjax({
                    url: '{{ route('employee-salary.payroll-cycle') }}',
                    type: "POST",
                    data: {
                        user_id: id,
                        cycle: cycle,
                        _token: token
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                        }
                    }
                })
            }


        });

        $('body').on('change', '.payroll-status', function() {
            const id = $(this).data('user-id');
            const status = $(this).val();
            const token = "{{ csrf_token() }}";
            if (id !== undefined && id != '') {
                $.easyAjax({
                    url: '{{ route('employee-salary.payroll-status') }}',
                    type: "POST",
                    data: {
                        user_id: id,
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
    </script>
@endpush
