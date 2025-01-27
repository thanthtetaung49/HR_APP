<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.location.addTitle')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
</div>
<div class="modal-body">

    <x-form id="save-location-form">
        <div class="add-client bg-white rounded">
            <div class="row">
                <div class="col-md-3">
                    <x-forms.text fieldId="location" :fieldLabel="__('app.menu.location')" fieldName="location" fieldRequired="true"
                        :fieldPlaceholder="__('placeholders.location')">
                    </x-forms.text>

                    @error('location')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </x-form>


</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-location-form-button" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        $(".select-picker").selectpicker();
    });

    $('#save-location-form-button').click(function() {
        var url = "{{ route('location.ajax.store') }}";
        $.easyAjax({
            url: url,
            container: '#save-location-form',
            type: "POST",
            data: $('#save-location-form').serialize(),
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-category",
            success: function(response) {
                let locations = response.data;
                let html = `<option value="">--</option>`;

                $.each(locations, function(index, value) {
                    html += `<option value="${value.id}">${value.location_name}</option>`;
                });

                $('#location').html(html);
                $('#location').selectpicker('refresh');
                $(MODAL_LG).modal('hide');
            }
        })
    });
</script>
