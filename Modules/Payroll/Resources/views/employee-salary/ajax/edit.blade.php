<div class="row">
    <div class="col-sm-12">
        <x-form id="update-salary-form">
            <x-cards.data :title="__('payroll::modules.payroll.updateSalary')" class="add-client">

                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12 my-2">
                                <x-employee :user="$employee" />
                            </div>
                            <div class="col-md-2">

                                <input type="hidden" name="user_id" id="user_id" value="{{ $user_id }}">
                                <input type="hidden" name="type" id="type" value="initial">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="add-client bg-white rounded">
                            <div class="border-bottom-grey"></div>

                            <div class="row p-20">
                                <div class="col-12">
                                    <h5>@lang('payroll::modules.payroll.allowanceHeading')</h5>
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.basicSalary')" fieldName="basic_salary" fieldId="basic_salary"
                                        :fieldPlaceholder="__('payroll::modules.payroll.expenseClaims')" :fieldValue="$allowance->basic_salary" />
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.technicalAllowance')" fieldName="technical_allowance"
                                        fieldId="technical_allowance" :fieldPlaceholder="__('payroll::modules.payroll.technicalAllowance')" :fieldValue="$allowance->technical_allowance" />
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.livingCostAllowance')" fieldName="living_cost_allowance"
                                        fieldId="living_cost_allowance" :fieldPlaceholder="__('payroll::modules.payroll.livingCostAllowance')" :fieldValue="$allowance->living_cost_allowance" />
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.specialAllowance')" fieldName="special_allowance"
                                        fieldId="special_allowance" :fieldPlaceholder="__('payroll::modules.payroll.specialAllowance')" :fieldValue="$allowance->technical_allowance"
                                        :fieldValue="$allowance->special_allowance" />
                                </div>
                            </div>

                            <div class="border-bottom-grey"></div>

                            <div class="row p-20">
                                <div class="col-12 ">
                                    <h5>@lang('payroll::modules.payroll.detectionHeading')</h5>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.otherDetection')" fieldName="other_detection"
                                        fieldId="other_detection" :fieldPlaceholder="__('payroll::modules.payroll.otherDetection')" :fieldValue="$detection->other_detection" />
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.creditSales')" fieldName="credit_sales" fieldId="credit_sales"
                                        :fieldPlaceholder="__('payroll::modules.payroll.creditSales')" :fieldValue="$detection->credit_sales" />
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.deposit')" fieldName="deposit" fieldId="deposit"
                                        :fieldPlaceholder="__('payroll::modules.payroll.deposit')" :fieldValue="$detection->deposit" />
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.Loan')" fieldName="loan" fieldId="loan" :fieldPlaceholder="__('payroll::modules.payroll.Loan')"
                                        :fieldValue="$detection->loan" />
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <x-forms.text :fieldLabel="__('payroll::modules.payroll.ssb')" fieldName="ssb" fieldId="ssb" :fieldPlaceholder="__('payroll::modules.payroll.ssb')"
                                        :fieldValue="$detection->ssb" />
                                </div>
                            </div>

                            <div class="border-bottom-grey"></div>

                        </div>
                    </div>
                </div>

                <div class='w-100 d-block d-lg-flex d-md-flex justify-content-start pt-3'>
                    <x-forms.button-primary id="update-employee-salary" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('employee-salary.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </div>
            </x-cards.data>
        </x-form>

    </div>
</div>
<script>
    $(document).ready(function() {
        $('body').on('click', '#update-employee-salary', function() {
            const url = "{{ route('employee-salary.update-salary', $user_id) }}";

            $.easyAjax({
                url: url,
                container: '#update-salary-form',
                type: "POST",
                blockUI: true,
                disableButton: true,
                buttonSelector: "#update-employee-salary",
                data: $('#update-salary-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.location.reload();
                        } else {
                            window.location.href = response.redirectUrl;
                        }
                    }
                }
            });

        });
    })
</script>

