<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('app.add') @lang('payroll::app.menu.payCode')</h5>
    <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <x-form id="addPayCode" method="POST" class="ajax-form">
            <div class="form-body">
                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text fieldId="code"
                                      :fieldLabel="__('payroll::app.menu.payCode')"
                                      fieldName="code" fieldRequired="true">
                        </x-forms.text>
                    </div>

                    <div class="col-lg-6">
                        <x-forms.text fieldId="name"
                                      :fieldLabel="__('payroll::app.menu.payCode').' '. __('app.name')"
                                      fieldName="name" fieldRequired="true">
                        </x-forms.text>
                    </div>

                    <div class="col-lg-12">
                        <div class=" form-group">

                            <label class="mb-12 f-14 text-dark-grey w-100"
                                   for="fixed">
                                   @lang('payroll::modules.payroll.overtimeCalculationWillbe')
                                   <i class="fa fa-question-circle" data-toggle="tooltip" data-original-title="{{__('payroll::messages.overtimeCalculation')}}"></i>

                                </label>
                                <div class="d-flex">
                                    <x-forms.radio fieldId="fixed-yes" fieldLabel="" class="mr-0"
                                    fieldName="fixed" fieldValue="no" checked="true"
                                    :checked="true">
                                    </x-forms.radio>
                                    <input type="number" class="form-control height-35 f-14 col-md-1" placeholder="@lang('payroll::modules.payroll.timesPlaceholder')"
           value="" name="times" id="times">

                                    <div class="form-description mt-2 ml-1">
                                        <p> {{ __('payroll::modules.payroll.timesField') }}
                                            {{-- <i class="fa fa-question-circle" data-toggle="tooltip" data-original-title="{{__('clan.attendance.beforeDaysPopover')}}"></i> --}}
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex">
                                    <x-forms.radio fieldId="times-yes" fieldLabel="" class="mr-0"
                                    fieldName="fixed" fieldValue="yes">
                                    </x-forms.radio>

                                    <div class="form-description mt-2 ml-1 mr-1">
                                        <p> {{ __('payroll::modules.payroll.fixedAmountOf') }}
                                        </p>
                                    </div>
                                    <input type="number" disabled class="form-control height-35 f-14 col-md-1" :placeholder="__('payroll::modules.payroll.fixedAmountPlaceholder')"
                                    value="" name="fixed_amount" id="fixed_amount">
                                    <div class="form-description mt-2 ml-1">
                                        <p> {{ __('payroll::modules.payroll.perOvertimeHour') }}
                                        </p>
                                    </div>
                                </div>
                        </div>
                    </div>
                    <div class="col-lg-12 mt-3">
                        <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.description')" fieldId="description"
                            fieldName="description" />
                    </div>

                </div>
            </div>
        </x-form>
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="savePayCode" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>

    $('input[name=fixed]').change(function(){
        var checkedVal = $('input[name="fixed"]:checked').val();
        if (checkedVal == 'yes') {
            $('.timesBox').addClass('d-none');
            $('.fixedAmountBox').removeClass('d-none');

            $('#times').prop('disabled', true);
            $('#fixed_amount').prop('disabled', false);
        }
        else{
            $('.timesBox').removeClass('d-none');
            $('.fixedAmountBox').addClass('d-none');
            $('#times').prop('disabled', false);
            $('#fixed_amount').prop('disabled', true);

        }
    });

    // save source
    $('#savePayCode').click(function (e) {
        e.preventDefault();
        // $('#savePayCode').attr('disabled', true);
        $.easyAjax({
            url: "{{ route('pay-codes.store') }}",
            container: '#addPayCode',
            type: "POST",
            blockUI: true,
            disableButton: true,
            buttonSelector: "#savePayCode",
            data: $('#addPayCode').serialize(),
            success: function (response) {
                console.log();
                $('#savePayCode').prop("disabled", false);
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        })
    });
</script>
