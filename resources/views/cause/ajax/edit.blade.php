@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('causes.update', $cause->id) }}" method="PUT">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.causes.addTitle')</h4>

                    <div class="row p-20">
                        <div class="col-md-4">
                            <x-forms.text fieldId="exit_reason_id" :fieldLabel="__('app.menu.exitsReason')" fieldName="exit_reason_id" fieldRequired="true"
                                :fieldPlaceholder="__('placeholders.exitsReason')" :fieldValue="old('exit_reason_id', $cause->exit_reason)">
                            </x-forms.text>

                            @error('exit_reason')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="parent_label" :fieldLabel="__('app.menu.criteria')" fieldName="criteria_id">
                            </x-forms.label>

                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="criteria_id" id="criteria_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($criterias as $criteria)
                                        <option value="{{ $criteria->id }}" @if ($criteria->id == $cause->criteria_id) selected @endif>{{ $criteria->criteria }}</option>
                                    @endforeach
                                </select>
                            </x-forms.input-group>

                            @error('criteria_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="action_taken" :fieldLabel="__('app.menu.actionTaken')" fieldName="action_taken"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.actionTaken')" :fieldValue="old('action_taken', $cause->action_taken)">
                            </x-forms.text>

                            @error('action_taken')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <x-form-actions>
                        <button type="submit" class="mr-3 btn-primary rounded f-14 p-2">
                            <i class="fa fa-check mr-1"></i>@lang('app.save')
                        </button>
                        <x-forms.button-cancel :link="route('causes.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection
