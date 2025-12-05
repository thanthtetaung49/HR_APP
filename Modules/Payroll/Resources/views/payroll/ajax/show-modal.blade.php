<div id="payroll-detail-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white border-bottom-grey  justify-content-between p-20">
                    <div class="row">
                        <div class="col-md-10">
                            <h3 class="heading-h3">@lang('payroll::modules.payroll.salarySlip')</h3>
                            <h5 class="text-lightest">@lang('payroll::modules.payroll.salarySlipHeading') {{ $salarySlip->duration }} @if (!is_null($salarySlip->payroll_cycle))
                                    ({{ __('payroll::app.menu.' . $salarySlip->payroll_cycle->cycle) }})
                                @endif
                            </h5>
                        </div>
                        <div class="col-md-2 text-right">
                            <div class="dropdown">
                                <button class="btn btn-lg f-14 px-2 py-1 text-dark-grey  rounded dropdown-toggle"
                                    type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                    aria-labelledby="dropdownMenuLink" tabindex="0">
                                    @if (
                                        $salarySlip->status != 'paid' &&
                                            (user()->permission('edit_payroll') == 'all' || user()->permission('edit_payroll') == 'added'))
                                        <a class="dropdown-item openRightModal"
                                            href="{{ route('payroll.edit', $salarySlip->id) }}">@lang('app.edit')</a>
                                    @endif
                                    <a class="dropdown-item"
                                        href="{{ route('payroll.download_pdf', md5($salarySlip->id)) }}">@lang('app.download')</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="col-12 px-0 pb-3 d-lg-flex">
                                <p class="mb-0 text-lightest f-14 w-30 d-inline-block ">
                                    @lang('app.employee') @lang('app.name')</p>
                                <p class="mb-0 text-dark-grey f-14">
                                    <x-employee :user="$salarySlip->user" />
                                </p>
                            </div>
                            <x-cards.data-row :label="__('app.designation')" :value="!is_null($salarySlip->user->employeeDetail->designation)
                                ? $salarySlip->user->employeeDetail->designation->name
                                : '-'" />
                            <x-cards.data-row :label="__('app.department')" :value="!is_null($salarySlip->user->employeeDetail->department)
                                ? $salarySlip->user->employeeDetail->department->team_name
                                : '-'" />
                            <x-cards.data-row :label="__('payroll::modules.payroll.salaryPaymentMethod')" :value="$salarySlip->salary_payment_method_id
                                ? $salarySlip->salary_payment_method->payment_method
                                : '--'" />
                            <x-cards.data-row :label="__('payroll::modules.payroll.bankName')" :value="!is_null($salarySlip->user->bank_name)
                                ? $salarySlip->user->bank_name
                                : '--'" />
                            <x-cards.data-row :label="__('payroll::modules.payroll.bankAccountNumber')" :value="!is_null($salarySlip->user->bank_account_number)
                                ? $salarySlip->user->bank_account_number
                                : '--'" />
                        </div>
                        <div class="col-md-4">
                            <x-cards.data-row :label="__('modules.employees.employeeId')" :value="$salarySlip->user->employeeDetail->employee_id" />

                            <x-cards.data-row :label="__('modules.employees.joiningDate')" :value="$salarySlip->user->employeeDetail->joining_date->translatedFormat(
                                $company->date_format,
                            )" />

                            <x-cards.data-row :label="__('modules.payments.paidOn')" :value="$salarySlip->paid_on
                                ? $salarySlip->paid_on->translatedFormat($company->date_format)
                                : '--'" />

                            <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                                <p class="mb-0 text-lightest f-14 w-30 d-inline-block ">
                                    @lang('app.status')</p>
                                <p class="mb-0 text-dark-grey f-14 w-70">
                                    @if ($salarySlip->status == 'generated')
                                        <x-status :value="__('payroll::modules.payroll.generated')" color="green" />
                                    @elseif ($salarySlip->status == 'review')
                                        <x-status :value="__('payroll::modules.payroll.review')" color="blue" />
                                    @elseif ($salarySlip->status == 'locked')
                                        <x-status :value="__('payroll::modules.payroll.locked')" color="red" />
                                    @elseif ($salarySlip->status == 'paid')
                                        <x-status :value="__('payroll::modules.payroll.paid')" color="dark-green" />
                                    @endif
                                </p>
                            </div>

                            <x-cards.data-row :label="__('payroll::modules.payroll.generatedOn')" :value="$salarySlip->created_at
                                ? $salarySlip->created_at->translatedFormat($company->date_format)
                                : '--'" />

                        </div>

                        <div class="col-md-2">
                            <div class="text-center border rounded p-20 ">
                                <small>@lang('payroll::modules.payroll.employeeNetPay')</small>
                                <h4 class="text-primary heading-h3 mt-1">
                                    {{ currency_format($salarySlip->net_salary, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <x-forms.custom-field-show :fields="$fields" :model="$employeeDetail"></x-forms.custom-field-show>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <x-table class="table-bordered" headType="thead-light">
                                    <x-slot name="thead">
                                        <th>@lang('payroll::modules.payroll.earning')</th>
                                        <th class="text-right">@lang('app.amount')</th>
                                    </x-slot>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.basicPay')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($basicSalary, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.actualBasicSalary') for {{ $month }} </td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($basicSalaryInMonth, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.technicalAllowance') for {{ $month }}</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($technicalAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.livingCostAllowance') for {{ $month }}</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($livingCostAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.specialAllowance') for {{ $month }}</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($specialAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.Overtime')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($overtimeAmount, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.offDayHolidaySalary')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($offDayHolidaySalary, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.gazattedAllowance')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($gazattedAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.eveningShiftAllowance')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($eveningShiftAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                </x-table>
                            </div>
                        </div>

                        <div class="col-md-6">

                            <div class="table-responsive">
                                <x-table class="table-bordered" headType="thead-light">
                                    <x-slot name="thead">
                                        <th>@lang('payroll::modules.payroll.deduction')</th>
                                        <th class="text-right">@lang('app.amount')</th>
                                    </x-slot>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.absent')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($absent, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.leaveWithoutPay')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($leaveWithoutPayDetection, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.afterLateDetection')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($afterLateDetection, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.breakTimeLateDetection')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($breakTimeLateDetection, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.creditSales')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($creditSales, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.deposit')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($deposit, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>@lang('payroll::modules.payroll.loan')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($loan, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>@lang('payroll::modules.payroll.ssb')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($ssb, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>


                                    <tr>
                                        <td>@lang('payroll::modules.payroll.otherDetection')</td>
                                        <td class="text-right text-uppercase">
                                            {{ currency_format($otherDetection, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                                        </td>
                                    </tr>

                                </x-table>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <h5 class="heading-h5 ml-3">@lang('payroll::modules.payroll.totalAllowance')</h5>
                        </div>
                        <div class="col-md-3 text-right">
                            <h5 class="heading-h5">
                                {{ currency_format($totalAllowance, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                            </h5>
                        </div>

                        <div class="col-md-3">
                            <h5 class="heading-h5">@lang('payroll::modules.payroll.totalDeductions')</h5>
                        </div>
                        <div class="col-md-3 text-right">
                            <h5 class="heading-h5">
                                {{ currency_format($totalDetection, $currency->currency ? $currency->currency->id : company()->currency->id) }}
                            </h5>
                        </div>

                        <div class="col-md-12 p-20 mt-3">
                            <h3 class="text-center heading-h3">
                                <span class="text-uppercase mr-3">@lang('payroll::modules.payroll.netSalary'):</span>
                                {{ currency_format(sprintf('%0.2f', $netSalary), $currency->currency ? $currency->currency->id : company()->currency->id) }}
                            </h3>
                            <h5 class="text-center text-lightest">@lang('payroll::modules.payroll.netSalary') =
                                (@lang('payroll::modules.payroll.totalAllowance') -
                                @lang('payroll::modules.payroll.totalDeductions'))</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
