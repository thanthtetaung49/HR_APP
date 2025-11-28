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
    $addDepartmentPermission = user()->permission('add_turn_over_reports');
@endphp


@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0 ml-3">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.location')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="location" id="location" data-live-search="true" data-size="8">
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
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.menu.turnOverYear')</p>
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

                <x-forms.link-secondary id="exportBtn" :link="route('turnOverReports.export')" class="mr-3 float-left" icon="file-export">
                    @lang('app.exportExcel')
                </x-forms.link-secondary>

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
            <div id="tableContainer" class="table-responsive">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="6">
                                <h3>Turnover Report
                                    <span id="locationTitle">(All Location)</span>
                                </h3>
                            </th>
                        </tr>
                        <tr>
                            <th rowspan="2" class="align-middle">Description</th>
                            @foreach ($months as $key => $month)
                                <th colspan="3">{{ $month }} - @php echo now()->year % 100; @endphp</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($months as $key => $month)
                                <th>Operation</th>
                                <th>Supporting</th>
                                <th>Total</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <td>Total MP</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation = 0;
                                    $supporting = 0;
                                @endphp

                                @foreach ($employeeTotal as $item)
                                    @if ($item->month == $key)
                                        @php
                                            $operation = $item->operation_employee_count;
                                            $supporting = $item->supporting_employee_count;
                                        @endphp
                                    @endif
                                @endforeach

                                <td>{{ $operation }}</td> <!-- Operation -->
                                <td>{{ $supporting }}</td> <!-- Supporting -->
                                <td>{{ $operation + $supporting }}</td> <!-- Total -->
                            @endforeach
                        </tr>
                        <tr>
                            <td>Resign</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation = 0;
                                    $supporting = 0;
                                    $total = 0;
                                @endphp

                                @foreach ($turnOverReports as $item)
                                    @if ($item->month == $key)
                                        @if ($item->department_type == 'operation')
                                            @php $operation = $item->resigned_total; @endphp
                                        @endif

                                        @if ($item->department_type == 'supporting')
                                            @php $supporting = $item->resigned_total; @endphp
                                        @endif
                                    @endif
                                @endforeach

                                @php $total = $operation + $supporting; @endphp

                                <td>{{ $operation }}</td> <!-- Operation -->
                                <td>{{ $supporting }}</td> <!-- Supporting -->
                                <td>{{ $total }}</td> <!-- Total -->
                            @endforeach
                        </tr>

                        {{-- Turnover % --}}
                        <tr>
                            <td>Turnover %</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation_total = 0;
                                    $supporting_total = 0;

                                    foreach ($employeeTotal as $item) {
                                        if ($item->month == $key) {
                                            $operation_total = $item->operation_employee_count;
                                            $supporting_total = $item->supporting_employee_count;
                                        }
                                    }

                                    $total_total = $operation_total + $supporting_total;

                                    // Get resign counts
                                    $operation_resign = 0;
                                    $supporting_resign = 0;

                                    foreach ($turnOverReports as $item) {
                                        if ($item->month == $key) {
                                            if ($item->department_type == 'operation') {
                                                $operation_resign = $item->resigned_total;
                                            }
                                            if ($item->department_type == 'supporting') {
                                                $supporting_resign = $item->resigned_total;
                                            }
                                        }
                                    }

                                    $total_resign = $operation_resign + $supporting_resign;

                                    // Percentages
                                    $operation_pct =
                                        $operation_total > 0
                                            ? round(($operation_resign / $operation_total) * 100, 0)
                                            : 0;
                                    $supporting_pct =
                                        $supporting_total > 0
                                            ? round(($supporting_resign / $supporting_total) * 100, 0)
                                            : 0;
                                    $total_pct = $total_total > 0 ? round(($total_resign / $total_total) * 100, 0) : 0;

                                @endphp

                                <td>{{ $operation_pct }}%</td>
                                <td>{{ $supporting_pct }}%</td>
                                <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                                    {{ $total_pct }}%
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>Probation</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation = 0;
                                    $supporting = 0;
                                    $total = 0;
                                @endphp

                                @foreach ($turnOverReports as $item)
                                    @if ($item->month == $key)
                                        @if ($item->department_type == 'operation')
                                            @php $operation = $item->probation_total; @endphp
                                        @endif

                                        @if ($item->department_type == 'supporting')
                                            @php $supporting = $item->probation_total; @endphp
                                        @endif
                                    @endif
                                @endforeach

                                @php $total = $operation + $supporting; @endphp

                                <td>{{ $operation }}</td> <!-- Operation -->
                                <td>{{ $supporting }}</td> <!-- Supporting -->
                                <td>{{ $total }}</td> <!-- Total -->
                            @endforeach
                        </tr>

                        {{-- Turnover % --}}
                        <tr>
                            <td>Turnover %</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation_total = 0;
                                    $supporting_total = 0;

                                    foreach ($employeeTotal as $item) {
                                        if ($item->month == $key) {
                                            $operation_total = $item->operation_employee_count;
                                            $supporting_total = $item->supporting_employee_count;
                                        }
                                    }

                                    $total_total = $operation_total + $supporting_total;

                                    // Get resign counts
                                    $operation_probation = 0;
                                    $supporting_probation = 0;

                                    foreach ($turnOverReports as $item) {
                                        if ($item->month == $key) {
                                            if ($item->department_type == 'operation') {
                                                $operation_probation = $item->probation_total;
                                            }
                                            if ($item->department_type == 'supporting') {
                                                $supporting_probation = $item->probation_total;
                                            }
                                        }
                                    }

                                    $total_probation = $operation_probation + $supporting_probation;

                                    // Percentages
                                    $operation_pct =
                                        $operation_total > 0
                                            ? round(($operation_probation / $operation_total) * 100, 0)
                                            : 0;
                                    $supporting_pct =
                                        $supporting_total > 0
                                            ? round(($supporting_probation / $supporting_total) * 100, 0)
                                            : 0;
                                    $total_pct =
                                        $total_total > 0 ? round(($total_probation / $total_total) * 100, 0) : 0;

                                @endphp

                                <td>{{ $operation_pct }}%</td>
                                <td>{{ $supporting_pct }}%</td>
                                <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                                    {{ $total_pct }}%
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>Permanent</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation = 0;
                                    $supporting = 0;
                                    $total = 0;
                                @endphp

                                @foreach ($turnOverReports as $item)
                                    @if ($item->month == $key)
                                        @if ($item->department_type == 'operation')
                                            @php $operation = $item->permanent_total; @endphp
                                        @endif

                                        @if ($item->department_type == 'supporting')
                                            @php $supporting = $item->permanent_total; @endphp
                                        @endif
                                    @endif
                                @endforeach

                                @php $total = $operation + $supporting; @endphp

                                <td>{{ $operation }}</td> <!-- Operation -->
                                <td>{{ $supporting }}</td> <!-- Supporting -->
                                <td>{{ $total }}</td> <!-- Total -->
                            @endforeach
                        </tr>

                        {{-- Turnover % --}}
                        <tr>
                            <td>Turnover %</td>
                            @foreach ($months as $key => $month)
                                @php
                                    $operation_total = 0;
                                    $supporting_total = 0;

                                    foreach ($employeeTotal as $item) {
                                        if ($item->month == $key) {
                                            $operation_total = $item->operation_employee_count;
                                            $supporting_total = $item->supporting_employee_count;
                                        }
                                    }

                                    $total_total = $operation_total + $supporting_total;

                                    // Get resign counts
                                    $operation_permanent = 0;
                                    $supporting_permanent = 0;

                                    foreach ($turnOverReports as $item) {
                                        if ($item->month == $key) {
                                            if ($item->department_type == 'operation') {
                                                $operation_permanent = $item->permanent_total;
                                            }
                                            if ($item->department_type == 'supporting') {
                                                $supporting_permanent = $item->permanent_total;
                                            }
                                        }
                                    }

                                    $total_permanent = $operation_permanent + $supporting_permanent;

                                    // Percentages
                                    $operation_pct =
                                        $operation_total > 0
                                            ? round(($operation_permanent / $operation_total) * 100, 0)
                                            : 0;
                                    $supporting_pct =
                                        $supporting_total > 0
                                            ? round(($supporting_permanent / $supporting_total) * 100, 0)
                                            : 0;
                                    $total_pct =
                                        $total_total > 0 ? round(($total_permanent / $total_total) * 100, 0) : 0;

                                @endphp

                                <td>{{ $operation_pct }}%</td>
                                <td>{{ $supporting_pct }}%</td>
                                <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                                    {{ $total_pct }}%
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <!-- leave table End -->

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    <script>
        $("#year, #location").on('change', function() {
            const year = $("#year").val();
            const locationId = $("#location").val();
            const locationName = $("#location").val() != "" ? $.trim($("#location option:selected").text()) : 'All Location';
            const shortFormatYear = parseInt(year) % 100

            const data = {
                'year': year,
                'locationId': locationId
            }

            $("#exportBtn").attr('href', "{{ route('turnOverReports.export') }}?year=" + year +"&locationId=" + locationId);

            $.ajax({
                type: "GET",
                url: "{{ route('turnOverReports.filter') }}",
                data: data,
                dataType: "json",
                success: function(response) {
                    const employeeTotal = response.employeeTotal;
                    const months = Object.values(response.months);
                    const turnOverReports = response.turnOverReports;

                    let theadMonth = '';
                    let theadCategory = '';

                    let tdTotalMP = '';
                    let tdResign = '';
                    let tdProbation = '';
                    let tdPermanent = '';

                    let turnOverResign = '';
                    let turnOverProbation = '';
                    let turnOverPermanent = '';

                    months.forEach((month, key) => {
                        const monthNumber = key + 1;

                        theadMonth += `<th colspan="3">${month} - ${shortFormatYear}</th>`
                        theadCategory += `
                                <th>Operation</th>
                                <th>Supporting</th>
                                <th>Total</th>
                        `

                        let operationTotalMp = 0;
                        let supportingTotalMp = 0;

                        let operationResign = 0;
                        let supportingResign = 0;
                        let operationProbation = 0;
                        let supportingProbation = 0;
                        let operationPermanent = 0;
                        let supportingPermanent = 0;

                        employeeTotal.forEach(item => {
                            if (item.month == monthNumber) {
                                operationTotalMp = item.operation_employee_count;
                                supportingTotalMp = item.supporting_employee_count;
                            }
                        })

                        turnOverReports.forEach(item => {
                            if (item.month == monthNumber) {
                                if (item.department_type == 'operation') {
                                    operationResign = item.resigned_total;
                                    operationProbation = item.probation_total;
                                    operationPermanent = item.permanent_total;
                                }

                                if (item.department_type == 'supporting') {
                                    supportingResign = item.resigned_total;
                                    supportingProbation = item.probation_total;
                                    supportingPermanent = item.permanent_total;
                                }
                            }
                        });

                        const totalMp = parseInt(operationTotalMp) + parseInt(
                        supportingTotalMp);

                        const totalResign = parseInt(operationResign) + parseInt(
                            supportingResign);
                        const totalProbation = parseInt(operationProbation) + parseInt(
                            supportingProbation);
                        const totalPermanent = parseInt(operationPermanent) + parseInt(
                            supportingPermanent);

                        const operationPercentResign = operationTotalMp > 0 ? Math.round((
                            operationResign / operationTotalMp) * 100, 2) : 0;
                        const supportingPercentResign = supportingTotalMp > 0 ? Math.round((
                            supportingResign / supportingTotalMp) * 100, 2) : 0;
                        const totalPercentResign = totalMp > 0 ? Math.round((totalResign /
                            totalMp) * 100, 0) : 0;

                        const operationPercentProbation = operationTotalMp > 0 ? Math.round((
                            operationProbation / operationTotalMp) * 100, 2) : 0;
                        const supportingPercentProbation = supportingTotalMp > 0 ? Math.round((
                            supportingProbation / supportingTotalMp) * 100, 2) : 0;
                        const totalPercentProbation = totalMp > 0 ? Math.round((totalProbation /
                            totalMp) * 100, 2) : 0;



                        const operationPercentPermanent = operationTotalMp > 0 ? Math.round((
                            operationPermanent / operationTotalMp) * 100, 2) : 0;
                        const supportingPercentPermanent = supportingTotalMp > 0 ? Math.round((
                            supportingPermanent / supportingTotalMp) * 100, 2) : 0;
                        const totalPercentPermanent = totalMp > 0 ? Math.round((totalPermanent /
                            totalMp) * 100, 2) : 0;

                        tdTotalMP += `
                            <td>${operationTotalMp}</td>
                            <td>${supportingTotalMp}</td>
                            <td>${totalMp}</td>
                        `

                        tdResign += `
                             <td>${operationResign}</td>
                            <td>${supportingResign}</td>
                            <td>${totalResign}</td>
                        `

                        turnOverResign += `
                            <td>${operationPercentResign}%</td>
                            <td>${supportingPercentResign}%</td>
                            <td class="${ totalPercentResign > 10 ? 'text-danger fw-bold' : '' }">${totalPercentResign}%</td>
                        `

                        tdProbation += `
                            <td>${operationProbation}</td>
                            <td>${supportingProbation}</td>
                            <td>${totalProbation}</td>
                        `

                        turnOverProbation += `
                            <td>${operationPercentProbation}%</td>
                            <td>${supportingPercentProbation}%</td>
                            <td class="${ totalPercentProbation > 10 ? 'text-danger fw-bold' : '' }">${totalPercentProbation}%</td>
                        `

                        tdPermanent += `
                            <td>${operationPermanent}</td>
                            <td>${supportingPermanent}</td>
                            <td>${totalPermanent}</td>
                        `

                        turnOverPermanent += `
                            <td>${operationPercentPermanent}%</td>
                            <td>${supportingPercentPermanent}%</td>
                            <td class="${ totalPercentPermanent > 10 ? 'text-danger fw-bold' : '' }">${totalPercentPermanent}%</td>
                        `

                    });

                    let table = `
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                            <th colspan="6">
                                <h3>Turnover Report
                                    <span id="locationTitle">(${locationName})</span>
                                </h3>
                            </th>
                        </tr>
                        <tr>
                            <th rowspan="2" class="align-middle">Description</th>
                            ${theadMonth}
                        </tr>
                        <tr>
                            ${theadCategory}
                        </tr>
                    </thead>
                        <tbody>
                            <tr>
                                <td>Total MP</td>
                                ${tdTotalMP}
                            </tr>
                            <tr>
                                <td>Resign</td>
                                ${tdResign}
                            </tr>
                            <tr>
                                <td>Turnover %</td>
                                ${turnOverResign}
                            </tr>

                            <tr>
                                <td>Probation</td>
                                ${tdProbation}
                            </tr>

                            <tr>
                                <td>Turnover %</td>
                                ${turnOverProbation}
                            </tr>

                            <tr>
                                <td>Permanent</td>
                                ${tdPermanent}
                            </tr>
                            <tr>
                                <td>Turnover %</td>
                                ${turnOverPermanent}
                            </tr>
                        </tbody>
                    </table>
                    `
                    $("#tableContainer").html(table)
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", status, error);
                }
            });

        })
    </script>
@endpush
