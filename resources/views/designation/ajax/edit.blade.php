<div class="row">
    <div class="col-sm-12">
        <x-form id="save-lead-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.menu.editdesignation')</h4>
                <div class="row p-20">
                    <div class="col-md-6">
                        <x-forms.text fieldId="designation_name" :fieldLabel="__('app.name')" fieldName="designation_name"
                            :fieldValue="$designation->name" fieldRequired="true" :fieldPlaceholder="__('placeholders.designation')">
                        </x-forms.text>
                    </div>
                    {{-- <div class="col-md-6">
                            <x-forms.label class="mt-3" fieldId="parent_label" :fieldLabel="__('app.menu.parent_id')" fieldName="parent_label">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="parent_id" id="parent_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($designations as $designations)
                                        @if ($designation->id != $designations->id)
                                            <option value="{{ $designations->id }}" @if ($designation->parent_id == $designations->id) selected @endif>{{ $designations->name }}</option>
                                        @endif
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
                                <option value="1" @if ($designation->rank_id == 1) selected @endif>Rank 1</option>
                                <option value="2" @if ($designation->rank_id == 2) selected @endif>Rank 2</option>
                                <option value="3" @if ($designation->rank_id == 3) selected @endif>Rank 3</option>
                                <option value="4" @if ($designation->rank_id == 4) selected @endif>Rank 4</option>
                                <option value="5" @if ($designation->rank_id == 5) selected @endif>Rank 5</option>
                                <option value="6" @if ($designation->rank_id == 6) selected @endif>Rank 6</option>
                                <option value="7" @if ($designation->rank_id == 7) selected @endif>Rank 7</option>
                                <option value="8" @if ($designation->rank_id == 8) selected @endif>Rank 8</option>
                                <option value="9" @if ($designation->rank_id == 9) selected @endif>Rank 9</option>
                                <option value="10" @if ($designation->rank_id == 10) selected @endif>Rank 10
                                </option>
                            </select>
                        </x-forms.input-group>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-leave-form" class="mr-3" icon="check">@lang('app.save')
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

        $('#save-leave-form').click(function() {

            const url = "{{ route('designations.update', $designation->id) }}";

            $.easyAjax({
                url: url,
                container: '#save-lead-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-leave-form",
                data: $('#save-lead-data-form').serialize(),
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });

        init(RIGHT_MODAL);
    });
</script>
