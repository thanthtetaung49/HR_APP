@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('sub-criteria.update', $subCriteria->id) }}" method="PUT">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('app.menu.editSubCriteria')</h4>
                    <div class="row p-20">
                        <div class="col-md-4">
                            <x-forms.text fieldId="sub_criteria" :fieldLabel="__('app.menu.subCriteria')" fieldName="sub_criteria"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.subCriteria')" :fieldValue="old('sub_criteria', $subCriteria->sub_criteria)">
                            </x-forms.text>

                            @error('sub_criteria')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="accountability" :fieldLabel="__('app.menu.accountability')" fieldName="accountability"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.accountability')" :fieldValue="old('accountability', $subCriteria->accountability)">
                            </x-forms.text>

                            @error('accountability')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="action_taken" :fieldLabel="__('app.menu.actionTaken')" fieldName="action_taken"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.actionTaken')" :fieldValue="old('action_taken', $subCriteria->action_taken)">
                            </x-forms.text>

                            @error('action_taken')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="responsible_person" :fieldLabel="__('app.menu.responsiblePerson')" fieldName="responsible_person"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.responsiblePerson')" :fieldValue="old('responsible_person', $subCriteria->responsible_person)">
                            </x-forms.text>

                            @error('responsible_person')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <x-form-actions>
                        <button type="submit" class="mr-3 btn-primary rounded f-14 p-2">
                            <i class="fa fa-check mr-1"></i>@lang('app.save')
                        </button>
                        <x-forms.button-cancel :link="route('sub-criteria.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection
