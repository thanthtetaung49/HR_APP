@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('report-permission.update', $report->id) }}" method="PUT">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.reportPermission.addTitle')</h4>
                    <div class="row p-20">

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="location_id" :fieldLabel="__('app.menu.location')" fieldName="location_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="location_id" id="location_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}"
                                            @if ($location->id == $report->location_id) selected @endif>{{ $location->location_name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('location_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="team_id" :fieldLabel="__('app.menu.department')" fieldName="team_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="team_id" id="team_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            @if ($department->id == $report->team_id) selected @endif>{{ $department->team_name }}
                                        </option>
                                    @endforeach

                                </select>

                                @error('team_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="designation_id" :fieldLabel="__('app.menu.designation')"
                                fieldName="designation_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="designation_id" id="designation_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}"
                                            @if ($designation->id == $report->designation_id) selected @endif>{{ $designation->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('designation_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>



                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="user_id" :fieldLabel="__('app.menu.employees')" fieldName="user_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="user_id" id="user_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            @if ($user->id == $report->user_id) selected @endif>{{ $user->name }}</option>
                                    @endforeach
                                </select>

                                @error('user_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="report_id" :fieldLabel="__('app.menu.reportName')" fieldName="report_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="report_id" id="user_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    <option value="1" selected>Man Power Report</option>
                                </select>

                                @error('report_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="permission" :fieldLabel="__('app.menu.permission')" fieldName="permission">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="permission" id="user_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    <option value="1" @if ($report->permission == 'yes') selected @endif>Yes</option>
                                    <option value="0" @if ($report->permission == 'no') selected @endif>No</option>
                                </select>

                                @error('permission')
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
