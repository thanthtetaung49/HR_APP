<style>
    #dob {
        display: block !important;
    }
</style>
<div class="row">
    <div class="col-sm-12">
        <x-form id="save-job-application-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('recruit::modules.jobApplication.jobApplication') @lang('app.edit')
                </h4>
                <input type="hidden" value="{{ $jobApplication->id }}" name="application_id">
                <div class="row p-20">
                    <div id="firstDiv" class="col-lg-12 col-xl-12">
                        <div class="row">
                            <div class="col-md-12">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg" :fieldLabel="__('modules.profile.profilePicture')"
                                    fieldName="photo" fieldId="photo" fieldHeight="119" :fieldValue="$jobApplication->photo ? $jobApplication->image_url : null" />
                            </div>

                            <div class="col-md-3">
                                <x-forms.label fieldId="job_id" fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.jobs')"
                                    class="mt-3"></x-forms.label>
                                <div class="form-group mb-0">
                                    <select name="job_id" id="job_id" class="form-control select-picker"
                                        data-size="8">
                                        <option value="">--</option>
                                        @foreach ($jobs as $job)
                                            <option @selected($job->id == $jobApplication->recruit_job_id) value="{{ $job->id }}">
                                                {{ $job->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text fieldId="name" :fieldLabel="__('recruit::modules.jobApplication.name')" fieldName="full_name"
                                    fieldRequired="true" :fieldValue="$jobApplication->full_name" :fieldPlaceholder="__('placeholders.name')">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text fieldId="father_name" :fieldLabel="__('recruit::modules.jobApplication.fatherName')" fieldName="father_name"
                                    fieldRequired="true" :fieldValue="$jobApplication->father_name" :fieldPlaceholder="__('placeholders.fatherName')">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text :fieldRequired="true" fieldId="email" :fieldLabel="__('recruit::modules.jobApplication.email')" fieldName="email"
                                    :fieldValue="$jobApplication->email" :fieldPlaceholder="__('placeholders.email')">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.tel fieldId="phone" :fieldLabel="__('app.phone')" fieldName="phone" :fieldValue="$jobApplication->phone"
                                    fieldPlaceholder="e.g. 987654321" fieldRequired="true"></x-forms.tel>
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="gender" :fieldRequired="true" fieldName="gender"
                                    :fieldLabel="__('recruit::modules.jobApplication.gender')">
                                    <option value="">--</option>
                                    <option value="male" @selected($jobApplication->gender === 'male')>
                                        @lang('app.male')
                                    </option>
                                    <option value="female" @selected($jobApplication->gender === 'female')>
                                        @lang('app.female')
                                    </option>
                                    <option value="others" @selected($jobApplication->gender === 'others')>
                                        @lang('app.others')
                                    </option>
                                </x-forms.select>
                            </div>



                            <div class="col-md-3">
                                <x-forms.text fieldId="current_location" :fieldLabel="__('recruit::modules.jobApplication.currentLocation')" fieldName="current_location"
                                    :fieldValue="$jobApplication->current_location" fieldPlaceholder="e.g. New York"
                                    ></x-forms.text>
                            </div>

                            <div class="col-md-3">
                                <label class="f-14 text-dark-grey mb-12 mt-3 " for="usr">@lang('recruit::modules.jobApplication.experience')</label>

                                <div class="mb-4">
                                    <select name="total_experience" class="form-control select-picker"
                                        id="total_experience" data-live-search="true" data-container="body"
                                        data-size="8">
                                        <option value="">--</option>
                                        @foreach (['fresher', '0-1', '1-2', '2-3', '3-4', '4-5', '5-6', '6-7', '7-8', '8-9', '9-10', '10-11', '11-12', '12-13', '13-14', 'over-15'] as $experience)
                                            <option value="{{ $experience }}" @selected($jobApplication->total_experience == $experience)>
                                                @if ($experience === 'fresher')
                                                    @lang('recruit::modules.jobApplication.fresher')
                                                @elseif ($experience === 'over-15')
                                                    @lang('recruit::modules.jobApplication.over15')
                                                @else
                                                    {{ $experience }} @lang('recruit::modules.jobApplication.years')
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>



                            <div class="col-md-3">
                                <x-forms.select fieldId="notice_period" :fieldLabel="__('recruit::modules.jobApplication.noticePeriod')" fieldName="notice_period">
                                    <option value="">--</option>
                                    @foreach (['15', '30', '45', '60', '75', '90'] as $days)
                                        <option value="{{ $days }}" @selected($jobApplication->notice_period == $days)>
                                            {{ $days }} @lang('recruit::modules.jobApplication.days')
                                        </option>
                                    @endforeach
                                    <option value="over-90" @selected($jobApplication->notice_period == 'over-90')>
                                        @lang('recruit::modules.jobApplication.over90')
                                    </option>
                                </x-forms.select>
                            </div>

                            {{-- <div class="col-md-3">
                                <x-forms.select fieldId="status_id" fieldName="status_id" :fieldLabel="__('recruit::modules.jobApplication.status')">
                                    @foreach ($applicationStatus as $status)
                                        <option @selected($status->id == $jobApplication->recruit_application_status_id)
                                            value="{{ $status->id }}"
                                            data-content="<i class='fa fa-circle mr-2' style='color: {{ $status->color }}'></i> {{ $status->status }}">
                                        </option>
                                    @endforeach
                                </x-forms.select>
                            </div> --}}

                            <div class="col-md-3" id="dob">
                                <x-forms.text class="date-picker" :fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.dateOfBirth')"
                                    fieldName="date_of_birth" fieldId="date_of_birth-1" :fieldPlaceholder="__('placeholders.date')"
                                    :fieldValue="$jobApplication->date_of_birth
                                        ? $jobApplication->date_of_birth->format($company->date_format)
                                        : ''" />
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="rank_level" fieldName="rank_level" :fieldLabel="'Rank Level'">
                                </x-forms.select>
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="management_rank_level" fieldName="management_rank_level"
                                    :fieldLabel="'Management Rank'">
                                </x-forms.select>
                            </div>

                            @include('recruit::job-applications.ajax.hr-workflow-fields')
                        </div>
                    </div>
                    <div class="col-md-12">
                        <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('recruit::modules.jobApplication.coverLetter')" fieldName="cover_letter"
                            fieldId="cover_letter" :fieldValue="$jobApplication->cover_letter" :fieldPlaceholder="__('recruit::modules.jobApplication.coverLetter')">
                        </x-forms.textarea>
                    </div>

                    <div class="col-md-12" id="resume1">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="resume" fieldRequired="false" :fieldLabel="__('recruit::app.menu.add') . ' ' . __('recruit::app.jobApplication.resume')"
                                class="mt-3"></x-forms.label>
                            <input type="file" class="dropify" name="resume"
                                data-allowed-file-extensions="txt pdf doc xls xlsx docx rtf png jpg jpeg svg"
                                data-messages-default="test" data-height="150"
                                @if ($jobApplictionFile) data-default-file="{{ $jobApplictionFile->file_url }}" @endif />
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="f-14 text-dark-grey mb-12 w-100" for="send_email"></label>
                            <div class="d-flex">
                                <x-forms.checkbox fieldId="send_email" :fieldLabel="__('recruit::modules.jobApplication.sendMailToJobApplicant')" fieldValue="1"
                                    fieldName="send_email" :checked="$jobApplication->send_email == 1"></x-forms.checkbox>
                            </div>
                        </div>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="save-job-application" class="mr-3"
                        icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('job-applications.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function() {
        const jobDropdown = $('#job_id');
        const rankLevelDropdown = $('#rank_level');
        const managementRankDropdown = $('#management_rank_level');


        const initialJobId = String(
            @json(optional($jobApplication->job)->id ?? '')
        );

        const initialRankLevel = @json(optional($jobApplication->job)->rank_level ?? optional(optional($jobApplication->job)->designation)->rank_id);

        const initialManagementRankId = @json(optional($jobApplication->job)->management_rank_level);

        function refreshSelectPicker(dropdown) {
            if (
                dropdown.length &&
                typeof dropdown.selectpicker === 'function'
            ) {
                dropdown.selectpicker('refresh');
            }
        }

        function setDropdownValue(dropdown, value) {
            const selectedValue =
                value === null || value === undefined ?
                '' :
                String(value);

            dropdown.val(selectedValue);

            if (typeof dropdown.selectpicker === 'function') {
                dropdown.selectpicker('val', selectedValue);
                dropdown.selectpicker('refresh');
            }
        }

        function resetRankDropdowns() {
            rankLevelDropdown
                .empty()
                .append(new Option('--', ''));

            managementRankDropdown
                .empty()
                .append(new Option('--', ''));

            refreshSelectPicker(rankLevelDropdown);
            refreshSelectPicker(managementRankDropdown);
        }

        function populateRankLevel(rankLevel) {
            const parsedRank = parseInt(rankLevel, 10);

            if (Number.isNaN(parsedRank)) {
                return;
            }

            rankLevelDropdown
                .empty()
                .append(new Option('--', ''))
                .append(
                    new Option(
                        'Rank ' + parsedRank,
                        parsedRank,
                        true,
                        true
                    )
                );

            setDropdownValue(rankLevelDropdown, parsedRank);
        }

        function populateManagementRank(id, name) {
            if (
                id === null ||
                id === undefined ||
                id === '' ||
                !name
            ) {
                return;
            }

            managementRankDropdown
                .empty()
                .append(new Option('--', ''))
                .append(
                    new Option(
                        name,
                        id,
                        true,
                        true
                    )
                );

            setDropdownValue(managementRankDropdown, id);
        }

        function loadJobRanks(jobId, useInitialValues = false) {
            resetRankDropdowns();

            if (!jobId) {
                return;
            }


            if (useInitialValues && initialRankLevel !== null) {
                populateRankLevel(initialRankLevel);
            }

            const url =
                "{{ route('job-applications.rank_level') }}";

            $.easyAjax({
                url: url,
                type: 'GET',
                disableButton: true,
                blockUI: true,
                data: {
                    job_id: jobId
                },

                success: function(response) {
                    const data = response.data ?? response;

                    if (data.status === 'error') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rank level is not assigned',
                            text: data.message
                        });

                        return;
                    }

                    const managementRankId =
                        data.management_rank_id ??
                        data.id ??
                        (
                            useInitialValues ?
                            initialManagementRankId :
                            null
                        );

                    const managementRankName =
                        data.management_rank_name ??
                        data.name;

                    const actualRankValue =
                        data.rank_level ??
                        data.rankLevel ??
                        (
                            useInitialValues ?
                            initialRankLevel :
                            null
                        ) ??
                        jobDropdown
                        .find('option:selected')
                        .attr('data-actual-rank');

                    resetRankDropdowns();

                    populateRankLevel(actualRankValue);

                    populateManagementRank(
                        managementRankId,
                        managementRankName
                    );

                    jobDropdown
                        .find('option:selected')
                        .attr(
                            'data-actual-rank',
                            actualRankValue ?? ''
                        );
                },

                error: function() {

                    resetRankDropdowns();

                    if (useInitialValues) {
                        populateRankLevel(initialRankLevel);
                    }
                }
            });
        }


        $(document)
            .off('change.jobRank', '#job_id')
            .on('change.jobRank', '#job_id', function() {
                loadJobRanks(
                    $(this).val(),
                    String($(this).val()) === initialJobId
                );
            });


        datepicker('#date_of_birth-1', {
            ...datepickerConfig,
            position: 'bl',
            maxDate: new Date(),

            onSelect: function() {
                setTimeout(function() {
                    calculateApplicantAge();
                }, 0);
            }
        });

        $(document).find('.dropify').dropify({
            messages: dropifyMessages
        });

        $(document)
            .off(
                'click.editJobApplication',
                '#save-job-application'
            )
            .on(
                'click.editJobApplication',
                '#save-job-application',
                function() {
                    const url =
                        "{{ route('job-applications.update', $jobApplication->id) }}";

                    $.easyAjax({
                        url: url,
                        container: '#save-job-application-data-form',
                        type: 'POST',
                        disableButton: true,
                        blockUI: true,
                        file: true,
                        buttonSelector: '#save-job-application',
                        data: $('#save-job-application-data-form')
                            .serialize(),

                        success: function(response) {
                            if (response.blacklistMatch) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Blacklist Match',
                                    text: response.message
                                });
                            }

                            if ($(RIGHT_MODAL).hasClass('in')) {
                                const closeButton =
                                    document.getElementById(
                                        'close-task-detail'
                                    );

                                if (closeButton) {
                                    closeButton.click();
                                }

                                if (
                                    $('#job-applications-table')
                                    .length
                                ) {
                                    window.LaravelDataTables[
                                        'job-applications-table'
                                    ].draw(false);
                                } else {
                                    window.location.href =
                                        response.redirectUrl;
                                }
                            } else {
                                window.location.href =
                                    response.redirectUrl;
                            }
                        }
                    });
                }
            );

        @if ($jobApplication->job && $jobApplication->job->is_dob_require)
            $('#dob').show();
        @else
            $('#dob').hide();
        @endif

        $('#resume1').show();

        init(RIGHT_MODAL);

        if (jobDropdown.val()) {
            loadJobRanks(jobDropdown.val(), true);
        }

        calculateApplicantAge();
    });
</script>
