<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function employeeBasicSalary ($userId) {
        $salary = Allowance::where('user_id', $userId)->sum('basic_salary');

        return [
            'basicSalary' => $salary
        ];
    }
}
