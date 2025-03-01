<head>
    <style type="text/css">
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }

            table.details tr th {
                background-color: #F2F2F2 !important;
            }

            .print_bg {
                background-color: #F2F2F2 !important;
            }

        }

        .print_bg {
            background-color: #F2F2F2 !important;
        }

        body {
            /* font-family: "Open Sans", helvetica, sans-serif; */
            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000000;
        }

        table.logo {
            -webkit-print-color-adjust: exact;
            border-collapse: inherit;
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 2px solid #25221F;
        }

        #logo {
            height: 50px;
        }

        table.emp {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px 0;
        }

        table.details,
        table.payment_details {
            width: 100%;
            border-collapse: collapse;
            /* margin-bottom: 10px; */
        }

        table.payment_total {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            text-align: center;
        }

        table.emp tr td {
            /* width: 100%; */
            padding: 10px 0;
        }

        table.details tr th {
            /* border: 1px solid #000000; */
            background-color: #F2F2F2;
            font-size: 15px;
            padding: 10px
        }

        table.details tr td {
            vertical-align: top;
            width: 30%;
            padding: 3px
        }

        table.payment_details>tbody>tr>td {
            /* border: 1px solid #000000; */
            padding: 12px 7px;
        }

        table.payment_total>tbody>tr>td {
            padding: 5px;
            width: 60%
        }

        table.logo>tbody>tr>td {
            border: 1px solid transparent;
        }

        .active {
            text-transform: uppercase;
        }

        .netpay {
            padding: 15px 10px;
            text-align: center;
            border: 1px solid #edeff0;
        }

        .text-info {
            color: #03a9f3;
            font-size: 15px;
        }

        .netsalary-title {
            text-transform: uppercase;
            font-size: 20px;
        }

        .netsalary-formula {
            font-size: 16px;
            color: #555555;
        }
    </style>
</head>

