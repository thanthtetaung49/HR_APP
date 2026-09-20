<?php

namespace Modules\Recruit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $company = company();

        if ($this->without_end_date == 'on') {
            $rules = [
                'title' => 'required',
                'status' => 'required',
                'department_id' => 'required|exists:teams,id',
                'hr_location_id' => 'required|exists:locations,id',
                'designation_id' => 'required|exists:designations,id',
                'management_rank_id' => 'required|integer|min:1',
                'location_id.0' => 'required',
                'vacancy_count' => 'required|numeric',
                'start_date' => 'required|date_format:"' . $company->date_format . '"',
                'skill_id.0' => 'required',
                'stage_id.0' => 'required',
                'recruiter' => 'required',
                'job_type_id' => 'required',
                'work_experience' => 'required',
                'paytype' => 'nullable',
                'start_amount' => 'nullable|numeric',
                'pay_according' => 'nullable',
            ];
        } else {
            $rules = [
                'title' => 'required',
                'status' => 'required',
                'department_id' => 'required',
                'hr_location_id' => 'required|exists:locations,id',
                'designation_id' => 'required|exists:designations,id',
                'management_rank_id' => 'required|integer|min:1',
                'location_id.0' => 'required',
                'vacancy_count' => 'required|numeric',
                'start_date' => 'required|date_format:"' . $company->date_format . '"',
                'end_date' => 'required|date_format:"' . $company->date_format . '"|after_or_equal:start_date',
                'skill_id.0' => 'required',
                'stage_id.0' => 'required',
                'recruiter' => 'required',
                'job_type_id' => 'required',
                'work_experience' => 'required',
                'paytype' => 'nullable',
                'start_amount' => 'nullable|numeric',
                'pay_according' => 'nullable',
            ];
        }

        if (request('paytype') == 'Range') {
            $rules['end_amount'] = 'gt:start_amount';
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        $msg = [
            'department_id.required' => __('recruit::messages.departmentRequire'),
            'location_id.0.required' => __('recruit::messages.locationRequire'),
            'skill_id.0.required' => __('recruit::messages.skillRequire'),
            'stage_id.0.required' => __('recruit::messages.stageRequire'),
            'job_type_id.required' => __('recruit::messages.jobTypeRequire'),
            'paytype.required' => __('recruit::messages.payType'),
        ];

        if (request('paytype') == 'Range') {
            $msg['start_amount.required'] = __('recruit::messages.rangeRequire');
            $msg['end_amount.gt'] = __('recruit::messages.maximumRequire');
        } elseif (request('paytype') == 'Starting') {
            $msg['start_amount.required'] = __('recruit::messages.startingRequire');
        } elseif (request('paytype') == 'Maximum') {
            $msg['start_amount.required'] = __('recruit::messages.maximumRequire');
        } elseif (request('paytype') == 'Exact Amount') {
            $msg['start_amount.required'] = __('recruit::messages.exactRequire');
        }

        return $msg;
    }
}
