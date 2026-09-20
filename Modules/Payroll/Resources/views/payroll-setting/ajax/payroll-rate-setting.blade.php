<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">
    <x-form id="payroll-rate-setting" method="POST" class="ajax-form">
        <div class="form-body">
            <div class="col-md-4">
                <x-forms.text fieldId="late_detection_rate" :fieldLabel="__('payroll::modules.payroll.lateDetectionRate')" fieldName="late_detection_rate"
                    fieldRequired="true" :fieldValue="$payrollSetting->late_detection_rate" :fieldPlaceholder="__('placeholders.lateDetectionRate')">
                </x-forms.text>
                @error('late_detection_rate')
                    <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="gazatted_allowance_rate" :fieldLabel="__('payroll::modules.payroll.gazattedAllowanceRate')" fieldName="gazatted_allowance_rate"
                    fieldRequired="true" :fieldValue="$payrollSetting->gazatted_allowance_rate" :fieldPlaceholder="__('placeholders.gazattedAllowanceRate')">
                </x-forms.text>
                @error('gazatted_allowance_rate')
                    <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    </x-form>
    <div class="w-100 border-top-grey set-btns">
        <x-setting-form-actions>
            <x-forms.button-primary id="save-payroll-rate" class="mr-3" icon="check">@lang('app.save')
            </x-forms.button-primary>
        </x-setting-form-actions>
    </div>
</div>


<script>
    $(document)
        .off(
            'click.payrollRate',
            '#save-payroll-rate'
        )
        .on(
            'click.payrollRate',
            '#save-payroll-rate',
            function(event) {
                event.preventDefault();

                clearPayrollRateErrors();

                $.easyAjax({
                    url: "{{ route('payroll-rate-settings.store') }}",

                    container: '#payroll-rate-setting',

                    type: 'POST',
                    blockUI: true,
                    disableButton: true,

                    buttonSelector: '#save-payroll-rate',

                    headers: {
                        Accept: 'application/json'
                    },

                    data: {
                        late_detection_rate: $('#late_detection_rate')
                            .val(),

                        gazatted_allowance_rate: $('#gazatted_allowance_rate')
                            .val(),

                        _token: "{{ csrf_token() }}"
                    },

                    success: function(response) {
                        clearPayrollRateErrors();
                    },

                    error: function(xhr) {
                        if (
                            xhr.status === 422 &&
                            xhr.responseJSON &&
                            xhr.responseJSON.errors
                        ) {
                            showPayrollRateErrors(
                                xhr.responseJSON.errors
                            );

                            return;
                        }

                        console.error(
                            xhr.responseText
                        );
                    }
                });
            }
        );

    function clearPayrollRateErrors() {
        $('#payroll-rate-setting')
            .find('.payroll-rate-error')
            .remove();

        $('#payroll-rate-setting')
            .find('.is-invalid')
            .removeClass('is-invalid');
    }

    function showPayrollRateErrors(errors) {
        Object.entries(errors).forEach(
            function([fieldName, messages]) {
                const input = $(
                    '[name="' + fieldName + '"]'
                );

                if (!input.length) {
                    return;
                }

                input.addClass('is-invalid');

                const errorElement = $('<span>', {
                    class: 'text-danger payroll-rate-error d-block mt-1',

                    text: Array.isArray(messages) ?
                        messages[0] : messages
                });

                input.after(errorElement);
            }
        );
    }

    $(document).on(
        'input',
        '#late_detection_rate, #gazatted_allowance_rate',
        function() {
            $(this).removeClass('is-invalid');

            $(this)
                .siblings('.payroll-rate-error')
                .remove();
        }
    );
</script>
