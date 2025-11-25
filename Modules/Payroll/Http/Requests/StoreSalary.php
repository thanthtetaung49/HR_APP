<?php

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalary extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'annual_salary' => 'required',
            // 'basic_salary' => 'required',
            // 'basic_value' => 'required',
            'basic_salary' => 'required',
            'technical_allowance' => 'required',
            'living_cost_allowance' => 'required',
            'special_allowance' => 'required',
            'other_detection' => 'required',
            'credit_sales' => 'required',
            'deposit' => 'required',
            'loan' => 'required',
            'ssb' => 'required'
        ];
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
        return [
            'basic_salary' => 'basic value field is required',
        ];
    }
}
