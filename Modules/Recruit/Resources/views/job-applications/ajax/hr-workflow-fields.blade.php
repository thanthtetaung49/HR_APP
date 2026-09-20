@php
    $experiences = old('work_experience_details', optional($jobApplication ?? null)->work_experience_details ?? [[]]);

    $rejectionReasons = [
        'location_unfit' => 'Location Unfit',
        'experience_gap' => 'Experience Gap (Overqualified or Underqualified)',
        'culture_unfit' => 'Culture Unfit',
        'salary_range_benefit' => 'Salary Range & Benefit',
        'other_competitor_join' => 'Other Competitor Join',
        'interview_absent' => 'Interview Absent',
        'not_contact' => 'Not Contact',
        'failed_assessment_or_interview' => 'Failed Assessment or Interview',
        'failed_reference_check' => 'Failed Reference Check',
        'position_closed' => 'Position Closed',
        'blacklist' => 'Blacklist',
    ];
@endphp

<div class="col-md-3">
    <x-forms.select fieldId="source" fieldName="source" :fieldLabel="__('recruit::modules.front.applicationSource')">
        <option value="">--</option>
        @foreach ($applicationSources as $source)
            <option value="{{ $source->id }}" @selected(old('source', optional($jobApplication ?? null)->application_source_id) == $source->id)>
                {{ $source->application_source }}
            </option>
        @endforeach
    </x-forms.select>
</div>

<div class="col-md-3">
    <x-forms.select fieldId="marital_status" fieldName="marital_status" fieldRequired="true" fieldLabel="Marital Status">
        <option value="">--</option>
        <option value="single" @selected(old('marital_status', optional($jobApplication ?? null)->marital_status) === 'single')>
            Single
        </option>
        <option value="married" @selected(old('marital_status', optional($jobApplication ?? null)->marital_status) === 'married')>
            Married
        </option>
        <option value="divorced" @selected(old('marital_status', optional($jobApplication ?? null)->marital_status) === 'divorced')>
            Divorced
        </option>
    </x-forms.select>
</div>

<div class="col-md-3">
    <x-forms.text fieldId="nrc" :fieldRequired="true" fieldName="nrc" fieldLabel="NRC" :fieldValue="old('nrc', optional($jobApplication ?? null)->nrc)" />
</div>

<div class="col-md-3">
    <div class="form-group my-3">
        <label class="f-14 text-dark-grey mb-12" for="age">Age</label>
        <input type="number" id="age" name="age" class="form-control height-35 f-14" min="0" readonly>
    </div>
</div>

<div class="col-md-3">
    <x-forms.number fieldId="last_salary_minimum" fieldName="last_salary_minimum" fieldLabel="Last Salary (Minimum)"
        :fieldValue="old('last_salary_minimum', optional($jobApplication ?? null)->last_salary_minimum)" minValue="0" />
</div>

<div class="col-md-3">
    <x-forms.number fieldId="expected_salary_minimum" fieldName="expected_salary_minimum"
        fieldLabel="Expected Salary (Minimum)" :fieldValue="old('expected_salary_minimum', optional($jobApplication ?? null)->expected_salary_minimum)" minValue="0" />
</div>

<div class="col-md-6">
    <x-forms.textarea fieldId="education" fieldName="education" fieldLabel="Education" :fieldValue="old('education', optional($jobApplication ?? null)->education)" />
</div>

<div class="col-md-6">
    <x-forms.textarea fieldId="certifications_qualifications" fieldName="certifications_qualifications"
        fieldLabel="Certifications & Qualifications" :fieldValue="old(
            'certifications_qualifications',
            optional($jobApplication ?? null)->certifications_qualifications,
        )" />
</div>

