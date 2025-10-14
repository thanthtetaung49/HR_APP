<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<style>
    .disabled {
            background-color: #F9D6D6; /* light red for disabled */
            color: #ccc; /* grey text for disabled */
            pointer-events: none; /* prevent click */
        }

</style>
<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('payroll::modules.payroll.addRequest')</h5>
    <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="overtimeHoursForm" method="POST">

        <div class="form-body">
            <div class="row">
                <div class="col-lg-8">
                    @if (user()->hasRole('admin'))

                    <x-forms.label fieldId="employee" :fieldLabel="__('app.employee')" :fieldRequired="true"
                    class="mt-3"> </x-forms.label>
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-original-title="{{__('payroll::messages.onlyEmployeesShow')}}"></i>

                    <select name="employee" id="employee" data-live-search="true" class="form-control select-picker" data-size="8">
                         <option value="">--</option>
                            @foreach ($employees as $employee)
                                <x-user-option :user="$employee" :selected="request()->has('default_assign') &&
                                    request('default_assign') == $employee->id" />
                            @endforeach
                    </select>

                    @else
                        <x-employee :user="user()"/>
                        <input type="hidden" value="{{ user()->id }}" name="employee" id="employee">
                    @endif
                </div>
            </div>

                <div class="pt-20 pr-20 row">
                    <div class="col-lg-3">
                        <x-forms.text class="date-picker" :fieldLabel="__('app.date')" fieldName="date[]"
                            fieldId="dateField1" :fieldPlaceholder="__('app.date')" fieldValue=""
                            fieldRequired="true" />
                    </div>

                    <div class="col-md-3 col-lg-3" id="set-time-estimate-fields">
                        <div class="form-group mt-5">
                            <input type="number" min="0" class="w-25 border rounded p-2 height-35 f-14"
                                   name="overtime_hours[]" value="">
                            @lang('app.hrs')
                            &nbsp;&nbsp;
                            <input type="number" min="0" name="minutes[]"
                                   value=""
                                   class="w-25 height-35 f-14 border rounded p-2">
                            @lang('app.mins')
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="my-3 form-group">
                            <x-forms.text :fieldLabel="__('app.reason')" fieldName="overtime_reasons[]"
                                fieldId="overtime_reasons" :fieldPlaceholder="__('app.reason')" fieldValue=""
                                fieldRequired="false" />
                        </div>
                    </div>


                </div>

                <div id="insertBefore"></div>

                <!--  ADD ITEM START-->

            <input type="hidden" name="start_date" id="start_date" value="">
            <input type="hidden" name="end_date" id="end_date" value="">

            <div class="col-md-12 pl-0">
                <a class="f-15 f-w-500" href="javascript:;" id="add-item"><i
                        class="mr-1 icons icon-plus font-weight-bold"></i> @lang('app.add')</a>
            </div>

        </div>
    </x-form>
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-request" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
      var applyDate = new Date();
      var lastDate = new Date();

    $(document).ready(function () {

        $(".select-picker").selectpicker();

        var $insertBefore = $('#insertBefore');
        var i = 1;
        var selectedDates = [];

        @if (!user()->hasRole('admin'))
            getPolicy({{ user()->id }})
        @endif
        $('#employee').change(function () {
            var id = $(this).val();
            getPolicy(id)
        });

        // Add More Inputs
        $('#add-item').click(function() {
            i += 1;

            var newDatePickerHtml = `
                <div id="addMoreBox${i}" class="clearfix pr-20 row">
                    <div class="col-lg-3 col-md-6 col-12">
                        <x-forms.text class="date-picker" :fieldLabel="__('app.date')" fieldName="date[]"
                        fieldId="dateField${i}" :fieldPlaceholder="__('app.date')" fieldValue="" fieldRequired="true" />
                    </div>
                    <div class="col-md-3 col-lg-3" id="set-time-estimate-fields">
                        <div class="form-group mt-5">
                            <input type="number" min="0" id="overtime_hours${i}" class="w-25 border rounded p-2 height-35 f-14"
                                   name="overtime_hours[]" value="">
                            @lang('app.hrs')
                            &nbsp;&nbsp;
                            <input type="number" min="0" name="minutes[]"
                                   value=""
                                   class="w-25 height-35 f-14 border rounded p-2">
                            @lang('app.mins')
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="my-3 form-group">
                            <x-forms.text :fieldLabel="__('app.reason')" fieldName="overtime_reasons[]"
                                fieldId="overtime_reasons" :fieldPlaceholder="__('app.reason')" fieldValue=""
                                fieldRequired="false" />
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-1 col-2 pt-2">
                        <a href="javascript:;" class="mt-5 d-flex align-items-center justify-content-center remove-item" data-item-id="${i}">
                            <i class="fa fa-times-circle f-20 text-lightest"></i>
                        </a>
                    </div>
                </div>`;

            $(newDatePickerHtml).insertBefore($insertBefore);

            // Recently Added date picker assign
            initializeDatePicker(`#dateField${i}`);
        });

        // Remove fields
        $('body').on('click', '.remove-item', function() {
            var index = $(this).data('item-id');
            var removedDate = $(`#dateField${index}`).val();
            $(`#addMoreBox${index}`).remove();
        });

    });

    // Function to initialize a date picker with disabled dates
    function initializeDatePicker(selector) {
            if ($(selector).length > 0) {
                datepicker(selector, {
                    position: 'bl',
                    minDate: applyDate,
                    maxDate: lastDate,
                    ...datepickerConfig
                });
            }
        }

    // save request
    $('#save-request').click(function (e) {
        e.preventDefault();

        $.easyAjax({
            url: "{{ route('overtime-requests.store') }}",
            container: '#overtimeHoursForm',
            type: "POST",
            blockUI: true,
            disableButton: true,
            buttonSelector: "#save-request",
            data: $('#overtimeHoursForm').serialize(),
            success: function (response) {
                if (response.status == "success") {
                    $(MODAL_LG).modal('hide');
                }
                showTable();
            }
        })
    });

    // save request
    function getPolicy(id){
        if(id != '' && id != undefined)
        {
            var url = "{{ route('overtime-request-policy', ':id') }}";
            url = url.replace(':id', id);
            $.easyAjax({
                url: url,
                container: '#overtimeHoursForm',
                type: "GET",
                blockUI: true,
                disableButton: true,
                buttonSelector: "#save-request",
                data: $('#overtimeHoursForm').serialize(),
                success: function (response) {
                    applyDate = new Date(response.applyDate);
                    lastDate = new Date(response.currentMonthDate);
                    $('#start_date').val(moment(applyDate).format('YYYY-MM-DD'));
                    $('#end_date').val(moment(lastDate).format('YYYY-MM-DD'));
                    initializeDatePicker('#dateField1');
                }
            });
        }

    }

</script>
