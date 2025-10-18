<div class="row">
    <div class="col-sm-12">
        <x-form id="save-designation-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.menu.addDesignation')</h4>
                <div class="row p-20">
                    <div class="col-md-6">
                        <x-forms.text fieldId="designation_name" :fieldLabel="__('app.name')" fieldName="name" fieldRequired="true"
                            :fieldPlaceholder="__('placeholders.designation')">
                        </x-forms.text>
                    </div>
                    {{-- <div class="col-md-6">
                        <x-forms.label class="mt-3" fieldId="parent_label" :fieldLabel="__('app.menu.parent_id')"
                                       fieldName="parent_label">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="parent_id" id="parent_id"
                                    data-live-search="true">
                                <option value="">--</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div> --}}

                    <div class="col-md-6">
                        <x-forms.label class="mt-3" fieldId="rank_id" :fieldLabel="__('app.menu.rank')" fieldName="rank">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="rank_id" id="rank_id"
                                data-live-search="true">
                                <option value="">--</option>
                                <option value="1">Rank 1</option>
                                <option value="2">Rank 2</option>
                                <option value="3">Rank 3</option>
                                <option value="4">Rank 4</option>
                                <option value="5">Rank 5</option>
                                <option value="6">Rank 6</option>
                                <option value="7">Rank 7</option>
                                <option value="8">Rank 8</option>
                                <option value="9">Rank 9</option>
                                <option value="10">Rank 10</option>
                            </select>
                        </x-forms.input-group>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-designation-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('designations.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>

            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {

        $('#save-designation-form').click(function() {

            const url = "{{ route('designations.store') }}";

            $.easyAjax({
                url: url,
                container: '#save-designation-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-designation-form",
                data: $('#save-designation-data-form').serialize(),
                success: function(response) {
                    if (response.status === 'success') {
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

        init(RIGHT_MODAL);
    });
</script>