<div class="col-md-12 mt-3">
    <label class="f-14 text-dark-grey">Work Experience (Company, Position, Years)</label>

    <div id="work-experience-rows">
        @foreach ($experiences as $index => $experience)
            <div class="row work-experience-row mb-2">
                <div class="col-md-4">
                    <input class="form-control height-35 f-14"
                        name="work_experience_details[{{ $index }}][company]" placeholder="Company"
                        value="{{ $experience['company'] ?? '' }}">
                </div>

                <div class="col-md-4">
                    <input class="form-control height-35 f-14"
                        name="work_experience_details[{{ $index }}][position]" placeholder="Position"
                        value="{{ $experience['position'] ?? '' }}">
                </div>

                <div class="col-md-3">
                    <input  step="0.1" min="0" class="form-control height-35 f-14"
                        name="work_experience_details[{{ $index }}][years]" placeholder="Years"
                        value="{{ $experience['years'] ?? '' }}">
                </div>

                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger remove-work-experience">
                        &times;
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-work-experience" class="btn btn-outline-secondary btn-sm">
        Add Work Experience
    </button>
</div>

<div class="col-md-3">
    <x-forms.select fieldId="selection_phase" fieldName="selection_phase" fieldRequired="true"
        fieldLabel="Selection Phase">
        <option value="">--</option>
    </x-forms.select>
</div>

<div class="col-md-3">
    <x-forms.select fieldId="overall_status" fieldName="overall_status" fieldRequired="true"
        fieldLabel="Overall Status">
        <option value="">--</option>
    </x-forms.select>
</div>

<div class="col-md-6 workflow-dependent d-none" id="keep-cv-fields">
    <x-forms.text fieldId="keep_cv_reason" fieldName="keep_cv_reason" fieldLabel="Keep CV Reason" :fieldValue="old('keep_cv_reason', optional($jobApplication ?? null)->keep_cv_reason)" />
</div>

