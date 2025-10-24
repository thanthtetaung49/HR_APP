@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('man-power-reports.update', $reports->id) }}" method="PUT">
                @csrf
                <div class="add-client bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang('modules.manPower.addTitle')</h4>

                    <div class="row p-20">
                        {{-- @dump($reports) --}}
                        <div class="col-md-4">
                            <x-forms.text fieldId="budget_year" :fieldLabel="__('app.menu.budgetYear')" fieldName="budget_year" fieldRequired="true"
                                :fieldPlaceholder="__('placeholders.budgetYear')" :fieldValue="old('budget_year', $reports->budget_year)">
                            </x-forms.text>

                            @error('budget_year')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="parent_label" :fieldLabel="__('app.menu.quarter')" fieldName="quater">
                            </x-forms.label>

                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="quarter" id="quarter"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    <option value="1" @if ($reports->quarter == 1) selected @endif>Q1 (Jan to Dec)
                                    </option>
                                    <option value="2" @if ($reports->quarter == 2) selected @endif>Q2 (Apr to Dec)
                                    </option>
                                    <option value="3" @if ($reports->quarter == 3) selected @endif>Q3 (Jul to
                                        Dec)</option>
                                    <option value="4" @if ($reports->quarter == 4) selected @endif>Q4 (Oct to Dec)
                                    </option>
                                </select>
                            </x-forms.input-group>

                            @error('quarter')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="man_power_setup" :fieldLabel="__('app.menu.manPowerSetup')" fieldName="man_power_setup"
                                fieldRequired="true" :fieldPlaceholder="__('placeholders.manPowerSetup')" :fieldValue="old('man_power_setup', $reports->man_power_setup)">
                            </x-forms.text>

                            @error('man_power_setup')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.text fieldId="man_power_basic_salary" :fieldLabel="__('app.menu.maxBasicSalary')"
                                fieldName="man_power_basic_salary" fieldRequired="true" :fieldPlaceholder="__('placeholders.maxBasicSalary')" :fieldValue="old('man_power_basic_salary', $reports->man_power_basic_salary)">
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
                                        <option value="{{ $department->id }}"
                                            @if ($department->id == $reports->team_id) selected @endif>{{ $department->team_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-forms.input-group>

                            @error('team_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="parent_label" :fieldLabel="__('app.menu.designation')" fieldName="position_id">
                            </x-forms.label>

                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="position_id" id="position_id"
                                    data-live-search="true">
                                    <option value="">--werwe</option>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}"
                                            @if ($reports->position_id == $designation->id) selected @endif>{{ $designation->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-forms.input-group>

                            @error('position_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        @can('canApprove', App\Models\ManPowerReport::class)
                            <div class="col-md-4">
                                <x-forms.label class="my-3" fieldId="parent_label" :fieldLabel="__('app.menu.status')" fieldName="position_id">
                                </x-forms.label>

                                <x-forms.input-group>
                                    <select class="form-control select-picker mt" name="status" id="status"
                                        data-live-search="true">
                                        <option value="">--</option>
                                        <option value="pending" @if ($reports->status == 'pending') selected @endif>Pending
                                        </option>
                                        <option value="approved" @if ($reports->status == 'approved') selected @endif>Approved
                                        </option>
                                        <option value="review" @if ($reports->status == 'review') selected @endif>Review</option>
                                    </select>
                                </x-forms.input-group>

                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <x-forms.text fieldId="remark_from" :fieldLabel="__('app.menu.remarkFrom')" fieldName="remark_from"
                                    fieldRequired="true" :fieldPlaceholder="__('placeholders.remark')" :fieldValue="old('remark_from', $reports->remark_from)">
                                </x-forms.text>

                                @error('remark_from')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endcan

                        @php
                            $roles = user()->roles;

                            $isAdmin = $roles->contains(function ($role) {
                                return $role->name === 'admin';
                            });

                            $isHRmanager = $roles->contains(function ($role) {
                                return $role->name === 'hr-manager';
                            });

                            $readOnly = false;

                            if ($isAdmin || $isHRmanager) {
                                $readOnly = true;
                            }

                        @endphp

                        <div class="col-md-4">
                            <x-forms.text fieldId="remark_to" :fieldLabel="__('app.menu.remarkTo')" fieldName="remark_to" fieldRequired="true"
                                :fieldPlaceholder="__('placeholders.remark')" :fieldValue="old('remark_to', $reports->remark_to)" :fieldReadOnly="$readOnly">
                            </x-forms.text>

                            @error('remark_to')
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

@push('scripts')
    <script>
        $('#team_id').change(function() {
            const teamId = $(this).val();

            const data = {
                teamId: teamId
            }

            $.ajax({
                type: "GET",
                url: "{{ route('manPowerReports.apply_department_filter') }}",
                data: data,
                dataType: "json",
                success: function(response) {
                    const designations = response.designations;
                    const designationId = @json($reports->position_id);

                    let options = `<option value="">--</option>`;

                    designations.forEach(item => {
                        console.log(designationId, item.id);
                        if (designationId === item.id) {
                            options +=
                                `<option value="${item.id}" selected>${item.name}</option>`;
                        } else {
                            options += `<option value="${item.id}">${item.name}</option>`;

                        }
                    });

                    $('#position_id').html(options);
                    $('#position_id').val(designationId);
                    $('#position_id').selectpicker('refresh');
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }).trigger('change');
    </script>
@endpush