<body>
    <table class="logo">
        <tr>
            <td>

            </td>
            <td>
                {{-- <p style="text-align: right;">
                <img src="{{ $company->logo_url }}" id="logo">
            </p> --}}

                <p style="text-align: right;">

                    <b>{{ $company->company_name }}</b><br />
                    {!! nl2br($company->defaultAddress->address) !!}<br />
                    <b>@lang('app.phone')</b>: {{ $company->company_phone }}<br />
                    <b>@lang('app.email')</b>: {{ $company->company_email }}

                </p>
            </td>
        </tr>
    </table>
    <table class="emp">
        <tbody>
            <tr>
                <td colspan="3" style="font-size: 18px; padding-bottom: 20px;">
                    <strong>@lang('payroll::modules.payroll.salarySlipHeading')
                        {{ \Carbon\Carbon::parse($salarySlip->year . '-' . $salarySlip->month . '-01')->translatedFormat('F Y') }}
                    </strong>
                </td>
            </tr>
            <tr>
                <td><strong>@lang('modules.employees.employeeId'):</strong> {{ $salarySlip->user->employeeDetail->employee_id }}
                </td>
                <td><strong>@lang('app.name'):</strong> {{ $salarySlip->user->name }}</td>
                <td><strong>@lang('payroll::modules.payroll.salarySlipNumber'):</strong> {{ $salarySlip->id }}</td>
            </tr>
            <tr>
                <td><strong>@lang('app.department')
                        :</strong>
                    {{ !is_null($salarySlip->user->employeeDetail->department) ? $salarySlip->user->employeeDetail->department->team_name : '-' }}
                </td>
                <td><strong>@lang('app.designation')
                        :</strong>
                    {{ !is_null($salarySlip->user->employeeDetail->designation) ? $salarySlip->user->employeeDetail->designation->name : '-' }}
                </td>
                <td><strong>@lang('modules.employees.joiningDate')
                        :</strong>
                    {{ $salarySlip->user->employeeDetail->joining_date->translatedFormat($company->date_format) }}</td>
            </tr>
            @if (isset($fields) && !is_null($fields))
                @php $numberCheck = 0; @endphp
                <tr>
                    @foreach ($fields as $key => $field)
                        @if ($numberCheck == 2)
                            @php $numberCheck = 0;@endphp
                        @endif
                        <td>
                            <strong>{{ $field->label }}:</strong>
                            @if ($field->type == 'text')
                                {{ $employeeDetail->custom_fields_data['field_' . $field->id] ?? '--' }}
                            @elseif($field->type == 'password')
                                {{ $employeeDetail->custom_fields_data['field_' . $field->id] ?? '--' }}
                            @elseif($field->type == 'number')
                                {{ $employeeDetail->custom_fields_data['field_' . $field->id] ?? '--' }}
                            @elseif($field->type == 'textarea')
                                {{ $employeeDetail->custom_fields_data['field_' . $field->id] ?? '--' }}
                            @elseif($field->type == 'radio')
                                {{ !is_null($employeeDetail->custom_fields_data['field_' . $field->id]) ? $employeeDetail->custom_fields_data['field_' . $field->id] : '--' }}
                            @elseif($field->type == 'select')
                                {{ !is_null($employeeDetail->custom_fields_data['field_' . $field->id]) && $employeeDetail->custom_fields_data['field_' . $field->id] != '' ? $field->values[$employeeDetail->custom_fields_data['field_' . $field->id]] : '--' }}
                            @elseif($field->type == 'checkbox')
                                <ul>
                                    @foreach ($field->values as $key => $value)
                                        @if (
                                            $employeeDetail->custom_fields_data['field_' . $field->id] != '' &&
                                                in_array($value, explode(', ', $employeeDetail->custom_fields_data['field_' . $field->id])))
                                            <li>{{ $value }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @elseif($field->type == 'date')
                                {{ isset($employeeDetail->custom_fields_data['field_' . $field->id]) ? Carbon\Carbon::parse($employeeDetail->custom_fields_data['field_' . $field->id])->translatedFormat($company->date_format) : '' }}
                            @endif
                        </td>

                        @if (($numberCheck == 0 && $key != 0) || sizeof($fields) == $key + 1)
                </tr>
                <tr>
            @endif
            @php $numberCheck++ @endphp
            @endforeach
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Table for Details -->
    <table class="details">

        <tr>
            <!-- Payment Info Slip Start-->
            <td>

                <table class="payment_details">
                    <thead>
                        <tr class="active">
                            <th class="text-uppercase">@lang('payroll::modules.payroll.earning')</th>
                            <th align="right" class="text-uppercase">@lang('app.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>@lang('payroll::modules.payroll.basicPay')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($basicSalary, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.technicalAllowance')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($technicalAllowance, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.livingCostAllowance')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($livingCostAllowance, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.specialAllowance')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($specialAllowance, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>

                    </tbody>
                </table>
                <!-- Table for Details -->
            </td>
            <!--  Payment Info Slip End-->


            <!-- Deduction start -->
            <td>
                <table class="payment_details">
                    <thead>
                        <tr class="active">
                            <th class="text-uppercase">@lang('payroll::modules.payroll.deduction')</th>
                            <th align="right" class="text-uppercase">@lang('app.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>@lang('payroll::modules.payroll.beforeLateDetection')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($beforeLateDetection, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.afterLateDetection')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($afterLateDetection, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.breakTimeLateDetection')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($breakTimeLateDetection, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.leaveWithoutPayDetection')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($leaveWithoutPayDetection, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('payroll::modules.payroll.otherDetection')</td>
                            <td align="right" class="text-uppercase">
                                {{ currency_format($monthlyOtherDetection?->other_detection, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}
                            </td>
                        </tr>
                    </tbody>

                </table>
            </td>
            <!--  Deductions End-->
        </tr>
        <tr>
            <td>
                <table class="payment_details">
                    <tr>

                        <td><strong>@lang('payroll::modules.payroll.totalAllowance')</strong></td>
                        <td align="right">
                            <strong>{{ currency_format($totalAllowance, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="payment_details">
                    <tr>
                        <td><strong>@lang('payroll::modules.payroll.totalDeductions')</strong></td>
                        @php
                            $allDeduction =
                                $beforeLateDetection +
                                $afterLateDetection +
                                $breakTimeLateDetection +
                                $leaveWithoutPayDetection +
                                $monthlyOtherDetection?->other_detection;
                        @endphp
                        <td align="right">
                            <strong>{{ currency_format($allDeduction, $payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id, false) }}
                                {!! htmlentities(
                                    $payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code,
                                ) !!}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!-- Table for Details -->
    <hr>
    <!-- TotalTotal -->
    <table class="payment_total">

    <tr>
        <td class="netsalary-title">
            <strong style="margin-right: 20px;">@lang('payroll::modules.payroll.netSalary'):</strong>
            {{ currency_format(sprintf('%0.2f', $totalAllowance - $allDeduction), ($payrollSetting->currency ? $payrollSetting->currency->id : company()->currency->id), false)}} {!! htmlentities($payrollSetting->currency ? $payrollSetting->currency->currency_code : company()->currency->currency_code) !!}
        </td>
    </tr>
    <tr>
        <td class="netsalary-formula">
            <h5 class="text-center text-muted">@lang('payroll::modules.payroll.netSalary') =
                (@lang('payroll::modules.payroll.totalAllowance') -
                @lang('payroll::modules.payroll.totalDeductions'))</h5>
        </td>
    </tr>


</table>
    <!-- TotalTotal -->
</body>
