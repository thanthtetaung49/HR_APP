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

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location" id="location" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.turnOverYear')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="year" id="year" data-live-search="true"
                    data-size="8">
                    @foreach (range(date('Y'), date('Y') - 10) as $year)
                        <option value="{{ $year }}" @selected($selectedYear == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <x-forms.link-secondary id="exportBtn" :link="route('turnOverReports.export')" class="mr-3 float-left" icon="file-export">
                    @lang('app.exportExcel')
                </x-forms.link-secondary>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div id="tableContainer" class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="{{ 1 + count($months) * 3 }}" class="text-left">
                                <h3 class="text-left">
                                    Turnover Report <span id="locationTitle">(All Location)</span>
                                </h3>
                            </th>
                        </tr>
                        <tr>
                            <th rowspan="2" class="align-middle">Description</th>
                            @foreach ($months as $month)
                                <th colspan="3">{{ $month }} - {{ $selectedYear % 100 }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($months as $month)
                                <th>Operation</th>
                                <th>Supporting</th>
                                <th>Total</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportRows as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                @foreach ($reportData as $monthData)
                                    @php($values = $monthData[$row['key']])
                                    @foreach (['operation', 'supporting', 'total'] as $category)
                                        @php($value = $values[$category])
                                        <td @class([
                                            'text-danger fw-bold' =>
                                                $row['percentage'] && $category === 'total' && $value > 10,
                                        ])>
                                            {{ $value }}{{ $row['percentage'] ? '%' : '' }}</td>
                                    @endforeach
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const reportCategories = ['operation', 'supporting', 'total'];

        function escapeHtml(value) {
            return $('<div>').text(value).html();
        }

        function renderReport(months, reportRows, reportData, year, locationName) {
            const shortYear = Number(year) % 100;
            const monthEntries = Object.entries(months);
            let monthHeaders = '';
            let categoryHeaders = '';
            let bodyRows = '';

            monthEntries.forEach(([monthNumber, monthLabel]) => {
                monthHeaders += `<th colspan="3">${escapeHtml(monthLabel)} - ${shortYear}</th>`;
                categoryHeaders += '<th>Operation</th><th>Supporting</th><th>Total</th>';
            });

            reportRows.forEach(row => {
                let cells = '';

                monthEntries.forEach(([monthNumber]) => {
                    const month = reportData[monthNumber];
                    const values = month[row.key];

                    reportCategories.forEach(category => {
                        const value = Number(values[category] ?? 0);
                        const cssClass = row.percentage && category === 'total' && value > 10 ?
                            ' class="text-danger fw-bold"' :
                            '';
                        cells += `<td${cssClass}>${value}${row.percentage ? '%' : ''}</td>`;
                    });
                });

                bodyRows += `<tr><td>${escapeHtml(row.label)}</td>${cells}</tr>`;
            });

            $('#tableContainer').html(`
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="${1 + (monthEntries.length * 3)}">
                                <h3 class="text-left">Turnover Report <span id="locationTitle">(${escapeHtml(locationName)})</span></h3>
                            </th>
                        </tr>
                        <tr><th rowspan="2" class="align-middle">Description</th>${monthHeaders}</tr>
                        <tr>${categoryHeaders}</tr>
                    </thead>
                    <tbody>${bodyRows}</tbody>
                </table>
            `);
        }

        $('#year, #location').on('change', function() {
            const year = $('#year').val();
            const locationId = $('#location').val();
            const locationName = locationId ?
                $.trim($('#location option:selected').text()) :
                'All Location';

            $('#exportBtn').attr(
                'href',
                "{{ route('turnOverReports.export') }}?year=" + encodeURIComponent(year) +
                '&locationId=' + encodeURIComponent(locationId)
            );

            $.ajax({
                url: "{{ route('turnOverReports.filter') }}",
                type: 'GET',
                data: {
                    year,
                    locationId
                },
                dataType: 'json',
                success: function(response) {
                    renderReport(
                        response.months,
                        response.reportRows,
                        response.reportData,
                        year,
                        locationName
                    );
                },
                error: function(xhr) {
                    console.error('Unable to load turnover report.', xhr.responseText);
                }
            });
        });
    </script>
@endpush
