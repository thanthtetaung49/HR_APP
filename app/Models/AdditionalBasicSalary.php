<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalBasicSalary extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function salaryAllowance () {
        return $this->belongsTo(Allowance::class, 'salary_allowance_id', 'id');
    }
}
