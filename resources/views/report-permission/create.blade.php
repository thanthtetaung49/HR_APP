@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="row p-20">
        <div class="col-sm-12">
            <x-form action="{{ route('report-permission.store') }}" method="POST">
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
                                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                                    @endforeach
                                </select>

                                @error('location_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </x-forms.input-group>
                        </div>

                        <div class="col-md-4">
                            <x-forms.label class="my-3" fieldId="team_id" :fieldLabel="__('app.menu.department')"
                                fieldName="team_id">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker mt" name="team_id" id="team_id"
                                    data-live-search="true">
                                    <option value="">--</option>

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
                                    data-live-search="true" >
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
                                    data-live-search="true" >
                                    <option value="">--</option>
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
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
                        <x-forms.button-cancel :link="route('report-permission.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $("#location_id").on('change', function() {
            let location_id = $(this).val();
            let department_html = `<option value="">--</option>`;
            let url = "{{ route('location.select') }}";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': location_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let teams = response.data;
                    let html = department_html;

                    teams.forEach((team) => {
                        html += `
                            <option value="${team.id}">${team.team_name}</option>
                        `
                    });

                    $("#team_id").html(html);
                    $("#team_id").selectpicker(
                        'refresh'); // refresh the bootstrap select ui
                }

            });

        });

        $("#team_id").on('change', function() {
            let team_id = $(this).val();
            let url = "{{ route('department.select') }}";
            let designation_html = `<option value="">--</option>`;

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': team_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let designations = response.data;
                    let html = designation_html;

                    designations.forEach((designation) => {
                        html += `
                            <option value="${designation.id}">${designation.name}</option>
                        `
                    });

                    $("#designation_id").html(html);
                    $("#designation_id").selectpicker('refresh'); // refresh the bootstrap select ui
                }
            });

        });


        $("#designation_id").on('change', function() {
            let designation_id = $(this).val();
            let url = "{{ route('designation.select') }}";
            let employee_html = `<option value="">--</option>`;

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    'id': designation_id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let users = response.data;
                    let html = employee_html;

                    users.forEach((user) => {
                        html += `
                            <option value="${user.id}">${user.name}</option>
                        `
                    });

                    $("#user_id").html(html);
                    $("#user_id").selectpicker('refresh'); // refresh the bootstrap select ui
                }
            });

        });
    </script>
@endpush
