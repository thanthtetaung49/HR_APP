<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class EmployeeShiftsImport implements ToArray
{
    public function __construct()
    {

    }

    public static function fields(): array
    {
        return array(
            array('id' => 'email', 'name' => __('app.email'), 'required' => 'Yes'),
            array('id' => 'date', 'name' => __('app.date'), 'required' => 'Yes'),
            array('id' => 'employee_shift_id', 'name' => __('app.shiftId'), 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }

}

