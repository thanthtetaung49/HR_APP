<?php

namespace Modules\Payroll\Http\Requests\OvertimeSetting\Paycode;

use Illuminate\Foundation\Http\FormRequest;

class PayCodeStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules()
    {

        $rules = [
            'name' => 'required | unique:pay_codes,name, null,id,company_id,' . company()->id,
            'code' => 'required | unique:pay_codes,code, null,id,company_id,' . company()->id,
        ];

        if($this->has('fixed') && $this->fixed == 'yes'){
            $rules['fixed_amount'] = 'required';
        }
        else{
            $rules['times'] = 'required';
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

}
