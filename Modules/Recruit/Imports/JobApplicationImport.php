<?php

namespace Modules\Recruit\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class JobApplicationImport implements ToArray
{

    public static function fields(): array
    {
        return array(
            array('id' => 'full_name', 'name' => __('recruit::modules.interviewSchedule.candidateName'), 'required' => 'Yes'),
            array('id' => 'email', 'name' => __('recruit::modules.form.email'), 'required' => 'No'),
            array('id' => 'phone', 'name' => __('recruit::modules.form.phone'), 'required' => 'No'),
            array('id' => 'gender', 'name' => __('recruit::modules.form.gender'), 'required' => 'No'),
            array('id' => 'current_location', 'name' => __('recruit::modules.jobApplication.location'), 'required' => 'No'),
            array('id' => 'source', 'name' => __('recruit::modules.form.application_source'), 'required' => 'No'),
            array('id' => 'current_ctc', 'name' => __('recruit::modules.form.current_ctc'), 'required' => 'No'),
            array('id' => 'expected_ctc', 'name' => __('recruit::modules.form.expected_ctc'), 'required' => 'No'),
            array('id' => 'total_experience', 'name' => __('recruit::modules.form.total_experience'), 'required' => 'No'),
            array('id' => 'date_of_birth', 'name' => 'Date of Birth (YYYY-MM-DD)', 'required' => 'Yes'),
            // array('id' => 'rank_level', 'name' => 'Rank Level', 'required' => 'Yes'),
            array('id' => 'marital_status', 'name' => 'Marital Status', 'required' => 'Yes'),
            array('id' => 'education', 'name' => 'Education', 'required' => 'No'),
            array('id' => 'certifications_qualifications', 'name' => 'Certifications & Qualifications', 'required' => 'No'),
            array('id' => 'work_experience_company', 'name' => 'Work Experience Company', 'required' => 'No'),
            array('id' => 'work_experience_position', 'name' => 'Work Experience Position', 'required' => 'No'),
            array('id' => 'work_experience_years', 'name' => 'Work Experience Years', 'required' => 'No'),
            array('id' => 'last_salary_minimum', 'name' => 'Last Salary (Minimum)', 'required' => 'No'),
            array('id' => 'expected_salary_minimum', 'name' => 'Expected Salary (Minimum)', 'required' => 'No'),
            array('id' => 'nrc', 'name' => 'NRC', 'required' => 'No'),
            array('id' => 'selection_phase', 'name' => 'Selection Phase', 'required' => 'No'),
            array('id' => 'overall_status', 'name' => 'Overall Status', 'required' => 'No'),
            array('id' => 'rejection_reason', 'name' => 'Rejection Reason', 'required' => 'No'),
            array('id' => 'keep_cv_reason', 'name' => 'Keep CV Reason', 'required' => 'No'),
            array('id' => 'job_offer_decision', 'name' => 'Job Offer Decision', 'required' => 'No'),
            array('id' => 'blacklist', 'name' => 'Blacklist (Yes/No)', 'required' => 'No'),
            array('id' => 'blacklist_reason', 'name' => 'Blacklist Reason', 'required' => 'No'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }

}
