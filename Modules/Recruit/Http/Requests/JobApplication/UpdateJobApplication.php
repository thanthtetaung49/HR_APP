<?php

namespace Modules\Recruit\Http\Requests\JobApplication;

use App\Http\Requests\CoreRequest;
use Illuminate\Validation\Rule;
use Modules\Recruit\Entities\RecruitJob;
use Modules\Recruit\Rules\CheckApplication;

class UpdateJobApplication extends CoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return bool
     */
    public function rules()
    {
        $setting = company();

        $allowedOverallStatuses = match (request('selection_phase')) {
            'cv_screening' => [
                'not_started',
                'keep_cv',
                'rejected',
            ],

            'first_interview',
            'second_interview' => [
                'in_progress',
                'keep_cv',
                'rejected',
                'passed',
            ],

            'job_offer' => [
                'hired',
            ],

            'hiring' => [
                'hired',
                'rejected',
            ],

            default => [],
        };

        if (request()->job_id) {
            $jobId = RecruitJob::where('id', request()->job_id)->first();

            $data = [
                'job_id' => 'required',
                'full_name' => 'required',
                'nrc' => 'required',
                'phone' => 'required',
                'father_name' => 'required',
                'date_of_birth' => 'required|date_format:"' . $setting->date_format . '"|before_or_equal:' . now($setting->timezone)->toDateString(),
                'marital_status' => 'required|in:single,married',
                'last_salary_minimum' => 'nullable|numeric|min:0',
                'expected_salary_minimum' => 'nullable|numeric|min:0',
                'selection_phase' => 'required|in:cv_screening,first_interview,second_interview,job_offer,hiring',
                'overall_status' => ['required', Rule::in($allowedOverallStatuses)],
                'rejection_reason' => 'required_if:overall_status,rejected|nullable|string',
                'job_offer_decision' => 'required_if:selection_phase,job_offer|nullable|in:accepted,declined',
                'job_offer_decision_reason' => 'required_if:job_offer_decision,declined|nullable|string',
                'blacklist_reason' => 'required_if:blacklist,1|nullable|string',
                'resume' => [
                    'nullable',
                    'file',
                    'mimes:txt,pdf,doc,xls,xlsx,docx,rtf,png,jpg,jpeg,svg',
                    'max:10240',
                ],
                'photo' => [
                    'nullable',
                    'file',
                    'mimes:png,jpg,jpeg,svg',
                    'max:10240',
                ],
            ];

            if (! is_null(request()->email) && request()->application_id != request()->id) {
                $data['email'] = [new CheckApplication];
            }

            if ($jobId->is_gender_require) {
                $data['gender'] = 'required';
            }

            if ($jobId->is_dob_require) {
                $data['date_of_birth'] = 'required|date_format:"' . $setting->date_format . '"|before_or_equal:' . now($setting->timezone)->toDateString();
            }


        } else {
            $data = [
                'job_id' => 'required',
                'full_name' => 'required',
                'phone' => 'required',
            ];
        }

        return $data;
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            //
        ];
    }
}
