@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('sub-criteria.store') }}" method="POST">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.subCriteria.addTitle')</h4>
                    <div class="row p-20">
                        <div class="col-md-4">
                            <x-forms.text fieldId="sub_criteria" :fieldLabel="__('app.menu.subCriteria')" fieldName="sub_criteria"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.subCriteria')">
                            </x-forms.text>

                            @error('sub_criteria')
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
