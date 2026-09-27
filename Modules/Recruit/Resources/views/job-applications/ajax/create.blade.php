<style>
    #dob {
        display: block !important;
    }
</style>
<div class="row">
    <div class="col-sm-12">
        <x-form id="save-job-application-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('recruit::modules.skill.createnew')</h4>
                <div class="row p-20">
                    <div id="firstDiv" class="col-lg-12 col-xl-12">
                        <div class="row">
                            <div class="col-md-12">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg" :fieldLabel="__('modules.profile.profilePicture')"
                                    fieldName="photo" fieldId="photo" fieldHeight="119" />
                            </div>

                            <div class="col-md-3">
                                <x-forms.label fieldId="job_id" fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.jobs')"
                                    class="mt-3"></x-forms.label>
                                <div class="form-group mb-0">
                                    @if ($jobId)
                                        <input type="hidden" name="job_id" value="{{ $jobId }}">
                                    @endif
                                    <select @if ($jobId) disabled @endif name="job_id"
                                        id="job_id" class="form-control select-picker" data-size="8">
                                        <option value="">--</option>
                                        @foreach ($jobs as $job)
                                            <option @if ($jobId && $job->id == $jobId) selected @endif
                                                value="{{ $job->id }}" data-actual-rank="{{ $job->rank_level }}">
                                                {{ $job->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text fieldId="name" :fieldLabel="__('recruit::modules.jobApplication.name')" fieldName="full_name"
                                    fieldRequired="true" :fieldPlaceholder="__('placeholders.name')">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text fieldId="father_name" :fieldLabel="__('recruit::modules.jobApplication.fatherName')" fieldName="father_name"
                                    fieldRequired="true" :fieldPlaceholder="__('placeholders.fatherName')">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.text fieldId="email" :fieldLabel="__('recruit::modules.jobApplication.email')" fieldName="email" :fieldPlaceholder="__('placeholders.email')" fieldRequired="true">
                                </x-forms.text>
                            </div>
                            <div class="col-md-3">
                                <x-forms.tel fieldId="phone" :fieldLabel="__('app.phone')" fieldName="phone"
                                    fieldPlaceholder="e.g. 987654321" fieldRequired="true"></x-forms.tel>
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="gender" :fieldRequired="true" fieldName="gender"
                                    :fieldLabel="__('recruit::modules.jobApplication.gender')">
                                    <option value="">--</option>
                                    <option value="male">@lang('app.male')</option>
                                    <option value="female">@lang('app.female')</option>
                                    <option value="others">@lang('app.others')</option>
                                </x-forms.select>
                            </div>



                            <div class="col-md-3">
                                <x-forms.text fieldId="current_location" :fieldLabel="__('recruit::modules.jobApplication.currentLocation')" fieldName="current_location"
                                    fieldPlaceholder="e.g. New York"></x-forms.text>
                            </div>

                            <div class="col-md-3">
                                <label class="f-14 text-dark-grey mb-12 mt-3 " for="usr">@lang('recruit::modules.jobApplication.experience')</label>

                                <div class="mb-4">
                                    <select name="total_experience" class="form-control select-picker"
                                        id="total_experience" data-live-search="true" data-container="body"
                                        data-size="8">
                                        <option value="">--</option>
                                        <option value="fresher">@lang('recruit::modules.jobApplication.fresher')</option>
                                        <option value="0-1">0-1 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="1-2">1-2 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="2-3">2-3 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="3-4">3-4 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="4-5">4-5 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="5-6">5-6 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="6-7">6-7 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="7-8">7-8 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="8-9">8-9 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="9-10">9-10 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="10-11">10-11 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="11-12">11-12 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="12-13">12-13 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="13-14">13-14 @lang('recruit::modules.jobApplication.years')</option>
                                        <option value="over-15">@lang('recruit::modules.jobApplication.over15')</option>
                                    </select>
                                </div>
                            </div>

                            {{-- <div class="col-md-3">
                                <x-forms.label class="my-3" fieldId="current_ctc"
                                    fieldLabel="Current CTC (MMK)"></x-forms.label>
                                <x-forms.input-group>
                                    <input type="number" min="0" step="0.01"
                                        class="form-control height-35 f-14" id="current_ctc" name="current_ctc"
                                        placeholder="Current CTC">
                                </x-forms.input-group>
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="currenct_ctc_rate" fieldName="currenct_ctc_rate"
                                    fieldLabel="Current CTC Rate">
                                    <option value="">--</option>
                                    <option value="Hour">@lang('recruit::app.job.hour')</option>
                                    <option value="Day">@lang('recruit::app.job.day')</option>
                                    <option value="Week">@lang('recruit::app.job.week')</option>
                                    <option value="Month">@lang('recruit::app.job.month')</option>
                                    <option value="Year">@lang('recruit::app.job.year')</option>
                                </x-forms.select>
                            </div>

                            <div class="col-md-3">
                                <x-forms.label class="my-3" fieldId="expected_ctc"
                                    fieldLabel="Expected CTC (MMK)"></x-forms.label>
                                <x-forms.input-group>
                                    <input type="number" min="0" step="0.01"
                                        class="form-control height-35 f-14" id="expected_ctc" name="expected_ctc"
                                        placeholder="Expected CTC">
                                </x-forms.input-group>
                            </div>

                            <div class="col-md-3">
                                <x-forms.select fieldId="expected_ctc_rate" fieldName="expected_ctc_rate"
                                    fieldLabel="Expected CTC Rate">
                                    <option value="">--</option>
                                    <option value="Hour">@lang('recruit::app.job.hour')</option>
                                    <option value="Day">@lang('recruit::app.job.day')</option>
                                    <option value="Week">@lang('recruit::app.job.week')</option>
                                    <option value="Month">@lang('recruit::app.job.month')</option>
                                    <option value="Year">@lang('recruit::app.job.year')</option>
                                </x-forms.select>
                            </div> --}}

                            <div class="col-md-3">
                                <x-forms.select fieldId="notice_period" :fieldLabel="__('recruit::modules.jobApplication.noticePeriod')" fieldName="notice_period">
                                    <option value="">--</option>
                                    <option value="15">15 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="30">30 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="45">45 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="60">60 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="75">75 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="90">90 @lang('recruit::modules.jobApplication.days')</option>
                                    <option value="over-90">@lang('recruit::modules.jobApplication.over90')</option>
                                </x-forms.select>
                            </div>

                            {{-- <div class="col-md-3">
                                <x-forms.select fieldId="status_id" fieldName="status_id" :fieldLabel="__('recruit::modules.jobApplication.status')">
                                    @foreach ($applicationStatus as $status)
                                        <option @if ($status->id == $statusId) selected @endif
                                            value="{{ $status->id }}"
                                            data-content="<i class='fa fa-circle mr-2' style='color: {{ $status->color }}'></i> {{ $status->status }}">
                                        </option>
                                    @endforeach
                                </x-forms.select>
                            </div> --}}

                            <div class="col-md-3" id="dob">
                                <x-forms.text class="date-picker" :fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.dateOfBirth')"
                                    fieldName="date_of_birth" fieldId="date_of_birth-1" :fieldPlaceholder="__('placeholders.date')"
                                    fieldValue="" />
                            </div>

                            {{-- <div class="col-md-3" id="dob">
                                <x-forms.text class="date-picker" :fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.startDate')"
                                    fieldName="start_date" fieldId="start_date" :fieldPlaceholder="__('placeholders.date')" :fieldValue="now()->format('Y-m-d')"
                                    :fieldReadOnly="true" />
                            </div>

                            <div class="col-md-3" id="dob">
                                <x-forms.text class="date-picker" :fieldRequired="true" :fieldLabel="__('recruit::modules.jobApplication.endDate')"
                                    fieldName="end_date" fieldId="end_date" :fieldPlaceholder="__('placeholders.date')" fieldValue=""
                                    :fieldReadOnly="true" />
                            </div> --}}

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
                            fieldId="cover_letter" :fieldPlaceholder="__('recruit::modules.jobApplication.coverLetter')">
                        </x-forms.textarea>
                    </div>

                    <div class="col-md-12" id="resume1">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="resume" :fieldLabel="__('recruit::app.menu.add') . ' ' . __('recruit::app.jobApplication.resume')" class="mt-3"></x-forms.label>
                            <input type="file" class="dropify" name="resume"
                                data-allowed-file-extensions="txt pdf doc xls xlsx docx rtf png jpg jpeg svg"
                                data-messages-default="test" data-height="150" />
                            <input type="hidden" name="resume">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="f-14 text-dark-grey mb-12 w-100" for="send_email"></label>
                            <div class="d-flex">
                                <x-forms.checkbox fieldId="send_email" :fieldLabel="__('recruit::modules.jobApplication.sendMailToJobApplicant')" fieldValue="1"
                                    fieldName="send_email"></x-forms.checkbox>
                            </div>
                        </div>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="save-job-application" class="mr-3"
                        icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-secondary class="mr-3" id="save-more-job-application"
                        icon="check-double">@lang('app.saveAddMore')
                    </x-forms.button-secondary>
                    <x-forms.button-cancel :link="route('job-applications.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function() {

        datepicker('#date_of_birth-1', {
            ...datepickerConfig,
            position: 'bl',
            maxDate: new Date(),

            onSelect: function(instance, selectedDate) {
                setTimeout(function() {
                    calculateApplicantAge();
                }, 0);
            }
        });

        // datepicker('#start_date', {
        //     ...datepickerConfig,
        //     position: 'bl',
        // });

        // datepicker('#end_date', {
        //     ...datepickerConfig,
        //     position: 'bl',
        // });

        $(document).find('.dropify').dropify({
            messages: dropifyMessages
        });

        $('#save-job-application').click(function() {
            const url = "{{ route('job-applications.store') }}";
            var data = $('#save-job-application-data-form').serialize();

            saveApplication(data, url, "#save-job-application");

        });

        $('#save-more-job-application').click(function() {
            const url = "{{ route('job-applications.store') }}?add_more=true";
            var data = $('#save-job-application-data-form').serialize();

            saveApplication(data, url, "#save-more-job-application");

        });

        function saveApplication(data, url, buttonSelector) {
            $.easyAjax({
                url: url,
                container: '#save-job-application-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: buttonSelector,
                data: data,
                success: function(response) {
                    if (response.add_more == true) {
                        $(RIGHT_MODAL_CONTENT).html(response.html.html);
                    } else if (response.blacklistMatch) {
                        Swal.fire({
                                icon: 'warning',
                                title: 'Blacklist Match',
                                text: response.message
                            })
                            .then(() => window.location.href = response.redirectUrl);
                    } else {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        };

        init(RIGHT_MODAL);
    });

    @if ($jobApp != null)
        @if ($jobApp->is_dob_require)
            $('#dob').show();
        @else
            $('#dob').hide();
        @endif
    @else
        $('#dob').hide();
    @endif

    // Resume is a permanent input and must always remain visible.
    $('#resume1').show();

    function safelyRefreshSelectPicker(dropdown) {
        if (typeof $.fn.selectpicker === 'function') {
            dropdown.selectpicker('refresh');
        }
    }

    $(document)
        .off('change.jobRank', '#job_id')
        .on('change.jobRank', '#job_id', function() {
            const jobId = $(this).val();
            const rankDropdown = $('#management_rank_level');
            const rankLevelDropdown = $('#rank_level');
            const startDate = $('#start_date').val();

            rankDropdown.html('<option value="">--</option>');
            rankLevelDropdown.html('<option value="">--</option>');

            safelyRefreshSelectPicker(rankDropdown);
            safelyRefreshSelectPicker(rankLevelDropdown);

            if (!jobId) {
                if (typeof populateApplicantSelectionPhases === 'function') {
                    populateApplicantSelectionPhases(false);
                    populateApplicantOverallStatuses(false);
                    refreshApplicantWorkflowFields();
                }

                return;
            }

            const url = "{{ route('job-applications.rank_level') }}";

            $.easyAjax({
                url: url,
                type: 'GET',
                disableButton: true,
                blockUI: true,
                data: {
                    job_id: jobId,
                    start_date: startDate
                },
                success: function(response) {
                    if (response.status === 'error') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rank level is not assigned',
                            text: response.message
                        });

                        return;
                    }

                    const managementRankName = response.name;
                    const managementRankId = response.id;

                    const actualRankValue =
                        response.rankLevel ??
                        response.rank_level ??
                        $('#job_id option:selected').attr('data-actual-rank');

                    const actualRank = parseInt(actualRankValue, 10);

                    rankDropdown.html('<option value="">--</option>');
                    rankLevelDropdown.html('<option value="">--</option>');

                    if (
                        managementRankId !== null &&
                        managementRankId !== undefined &&
                        managementRankName
                    ) {
                        rankDropdown.append(
                            new Option(
                                managementRankName,
                                managementRankId,
                                true,
                                true
                            )
                        );

                        rankDropdown.val(String(managementRankId));
                    }

                    if (!Number.isNaN(actualRank)) {
                        rankLevelDropdown.append(
                            new Option(
                                'Rank ' + actualRank,
                                actualRank,
                                true,
                                true
                            )
                        );

                        rankLevelDropdown.val(String(actualRank));
                    }

                    safelyRefreshSelectPicker(rankDropdown);
                    safelyRefreshSelectPicker(rankLevelDropdown);

                    $('#job_id option:selected').attr(
                        'data-actual-rank',
                        Number.isNaN(actualRank) ? '' : String(actualRank)
                    );

                    if (typeof populateApplicantSelectionPhases === 'function') {
                        populateApplicantSelectionPhases(false);
                        populateApplicantOverallStatuses(false);
                        refreshApplicantWorkflowFields();
                    }
                },
                error: function() {
                    rankDropdown.html('<option value="">--</option>');
                    rankLevelDropdown.html('<option value="">--</option>');
                    safelyRefreshSelectPicker(rankDropdown);
                    safelyRefreshSelectPicker(rankLevelDropdown);
                }
            });
        });
</script>
