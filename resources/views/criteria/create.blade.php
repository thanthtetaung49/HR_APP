@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('criteria.store') }}" method="POST">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.criteria.addTitle')</h4>
                    <div class="row p-20">

                        @if (isset($fields) && count($fields) > 0)
                            @foreach ($fields as $field)
                                @if ($field->type == 'select' && $field->name == 'exit-reasons-1')
                                    <div class="col-md-6">

                                        <x-forms.label class="my-3" fieldId="exit_reason_id"
                                            :fieldLabel="$field->label" :fieldRequired="($field->required === 'yes') ? true : false">
                                        </x-forms.label>

                                        <x-forms.input-group>
                                            <select class="form-control select-picker mt" name="exit_reason_id"
                                                id="exit_reason_id" data-live-search="true">
                                                @foreach ($field->values as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </x-forms.input-group>
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        <div class="col-md-6">
                            <x-forms.label class="my-3" fieldId="sub_criteria_ids" :fieldLabel="__('app.menu.subCriteria')"
                                fieldName="sub_criteria_ids">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="sub_criteria_ids[]" id="sub_criteria_ids"
                                    data-live-search="true" multiple>
                                    <option value="">--</option>
                                    @foreach ($subCriterias as $subCriteria)
                                        <option value="{{ $subCriteria->id }}">{{ $subCriteria->sub_criteria }}</option>
                                    @endforeach
                                </select>

                                @error('sub_criteria_ids')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>
                    </div>

                    <x-form-actions>
                        <button type="submit" class="mr-3 btn-primary rounded f-14 p-2">
                            <i class="fa fa-check mr-1"></i>@lang('app.save')
                        </button>
                        <x-forms.button-cancel :link="route('criteria.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection
