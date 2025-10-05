@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('man-power-reports.store') }}" method="POST">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.manPower.addTitle')</h4>
                    <div class="row p-20">
                        <div class="col-md-4">
                            <x-forms.text fieldId="man_power_setup" :fieldLabel="__('app.menu.manPowerSetup')" fieldName="man_power_setup"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.manPowerSetup')">
                            </x-forms.text>

                            @error('man_power_setup')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="man_power_basic_salary" :fieldLabel="__('app.menu.maxBasicSalary')"
                                fieldName="man_power_basic_salary" fieldRequired="true" :fieldPlaceholder="__('placeholders.maxBasicSalary')">
                            </x-forms.text>

                            @error('man_power_basic_salary')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="parent_label" :fieldLabel="__('app.menu.teams')" fieldName="team_id">
                            </x-forms.label>

                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="team_id" id="team_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->team_name }}</option>
                                    @endforeach
                                </select>
                            </x-forms.input-group>

                            @error('team_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <x-form-actions>
                        <button type="submit" class="mr-3 btn-primary rounded f-14 p-2">
                            <i class="fa fa-check mr-1"></i>@lang('app.save')
                        </button>
                        <x-forms.button-cancel :link="route('man-power-reports.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection
