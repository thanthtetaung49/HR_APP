@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
    <style>
        .filter-box {
            z-index: 2;
        }

        table th,
        table td {
            text-align: center;
            vertical-align: middle;
        }

        .table-bordered td,
        .table-bordered th {
            border: 1px solid #000 !important;
        }
    </style>
@endpush

@php
    $addDepartmentPermission = user()->permission('add_bank_reports');
@endphp


@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location_id" id="location_id" data-live-search="true"
                    data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($locations as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->location_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.month')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="month" id="month" data-live-search="true"
                    data-size="8">
                    @foreach ($months as $key => $month)
                        <option value="{{ $key }}" {{ date('M') == $month ? 'selected' : '' }}>
                            {{ $month }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.year')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="year" id="year" data-live-search="true"
                    data-size="8">
                    @foreach (range(date('Y'), date('Y') - 10) as $year)
                        <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

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

        <div>
            <h4>Bank Report For <span id="monthName"></span></h3>
        </div>

        <!-- leave table Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- leave table End -->

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')


    <script>
        $('#bankreport-table').on('preXhr.dt', function(e, settings, data) {
            const locationId = $('#location_id').val();
            const month = $('#month').val();
            const year = parseInt($("#year").val());

            $searchText = $('#search-text-field').val();

            data['searchText'] = $searchText;
            data['locationId'] = locationId;
            data['month'] = month;
            data['year'] = year;

            const currentMonthMinusOne = parseInt($("#month").val()) - 1;

            const monthName = new Date(year, currentMonthMinusOne - 1).toLocaleString('en-US', {
                month: 'long'
            });

            $("#monthName").html(monthName)

        });

        const showTable = () => {
            window.LaravelDataTables["bankreport-table"].draw(true);
        }

        $('#location_id, #month, #year').on('change keyup',
            function() {
                if ($('#location_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#month').val() != "all") {
                    const currentMonthMinusOne = parseInt($("#month").val()) - 1;
                    const currentYear = parseInt($("#year").val());

                    const monthName = new Date(currentYear, currentMonthMinusOne - 1).toLocaleString('en-US', {
                        month: 'long'
                    });

                    $("#monthName").html(monthName);

                    $('#reset-filters').removeClass('d-none');
                } else if ($('#year').val() != "all") {
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

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });
    </script>
@endpush
