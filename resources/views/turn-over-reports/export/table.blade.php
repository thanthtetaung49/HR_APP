<style>
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

<table class="table table-bordered">
    <thead>
        <tr>
            <th rowspan="2" class="align-middle">Description</th>
            @foreach ($months as $key => $month)
                <th colspan="3">{{ $month }} - {{ $shortFormatYear }}</th>
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

                @foreach ($resigned as $item)
                    @if ($item->month == $key)
                        @if ($item->department_type == 'operation')
                            @php $operation = $item->total; @endphp
                        @endif

                        @if ($item->department_type == 'supporting')
                            @php $supporting = $item->total; @endphp
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

                    foreach ($resigned as $item) {
                        if ($item->month == $key) {
                            if ($item->department_type == 'operation') {
                                $operation_resign = $item->total;
                            }
                            if ($item->department_type == 'supporting') {
                                $supporting_resign = $item->total;
                            }
                        }
                    }

                    $total_resign = $operation_resign + $supporting_resign;

                    // Percentages
                    $operation_pct = $operation_total > 0 ? round(($operation_resign / $operation_total) * 100, 0) : 0;
                    $supporting_pct =
                        $supporting_total > 0 ? round(($supporting_resign / $supporting_total) * 100, 0) : 0;
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

                @foreach ($probation as $item)
                    @if ($item->month == $key)
                        @if ($item->department_type == 'operation')
                            @php $operation = $item->total; @endphp
                        @endif

                        @if ($item->department_type == 'supporting')
                            @php $supporting = $item->total; @endphp
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

                    foreach ($probation as $item) {
                        if ($item->month == $key) {
                            if ($item->department_type == 'operation') {
                                $operation_probation = $item->total;
                            }
                            if ($item->department_type == 'supporting') {
                                $supporting_probation = $item->total;
                            }
                        }
                    }

                    $total_probation = $operation_probation + $supporting_probation;

                    // Percentages
                    $operation_pct =
                        $operation_total > 0 ? round(($operation_probation / $operation_total) * 100, 0) : 0;
                    $supporting_pct =
                        $supporting_total > 0 ? round(($supporting_probation / $supporting_total) * 100, 0) : 0;
                    $total_pct = $total_total > 0 ? round(($total_probation / $total_total) * 100, 0) : 0;

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

                @foreach ($permanent as $item)
                    @if ($item->month == $key)
                        @if ($item->department_type == 'operation')
                            @php $operation = $item->total; @endphp
                        @endif

                        @if ($item->department_type == 'supporting')
                            @php $supporting = $item->total; @endphp
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

                    foreach ($permanent as $item) {
                        if ($item->month == $key) {
                            if ($item->department_type == 'operation') {
                                $operation_permanent = $item->total;
                            }
                            if ($item->department_type == 'supporting') {
                                $supporting_permanent = $item->total;
                            }
                        }
                    }

                    $total_permanent = $operation_permanent + $supporting_permanent;

                    // Percentages
                    $operation_pct =
                        $operation_total > 0 ? round(($operation_permanent / $operation_total) * 100, 0) : 0;
                    $supporting_pct =
                        $supporting_total > 0 ? round(($supporting_permanent / $supporting_total) * 100, 0) : 0;
                    $total_pct = $total_total > 0 ? round(($total_permanent / $total_total) * 100, 0) : 0;

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
