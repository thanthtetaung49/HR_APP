<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class EmployeeSalaryImport implements ToArray
{
    public function __construct()
    {

    }

    public static function fields(): array
    {
        return array(
            array('id' => 'email', 'name' => __('app.email'), 'required' => 'Yes'),
            array('id' => 'basic_salary', 'name' => __('modules.allowance.basicSalary'), 'required' => 'Yes'),
            array('id' => 'technical_allowance', 'name' => __('modules.allowance.technicalAllowance'), 'required' => 'Yes'),
            array('id' => 'living_cost_allowance', 'name' => __('modules.allowance.livingCostAllowance'), 'required' => 'Yes'),
            array('id' => 'special_allowance', 'name' => __('modules.allowance.specialAllowance'), 'required' => 'Yes'),
            array('id' => 'other_detection', 'name' => __('modules.detection.otherDetection'), 'required' => 'Yes'),
            array('id' => 'credit_sales', 'name' => __('modules.detection.creditSales'), 'required' => 'Yes'),
            array('id' => 'deposit', 'name' => __('modules.detection.deposit'), 'required' => 'Yes'),
            array('id' => 'loan', 'name' => __('modules.detection.loan'), 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }

}

