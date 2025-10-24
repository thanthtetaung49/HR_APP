<style>
    .invalid-feedback {
        display: contents;
    }
</style>
{{-- @dd($criterias) --}}
@if (isset($fields) && count($fields) > 0)
    <div {{ $attributes->merge(['class' => 'row p-20']) }}>
        @foreach ($fields as $field)
            <div class="col-md-3">
                <div class="form-group">
                    @if ($field->type == 'text')
                        <x-forms.text fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldLabel="$field->label" fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldPlaceholder="$field->label" :fieldRequired="$field->required == 'yes' ? 'true' : 'false'" :fieldValue="$model->custom_fields_data['field_' . $field->id] ?? ''">
                        </x-forms.text>
                    @elseif($field->type == 'password')
                        <x-forms.password fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldLabel="$field->label" fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldPlaceholder="$field->label" :fieldRequired="($field->required === 'yes') ? true : false" :fieldValue="$model->custom_fields_data['field_' . $field->id] ?? ''">
                        </x-forms.password>
                    @elseif($field->type == 'number')
                        <x-forms.number fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldLabel="$field->label" fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldPlaceholder="$field->label" :fieldRequired="($field->required === 'yes') ? true : false" :fieldValue="$model->custom_fields_data['field_' . $field->id] ?? ''">
                        </x-forms.number>
                    @elseif($field->type == 'textarea')
                        <x-forms.textarea :fieldLabel="$field->label"
                            fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]" :fieldRequired="($field->required === 'yes') ? true : false"
                            :fieldPlaceholder="$field->label" :fieldValue="$model->custom_fields_data['field_' . $field->id] ?? ''">
                        </x-forms.textarea>
                    @elseif($field->type == 'radio')
                        <div class="form-group my-3">
                            <x-forms.label fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                                :fieldLabel="$field->label" :fieldRequired="($field->required === 'yes') ? true : false">
                            </x-forms.label>
                            <div class="d-flex flex-wrap">
                                <input type="hidden" name="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                                    id="{{ $field->field_name . '_' . $field->id }}" />
                                @foreach ($field->values as $key => $value)
                                    <x-forms.radio fieldId="optionsRadios{{ $key . $field->id }}" :fieldLabel="$value"
                                        fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                                        :fieldValue="$value" :checked="$model && $model->custom_fields_data['field_' . $field->id] == $value
                                            ? true
                                            : false" :fieldRequired="($field->required === 'yes') ? true : false" />
                                @endforeach
                            </div>
                        </div>
                    @elseif($field->type == 'date')
                        <x-forms.datepicker custom="true"
                            fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]" :fieldRequired="($field->required === 'yes') ? true : false"
                            :fieldLabel="$field->label" fieldName="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            :fieldValue="$model && $model->custom_fields_data['field_' . $field->id] != ''
                                ? \Carbon\Carbon::parse($model->custom_fields_data['field_' . $field->id])->format(
                                    company()->date_format,
                                )
                                : now()->format(company()->date_format)" :fieldPlaceholder="$field->label" />
                    @elseif($field->type == 'checkbox')
                        <div class="col-md-12 p-0">
                            <div class="form-group my-3">
                                <x-forms.label
                                    fieldId="custom_fields_data[{{ $field->field_name . '_' . $field->id }}]"
                                    :fieldLabel="$field->label" :fieldRequired="($field->required === 'yes') ? true : false">
                                </x-forms.label>
                                <div class="d-flex flex-wrap checkbox-{{ $field->id }}">
                                    @php
                                        $checkedValues = '';

                                        foreach (json_decode($field->values) as $key => $value) {
                                            if (
                                                $model &&
                                                $model->custom_fields_data['field_' . $field->id] != '' &&
                                                in_array(
                                                    $value,
                                                    explode(', ', $model->custom_fields_data['field_' . $field->id]),
                                                )
                                            ) {
                                                $checkedValues .= $checkedValues == '' ? $value : ', ' . $value;
                                            }
                                        }
                                    @endphp

                                    <input type="hidden"
                                        name="custom_fields_data[{{ $field->field_name . '_' . $field->id }}]"
                                        id="{{ $field->field_name . '_' . $field->id }}" value="{{ $checkedValues }}">
                                    @foreach (json_decode($field->values) as $key => $value)
                                        <div class="col-6 p-0">

                                            <x-forms.checkbox fieldId="optionsRadios{{ $key . $field->id }}"
                                                :fieldLabel="$value" :fieldName="$field->field_name . '_' . $field->id . '[]'" :fieldValue="$value"
                                                :checked="$model &&
                                                    $model->custom_fields_data['field_' . $field->id] != '' &&
                                                    in_array(
                                                        $value,
                                                        explode(
                                                            ', ',
                                                            $model->custom_fields_data['field_' . $field->id],
                                                        ),
                                                    )"
                                                onchange="checkboxChange('checkbox-{{ $field->id }}', '{{ $field->field_name . '_' . $field->id }}')"
                                                :fieldRequired="($field->required === 'yes') ? true : false" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @elseif ($field->type == 'file')
                        <input type="hidden" name="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                            value="{{ $model ? $model->custom_fields_data['field_' . $field->id] : '' }}">
                        <x-forms.file class="custom-field-file" :fieldLabel="$field->label" :fieldRequired="($field->required === 'yes') ? true : false" :fieldName="'custom_fields_data[' . $field->name . '_' . $field->id . ']'"
                            :fieldId="'custom_fields_data[' . $field->name . '_' . $field->id . ']'" :fieldValue="$model
                                ? ($model->custom_fields_data['field_' . $field->id] != ''
                                    ? asset_url_local_s3(
                                        'custom_fields/' . $model->custom_fields_data['field_' . $field->id],
                                    )
                                    : '')
                                : ''" />
                    @elseif($field->type == 'select')
                        @if ($field->name != 'exit-reasons-1')
                            <div class="form-group my-3">
                                <x-forms.label fieldId="custom_fields_data[{{ $field->name . '_' . $field->id }}]"
                                    :fieldLabel="$field->label" :fieldRequired="($field->required === 'yes') ? true : false">
                                </x-forms.label>
                                {!! Form::select(
                                    'custom_fields_data[' . $field->name . '_' . $field->id . ']',
                                    $field->values,
                                    $model ? $model->custom_fields_data['field_' . $field->id] : '',
                                    ['class' => 'form-control select-picker'],
                                ) !!}
                            </div>
                        @endif
                    @endif

                    <div class="form-control-focus"></div>
                    <span class="help-block"></span>
                </div>
            </div>
        @endforeach

        <div class="col-md-4">
            <x-forms.label class="my-3" fieldId="criteria_id" :fieldLabel="__('app.menu.exitsReason')" fieldRequired="true">
            </x-forms.label>

            <x-forms.input-group>
                <select class="form-control select-picker" name="criteria_id" id="criteria_id" data-live-search="true">
                    <option value="">-----</option>

                    @foreach ($criterias as $criteria)
                        @php
                            $employee = new App\Models\EmployeeDetails();
                            $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

                            if ($getCustomFieldGroupsWithFields) {
                                $fields = $getCustomFieldGroupsWithFields->fields;
                            }

                            if (isset($fields) && count($fields) > 0) {
                                foreach ($fields as $field) {
                                    if ($field->type == 'select' && $field->name == 'exit-reasons-1') {
                                        $options = $field->values;
                                        $exitReason = $options[$criteria->exit_reason_id] ?? $criteria->exit_reason_id;
                                    }
                                }
                            }
                        @endphp

                        @if ($model)
                            <option value="{{ $criteria->id }}" @if ($criteria->id == $model->criteria_id) selected @endif>
                                {{ $exitReason }}</option>
                        @else
                            <option value="{{ $criteria->id }}">
                                {{ $exitReason }}</option>
                        @endif
                    @endforeach
                </select>
            </x-forms.input-group>
        </div>

        <div class="col-md-4">
            <x-forms.label class="my-3" fieldId="sub_criteria_id" :fieldLabel="__('app.menu.subCriteria')" fieldName="sub_criteria_id">
            </x-forms.label>
            <x-forms.input-group>
                <select class="form-control select-picker mt" name="sub_criteria_id" id="sub_criteria_id"
                    data-live-search="true">
                    <option value="">--</option>
                    @if ($subCriterias)
                        @foreach ($subCriterias as $subCriteria)
                            <option value="{{ $subCriteria->id }}" @if ($model->sub_criteria_id == $subCriteria->id) selected @endif>
                                {{ $subCriteria->sub_criteria }}</option>
                        @endforeach
                    @endif
                </select>

                @error('sub_criteria_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </x-forms.input-group>
        </div>
    </div>
@endif