<div class="col-md-6 workflow-dependent d-none" id="rejection-fields">
    <x-forms.select fieldId="rejection_reason" fieldName="rejection_reason" fieldLabel="Rejection Reason">
        <option value="">--</option>
        @foreach ($rejectionReasons as $value => $label)
            <option value="{{ $value }}" @selected(old('rejection_reason', optional($jobApplication ?? null)->rejection_reason) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </x-forms.select>

    <x-forms.textarea fieldId="rejection_reason_details" fieldName="rejection_reason_details"
        fieldLabel="Rejection Details" :fieldValue="old('rejection_reason_details', optional($jobApplication ?? null)->rejection_reason_details)" />
</div>

<div class="col-md-6 workflow-dependent d-none" id="offer-decision-fields">
    <x-forms.select fieldId="job_offer_decision" fieldName="job_offer_decision" fieldLabel="Job Offer Decision">
        <option value="">--</option>
        <option value="accepted" @selected(old('job_offer_decision', optional($jobApplication ?? null)->job_offer_decision) === 'accepted')>
            Accepted (Joined)
        </option>
        <option value="declined" @selected(old('job_offer_decision', optional($jobApplication ?? null)->job_offer_decision) === 'declined')>
            Declined (Did Not Join)
        </option>
    </x-forms.select>

    <x-forms.textarea fieldId="job_offer_decision_reason" fieldName="job_offer_decision_reason"
        fieldLabel="Offer Decision Reason" :fieldValue="old('job_offer_decision_reason', optional($jobApplication ?? null)->job_offer_decision_reason)" />
</div>

<div class="col-md-12 mt-3">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="blacklist" name="blacklist" value="1"
            @checked((bool) old('blacklist', optional($jobApplication ?? null)->is_blacklisted))>
        <label class="custom-control-label" for="blacklist">Add applicant to blacklist</label>
    </div>
</div>

<div class="col-md-12 workflow-dependent d-none" id="blacklist-reason-fields">
    <x-forms.textarea fieldId="blacklist_reason" fieldName="blacklist_reason" fieldLabel="Blacklist Reason"
        :fieldValue="old('blacklist_reason', optional($jobApplication ?? null)->blacklist_reason)" />
</div>

<script>
    window.applicantSelectionPhaseLabels = {
        cv_screening: 'CV Screening',
        first_interview: 'First Interview',
        second_interview: 'Second Interview',
        hiring: 'Hiring',
        job_offer: 'Job Offer'
    };

    window.savedApplicantSelectionPhase = @json(old('selection_phase', optional($jobApplication ?? null)->selection_phase ?? 'cv_screening'));

    window.applicantOverallStatusByPhase = {
        cv_screening: {
            not_started: 'Not Started',
            keep_cv: 'Keep CV',
            rejected: 'Rejected'
        },
        first_interview: {
            in_progress: 'In Progress',
            keep_cv: 'Keep CV',
            rejected: 'Rejected',
            passed: 'Passed'
        },
        second_interview: {
            in_progress: 'In Progress',
            keep_cv: 'Keep CV',
            rejected: 'Rejected',
            passed: 'Passed'
        },
        hiring: {
            hired: 'Hired',
            rejected: 'Rejected'
        },
        job_offer: {
            hired: 'Hired'
        }
    };

    window.savedApplicantOverallStatus = @json(old('overall_status', optional($jobApplication ?? null)->overall_status ?? 'not_started'));

    function refreshApplicantSelectPicker(dropdown) {
        if (typeof $.fn.selectpicker === 'function') {
            dropdown.selectpicker('refresh');
        }
    }

    function getSelectedJobActualRank() {
        const selectedJob = $('#job_id option:selected');
        const actualRank = parseInt(selectedJob.attr('data-actual-rank'), 10);

        return Number.isNaN(actualRank) ? null : actualRank;
    }

    function populateApplicantSelectionPhases(keepSavedValue = false) {
        const selectionPhaseDropdown = $('#selection_phase');
        const actualRank = getSelectedJobActualRank();
        const currentValue = keepSavedValue ?
            window.savedApplicantSelectionPhase :
            selectionPhaseDropdown.val();

        selectionPhaseDropdown.html('<option value="">--</option>');

        Object.entries(window.applicantSelectionPhaseLabels).forEach(function([value, label]) {
            if (value === 'second_interview' && (actualRank === null || actualRank < 4)) {
                return;
            }

            selectionPhaseDropdown.append(new Option(label, value));
        });

        const phaseExists = selectionPhaseDropdown
            .find('option[value="' + currentValue + '"]')
            .length > 0;

        selectionPhaseDropdown.val(phaseExists ? currentValue : 'cv_screening');

        refreshApplicantSelectPicker(selectionPhaseDropdown);
    }

    function populateApplicantOverallStatuses(keepSavedValue = false) {
        const phase = $('#selection_phase').val();
        const overallStatusDropdown = $('#overall_status');
        const allowedStatuses = window.applicantOverallStatusByPhase[phase] || {};
        const currentValue = keepSavedValue ?
            window.savedApplicantOverallStatus :
            overallStatusDropdown.val();

        overallStatusDropdown.html('<option value="">--</option>');

        Object.entries(allowedStatuses).forEach(function([value, label]) {
            overallStatusDropdown.append(new Option(label, value));
        });

        if (currentValue && Object.prototype.hasOwnProperty.call(allowedStatuses, currentValue)) {
            overallStatusDropdown.val(currentValue);
        } else {
            overallStatusDropdown.val(Object.keys(allowedStatuses)[0] || '');
        }

        refreshApplicantSelectPicker(overallStatusDropdown);
    }

    function setApplicantWorkflowSectionVisibility(selector, visible) {
        const section = $(selector);

        section.toggleClass('d-none', !visible);
        section.find(':input').prop('disabled', !visible);

        section.find('select.select-picker').each(function() {
            refreshApplicantSelectPicker($(this));
        });
    }

    function refreshApplicantWorkflowFields() {
        const status = $('#overall_status').val();
        const phase = $('#selection_phase').val();
        const isBlacklisted = $('#blacklist').is(':checked');

        setApplicantWorkflowSectionVisibility(
            '#rejection-fields',
            status === 'rejected'
        );

        setApplicantWorkflowSectionVisibility(
            '#keep-cv-fields',
            status === 'keep_cv'
        );

        setApplicantWorkflowSectionVisibility(
            '#offer-decision-fields',
            phase === 'job_offer'
        );

        setApplicantWorkflowSectionVisibility(
            '#blacklist-reason-fields',
            isBlacklisted
        );

        $('#rejection_reason').prop('required', status === 'rejected');
        $('#job_offer_decision').prop('required', phase === 'job_offer');
        $('#blacklist_reason').prop('required', isBlacklisted);
    }

    function calculateApplicantAge() {
        const dobInput = document.getElementById('date_of_birth-1');
        const ageInput = document.getElementById('age');

        if (!dobInput || !ageInput) {
            return;
        }

        const dobValue = dobInput.value.trim();

        if (!dobValue) {
            ageInput.value = '';
            return;
        }

        // The application uses DD-MM-YYYY, for example 11-01-1999.
        const dateParts = dobValue.split('-');

        if (dateParts.length !== 3) {
            ageInput.value = '';
            return;
        }

        const day = parseInt(dateParts[0], 10);
        const month = parseInt(dateParts[1], 10);
        const year = parseInt(dateParts[2], 10);
        const birthDate = new Date(year, month - 1, day);

        if (
            Number.isNaN(day) ||
            Number.isNaN(month) ||
            Number.isNaN(year) ||
            birthDate.getFullYear() !== year ||
            birthDate.getMonth() !== month - 1 ||
            birthDate.getDate() !== day
        ) {
            ageInput.value = '';
            return;
        }

        const today = new Date();

        if (birthDate > today) {
            ageInput.value = '';
            return;
        }

        let age = today.getFullYear() - birthDate.getFullYear();
        const birthdayHasPassed =
            today.getMonth() > birthDate.getMonth() ||
            (today.getMonth() === birthDate.getMonth() &&
                today.getDate() >= birthDate.getDate());

        if (!birthdayHasPassed) {
            age--;
        }

        ageInput.value = age;
    }

    $(document)
        .off('change.applicantPhase', '#selection_phase')
        .on('change.applicantPhase', '#selection_phase', function() {
            populateApplicantOverallStatuses(false);
            refreshApplicantWorkflowFields();
        });

    $(document)
        .off('change.applicantJobRank', '#job_id')
        .on('change.applicantJobRank', '#job_id', function() {
            populateApplicantSelectionPhases(false);
            populateApplicantOverallStatuses(false);
            refreshApplicantWorkflowFields();
        });

    $(document)
        .off('change.applicantWorkflow', '#overall_status, #blacklist')
        .on('change.applicantWorkflow', '#overall_status, #blacklist',
            refreshApplicantWorkflowFields);

    $(document)
        .off('input.applicantAge change.applicantAge blur.applicantAge', '#date_of_birth-1')
        .on('input.applicantAge change.applicantAge blur.applicantAge', '#date_of_birth-1',
            calculateApplicantAge);

    $(document)
        .off('click.applicantWorkExperience', '#add-work-experience')
        .on('click.applicantWorkExperience', '#add-work-experience', function() {
            const index = $('#work-experience-rows .work-experience-row').length;

            $('#work-experience-rows').append(
                `<div class="row work-experience-row mb-2">
                    <div class="col-md-4">
                        <input class="form-control height-35 f-14"
                            name="work_experience_details[${index}][company]" placeholder="Company">
                    </div>
                    <div class="col-md-4">
                        <input class="form-control height-35 f-14"
                            name="work_experience_details[${index}][position]" placeholder="Position">
                    </div>
                    <div class="col-md-3">
                        <input type="text" step="0.1" min="0"
                            class="form-control height-35 f-14"
                            name="work_experience_details[${index}][years]" placeholder="Years">
                    </div>
                    <div class="col-md-1">
                        <button type="button"
                            class="btn btn-outline-danger remove-work-experience">&times;</button>
                    </div>
                </div>`
            );
        });

    $(document)
        .off('click.removeApplicantWorkExperience', '.remove-work-experience')
        .on('click.removeApplicantWorkExperience', '.remove-work-experience', function() {
            $(this).closest('.work-experience-row').remove();
        });

    populateApplicantSelectionPhases(true);
    populateApplicantOverallStatuses(true);
    refreshApplicantWorkflowFields();

    // The modal content and datepicker can finish initializing after this partial runs.
    setTimeout(calculateApplicantAge, 200);
</script>
