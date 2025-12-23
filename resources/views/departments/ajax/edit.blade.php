<style>
    .mt {
        margin-top: -4px;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-department-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.menu.editDepartment')</h4>

                <div class="row p-20">
                    <div class="col-md-4">
                        <x-forms.text fieldId="team_name" :fieldLabel="__('app.name')" fieldName="team_name" fieldRequired="true"
                            fieldValue="{{ $department->team_name }}">
                        </x-forms.text>
                    </div>
                    <div class="col-md-4">
                        <x-forms.label class="my-3 mt-2" fieldId="parent_label" :fieldLabel="__('app.parentId')"
                            fieldName="parent_label">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker mt" name="parent_id" id="parent_id"
                                data-live-search="true">
                                <option value="">--</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item->id }}"
                                        @if ($department->parent_id == $item->id) selected @endif>{{ $item->team_name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>

                    <div class="col-md-4">
                        <x-forms.label class="my-3" fieldId="location_id" :fieldLabel="__('app.location')" fieldName="location">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker mt" name="location" id="location_id"
                                data-live-search="true">
                                <option value="">--</option>
                                @foreach ($locations as $location)
                                    @if ($location->id == $department->location_id)
                                        <option value="{{ $location->id }}" selected>{{ $location->location_name }}
                                        </option>
                                    @else
                                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>

                    <div class="col-md-4">
                        <x-forms.label class="my-3" fieldId="designation_label" :fieldLabel="__('app.designation')"
                            fieldName="designation_label">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker mt" name="designation_id[]" id="designation_id"
                                multiple data-live-search="true">
                                <option value="">--</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}"
                                        @foreach ($selectedDesignations as $selectedDesignation)
                                        @if ($designation->id == $selectedDesignation)
                                            selected
                                        @endif @endforeach>
                                        {{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>

                    <div class="col-md-4 pb-3">
                        <x-forms.label class="my-3" fieldId="designation_label" :fieldLabel="__('app.departmentGroup')"
                            fieldName="department type">
                        </x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker mt" name="department_type" id="department_type"
                                data-live-search="true">
                                <option value="">--</option>
                                <option value="operation" @if ($department->department_type === 'operation') selected @endif>Operation
                                </option>
                                <option value="supporting" @if ($department->department_type === 'supporting') selected @endif>Supporting
                                </option>
                            </select>
                        </x-forms.input-group>
                    </div>
                </div>


                <x-form-actions>
                    <x-forms.button-primary id="save-department-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('departments.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function() {

        $('#save-department-form').click(function() {

            const url = "{{ route('departments.update', $department->id) }}";

            $.easyAjax({
                url: url,
                container: '#save-department-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-department-form",
                data: $('#save-department-data-form').serialize(),
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });

        init(RIGHT_MODAL);
    });
</script>