{{-- <script>
    // $(document).ready(function() {

    //     $('.select-picker').selectpicker();

    //     function getBasicCalculations() {

    //         var basicType = $('#basic-type').val();
    //         var basicValue = $('#basic_value').val();
    //         var annualSalary = $('#annual_salary').val();
    //         var userId = $('#user_id').val();

    //         const url = "{{ route('employee-salary.get_update_salary') }}";
    //         $.easyAjax({
    //             url: url,
    //             type: "GET",
    //             disableButton: true,
    //             blockUI: true,
    //             data: {
    //                 basicType: basicType,
    //                 basicValue: basicValue,
    //                 annualSalary: annualSalary,
    //                 userId: userId
    //             },
    //             success: function(response) {
    //                 $('#components').html(response.component)
    //             }
    //         })
    //     }

    //     let timeout;

    //     function changeClc() {
    //         clearTimeout(
    //         timeout); // Make a new timeout set to go off in 800ms timeout = setTimeout(function() { // Put your code here that you want to run // after the user has stopped typing for a little bit }, 800);

    //         timeout = setTimeout(function() {
    //             var basicSalary = $('#basic_value').val();
    //             if ($('#basic-type').val() == 'ctc_percent' && basicSalary > 100) {
    //                 $('#basic_value').val(100);
    //             }
    //             if (basicSalary > 0) {
    //                 getBasicCalculations();
    //             }

    //         }, 800);
    //     }


    //     $("#annual_salary").on("keyup change", function(e) {
    //         var annualSalary = $(this).val();
    //         var monthlySalary = annualSalary / 12;
    //         let netMonthlySalary = number_format(monthlySalary.toFixed(2));
    //         $('#monthly_salary').html(netMonthlySalary);
    //         changeClc();
    //     });

    //     $("#components #basic_value").on("keyup change", function(e) {
    //         changeClc();
    //     });

    //     changeClc();

    // });


    // $(".variable-deduction").on("keyup change", function(e) {
    //     var variable = parseInt($(this).val());
    //     var totalDeduction = {{ $expenses }};
    //     var deductionTotalWithoutVar = {{ $deductionTotalWithoutVar }};
    //     var total = (totalDeduction - deductionTotalWithoutVar) + variable;
    //     var totalAnnual = total * 12;
    //     $('.expenses').html(number_format(total));
    //     $('.expensesAnnual').html(number_format(totalAnnual));
    // });

    // function selectType(vals) {
    //     getBasicCalculations();
    // }


    // $('body').on('click', '#update-employee-salary', function() {
    //     const url = "{{ route('employee-salary.update-salary', $employeeMonthlySalary->id) }}";

    //     $.easyAjax({
    //         url: url,
    //         container: '#update-salary-form',
    //         type: "POST",
    //         blockUI: true,
    //         disableButton: true,
    //         buttonSelector: "#update-employee-salary",
    //         data: $('#update-salary-form').serialize(),
    //         success: function(response) {
    //             if (response.status == 'success') {
    //                 if ($(MODAL_XL).hasClass('show')) {
    //                     $(MODAL_XL).modal('hide');
    //                     window.location.reload();
    //                 } else {
    //                     window.location.href = response.redirectUrl;
    //                 }
    //             }
    //         }
    //     });

    // });

    // function number_format(number) {
    //     let decimals = '{{ currency_format_setting()->no_of_decimal }}';
    //     let thousands_sep = '{{ currency_format_setting()->thousand_separator }}';
    //     let currency_position = '{{ currency_format_setting()->currency_position }}';
    //     let dec_point = '{{ currency_format_setting()->decimal_separator }}';
    //     // Strip all characters but numerical ones.
    //     number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

    //     var currency_symbol =
    //         '{{ $currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol }}';

    //     var n = !isFinite(+number) ? 0 : +number,
    //         prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    //         sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    //         dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    //         s = '',
    //         toFixedFix = function(n, prec) {
    //             var k = Math.pow(10, prec);
    //             return '' + Math.round(n * k) / k;
    //         };
    //     // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    //     s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    //     if (s[0].length > 3) {
    //         s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    //     }
    //     if ((s[1] || '').length < prec) {
    //         s[1] = s[1] || '';
    //         s[1] += new Array(prec - s[1].length + 1).join('0');
    //     }

    //     // number = dec_point == '' ? s[0] : s.join(dec);

    //     number = s.join(dec);

    //     switch (currency_position) {
    //         case 'left':
    //             number = currency_symbol + number;
    //             break;
    //         case 'right':
    //             number = number + currency_symbol;
    //             break;
    //         case 'left_with_space':
    //             number = currency_symbol + ' ' + number;
    //             break;
    //         case 'right_with_space':
    //             number = number + ' ' + currency_symbol;
    //             break;
    //         default:
    //             number = currency_symbol + number;
    //             break;
    //     }
    //     return number;
    // }

    // $('.variable').on('keydown', e => {

    //     lastValue = $(e.target).val();
    //     lastValue = lastValue.replace(/[,]/g, '');
    // });
</script> --}}
{{-- <script>
    lastValue = 0;
    yearlySalary = {{ $employeeMonthlySalary->effective_annual_salary }}
    $('.variable').on('keyup', function(e) {
        var variable = $(this).val();
        var id = $(this).data('type-id');
        var type = $(this).data('type');

        var yearly = (variable.replace(/[,]/g, '') * 12);
        if (type == 'deduction') {
            $('#variableAnuallyDeduction' + id).val(yearly);
        } else {
            $('#variableAnually' + id).val(yearly);
        }

        salaryClaculation(variable.replace(/[,]/g, ''));
    })

    $('.variable').on('keydown', e => {
        lastValue = $(e.target).val();
        lastValue = lastValue.replace(/[,]/g, '');
    });

    function salaryClaculation(variable) {

        var fixed = $('.fixedAllowance').val();

        if (fixed == '' || fixed == 'NaN' || fixed == undefined) {
            fixed = 0;
        }

        if (lastValue == '' || lastValue == 'NaN' || lastValue == undefined) {
            lastValue = 0;
        }

        if (variable == '' || variable == 'NaN' || variable == undefined) {
            variable = 0;
        }

        var newFixed = 0;

        if (lastValue > variable) {
            newFixed = (lastValue - variable) + parseInt(fixed);
        }

        if (lastValue < variable) {
            newFixed = (parseInt(fixed) - (variable - lastValue));
        }

        if (lastValue == variable) {
            newFixed = parseInt(fixed);
        }

        if ((variable == '' || variable == 'NaN' || variable == undefined) && (lastValue == '' || lastValue == 'NaN' ||
                lastValue == undefined)) {
            newFixed = fixed;
        }

        $('.fixedAllowance').val(newFixed);

        var yearlyvariableFix = newFixed * 12;

        $('.monthlyFixedAllowance').html(number_format(newFixed));

        if (newFixed < 0) {
            $(".monthlyFixedAllowance").addClass("text-danger");
            $(".yearFixedAllowance").addClass("text-danger");
        } else {
            $(".monthlyFixedAllowance").removeClass("text-danger");
            $(".yearFixedAllowance").removeClass("text-danger");
        }


        $('.yearFixedAllowance').html(number_format(yearlyvariableFix));
    }
</script> --}}
